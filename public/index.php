<?php
// Start session
session_start();

// Load Configuration
require_once __DIR__ . '/../config/config.php';

// Error reporting (development mode)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Fall back to manual includes if autoloader fails
require_once __DIR__ . '/../src/Services/Auth.php';
require_once __DIR__ . '/../src/Controllers/BaseController.php';
require_once __DIR__ . '/../src/Controllers/HomeController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';
require_once __DIR__ . '/../src/Controllers/MovieController.php';
require_once __DIR__ . '/../src/Controllers/AnnotationController.php';
require_once __DIR__ . '/../src/Controllers/CollectionController.php';
require_once __DIR__ . '/../src/Controllers/ApiController.php';

// Route parsing
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

try {
    // Route Definitions
    switch ($routePath) {
        // Home routes
        case '/':
            $controller = new \App\Controllers\HomeController();
            $controller->index();
            break;
        case '/about':
            $controller = new \App\Controllers\HomeController();
            $controller->about();
            break;
        case '/contact':
            $controller = new \App\Controllers\HomeController();
            $controller->contact();
            break;
        
        // User routes
        case '/login':
            $controller = new \App\Controllers\UserController();
            $controller->login();
            break;
        case '/logout':
            $controller = new \App\Controllers\UserController();
            $controller->logout();
            break;
        case '/register':
            $controller = new \App\Controllers\UserController();
            $controller->register();
            break;
        case '/profile':
            $controller = new \App\Controllers\UserController();
            $controller->profile($_GET['username'] ?? null);
            break;
        case '/profile/edit':
            $controller = new \App\Controllers\UserController();
            $controller->edit();
            break;
        case '/profile/change-password':
            $controller = new \App\Controllers\UserController();
            $controller->changePassword();
            break;
            
        // Movie routes
        case '/movies':
            $controller = new \App\Controllers\MovieController();
            $controller->index();
            break;
        case '/movie':
            $controller = new \App\Controllers\MovieController();
            $controller->show($_GET['id'] ?? null);
            break;
        case '/movie/add':
            $controller = new \App\Controllers\MovieController();
            $controller->add();
            break;
        case '/movie/search':
            $controller = new \App\Controllers\MovieController();
            $controller->search();
            break;
        case '/movie/rate':
            $controller = new \App\Controllers\MovieController();
            $controller->rate();
            break;
        case '/movie/toggle-favorite':
            $controller = new \App\Controllers\MovieController();
            $controller->toggleFavorite();
            break;
        case '/movie/add-annotation':
            $controller = new \App\Controllers\MovieController();
            $controller->addAnnotation();
            break;
            
        // Collection routes
        case '/collections':
            $controller = new \App\Controllers\CollectionController();
            $controller->index();
            break;
        case '/collection':
            $controller = new \App\Controllers\CollectionController();
            $controller->show($_GET['id'] ?? null);
            break;
        case '/collection/create':
            $controller = new \App\Controllers\CollectionController();
            $controller->create();
            break;
        case '/collection/edit':
            $controller = new \App\Controllers\CollectionController();
            $controller->edit($_GET['id'] ?? null);
            break;
        case '/collection/delete':
            $controller = new \App\Controllers\CollectionController();
            $controller->delete();
            break;
        case '/collection/add-movie':
            $controller = new \App\Controllers\CollectionController();
            $controller->addMovie();
            break;
        case '/collection/remove-movie':
            $controller = new \App\Controllers\CollectionController();
            $controller->removeMovie();
            break;
            
        // Annotation routes
        case '/annotation':
            $controller = new \App\Controllers\AnnotationController();
            $controller->show($_GET['id'] ?? null);
            break;
        case '/annotation/edit':
            $controller = new \App\Controllers\AnnotationController();
            $controller->edit($_GET['id'] ?? null);
            break;
        case '/annotation/delete':
            $controller = new \App\Controllers\AnnotationController();
            $controller->delete();
            break;
        case '/annotation/toggle-like':
            $controller = new \App\Controllers\AnnotationController();
            $controller->toggleLike();
            break;
        case '/annotation/add-comment':
            $controller = new \App\Controllers\AnnotationController();
            $controller->addComment();
            break;
        case '/annotation/delete-comment':
            $controller = new \App\Controllers\AnnotationController();
            $controller->deleteComment();
            break;
            
        // API routes
        case '/api/search-movies':
            $controller = new \App\Controllers\ApiController();
            $controller->searchMovies();
            break;
        case '/api/movie':
            $controller = new \App\Controllers\ApiController();
            $controller->getMovie();
            break;
        case '/api/annotations':
            $controller = new \App\Controllers\ApiController();
            $controller->getMovieAnnotations();
            break;
            
        // Search
        case '/search':
            $controller = new \App\Controllers\HomeController();
            $controller->search();
            break;
            
        // Handle 404 Not Found
        default:
            http_response_code(404);
            $controller = new \App\Controllers\HomeController();
            $controller->notFound();
            break;
    }
} catch (Exception $e) {
    // Handle exceptions
    http_response_code(500);
    if (defined('DEV_MODE') && DEV_MODE) {
        echo "<h1>Error</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        // In production, show a generic error
        $controller = new \App\Controllers\HomeController();
        $controller->error($e->getMessage());
    }
}