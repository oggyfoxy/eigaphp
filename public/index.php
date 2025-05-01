<?php
// Start session (we'll need this for login later)
session_start();

// 1. Load Configuration
require_once __DIR__ . '/../config/config.php';

// 2. Basic Autoloader
spl_autoload_register(function ($class_name) {
    $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
    $prefix = 'App\\'; // Be careful with escaping backslashes if using double quotes
    $base_dir = __DIR__ . '/../src/'; // Path to the src directory

    $len = strlen($prefix);
    if (strncmp($prefix, $class_name, $len) !== 0) {
        // echo "Autoloader skipping non-App class: " . $class_name . "<br>"; // Optional debug
        return;
    }

    $relative_class = substr($class_name, $len);
    $file = $base_dir . $relative_class . '.php';

    // --- TEMPORARY DEBUG ---
    echo "Autoloader attempting to load class: " . $class_name . "<br>";
    echo "Checking file path: " . $file . "<br>";
    // --- END DEBUG ---

    if (file_exists($file)) {
        // echo "File found! Requiring: " . $file . "<br>"; // More debug
        require_once $file;
    } else {
        echo "File NOT FOUND: " . $file . "<br>"; // Important debug!
        // Log error or handle missing class file
        error_log("Autoloader: Could not find file for class: " . $class_name . " at path: " . $file);
    }
});



// 3. Simple Routing
$requestUri = $_SERVER['REQUEST_URI'];
// Remove base path and query string to get the route path
$basePath = dirname($_SERVER['SCRIPT_NAME']); // Gets '/eiganights_php/public' or similar
if ($basePath !== '/' && strpos($requestUri, $basePath) === 0) {
    $routePath = substr($requestUri, strlen($basePath));
} else {
    $routePath = $requestUri;
}
$routePath = strtok($routePath, '?'); // Remove query string like ?id=123
$routePath = rtrim($routePath, '/'); // Remove trailing slash
if (empty($routePath)) {
    $routePath = '/'; // Default to root if empty after trimming
}


// --- Route Definitions ---
// Match the cleaned route path
switch ($routePath) {
    case '/':
            
        require_once __DIR__ . '/../src/Controllers/HomeController.php';
        require_once __DIR__ . '/../src/Controllers/BaseController.php';

        // Load Home Controller
        $controller = new App\Controllers\HomeController(); // Assumes HomeController exists
        $controller->index(); // Call the index method
        break;

    case '/movies':
        // Example: Load Movie Controller (We'll create this later)
        // $controller = new App\Controllers\MovieController();
        // $controller->index();
        echo "Movie List Page (Not Implemented Yet)"; // Placeholder
        break;

    case '/movie': // Example: /movie?id=123 - Query string handled separately
        // Example: Load Movie Controller show method (Later)
        // $controller = new App\Controllers\MovieController();
        // $controller->show($_GET['id'] ?? null); // Pass ID from query string
        echo "Single Movie Page (Not Implemented Yet)"; // Placeholder
        break;

    // Add more cases for /profile, /login, /api/..., etc. later

    default:
        // Handle 404 Not Found
        http_response_code(404);
        // You would ideally render a proper 404 view here
        // For now, just echo:
        require_once __DIR__ . '/../src/Views/errors/404.php'; // Need to create this file
        break;
}


// --- Helper function often put in a BaseController or utility class ---
// (We'll create controllers next, which will use this concept)
/*
function renderView($viewName, $data = []) {
    extract($data); // Make array keys into variables (e.g., $data['title'] becomes $title)

    // Start output buffering
    ob_start();
    // Include the specific view file (e.g., 'home/index.php')
    require __DIR__ . '/../src/Views/' . $viewName . '.php';
    // Get the content of the buffer
    $viewContent = ob_get_clean();

    // Now include the main layout, passing the title and content
    $pageTitle = $data['pageTitle'] ?? 'eiganights'; // Pass title from data
    require __DIR__ . '/../src/Views/layouts/main.php';
}
*/