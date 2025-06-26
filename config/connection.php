<?php
/**
 * Database Connection Handler
 * Provides a reusable database connection for all PHP files
 */

class DatabaseConnection {
    private static $instance = null;
    private $connection;
    private $host = 'localhost';
    private $username = 'root';
    private $password = 'root';
    private $database = 'ssr_cinema';

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            // First connect without database to create it if needed
            $this->connection = new mysqli($this->host, $this->username, $this->password);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }

            // Create database if it doesn't exist
            $this->connection->query("CREATE DATABASE IF NOT EXISTS `{$this->database}`");
            
            // Select the database
            $this->connection->select_db($this->database);
            
            // Set charset
            $this->connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function escape_string($string) {
        return $this->connection->real_escape_string($string);
    }

    public function insert_id() {
        return $this->connection->insert_id;
    }

    public function affected_rows() {
        return $this->connection->affected_rows;
    }

    public function error() {
        return $this->connection->error;
    }

    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    private function __wakeup() {}
}

// Helper function for easy access
function getDB() {
    return DatabaseConnection::getInstance();
}
?>
