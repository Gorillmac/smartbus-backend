<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use SmartBus\Api;
use SmartBus\Response;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if ($scriptName !== '' && str_starts_with($path, $scriptName)) {
    $path = substr($path, strlen($scriptName)) ?: '/';
}
$scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($scriptDir !== '' && $scriptDir !== '.' && str_starts_with($path, $scriptDir)) {
    $path = substr($path, strlen($scriptDir)) ?: '/';
}

if ($method === 'OPTIONS') {
    Response::json(['ok' => true]);
}

$api = new Api();

try {
    $api->handle($method, $path);
} catch (Throwable $e) {
    $debug = env('APP_DEBUG', 'false') === 'true';
    Response::json([
        'error' => 'Server error',
        'message' => $debug ? $e->getMessage() : 'Something went wrong',
    ], 500);
}
