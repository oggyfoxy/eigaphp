<?php
namespace App\Controllers;

use App\Services\Auth;

/**
 * Base Controller for common functionalities
 */
class BaseController {
    /**
     * Data to pass to all views
     */
    protected $viewData = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Set global view data available in all templates
        $this->viewData = [
            'siteName' => SITE_NAME,
            'baseUrl' => BASE_URL,
            'currentYear' => date('Y'),
            'isLoggedIn' => Auth::isLoggedIn(),
            'currentUser' => Auth::user()
        ];
    }
    
    /**
     * Renders a view file within the main layout.
     *
     * @param string $viewName The path to the view file relative to the Views directory (e.g., 'home/index'). NO .php extension needed.
     * @param array $data Optional data to pass to the view (becomes variables). Should include 'pageTitle'.
     */
    protected function render(string $viewName, array $data = []) {
        // Merge with default view data
        $data = array_merge($this->viewData, $data);
        
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
     * Returns JSON response. Useful for API endpoints and AJAX requests.
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
    
    /**
     * Set a flash message to be displayed on the next page load
     *
     * @param string $type The type of message (success, info, warning, error)
     * @param string $message The message text
     */
    protected function setFlashMessage(string $type, string $message) {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Get all flash messages and clear them from the session
     *
     * @return array The flash messages
     */
    protected function getFlashMessages() {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    
    /**
     * Check if user is authenticated, redirect to login if not
     */
    protected function requireAuth() {
        if (!Auth::isLoggedIn()) {
            $this->setFlashMessage('warning', 'Please log in to access that page.');
            $this->redirect('login');
        }
    }
    
    /**
     * Check if user has permission to perform an action
     *
     * @param string $action The action to check
     * @param string|null $resourceType The type of resource (optional)
     * @param int|null $resourceId The ID of the resource (optional)
     * @param bool $redirectOnFailure Whether to redirect on failure (default true)
     * @return bool Whether the user has permission
     */
    protected function checkPermission(string $action, $resourceType = null, $resourceId = null, $redirectOnFailure = true) {
        $hasPermission = Auth::can($action, $resourceType, $resourceId);
        
        if (!$hasPermission && $redirectOnFailure) {
            $this->setFlashMessage('error', 'You do not have permission to perform that action.');
            $this->redirect('');
        }
        
        return $hasPermission;
    }
    
    /**
     * Get POST data with validation and filtering
     *
     * @param array $fields The fields to get from POST data
     * @param bool $required Whether all fields are required
     * @return array The filtered and validated data
     */
    protected function getPostData(array $fields, bool $required = true) {
        $data = [];
        $errors = [];
        
        foreach ($fields as $field) {
            $value = $_POST[$field] ?? null;
            
            if ($required && ($value === null || $value === '')) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            } else {
                // Basic filtering
                if (is_string($value)) {
                    $value = trim($value);
                }
                
                $data[$field] = $value;
            }
        }
        
        return ['data' => $data, 'errors' => $errors];
    }
}