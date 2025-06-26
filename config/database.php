<?php
// SSR Cinema - Simple Backend Setup (Database + Tables + Sample Data)

class Database {
    private $host = 'localhost';
    private $user = 'root';
    private $pass = 'root';
    private $dbname = 'ssr_cinema';
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        $this->conn->query("CREATE DATABASE IF NOT EXISTS $this->dbname");
        $this->conn->select_db($this->dbname);
    }
}

class CinemaSetup {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function setup() {
        $this->createUsersTable();
        $this->createUserSessionsTable();
        $this->createMoviesTable();
        $this->createBookingsTable();
        $this->insertAdminUser();
    }

    private function createUsersTable() {
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE
        )";
        $this->conn->query($query);
    }

    private function createUserSessionsTable() {
        $query = "CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $this->conn->query($query);
    }

    private function createMoviesTable() {
        $query = "CREATE TABLE IF NOT EXISTS movies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            description TEXT,
            genre VARCHAR(100),
            image_url VARCHAR(255),
            trailer_url VARCHAR(255)
        )";
        $this->conn->query($query);
    }

    private function createBookingsTable() {
        $query = "CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            movie_id INT,
            number_of_tickets INT NOT NULL,
            booking_date DATE NOT NULL,
            total_amount DECIMAL(10,2) DEFAULT 0.00,
            booking_status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE SET NULL
        )";
        $this->conn->query($query);
    }

    private function insertAdminUser() {
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@ssrcinema.com';
        $name = 'Admin';

        $stmt = $this->conn->prepare("INSERT IGNORE INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $name, $email, $username, $password);
        $stmt->execute();
    }
}

$database = new Database();
$setup = new CinemaSetup($database->conn);
$setup->setup();

echo "<h2>✅ SSR Cinema Database Setup Complete.</h2><p>Admin Username: <code>admin</code> | Password: <code>admin123</code></p>";
?>
