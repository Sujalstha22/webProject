<?php
// auth.php - User Authentication for SSR Cinema

session_start();
require_once 'Ssr Cinema Setup.php';

class UserAuth {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
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
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Email or username already exists.'];
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (full_name, email, username, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $username, $hashed_password);
        $stmt->execute();

        return ['success' => true, 'message' => 'Registration successful.'];
    }

    public function login($username_or_email, $password) {
        $stmt = $this->conn->prepare("SELECT id, full_name, email, username, password, is_admin FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['logged_in'] = true;

                return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Incorrect password.'];
            }
        }

        return ['success' => false, 'message' => 'User not found.'];
    }

    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out.'];
    }

    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }

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

// Handle POST requests
$auth = new UserAuth((new Database())->conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    switch ($action) {
        case 'register':
            echo json_encode($auth->register(
                $_POST['full_name'] ?? '',
                $_POST['email'] ?? '',
                $_POST['username'] ?? '',
                $_POST['password'] ?? '',
                $_POST['confirm_password'] ?? ''
            ));
            break;

        case 'login':
            echo json_encode($auth->login(
                $_POST['username_or_email'] ?? '',
                $_POST['password'] ?? ''
            ));
            break;

        case 'logout':
            echo json_encode($auth->logout());
            break;

        case 'check_session':
            echo json_encode([
                'success' => $auth->isLoggedIn(),
                'user' => $auth->getCurrentUser()
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }

    exit;
}
?>
