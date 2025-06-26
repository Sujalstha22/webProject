<?php
/**
 * User Authentication System - Fixed
 */

session_start();

// Include database connection
$db_path = __DIR__ . '/../config/database.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    // Fallback path
    require_once '../config/database.php';
}

class UserAuth {
    private $db;

    public function __construct() {
        $this->db = getDB();
        $this->setupUsersTable();
    }

    private function setupUsersTable() {
        try {
            // Create users table
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                is_admin BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create users table: " . $this->db->error);
            }
            
            // Create default admin if not exists
            $this->createDefaultAdmin();
            
        } catch (Exception $e) {
            error_log("Users table setup failed: " . $e->getMessage());
        }
    }

    private function createDefaultAdmin() {
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row['count'] == 0) {
                    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 1)");
                    if ($stmt) {
                        $name = 'Admin User';
                        $email = 'admin@ssrcinema.com';
                        $username = 'admin';
                        $stmt->bind_param("ssss", $name, $email, $username, $adminPassword);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Admin creation failed: " . $e->getMessage());
        }
    }

    public function register($full_name, $email, $username, $password, $confirm_password) {
        // Validation
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
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'User with this email or username already exists.'];
            }
            $stmt->close();

            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 0)");
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("ssss", $full_name, $email, $username, $hashed_password);
            
            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Account created successfully!'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function login($username_or_email, $password) {
        if (empty($username_or_email) || empty($password)) {
            return ['success' => false, 'message' => 'Username/email and password are required.'];
        }

        try {
            $stmt = $this->db->prepare("SELECT id, full_name, email, username, password, is_admin FROM users WHERE email = ? OR username = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'username' => $user['username'],
                        'is_admin' => (bool)$user['is_admin']
                    ];
                    
                    $stmt->close();
                    return [
                        'success' => true, 
                        'message' => 'Login successful!', 
                        'user' => $_SESSION['user']
                    ];
                } else {
                    $stmt->close();
                    return ['success' => false, 'message' => 'Incorrect password.'];
                }
            } else {
                $stmt->close();
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
}

// Handle requests
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
