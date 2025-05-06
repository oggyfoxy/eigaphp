<?php
namespace App\Models;

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $sql = "SELECT id, username, email, avatar, bio, created_at, updated_at, last_login 
                FROM users 
                WHERE id = ?";
        $result = $this->db->select($sql, [$id]);
        
        return $result ? $result[0] : false;
    }
    
    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        $sql = "SELECT id, username, email, avatar, bio, created_at, updated_at, last_login 
                FROM users 
                WHERE username = ?";
        $result = $this->db->select($sql, [$username]);
        
        return $result ? $result[0] : false;
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $sql = "SELECT id, username, email, avatar, bio, created_at, updated_at, last_login 
                FROM users 
                WHERE email = ?";
        $result = $this->db->select($sql, [$email]);
        
        return $result ? $result[0] : false;
    }
    
    /**
     * Register new user
     */
    public function register($username, $email, $password) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password) 
                VALUES (?, ?, ?)";
        return $this->db->insert($sql, [$username, $email, $hashedPassword]);
    }
    
    /**
     * Verify login
     */
    public function login($email, $password) {
        $sql = "SELECT id, username, email, password, avatar 
                FROM users 
                WHERE email = ?";
        $result = $this->db->select($sql, [$email]);
        
        if (!$result) {
            return false;
        }
        
        $user = $result[0];
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Update last login time
            $this->updateLastLogin($user['id']);
            
            // Remove password before returning user data
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE users 
                SET last_login = CURRENT_TIMESTAMP 
                WHERE id = ?";
        return $this->db->update($sql, [$userId]);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $allowedFields = ['username', 'email', 'bio', 'avatar'];
        $updates = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE users 
                SET " . implode(', ', $updates) . " 
                WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Update password
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users 
                SET password = ? 
                WHERE id = ?";
        
        return $this->db->update($sql, [$hashedPassword, $userId]);
    }
    
    /**
     * Get followers of a user
     */
    public function getFollowers($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT u.id, u.username, u.avatar, uf.created_at as followed_since
                FROM user_follows uf
                JOIN users u ON uf.follower_id = u.id
                WHERE uf.followed_id = ?
                ORDER BY uf.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$userId, $limit, $offset]);
    }
    
    /**
     * Get users followed by a user
     */
    public function getFollowing($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT u.id, u.username, u.avatar, uf.created_at as following_since
                FROM user_follows uf
                JOIN users u ON uf.followed_id = u.id
                WHERE uf.follower_id = ?
                ORDER BY uf.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$userId, $limit, $offset]);
    }
    
    /**
     * Follow a user
     */
    public function followUser($followerId, $followedId) {
        // Can't follow yourself
        if ($followerId == $followedId) {
            return false;
        }
        
        $sql = "INSERT INTO user_follows (follower_id, followed_id) 
                VALUES (?, ?)";
        
        return $this->db->insert($sql, [$followerId, $followedId]);
    }
    
    /**
     * Unfollow a user
     */
    public function unfollowUser($followerId, $followedId) {
        $sql = "DELETE FROM user_follows 
                WHERE follower_id = ? AND followed_id = ?";
        
        return $this->db->delete($sql, [$followerId, $followedId]);
    }
    
    /**
     * Check if a user is following another user
     */
    public function isFollowing($followerId, $followedId) {
        $sql = "SELECT id FROM user_follows 
                WHERE follower_id = ? AND followed_id = ?";
        $result = $this->db->select($sql, [$followerId, $followedId]);
        
        return !empty($result);
    }
    
    /**
     * Get user's activity feed
     */
    public function getActivityFeed($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT a.id, a.type, a.created_at, 
                u.id as user_id, u.username, u.avatar,
                CASE
                    WHEN a.type = 'annotation' THEN an.id
                    WHEN a.type = 'comment' THEN ac.id
                    WHEN a.type = 'like' THEN al.id
                    WHEN a.type = 'rating' THEN ur.id
                    ELSE NULL
                END as content_id,
                CASE
                    WHEN a.type = 'annotation' THEN an.content
                    WHEN a.type = 'comment' THEN ac.content
                    WHEN a.type = 'rating' THEN CONCAT(ur.rating, '/10')
                    ELSE NULL
                END as content,
                m.id as movie_id, m.title as movie_title, m.poster_path
                FROM activity a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN annotations an ON a.type = 'annotation' AND a.related_id = an.id
                LEFT JOIN annotation_comments ac ON a.type = 'comment' AND a.related_id = ac.id
                LEFT JOIN annotation_likes al ON a.type = 'like' AND a.related_id = al.id
                LEFT JOIN user_ratings ur ON a.type = 'rating' AND a.related_id = ur.id
                LEFT JOIN movies m ON 
                    (a.type = 'annotation' AND an.movie_id = m.id) OR
                    (a.type = 'rating' AND ur.movie_id = m.id)
                WHERE a.user_id IN (
                    SELECT followed_id FROM user_follows WHERE follower_id = ?
                )
                ORDER BY a.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$userId, $limit, $offset]);
    }
    
    /**
     * Get user's favorite movies
     */
    public function getFavoriteMovies($userId, $limit = 12, $offset = 0) {
        $sql = "SELECT m.id, m.title, m.release_year, m.poster_path, uf.created_at
                FROM user_favorites uf
                JOIN movies m ON uf.movie_id = m.id
                WHERE uf.user_id = ?
                ORDER BY uf.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$userId, $limit, $offset]);
    }
    
    /**
     * Get user's recent annotations
     */
    public function getRecentAnnotations($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT a.id, a.content, a.created_at, a.timestamp_seconds,
                m.id as movie_id, m.title as movie_title, m.poster_path,
                (SELECT COUNT(*) FROM annotation_likes WHERE annotation_id = a.id) as likes_count,
                (SELECT COUNT(*) FROM annotation_comments WHERE annotation_id = a.id) as comments_count
                FROM annotations a
                JOIN movies m ON a.movie_id = m.id
                WHERE a.user_id = ?
                ORDER BY a.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$userId, $limit, $offset]);
    }
}