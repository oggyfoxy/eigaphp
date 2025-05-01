<?php
namespace App\Models; // Use a namespace to organize code

use PDO; // PHP Data Objects extension for database access
use PDOException; // For handling database errors

class Database {
    private static $instance = null;
    private $conn;      // Default XAMPP password is empty (change if needed)

    private function __construct() {
        // Use constants defined in config.php
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $db_name = defined('DB_NAME') ? DB_NAME : 'eiganights_db';
        $username = defined('DB_USER') ? DB_USER : 'root';
        $password = defined('DB_PASS') ? DB_PASS : '';

        $dsn = 'mysql:host=' . $host . ';dbname=' . $db_name . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            // Use the variables holding config values
            $this->conn = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection failed. Please check configuration in config/config.php');
        }
    }

    // The public static method to get the single instance
    public static function getInstance() {
        if (self::$instance == null) {
            // If instance doesn't exist, create it by calling the private constructor
            self::$instance = new Database();
        }
        // Return the single instance
        return self::$instance;
    }

    // Method to get the actual PDO connection object
    public function getConnection() {
        return $this->conn;
    }

    // Prevent cloning and unserialization of the instance (Singleton pattern)


    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    private function __clone() { }
    public function __wakeup() { }
}


// --- How to use it later in other files ---
// require_once __DIR__ . '/Database.php'; // Make sure path is correct
// $dbInstance = Database::getInstance();
// $pdoConnection = $dbInstance->getConnection();
// $stmt = $pdoConnection->prepare("SELECT * FROM some_table WHERE id = ?");
// $stmt->execute([1]);
// $result = $stmt->fetch();