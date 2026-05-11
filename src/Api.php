<?php

declare(strict_types=1);

namespace SmartBus;

use PDO;

final class Api
{
    private PDO $db;

    private array $resources = [
        'users' => 'users',
        'routes' => 'routes',
        'buses' => 'buses',
        'tickets' => 'tickets',
        'trips' => 'trips',
        'bookings' => 'bookings',
        'announcements' => 'announcements',
        'notifications' => 'notifications',
        'complaints' => 'complaints',
        'active_trips' => 'active_trips',
    ];

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function handle(string $method, string $path): void
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if (($segments[0] ?? '') !== 'api') {
            Response::json(['error' => 'Not found'], 404);
        }

        $resource = $segments[1] ?? '';
        $id = $segments[2] ?? null;

        if ($method === 'GET' && $resource === 'health') {
            Response::json(['ok' => true, 'service' => 'smartbus-backend']);
        }

        if ($method === 'POST' && $resource === 'login') {
            $this->login();
        }

        if ($resource === 'seat-data') {
            $this->seatData($method);
        }

        if ($resource === 'chats') {
            $this->chats($method, $segments);
        }

        if (!isset($this->resources[$resource])) {
            Response::json(['error' => 'Unknown resource'], 404);
        }

        if ($method === 'POST' && $id === 'sync') {
            $this->replaceAll($resource);
        }

        match ($method) {
            'GET' => $id === null ? $this->index($resource) : $this->show($resource, $id),
            'POST' => $this->create($resource),
            'PATCH' => $id === null ? Response::json(['error' => 'Missing id'], 400) : $this->update($resource, $id),
            'DELETE' => $id === null ? Response::json(['error' => 'Missing id'], 400) : $this->delete($resource, $id),
            default => Response::json(['error' => 'Method not allowed'], 405),
        };
    }

    private function login(): void
    {
        $body = Request::json();
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !hash_equals((string)$user['password_hash'], hash('sha256', $password))) {
            Response::json(['error' => 'Invalid email or password'], 401);
        }

        unset($user['password_hash']);
        Response::json(['user' => $this->castRow($user)]);
    }

    private function index(string $resource): void
    {
        $table = $this->resources[$resource];
        $sql = "SELECT * FROM {$table}";
        $params = [];

        if (isset($_GET['userId']) && $this->hasColumn($table, 'user_id')) {
            $sql .= ' WHERE user_id = ?';
            $params[] = $_GET['userId'];
        } elseif (isset($_GET['role']) && $table === 'users') {
            $sql .= ' WHERE role = ?';
            $params[] = $_GET['role'];
        }

        $sql .= ' ORDER BY id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        Response::json(array_map(fn (array $row): array => $this->dbToFrontend($table, $row), $stmt->fetchAll()));
    }

    private function show(string $resource, string $id): void
    {
        $table = $this->resources[$resource];
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            Response::json(['error' => 'Record not found'], 404);
        }

        Response::json($this->dbToFrontend($table, $row));
    }

    private function create(string $resource): void
    {
        $table = $this->resources[$resource];
        $data = $this->frontendToDb($table, Request::json());
        unset($data['id'], $data['created_at'], $data['updated_at']);

        if ($table === 'users' && isset($data['password'])) {
            $data['password_hash'] = hash('sha256', (string)$data['password']);
            unset($data['password']);
        }

        $data = $this->filterColumns($table, $data);

        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnSql = implode(', ', $columns);
        $stmt = $this->db->prepare("INSERT INTO {$table} ({$columnSql}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));

        $this->show($resource, (string)$this->db->lastInsertId());
    }

    private function update(string $resource, string $id): void
    {
        $table = $this->resources[$resource];
        $data = $this->frontendToDb($table, Request::json());
        unset($data['id'], $data['created_at'], $data['updated_at']);

        if ($table === 'users' && isset($data['password'])) {
            $data['password_hash'] = hash('sha256', (string)$data['password']);
            unset($data['password']);
        }

        $data = $this->filterColumns($table, $data);

        if ($data === []) {
            Response::json(['error' => 'No update fields provided'], 422);
        }

        $set = implode(', ', array_map(fn (string $column): string => "{$column} = ?", array_keys($data)));
        $stmt = $this->db->prepare("UPDATE {$table} SET {$set} WHERE id = ?");
        $stmt->execute([...array_values($data), $id]);

        $this->show($resource, $id);
    }

    private function delete(string $resource, string $id): void
    {
        $table = $this->resources[$resource];
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(['deleted' => true]);
    }

    private function replaceAll(string $resource): void
    {
        $table = $this->resources[$resource];
        $rows = Request::json();
        if (!array_is_list($rows)) {
            Response::json(['error' => 'Expected an array of records'], 422);
        }

        $this->db->beginTransaction();
        try {
            $this->db->exec("DELETE FROM {$table}");
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $data = $this->filterColumns($table, $this->frontendToDb($table, $row));
                if ($data === []) {
                    continue;
                }
                $columns = array_keys($data);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $columnSql = implode(', ', $columns);
                $stmt = $this->db->prepare("INSERT INTO {$table} ({$columnSql}) VALUES ({$placeholders})");
                $stmt->execute(array_values($data));
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        Response::json(['synced' => true]);
    }

    private function seatData(string $method): void
    {
        if ($method === 'GET') {
            $rows = $this->db->query('SELECT bus_id, seat_number FROM seat_reservations ORDER BY bus_id, seat_number')->fetchAll();
            $data = [];
            foreach ($rows as $row) {
                $data[(string)$row['bus_id']][] = (int)$row['seat_number'];
            }
            Response::json($data);
        }

        if ($method === 'POST' || $method === 'PATCH') {
            $body = Request::json();
            if (isset($body[0]) || array_reduce(array_keys($body), fn ($carry, $key) => $carry || is_array($body[$key]), false)) {
                $this->db->beginTransaction();
                try {
                    $this->db->exec('DELETE FROM seat_reservations');
                    $stmt = $this->db->prepare('INSERT IGNORE INTO seat_reservations (bus_id, seat_number) VALUES (?, ?)');
                    foreach ($body as $busId => $seats) {
                        if (!is_array($seats)) {
                            continue;
                        }
                        foreach ($seats as $seatNumber) {
                            $stmt->execute([(int)$busId, (int)$seatNumber]);
                        }
                    }
                    $this->db->commit();
                } catch (\Throwable $e) {
                    $this->db->rollBack();
                    throw $e;
                }
                Response::json(['synced' => true]);
            }

            $busId = (int)($body['busId'] ?? 0);
            $seatNumber = (int)($body['seatNumber'] ?? 0);
            if ($busId <= 0 || $seatNumber <= 0) {
                Response::json(['error' => 'busId and seatNumber are required'], 422);
            }
            $stmt = $this->db->prepare('INSERT IGNORE INTO seat_reservations (bus_id, seat_number) VALUES (?, ?)');
            $stmt->execute([$busId, $seatNumber]);
            Response::json(['reserved' => true]);
        }

        Response::json(['error' => 'Method not allowed'], 405);
    }

    private function chats(string $method, array $segments): void
    {
        $driverId = (int)($segments[2] ?? 0);
        $passengerId = (int)($segments[3] ?? 0);

        if ($driverId <= 0 || $passengerId <= 0) {
            Response::json(['error' => 'driverId and passengerId are required'], 422);
        }

        if ($method === 'GET') {
            $stmt = $this->db->prepare('SELECT * FROM chat_messages WHERE driver_id = ? AND passenger_id = ? ORDER BY id ASC');
            $stmt->execute([$driverId, $passengerId]);
            Response::json(array_map(fn (array $row): array => $this->dbToFrontend('chat_messages', $row), $stmt->fetchAll()));
        }

        if ($method === 'POST') {
            $body = Request::json();
            $stmt = $this->db->prepare('INSERT INTO chat_messages (driver_id, passenger_id, sender_role, message, sent_time, sent_date, is_read) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $driverId,
                $passengerId,
                $body['from'] ?? $body['senderRole'] ?? 'passenger',
                $body['message'] ?? '',
                $body['time'] ?? date('H:i'),
                $body['date'] ?? date('Y-m-d'),
                !empty($body['read']) ? 1 : 0,
            ]);
            Response::json(['id' => (int)$this->db->lastInsertId()], 201);
        }

        Response::json(['error' => 'Method not allowed'], 405);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE ?');
        $stmt->execute([$column]);
        return (bool)$stmt->fetch();
    }

    private function frontendToDb(string $table, array $data): array
    {
        $fieldMap = [
            'users' => ['joinDate' => 'join_date', 'licenseNumber' => 'license_number', 'busId' => 'bus_id'],
            'buses' => ['routeId' => 'route_id', 'driverId' => 'driver_id'],
            'tickets' => ['userId' => 'user_id', 'routeId' => 'route_id', 'issueDate' => 'issue_date', 'expiryDate' => 'expiry_date'],
            'trips' => ['userId' => 'user_id', 'routeId' => 'route_id', 'busId' => 'bus_id', 'date' => 'trip_date'],
            'bookings' => ['userId' => 'user_id', 'routeId' => 'route_id', 'time' => 'booking_time', 'date' => 'booking_date'],
            'announcements' => ['date' => 'announcement_date'],
            'notifications' => ['userId' => 'user_id', 'time' => 'notification_time', 'date' => 'notification_date', 'read' => 'is_read'],
            'complaints' => ['userId' => 'user_id', 'routeId' => 'route_id', 'date' => 'complaint_date'],
            'active_trips' => ['driverId' => 'driver_id', 'busId' => 'bus_id', 'routeId' => 'route_id', 'startTime' => 'start_time', 'date' => 'trip_date'],
        ];

        $mapped = [];
        foreach ($data as $key => $value) {
            $column = $fieldMap[$table][$key] ?? preg_replace('/[A-Z]/', '_$0', (string)$key);
            $column = strtolower((string)$column);
            if ($column === 'fare' && is_string($value)) {
                $value = (float)preg_replace('/[^0-9.]/', '', $value);
            }
            if (in_array($column, ['stops', 'schedule'], true) && is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }
            if (in_array($column, ['is_read', 'arrived'], true)) {
                $value = $value ? 1 : 0;
            }
            $mapped[$column] = $value;
        }
        return $mapped;
    }

    private function dbToFrontend(string $table, array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (in_array($key, ['id', 'user_id', 'route_id', 'bus_id', 'driver_id', 'capacity', 'seats', 'rating', 'lat', 'lng', 'arrived', 'is_read'], true)) {
                $row[$key] = is_numeric($value) ? $value + 0 : $value;
            }
            if (in_array($key, ['stops', 'schedule'], true) && is_string($value)) {
                $row[$key] = json_decode($value, true) ?: [];
            }
        }

        unset($row['password_hash']);

        $fieldMap = [
            'join_date' => 'joinDate',
            'license_number' => 'licenseNumber',
            'bus_id' => 'busId',
            'route_id' => 'routeId',
            'driver_id' => 'driverId',
            'user_id' => 'userId',
            'issue_date' => 'issueDate',
            'expiry_date' => 'expiryDate',
            'trip_date' => 'date',
            'booking_time' => 'time',
            'booking_date' => 'date',
            'announcement_date' => 'date',
            'notification_time' => 'time',
            'notification_date' => 'date',
            'complaint_date' => 'date',
            'is_read' => 'read',
            'start_time' => 'startTime',
            'sender_role' => 'from',
            'sent_time' => 'time',
            'sent_date' => 'date',
        ];

        $mapped = [];
        foreach ($row as $key => $value) {
            if ($key === 'fare') {
                $value = 'R ' . number_format((float)$value, 2);
            }
            $mapped[$fieldMap[$key] ?? $key] = $value;
        }

        return $mapped;
    }

    private function filterColumns(string $table, array $data): array
    {
        static $columns = [];
        if (!isset($columns[$table])) {
            $columns[$table] = array_column($this->db->query('SHOW COLUMNS FROM ' . $table)->fetchAll(), 'Field');
        }

        return array_intersect_key($data, array_flip($columns[$table]));
    }
}
