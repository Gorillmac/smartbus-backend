<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$schemaFile = $root . '/database/schema.sql';
$seedFile = $root . '/database/seed.sql';
$envFile = $root . '/.env';

$defaults = [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'smartbus',
    'db_user' => 'root',
    'db_pass' => '',
    'app_url' => guessAppUrl(),
    'cors_origin' => '*',
];

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = array_merge($defaults, [
        'db_host' => trim((string)($_POST['db_host'] ?? $defaults['db_host'])),
        'db_port' => trim((string)($_POST['db_port'] ?? $defaults['db_port'])),
        'db_name' => trim((string)($_POST['db_name'] ?? $defaults['db_name'])),
        'db_user' => trim((string)($_POST['db_user'] ?? $defaults['db_user'])),
        'db_pass' => (string)($_POST['db_pass'] ?? ''),
        'app_url' => rtrim(trim((string)($_POST['app_url'] ?? $defaults['app_url'])), '/'),
        'cors_origin' => trim((string)($_POST['cors_origin'] ?? $defaults['cors_origin'])),
    ]);

    try {
        validateDatabaseName($input['db_name']);

        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $input['db_host'], $input['db_port']);
        $pdo = new PDO($dsn, $input['db_user'], $input['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $database = str_replace('`', '``', $input['db_name']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$database}`");
        runSqlFile($pdo, $schemaFile);
        runSqlFile($pdo, $seedFile);
        writeEnvFile($envFile, $input);

        $message = 'Setup complete. The database was created, demo data was imported, and .env was saved.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
} else {
    $input = $defaults;
}

function guessAppUrl(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return rtrim($https . '://' . $host . $script, '/');
}

function validateDatabaseName(string $name): void
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('Database name may only contain letters, numbers, and underscores.');
    }
}

function runSqlFile(PDO $pdo, string $file): void
{
    if (!is_file($file)) {
        throw new RuntimeException("Missing SQL file: {$file}");
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Could not read SQL file: {$file}");
    }

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $pdo->exec($statement);
    }
}

function writeEnvFile(string $file, array $input): void
{
    $content = implode(PHP_EOL, [
        'APP_ENV=development',
        'APP_DEBUG=true',
        'APP_URL=' . $input['app_url'],
        '',
        'DB_HOST=' . $input['db_host'],
        'DB_PORT=' . $input['db_port'],
        'DB_DATABASE=' . $input['db_name'],
        'DB_USERNAME=' . $input['db_user'],
        'DB_PASSWORD=' . $input['db_pass'],
        '',
        'CORS_ALLOWED_ORIGIN=' . ($input['cors_origin'] ?: '*'),
        '',
    ]);

    if (file_put_contents($file, $content) === false) {
        throw new RuntimeException('Could not write .env file. Check folder permissions.');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SmartBus Backend Setup</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f7fb; color:#172033; margin:0; padding:32px; }
    main { max-width:760px; margin:0 auto; background:#fff; border:1px solid #d9e2ef; border-radius:8px; padding:28px; box-shadow:0 10px 30px rgba(20,32,50,.08); }
    h1 { margin:0 0 8px; font-size:28px; }
    p { line-height:1.5; }
    label { display:block; font-weight:700; margin:16px 0 6px; }
    input { width:100%; box-sizing:border-box; padding:12px; border:1px solid #c8d3e1; border-radius:6px; font-size:15px; }
    button { margin-top:22px; padding:12px 18px; border:0; border-radius:6px; background:#0b63ce; color:#fff; font-weight:700; cursor:pointer; }
    code { background:#eef3f9; padding:2px 5px; border-radius:4px; }
    .ok { background:#e8f8ee; border:1px solid #9bd6af; color:#145c2b; padding:12px; border-radius:6px; }
    .err { background:#fff1f1; border:1px solid #ee9d9d; color:#8b1e1e; padding:12px; border-radius:6px; }
    .grid { display:grid; grid-template-columns:1fr 120px; gap:12px; }
    @media (max-width:640px) { body { padding:16px; } .grid { grid-template-columns:1fr; } }
  </style>
</head>
<body>
<main>
  <h1>SmartBus Backend Setup</h1>
  <p>Use this once after copying the backend into XAMPP <code>htdocs</code>. It creates the MySQL database, imports tables, imports demo records, and saves the connection settings.</p>

  <?php if ($message): ?><p class="ok"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <form method="post">
    <div class="grid">
      <div>
        <label for="db_host">MySQL Host</label>
        <input id="db_host" name="db_host" value="<?= htmlspecialchars($input['db_host']) ?>" required>
      </div>
      <div>
        <label for="db_port">Port</label>
        <input id="db_port" name="db_port" value="<?= htmlspecialchars($input['db_port']) ?>" required>
      </div>
    </div>

    <label for="db_name">Database Name</label>
    <input id="db_name" name="db_name" value="<?= htmlspecialchars($input['db_name']) ?>" required>

    <label for="db_user">MySQL Username</label>
    <input id="db_user" name="db_user" value="<?= htmlspecialchars($input['db_user']) ?>" required>

    <label for="db_pass">MySQL Password</label>
    <input id="db_pass" name="db_pass" type="password" value="<?= htmlspecialchars($input['db_pass']) ?>">

    <label for="app_url">Backend URL</label>
    <input id="app_url" name="app_url" value="<?= htmlspecialchars($input['app_url']) ?>" required>

    <label for="cors_origin">Allowed Frontend Origin</label>
    <input id="cors_origin" name="cors_origin" value="<?= htmlspecialchars($input['cors_origin']) ?>" placeholder="* or https://your-frontend-site.com">

    <button type="submit">Run One-Time Setup</button>
  </form>
</main>
</body>
</html>
