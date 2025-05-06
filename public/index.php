<?php
// Start session
session_start();

// Load Configuration
require_once __DIR__ . '/../config/config.php';

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Simple Routing
    $requestUri = $_SERVER['REQUEST_URI'];
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    if ($basePath !== '/' && strpos($requestUri, $basePath) === 0) {
        $routePath = substr($requestUri, strlen($basePath));
    } else {
        $routePath = $requestUri;
    }
    $routePath = strtok($routePath, '?');
    $routePath = rtrim($routePath, '/');
    if (empty($routePath)) {
        $routePath = '/';
    }

    // Try manually requiring files
    require_once __DIR__ . '/../src/Services/Auth.php';
    require_once __DIR__ . '/../src/Controllers/BaseController.php';
    require_once __DIR__ . '/../src/Controllers/HomeController.php';

    // Route Definitions
    switch ($routePath) {
        case '/':
            $controller = new \App\Controllers\HomeController();
            $controller->index();
            break;
            
        // Add your other routes
        case '/login':
            echo "Login page coming soon";
            break;
            
        case '/register':
            echo "Register page coming soon";
            break;
            
        default:
            http_response_code(404);
            echo "Page not found: " . $routePath;
            break;
    }
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}