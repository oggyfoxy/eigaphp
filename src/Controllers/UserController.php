<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Movie;
use App\Models\Collection;
use App\Services\Auth;

/**
 * Controller for user-related pages and actions
 */
class UserController extends BaseController {
    private $userModel;
    private $movieModel;
    private $collectionModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->movieModel = new Movie();
        $this->collectionModel = new Collection();
    }
    
    /**
     * User profile page
     */
    public function profile($username = null) {
        if (!$username) {
            $username = $_GET['username'] ?? null;
        }
        
        // If no username provided and user is logged in, show their profile
        if (!$username && Auth::isLoggedIn()) {
            $username = Auth::user()['username'];
        }
        
        if (!$username) {
            $this->setFlashMessage('error', 'User not found.');
            $this->redirect('');
            return;
        }
        
        // Get user data
        $user = $this->userModel->getUserByUsername($username);
        
        if (!$user) {
            $this->setFlashMessage('error', 'User not found.');
            $this->redirect('');
            return;
        }
        
        // Get user's recent annotations
        $annotations = $this->userModel->getRecentAnnotations($user['id'], 10);
        
        // Get user's favorite movies
        $favoriteMovies = $this->userModel->getFavoriteMovies($user['id'], 8);
        
        // Get user's collections
        $isOwner = Auth::isLoggedIn() && Auth::userId() == $user['id'];
        $collections = $this->collectionModel->getUserCollections($user['id'], $isOwner, 4);
        
        // Check if current user is following this user
        $isFollowing = false;
        if (Auth::isLoggedIn() && Auth::userId() != $user['id']) {
            $isFollowing = $this->userModel->isFollowing(Auth::userId(), $user['id']);
        }
        
        $data = [
            'pageTitle' => $user['username'] . '\'s Profile',
            'flashMessages' => $this->getFlashMessages(),
            'user' => $user,
            'annotations' => $annotations,
            'favoriteMovies' => $favoriteMovies,
            'collections' => $collections,
            'isOwner' => $isOwner,
            'isFollowing' => $isFollowing
        ];
        
        $this->render('users/profile', $data);
    }
    
    /**
     * Login page
     */
    public function login() {
        // If already logged in, redirect to home
        if (Auth::isLoggedIn()) {
            $this->redirect('');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['email', 'password']);
            
            if (!empty($result['errors'])) {
                $data = [
                    'pageTitle' => 'Login',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $result['errors']
                ];
                
                $this->render('users/login', $data);
                return;
            }
            
            $email = $result['data']['email'];
            $password = $result['data']['password'];
            
            // Attempt login
            if (Auth::login($email, $password)) {
                // Redirect to home page or intended URL
                $redirect = $_SESSION['redirect_after_login'] ?? '';
                unset($_SESSION['redirect_after_login']);
                
                $this->setFlashMessage('success', 'Welcome back, ' . Auth::user()['username'] . '!');
                $this->redirect($redirect ?: '');
            } else {
                $data = [
                    'pageTitle' => 'Login',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['login' => 'Invalid email or password.'],
                    'email' => $email
                ];
                
                $this->render('users/login', $data);
            }
        } else {
            // Store the current URL for redirect after login if it's not the login page itself
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            if (!empty($referrer) && strpos($referrer, 'login') === false) {
                $_SESSION['redirect_after_login'] = $referrer;
            }
            
            $data = [
                'pageTitle' => 'Login',
                'flashMessages' => $this->getFlashMessages()
            ];
            
            $this->render('users/login', $data);
        }
    }
    
    /**
     * Logout action
     */
    public function logout() {
        if (Auth::isLoggedIn()) {
            Auth::logout();
            $this->setFlashMessage('success', 'You have been logged out.');
        }
        
        $this->redirect('');
    }
    
    /**
     * Registration page
     */
    public function register() {
        // If already logged in, redirect to home
        if (Auth::isLoggedIn()) {
            $this->redirect('');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['username', 'email', 'password', 'password_confirm']);
            
            if (!empty($result['errors'])) {
                $data = [
                    'pageTitle' => 'Register',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $result['errors'],
                    'formData' => $result['data']
                ];
                
                $this->render('users/register', $data);
                return;
            }
            
            // Register user
            $registerResult = Auth::register(
                $result['data']['username'],
                $result['data']['email'],
                $result['data']['password'],
                $result['data']['password_confirm']
            );
            
            if ($registerResult['success']) {
                $this->setFlashMessage('success', 'Registration successful! Welcome to ' . SITE_NAME . '.');
                $this->redirect('');
            } else {
                $data = [
                    'pageTitle' => 'Register',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $registerResult['errors'],
                    'formData' => $result['data']
                ];
                
                $this->render('users/register', $data);
            }
        } else {
            $data = [
                'pageTitle' => 'Register',
                'flashMessages' => $this->getFlashMessages()
            ];
            
            $this->render('users/register', $data);
        }
    }
    
    /**
     * Edit profile page
     */
    public function edit() {
        // Check if user is logged in
        $this->requireAuth();
        
        $userId = Auth::userId();
        $user = Auth::user();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['username', 'email', 'bio']);
            
            if (!empty($result['errors'])) {
                $data = [
                    'pageTitle' => 'Edit Profile',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $result['errors'],
                    'user' => $user
                ];
                
                $this->render('users/edit', $data);
                return;
            }
            
            // Handle avatar upload if provided
            $avatar = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatar = $this->handleAvatarUpload($userId);
                
                if (is_array($avatar)) { // Error occurred
                    $data = [
                        'pageTitle' => 'Edit Profile',
                        'flashMessages' => $this->getFlashMessages(),
                        'errors' => $avatar,
                        'user' => $user
                    ];
                    
                    $this->render('users/edit', $data);
                    return;
                }
            }
            
            // Update profile data
            $updateData = [
                'username' => $result['data']['username'],
                'email' => $result['data']['email'],
                'bio' => $result['data']['bio']
            ];
            
            if ($avatar) {
                $updateData['avatar'] = $avatar;
            }
            
            $success = $this->userModel->updateProfile($userId, $updateData);
            
            if ($success) {
                $this->setFlashMessage('success', 'Profile updated successfully!');
                $this->redirect('profile');
            } else {
                $data = [
                    'pageTitle' => 'Edit Profile',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['general' => 'Failed to update profile.'],
                    'user' => $user
                ];
                
                $this->render('users/edit', $data);
            }
        } else {
            $data = [
                'pageTitle' => 'Edit Profile',
                'flashMessages' => $this->getFlashMessages(),
                'user' => $user
            ];
            
            $this->render('users/edit', $data);
        }
    }
    
    /**
     * Handle avatar image upload
     * 
     * @param int $userId The user ID
     * @return string|array The avatar filename or an array of errors
     */
    private function handleAvatarUpload($userId) {
        $file = $_FILES['avatar'];
        $errors = [];
        
        // Check file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $errors['avatar'] = 'Avatar image must be less than 2MB.';
            return $errors;
        }
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors['avatar'] = 'Avatar must be a JPEG, PNG, or GIF image.';
            return $errors;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadsDir = __DIR__ . '/../../public/uploads/avatars';
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . uniqid() . '.' . $extension;
        $targetPath = $uploadsDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors['avatar'] = 'Failed to upload avatar image.';
            return $errors;
        }
        
        return 'uploads/avatars/' . $filename;
    }
    
    /**
     * Change password page
     */
    public function changePassword() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['current_password', 'new_password', 'confirm_password']);
            
            if (!empty($result['errors'])) {
                $data = [
                    'pageTitle' => 'Change Password',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $result['errors']
                ];
                
                $this->render('users/change_password', $data);
                return;
            }
            
            // Verify current password
            $currentPassword = $result['data']['current_password'];
            $newPassword = $result['data']['new_password'];
            $confirmPassword = $result['data']['confirm_password'];
            
            // Check if current password is correct
            if (!Auth::login(Auth::user()['email'], $currentPassword)) {
                $data = [
                    'pageTitle' => 'Change Password',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['current_password' => 'Current password is incorrect.']
                ];
                
                $this->render('users/change_password', $data);
                return;
            }
            
            // Check if new passwords match
            if ($newPassword !== $confirmPassword) {
                $data = [
                    'pageTitle' => 'Change Password',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['confirm_password' => 'New passwords do not match.']
                ];
                
                $this->render('users/change_password', $data);
                return;
            }
            
            // Update password
            $success = $this->userModel->updatePassword(Auth::userId(), $newPassword);
            
            if ($success) {
                $this->setFlashMessage('success', 'Password changed successfully!');
                $this->redirect('profile');
            } else {
                $data = [
                    'pageTitle' => 'Change Password',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['general' => 'Failed to change password.']
                ];
                
                $this->render('users/change_password', $data);
            }
        } else {
            $data = [
                'pageTitle' => 'Change Password',
                'flashMessages' => $this->getFlashMessages()
            ];
            
            $this->render('users/change_password', $data);
        }
    }
    
    /**
     * Follow/unfollow a user
     */
    public function toggleFollow() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['user_id']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $followedId = $result['data']['user_id'];
            $followerId = Auth::userId();
            
            // Can't follow yourself
            if ($followedId == $followerId) {
                $this->jsonResponse(['success' => false, 'errors' => ['user_id' => 'You cannot follow yourself.']], 400);
                return;
            }
            
            // Check if already following
            $isFollowing = $this->userModel->isFollowing($followerId, $followedId);
            
            if ($isFollowing) {
                // Unfollow
                $success = $this->userModel->unfollowUser($followerId, $followedId);
                $message = 'Unfollowed successfully.';
                $newStatus = false;
            } else {
                // Follow
                $success = $this->userModel->followUser($followerId, $followedId);
                $message = 'Followed successfully!';
                $newStatus = true;
            }
            
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'isFollowing' => $newStatus
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'errors' => ['general' => 'Failed to update follow status.']], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse(['success' => false, 'errors' => ['method' => 'Invalid request method.']], 405);
        }
    }
    
    /**
     * User activity feed
     */
    public function feed() {
        // Check if user is logged in
        $this->requireAuth();
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Get activity feed
        $feed = $this->userModel->getActivityFeed(Auth::userId(), $limit, $offset);
        
        $data = [
            'pageTitle' => 'Activity Feed',
            'flashMessages' => $this->getFlashMessages(),
            'feed' => $feed,
            'page' => $page,
            'limit' => $limit
        ];
        
        $this->render('users/feed', $data);
    }
    
    /**
     * User's favorite movies
     */
    public function favorites($username = null) {
        if (!$username) {
            $username = $_GET['username'] ?? null;
        }
        
        // If no username provided and user is logged in, show their favorites
        if (!$username && Auth::isLoggedIn()) {
            $username = Auth::user()['username'];
        }
        
        if (!$username) {
            $this->setFlashMessage('error', 'User not found.');
            $this->redirect('');
            return;
        }
        
        // Get user data
        $user = $this->userModel->getUserByUsername($username);
        
        if (!$user) {
            $this->setFlashMessage('error', 'User not found.');
            $this->redirect('');
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = 24;
        $offset = ($page - 1) * $limit;
        
        // Get user's favorite movies
        $favoriteMovies = $this->userModel->getFavoriteMovies($user['id'], $limit, $offset);
        
        $data = [
            'pageTitle' => $user['username'] . '\'s Favorite Movies',
            'flashMessages' => $this->getFlashMessages(),
            'user' => $user,
            'movies' => $favoriteMovies,
            'page' => $page,
            'limit' => $limit
        ];
        
        $this->render('users/favorites', $data);
    }
}