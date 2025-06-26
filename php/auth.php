<?php
session_start();

class UserAuth {
    private $pdo;

    public function __construct() {
        try {
            // Database connection with database creation
            $host = 'localhost';
            $user = 'root';
            $pass = 'root';
            $dbname = 'ssr_cinema';
            
            // First connect without database to create it if needed
            $pdo_temp = new PDO("mysql:host=$host", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            // Create database if it doesn't exist
            $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
            
            // Now connect to the specific database
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            // Ensure users table exists with all columns
            $this->createAndFixUsersTable();
            
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function createAndFixUsersTable() {
        try {
            // Create users table if it doesn't exist
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Check existing columns
            $stmt = $this->pdo->query("DESCRIBE users");
            $existingColumns = [];
            while ($row = $stmt->fetch()) {
                $existingColumns[] = $row['Field'];
            }
            
            // Add missing columns
            if (!in_array('is_admin', $existingColumns)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
            }
            
            // Ensure admin user exists
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
            if ($stmt->fetch()['count'] == 0) {
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute(['Admin User', 'admin@ssrcinema.com', 'admin', $adminPassword]);
            } else {
                // Update existing admin user to have admin privileges
                $this->pdo->exec("UPDATE users SET is_admin = 1 WHERE username = 'admin'");
            }
            
            // Ensure test user exists
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'testuser'");
            if ($stmt->fetch()['count'] == 0) {
                $testPassword = password_hash('test123', PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute(['Test User', 'test@example.com', 'testuser', $testPassword]);
            }
            
        } catch (Exception $e) {
            // If there's still an error, try to work without is_admin column
            error_log("Users table setup error: " . $e->getMessage());
        }
    }

    public function register($full_name, $email, $username, $password, $confirm_password) {
        if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        if ($password !== $confirm_password) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters long.'];
        }

        try {
            // Check if user already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'User with this email or username already exists.'];
            }

            // Create new user - check if is_admin column exists
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Check if is_admin column exists
            $stmt = $this->pdo->query("DESCRIBE users");
            $columns = [];
            while ($row = $stmt->fetch()) {
                $columns[] = $row['Field'];
            }
            
            if (in_array('is_admin', $columns)) {
                $stmt = $this->pdo->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$full_name, $email, $username, $hashed_password]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO users (full_name, email, username, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $username, $hashed_password]);
            }

            return ['success' => true, 'message' => 'Account created successfully!'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function login($username_or_email, $password) {
        if (empty($username_or_email) || empty($password)) {
            return ['success' => false, 'message' => 'Username/email and password are required.'];
        }

        try {
            // Check if is_admin column exists
            $stmt = $this->pdo->query("DESCRIBE users");
            $columns = [];
            while ($row = $stmt->fetch()) {
                $columns[] = $row['Field'];
            }
            
            // Build query based on available columns
            if (in_array('is_admin', $columns)) {
                $query = "SELECT id, full_name, email, username, password, is_admin FROM users WHERE email = ? OR username = ?";
            } else {
                $query = "SELECT id, full_name, email, username, password FROM users WHERE email = ? OR username = ?";
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$username_or_email, $username_or_email]);

            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password'])) {
                    // Set session data
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'username' => $user['username'],
                        'is_admin' => isset($user['is_admin']) ? (bool)$user['is_admin'] : ($user['username'] === 'admin')
                    ];
                    
                    return [
                        'success' => true, 
                        'message' => 'Login successful!', 
                        'user' => $_SESSION['user']
                    ];
                } else {
                    return ['success' => false, 'message' => 'Incorrect password.'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    public function checkSession() {
        if (isset($_SESSION['user'])) {
            return ['success' => true, 'user' => $_SESSION['user']];
        }
        return ['success' => false, 'message' => 'Not logged in.'];
    }
    
    public function fixUsersTable() {
        try {
            $this->createAndFixUsersTable();
            return ['success' => true, 'message' => 'Users table fixed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fix users table: ' . $e->getMessage()];
        }
    }
}

// Handle AJAX POST requests
header('Content-Type: application/json');

try {
    $auth = new UserAuth();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'register':
                $result = $auth->register(
                    $_POST['full_name'] ?? '',
                    $_POST['email'] ?? '',
                    $_POST['username'] ?? '',
                    $_POST['password'] ?? '',
                    $_POST['confirm_password'] ?? ''
                );
                echo json_encode($result);
                break;

            case 'login':
                $result = $auth->login(
                    $_POST['username_or_email'] ?? '',
                    $_POST['password'] ?? ''
                );
                echo json_encode($result);
                break;

            case 'logout':
                $result = $auth->logout();
                echo json_encode($result);
                break;

            case 'check_session':
                $result = $auth->checkSession();
                echo json_encode($result);
                break;
                
            case 'fix_table':
                $result = $auth->fixUsersTable();
                echo json_encode($result);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Only POST requests allowed.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
