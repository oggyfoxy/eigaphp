<?php
namespace App\Controllers;

// Base Controller for common functionalities
class BaseController {

    /**
     * Renders a view file within the main layout.
     *
     * @param string $viewName The path to the view file relative to the Views directory (e.g., 'home/index'). NO .php extension needed.
     * @param array $data Optional data to pass to the view (becomes variables). Should include 'pageTitle'.
     */
    protected function render(string $viewName, array $data = []) {
        // Construct the full path to the view file
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $viewName) . '.php'; // Allow dot notation like 'movies.show'

        if (!file_exists($viewPath)) {
            // Handle view not found error gracefully
            error_log("View file not found: " . $viewPath);
            // Maybe render an error view or throw an exception
            http_response_code(500);
            echo "Error: View file not found."; // Simple error for now
            exit;
        }

        // Extract data array keys into variables ($title, $movies, etc.)
        // Prefixed to avoid conflicts with internal variables
        extract($data, EXTR_PREFIX_SAME, 'data_');

        // Start output buffering to capture the view's output
        ob_start();
        // Include the specific view file
        require $viewPath;
        // Get the captured output
        $viewContent = ob_get_clean(); // $viewContent variable now holds the HTML from the specific view

        // Now include the main layout file.
        // The layout file itself will use $pageTitle (expected from $data) and $viewContent.
        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Redirects to a different URL.
     *
     * @param string $url The URL to redirect to (relative to BASE_URL or absolute).
     */
    protected function redirect(string $url) {
        // If it's not an absolute URL, prepend BASE_URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // Ensure BASE_URL ends with a slash and $url doesn't start with one
            $baseUrl = rtrim(BASE_URL, '/') . '/';
            $url = $baseUrl . ltrim($url, '/');
        }
        header('Location: ' . $url);
        exit; // Important to stop script execution after redirect header
    }

     /**
     * Returns JSON response. Useful for API endpoints.
     *
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code (default 200).
     */
    protected function jsonResponse($data, int $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit; // Stop script execution
    }
}