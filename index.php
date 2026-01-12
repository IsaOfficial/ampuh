<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', __DIR__);
define('PUBLIC_PATH', realpath(__DIR__ . '/public'));

// Config
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

// Run
$router->run();
