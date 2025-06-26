<?php
/**
 * User Authentication Handler
 * SSR Cinema User Registration and Login
 */

session_start();
require_once '../config/database.php';

class UserAuth {
    private $conn;
    private $table_name = "users";
    private $sessions_table = "user_sessions";
    private $activity_table = "user_activity";
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes in seconds

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();

        // Set secure session parameters
        $this->setSecureSessionParams();
    }

    /**
     * Set secure session parameters
     */
    private function setSecureSessionParams() {
        // Set session cookie parameters for security
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $httponly = true;
        $samesite = 'Strict';

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 3600, // 1 hour
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite
            ]);
        } else {
            session_set_cookie_params(3600, '/', '', $secure, $httponly);
        }
    }

    /**
     * Register a new user
     */
    public function register($full_name, $email, $username, $password, $confirm_password) {
        // Validate input
        if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if ($password !== $confirm_password) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        // Check if user already exists
        if ($this->userExists($email, $username)) {
            return ['success' => false, 'message' => 'User with this email or username already exists.'];
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $query = "INSERT INTO " . $this->table_name . " 
                 (full_name, email, username, password) 
                 VALUES (:full_name, :email, :username, :password)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Registration successful!'];
            } else {
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
        } catch (PDOException $exception) {
            return ['success' => false, 'message' => 'Database error: ' . $exception->getMessage()];
        }
    }

    /**
     * Login user
     */
    public function login($username_or_email, $password) {
        if (empty($username_or_email) || empty($password)) {
            return ['success' => false, 'message' => 'Username/Email and password are required.'];
        }

        $query = "SELECT id, full_name, email, username, password, is_admin 
                 FROM " . $this->table_name . " 
                 WHERE email = :username_or_email OR username = :username_or_email";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username_or_email', $username_or_email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    $_SESSION['logged_in'] = true;

                    return [
                        'success' => true, 
                        'message' => 'Login successful!',
                        'user' => [
                            'id' => $user['id'],
                            'full_name' => $user['full_name'],
                            'email' => $user['email'],
                            'username' => $user['username'],
                            'is_admin' => $user['is_admin']
                        ]
                    ];
                } else {
                    return ['success' => false, 'message' => 'Invalid password.'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found.'];
            }
        } catch (PDOException $exception) {
            return ['success' => false, 'message' => 'Database error: ' . $exception->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    /**
     * Check if user exists
     */
    private function userExists($email, $username) {
        $query = "SELECT id FROM " . $this->table_name . " 
                 WHERE email = :email OR username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }

    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'full_name' => $_SESSION['full_name'],
                'email' => $_SESSION['email'],
                'username' => $_SESSION['username'],
                'is_admin' => $_SESSION['is_admin']
            ];
        }
        return null;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new UserAuth();
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
            break;

        case 'login':
            $result = $auth->login(
                $_POST['username_or_email'] ?? '',
                $_POST['password'] ?? ''
            );
            break;

        case 'logout':
            $result = $auth->logout();
            break;

        case 'check_session':
            if ($auth->isLoggedIn()) {
                $result = [
                    'success' => true,
                    'user' => $auth->getCurrentUser()
                ];
            } else {
                $result = ['success' => false, 'message' => 'Not logged in.'];
            }
            break;

        default:
            $result = ['success' => false, 'message' => 'Invalid action.'];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>
