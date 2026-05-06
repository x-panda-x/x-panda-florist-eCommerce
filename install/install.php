<?php

declare(strict_types=1);

define('INSTALL_BASE_PATH', dirname(__DIR__));
define('INSTALL_LOCK_FILE', INSTALL_BASE_PATH . '/storage/install.lock');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = INSTALL_BASE_PATH . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$errors = [];
$success = false;
$lockExists = is_file(INSTALL_LOCK_FILE);
$requirements = installerRequirements();
$formData = [
    'database_host' => $_POST['database_host'] ?? '127.0.0.1',
    'database_port' => $_POST['database_port'] ?? '3306',
    'database_name' => $_POST['database_name'] ?? '',
    'database_user' => $_POST['database_user'] ?? '',
    'database_password' => $_POST['database_password'] ?? '',
    'admin_name' => $_POST['admin_name'] ?? '',
    'admin_email' => $_POST['admin_email'] ?? '',
    'admin_password' => $_POST['admin_password'] ?? '',
];

if ($lockExists) {
    http_response_code(403);
    $errors[] = 'Installer is locked. Installation has already been completed.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($requirements !== []) {
        $errors = array_merge($errors, $requirements);
    } else {
        $errors = array_merge($errors, validateInstallerInput($formData));
    }

    if ($errors === []) {
        try {
            $connectionConfig = [
                'driver' => 'mysql',
                'host' => trim((string) $formData['database_host']),
                'port' => (int) $formData['database_port'],
                'database' => trim((string) $formData['database_name']),
                'username' => trim((string) $formData['database_user']),
                'password' => (string) $formData['database_password'],
                'charset' => 'utf8mb4',
            ];

            testDatabaseConnection($connectionConfig);

            App\Core\Database::reset();
            $database = App\Core\Database::getInstance($connectionConfig);

            $runner = new App\Core\MigrationRunner($database, INSTALL_BASE_PATH . '/database/migrations');
            $runner->runPending();

            createInitialAdmin($database->connection(), $formData);
            writeDatabaseConfig($connectionConfig, INSTALL_BASE_PATH . '/config/database.php');
            createInstallerLock(INSTALL_LOCK_FILE);

            $success = true;
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            App\Core\Database::reset();
        }
    }
}

function installerRequirements(): array
{
    $errors = [];

    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        $errors[] = 'PHP 8.0 or higher is required.';
    }

    if (!extension_loaded('pdo')) {
        $errors[] = 'The PDO extension is required.';
    }

    if (!extension_loaded('pdo_mysql')) {
        $errors[] = 'The PDO MySQL extension is required.';
    }

    foreach ([
        INSTALL_BASE_PATH . '/config',
        INSTALL_BASE_PATH . '/storage',
        INSTALL_BASE_PATH . '/storage/cache',
        INSTALL_BASE_PATH . '/storage/cache/sessions',
        INSTALL_BASE_PATH . '/storage/logs',
        INSTALL_BASE_PATH . '/public/uploads',
    ] as $path) {
        if (!ensureInstallPathWritable($path)) {
            $errors[] = 'Path must exist and be writable: ' . $path;
        }
    }

    return $errors;
}

function validateInstallerInput(array $input): array
{
    $errors = [];

    foreach (['database_host', 'database_port', 'database_name', 'database_user', 'admin_name', 'admin_email', 'admin_password'] as $field) {
        if (trim((string) ($input[$field] ?? '')) === '') {
            $errors[] = 'The ' . str_replace('_', ' ', $field) . ' field is required.';
        }
    }

    if ((string) ($input['admin_email'] ?? '') !== '' && !filter_var($input['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Admin email must be a valid email address.';
    }

    return $errors;
}

function testDatabaseConnection(array $config): void
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function createInitialAdmin(PDO $pdo, array $input): void
{
    $statement = $pdo->prepare(
        'INSERT INTO admins (name, email, password_hash, is_active, created_at)
         VALUES (:name, :email, :password_hash, :is_active, NOW())'
    );

    $statement->execute([
        'name' => trim((string) $input['admin_name']),
        'email' => trim((string) $input['admin_email']),
        'password_hash' => password_hash((string) $input['admin_password'], PASSWORD_DEFAULT),
        'is_active' => 1,
    ]);
}

function writeDatabaseConfig(array $config, string $path): void
{
    $export = var_export([
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => $config['host'],
                'port' => (int) $config['port'],
                'database' => $config['database'],
                'username' => $config['username'],
                'password' => $config['password'],
                'charset' => $config['charset'],
            ],
        ],
    ], true);

    $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . $export . ";\n";

    if (file_put_contents($path, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write database configuration.');
    }
}

function createInstallerLock(string $path): void
{
    $contents = "installed_at=" . date('c') . PHP_EOL;

    if (file_put_contents($path, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Unable to create installer lock file.');
    }
}

function ensureInstallPathWritable(string $path): bool
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        return false;
    }

    return is_writable($path);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lily and Rose Installer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 32px 16px;
            background: #f8f5ef;
            color: #1f2933;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
        }
        h1 {
            margin-top: 0;
        }
        .message {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .error {
            background: #fef2f2;
            color: #991b1b;
        }
        .success {
            background: #ecfdf5;
            color: #166534;
        }
        .requirements {
            margin-bottom: 24px;
        }
        .field {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-weight: 700;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        button {
            background: #1f6f5f;
            color: #ffffff;
            border: 0;
            border-radius: 8px;
            padding: 12px 18px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Lily and Rose Installer</h1>

    <div class="requirements">
        <strong>Environment checks</strong>
        <ul>
            <li>PHP version: <?php echo htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8'); ?></li>
            <li>PDO extension: <?php echo extension_loaded('pdo') ? 'available' : 'missing'; ?></li>
            <li>PDO MySQL extension: <?php echo extension_loaded('pdo_mysql') ? 'available' : 'missing'; ?></li>
        </ul>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <div class="message success">Installation completed successfully.</div>
    <?php elseif (!$lockExists): ?>
        <form method="post">
            <div class="field">
                <label for="database_host">Database Host</label>
                <input id="database_host" name="database_host" type="text" value="<?php echo htmlspecialchars((string) $formData['database_host'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="database_port">Database Port</label>
                <input id="database_port" name="database_port" type="text" value="<?php echo htmlspecialchars((string) $formData['database_port'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="database_name">Database Name</label>
                <input id="database_name" name="database_name" type="text" value="<?php echo htmlspecialchars((string) $formData['database_name'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="database_user">Database User</label>
                <input id="database_user" name="database_user" type="text" value="<?php echo htmlspecialchars((string) $formData['database_user'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="database_password">Database Password</label>
                <input id="database_password" name="database_password" type="password" value="<?php echo htmlspecialchars((string) $formData['database_password'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="admin_name">Admin Name</label>
                <input id="admin_name" name="admin_name" type="text" value="<?php echo htmlspecialchars((string) $formData['admin_name'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="admin_email">Admin Email</label>
                <input id="admin_email" name="admin_email" type="email" value="<?php echo htmlspecialchars((string) $formData['admin_email'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field">
                <label for="admin_password">Admin Password</label>
                <input id="admin_password" name="admin_password" type="password" value="<?php echo htmlspecialchars((string) $formData['admin_password'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <button type="submit">Run Installation</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
