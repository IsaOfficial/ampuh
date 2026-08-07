<?php

define('BASE_PATH', __DIR__);
define('PUBLIC_PATH', realpath(__DIR__ . '/public'));

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $staticPath = realpath(BASE_PATH . $requestPath);

    if ($staticPath && is_file($staticPath) && str_starts_with($staticPath, BASE_PATH)) {
        return false;
    }
}

function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value);
        $value = trim($value, '"');
        $value = trim($value, "'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

loadEnvFile(BASE_PATH . '/.env');

$debugMode = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $debugMode ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Composer Autoload
$autoloadPaths = [
    BASE_PATH . '/vendor/autoload.php',
    BASE_PATH . '/public/assets/vendor/autoload.php',
];

foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

// Config
require BASE_PATH . '/backend/config/app.php';
require BASE_PATH . '/backend/config/database.php';

// Core
require BASE_PATH . '/backend/core/router.php';

// Helpers
require BASE_PATH . '/backend/bootstrap/helpers.php';

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $exception): void {
    if ($exception instanceof PDOException) {
        ErrorPage::databaseUnavailable($exception);
        return;
    }

    ErrorPage::serverError($exception);
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    ErrorPage::serverError(new ErrorException(
        $error['message'],
        0,
        $error['type'],
        $error['file'],
        $error['line']
    ));
});

// Models
require BASE_PATH . '/backend/bootstrap/models.php';

// Services
require BASE_PATH . '/backend/bootstrap/services.php';

// Middleware
require BASE_PATH . '/backend/bootstrap/middlewares.php';

// Controllers
require BASE_PATH . '/backend/bootstrap/controllers.php';

// Router
$router = new Router();

// Routes
require BASE_PATH . '/backend/routes/web.php';

$protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
$supportedProtocols = ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0', 'HTTP/3', 'HTTP/3.0'];

if (!in_array($protocol, $supportedProtocols, true)) {
    ErrorPage::httpVersionNotSupported();
    exit;
}

// AdminSeeder
require BASE_PATH . '/backend/seeders/AdminSeeder.php';
// $seeder = new AdminSeeder();
// $seeder->run(Database::getConnection());

// Run
$router->run();
