<?php
/**
 * Database Test API
 * Backend API for the database test modal
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

$action = $_GET['action'] ?? 'test_connection';

try {
    switch ($action) {
        case 'get_config':
            getConfig();
            break;
        case 'test_connection':
            testConnection();
            break;
        case 'check_database':
            checkDatabase();
            break;
        case 'test_tables':
            testTables();
            break;
        case 'test_crud':
            testCRUDOperations();
            break;
        case 'test_auth':
            testAuthSystem();
            break;
        case 'test_user_creation':
            testUserCreation();
            break;
        case 'test_sessions':
            testSessionManagement();
            break;
        case 'test_movies':
            testMovieOperations();
            break;
        case 'test_bookings':
            testBookingSystem();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getConfig() {
    try {
        $database = new Database();
        $config = $database->getConfig();
        echo json_encode(['success' => true, 'config' => $config]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testConnection() {
    try {
        $database = new Database();
        $result = $database->testConnection();
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function checkDatabase() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if database exists and get tables
        $stmt = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Database exists with ' . count($tables) . ' tables',
            'tables' => $tables
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testTables() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $requiredTables = ['users', 'movies', 'bookings', 'user_sessions'];
        $details = [];
        
        foreach ($requiredTables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                // Get table structure
                $structure = $conn->query("DESCRIBE $table")->fetchAll();
                $details[$table] = [
                    'exists' => true,
                    'columns' => count($structure)
                ];
            } else {
                $details[$table] = ['exists' => false];
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Table structure test completed',
            'details' => $details
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testCRUDOperations() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Test INSERT
        $testData = [
            'full_name' => 'Test User CRUD',
            'email' => 'testcrud_' . time() . '@example.com',
            'username' => 'testcrud_' . time(),
            'password' => password_hash('testpass', PASSWORD_DEFAULT)
        ];
        
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password) VALUES (?, ?, ?, ?)");
        $stmt->execute(array_values($testData));
        $userId = $conn->lastInsertId();
        
        // Test SELECT
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Test UPDATE
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->execute(['Test User CRUD Updated', $userId]);
        
        // Test DELETE
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'CRUD operations test passed',
            'test_user_id' => $userId
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testAuthSystem() {
    try {
        if (file_exists('php/auth.php')) {
            require_once 'php/auth.php';
            $auth = new UserAuth();
            echo json_encode([
                'success' => true,
                'message' => 'Authentication system loaded successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Auth system file not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testUserCreation() {
    try {
        require_once 'php/auth.php';
        $auth = new UserAuth();
        
        $testEmail = 'testuser_' . time() . '@example.com';
        $testUsername = 'testuser_' . time();
        
        $result = $auth->register(
            'Test User Creation',
            $testEmail,
            $testUsername,
            'testpass123',
            'testpass123'
        );
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testSessionManagement() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if user_sessions table exists and can be queried
        $stmt = $conn->query("SELECT COUNT(*) as session_count FROM user_sessions");
        $result = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Session management test passed',
            'session_count' => $result['session_count']
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testMovieOperations() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Test movie queries
        $stmt = $conn->query("SELECT COUNT(*) as movie_count FROM movies");
        $result = $stmt->fetch();
        
        // Test movie insertion
        $testMovie = [
            'title' => 'Test Movie ' . time(),
            'description' => 'Test movie description',
            'genre' => 'Test',
            'language' => 'English',
            'image_url' => 'test.jpg',
            'is_showing' => 1,
            'show_times' => '["10:00", "14:00", "18:00"]'
        ];
        
        $stmt = $conn->prepare("INSERT INTO movies (title, description, genre, language, image_url, is_showing, show_times) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array_values($testMovie));
        $movieId = $conn->lastInsertId();
        
        // Clean up test movie
        $stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$movieId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Movie operations test passed',
            'movie_count' => $result['movie_count']
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function testBookingSystem() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Test booking queries
        $stmt = $conn->query("SELECT COUNT(*) as booking_count FROM bookings");
        $result = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking system test passed',
            'booking_count' => $result['booking_count']
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
