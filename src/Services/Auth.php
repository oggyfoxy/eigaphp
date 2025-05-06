<?php
namespace App\Services;

use App\Models\User;

/**
 * Authentication and session management service
 */
class Auth {
    private static $user = null;
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user data
     */
    public static function user() {
        if (self::$user !== null) {
            return self::$user;
        }
        
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $userModel = new User();
        self::$user = $userModel->getUserById($_SESSION['user_id']);
        
        return self::$user;
    }
    
    /**
     * Get current user ID
     */
    public static function userId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Login a user
     */
    public static function login($email, $password) {
        $userModel = new User();
        $user = $userModel->login($email, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Store user data in memory
            self::$user = $user;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout the user
     */
    public static function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // If it's desired to kill the session, also delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Finally, destroy the session
        session_destroy();
        
        // Clear user data in memory
        self::$user = null;
    }
    
    /**
     * Register a new user
     */
    public static function register($username, $email, $password, $passwordConfirm) {
        // Validate inputs
        $errors = [];
        
        // Username validation
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors['username'] = 'Username must be between 3 and 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        }
        
        // Email validation
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        // Password validation
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        // Password confirmation
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if username or email already exists
        $userModel = new User();
        $existingUser = $userModel->getUserByUsername($username);
        
        if ($existingUser) {
            return ['success' => false, 'errors' => ['username' => 'Username already taken']];
        }
        
        $existingEmail = $userModel->getUserByEmail($email);
        
        if ($existingEmail) {
            return ['success' => false, 'errors' => ['email' => 'Email already registered']];
        }
        
        // Register the user
        $userId = $userModel->register($username, $email, $password);
        
        if ($userId) {
            // Auto-login the user
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Registration failed']];
    }
    
    /**
     * Check if user has permission to perform an action
     */
    public static function can($action, $resourceType = null, $resourceId = null) {
        // If not logged in, no permissions
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $userId = self::userId();
        
        // Basic permissions
        switch ($action) {
            case 'view_public':
                return true; // Anyone can view public resources
                
            case 'create_annotation':
            case 'create_collection':
            case 'rate_movie':
                return true; // Logged in users can create
                
            case 'update_user':
            case 'delete_user':
                // Can only update/delete own user
                return $resourceId == $userId;
                
            case 'update_annotation':
            case 'delete_annotation':
                // Check if annotation belongs to user
                if ($resourceId) {
                    $db = \App\Models\Database::getInstance();
                    $sql = "SELECT user_id FROM annotations WHERE id = ?";
                    $result = $db->select($sql, [$resourceId]);
                    
                    return $result && $result[0]['user_id'] == $userId;
                }
                return false;
                
            case 'update_collection':
            case 'delete_collection':
                // Check if collection belongs to user
                if ($resourceId) {
                    $db = \App\Models\Database::getInstance();
                    $sql = "SELECT user_id FROM collections WHERE id = ?";
                    $result = $db->select($sql, [$resourceId]);
                    
                    return $result && $result[0]['user_id'] == $userId;
                }
                return false;
                
            default:
                return false;
        }
    }
}