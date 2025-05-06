<?php
namespace App\Models;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        // Use constants defined in config.php
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $db_name = defined('DB_NAME') ? DB_NAME : 'eiganights_db';
        $username = defined('DB_USER') ? DB_USER : 'root';
        $password = defined('DB_PASS') ? DB_PASS : '';
        
        $dsn = 'mysql:host=' . $host . ';dbname=' . $db_name . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->conn = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection failed. Please check configuration.');
        }
    }
    
    // The public static method to get the single instance
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Method to get the actual PDO connection object
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning and unserialization of the instance (Singleton pattern)
    private function __clone() { }
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Helper method for SELECT queries
    public function select($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Database Query Error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Helper method for INSERT queries
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log('Database Insert Error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Helper method for UPDATE queries
    public function update($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Database Update Error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Helper method for DELETE queries
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Database Delete Error: ' . $e->getMessage());
            return false;
        }
    }
}