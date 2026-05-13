<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use SmartBus\Database;

$message = null;
$error = null;

try {
    $pdo = Database::connection();
    $stmt = $pdo->prepare(
        'INSERT INTO users (id, name, email, password_hash, role, phone, join_date, license_number, bus_id)
         VALUES (:id, :name, :email, :password_hash, :role, :phone, :join_date, :license_number, :bus_id)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           email = VALUES(email),
           password_hash = VALUES(password_hash),
           role = VALUES(role),
           phone = VALUES(phone),
           join_date = VALUES(join_date),
           license_number = VALUES(license_number),
           bus_id = VALUES(bus_id)'
    );

    $users = [
        [1, 'John Passenger', 'user@smartbus.com', 'password123', 'passenger', '082 111 2222', '2025-03-01', null, null],
        [3, 'Mike Driver', 'driver@smartbus.com', 'driver123', 'driver', '082 555 6666', null, 'DL-2024-001', 1],
        [5, 'Admin User', 'admin@smartbus.com', 'admin123', 'admin', '011 222 3333', null, null, null],
    ];

    foreach ($users as $user) {
        [$id, $name, $email, $password, $role, $phone, $joinDate, $licenseNumber, $busId] = $user;
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'password_hash' => hash('sha256', $password),
            'role' => $role,
            'phone' => $phone,
            'join_date' => $joinDate,
            'license_number' => $licenseNumber,
            'bus_id' => $busId,
        ]);
    }

    $pdo->exec('UPDATE buses SET driver_id = 3 WHERE id = 1');
    $message = 'Demo logins repaired successfully.';
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Repair SmartBus Demo Logins</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f7fb; color:#172033; margin:0; padding:32px; }
    main { max-width:720px; margin:0 auto; background:#fff; border:1px solid #d9e2ef; border-radius:8px; padding:28px; }
    .ok { background:#e8f8ee; border:1px solid #9bd6af; color:#145c2b; padding:12px; border-radius:6px; }
    .err { background:#fff1f1; border:1px solid #ee9d9d; color:#8b1e1e; padding:12px; border-radius:6px; }
    code { background:#eef3f9; padding:2px 5px; border-radius:4px; }
  </style>
</head>
<body>
<main>
  <h1>Repair SmartBus Demo Logins</h1>
  <?php if ($message): ?><p class="ok"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <p>Demo accounts:</p>
  <pre>Passenger: user@smartbus.com / password123
Driver: driver@smartbus.com / driver123
Admin: admin@smartbus.com / admin123</pre>
  <p>Test the API at <code>/api/health</code> after this page succeeds.</p>
</main>
</body>
</html>
