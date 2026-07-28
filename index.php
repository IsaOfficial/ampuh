<?php

define('BASE_PATH', __DIR__);
define('PUBLIC_PATH', realpath(__DIR__ . '/public'));

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

// AdminSeeder
require BASE_PATH . '/backend/seeders/AdminSeeder.php';
// $seeder = new AdminSeeder();
// $seeder->run(Database::getConnection());

// Run
$router->run();