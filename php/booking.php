<?php
/**
 * Booking Management - Fixed Database Connection
 */

session_start();

// Include database connection with fallback
$db_path = __DIR__ . '/../config/database.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    require_once '../config/database.php';
}

class BookingManager {
    private $db;

    public function __construct() {
        $this->db = getDB();
        $this->setupBookingsTable();
    }

    private function setupBookingsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                movie_id INT,
                movie_title VARCHAR(150),
                number_of_tickets INT NOT NULL,
                booking_date DATE NOT NULL,
                total_amount DECIMAL(10,2) DEFAULT 0.00,
                booking_status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create bookings table: " . $this->db->error);
            }
            
        } catch (Exception $e) {
            error_log("Bookings table setup failed: " . $e->getMessage());
        }
    }

    public function createBooking($full_name, $email, $movie_title, $booking_date, $number_of_tickets, $showtime = null, $movie_id = null, $ticket_price = 500) {
        // Validation
        if (empty($full_name) || empty($email) || empty($movie_title) || empty($booking_date) || empty($number_of_tickets)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        if ($number_of_tickets <= 0 || $number_of_tickets > 10) {
            return ['success' => false, 'message' => 'Number of tickets must be between 1 and 10.'];
        }

        $today = date('Y-m-d');
        if ($booking_date < $today) {
            return ['success' => false, 'message' => 'Booking date cannot be in the past.'];
        }

        try {
            // Get movie ID if not provided
            if (!$movie_id) {
                $movie_id = $this->getMovieIdByTitle($movie_title);
            }
            
            // Calculate total amount
            $total_amount = $number_of_tickets * $ticket_price;

            // Create bookings table with showtime if it doesn't exist
            $this->setupBookingsTableWithShowtime();

            // Insert booking
            $stmt = $this->db->prepare("INSERT INTO bookings (full_name, email, movie_id, movie_title, booking_date, number_of_tickets, total_amount, showtime, ticket_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
        
            $stmt->bind_param("ssissidsd", $full_name, $email, $movie_id, $movie_title, $booking_date, $number_of_tickets, $total_amount, $showtime, $ticket_price);

            if ($stmt->execute()) {
                $booking_id = $this->db->insert_id;
                $stmt->close();
                return [
                    'success' => true, 
                    'message' => 'Booking confirmed successfully!',
                    'booking_id' => $booking_id,
                    'total_amount' => $total_amount,
                    'showtime' => $showtime,
                    'ticket_price' => $ticket_price
                ];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Booking failed. Please try again.'];
            }
        
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    private function setupBookingsTableWithShowtime() {
        try {
            // Check if showtime column exists
            $result = $this->db->query("SHOW COLUMNS FROM bookings LIKE 'showtime'");
            if ($result->num_rows == 0) {
                // Add showtime column
                $this->db->query("ALTER TABLE bookings ADD COLUMN showtime VARCHAR(20) DEFAULT NULL");
            }
        
            // Check if ticket_price column exists
            $result = $this->db->query("SHOW COLUMNS FROM bookings LIKE 'ticket_price'");
            if ($result->num_rows == 0) {
                // Add ticket_price column
                $this->db->query("ALTER TABLE bookings ADD COLUMN ticket_price DECIMAL(10,2) DEFAULT 500.00");
            }
        } catch (Exception $e) {
            error_log("Bookings table update failed: " . $e->getMessage());
        }
    }

    public function getMoviesForBooking() {
        try {
            $result = $this->db->query("SELECT id, title FROM movies WHERE is_showing = 1 ORDER BY title");
            
            if (!$result) {
                throw new Exception("Query failed: " . $this->db->error);
            }
            
            $movies = [];
            while ($row = $result->fetch_assoc()) {
                $movies[] = [
                    'id' => (int)$row['id'],
                    'title' => $row['title']
                ];
            }
            
            return ['success' => true, 'movies' => $movies];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error loading movies: ' . $e->getMessage()];
        }
    }

    public function getBookingStats() {
        try {
            $stats = [];

            // Total bookings
            $result = $this->db->query("SELECT COUNT(*) as total_bookings FROM bookings");
            $stats['total_bookings'] = $result ? $result->fetch_assoc()['total_bookings'] : 0;

            // Total tickets sold today
            $result = $this->db->query("SELECT SUM(number_of_tickets) as tickets_today FROM bookings WHERE DATE(created_at) = CURDATE()");
            $stats['tickets_today'] = $result ? ($result->fetch_assoc()['tickets_today'] ?? 0) : 0;

            // Total revenue
            $result = $this->db->query("SELECT SUM(total_amount) as total_revenue FROM bookings");
            $stats['total_revenue'] = $result ? ($result->fetch_assoc()['total_revenue'] ?? 0) : 0;

            // Movies currently showing
            $result = $this->db->query("SELECT COUNT(*) as movies_showing FROM movies WHERE is_showing = 1");
            $stats['movies_showing'] = $result ? $result->fetch_assoc()['movies_showing'] : 0;

            return ['success' => true, 'stats' => $stats];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error loading stats: ' . $e->getMessage()];
        }
    }

    private function getMovieIdByTitle($title) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM movies WHERE title = ? AND is_showing = 1");
            if (!$stmt) {
                return null;
            }
            
            $stmt->bind_param("s", $title);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                return $row['id'];
            }
            $stmt->close();
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }
}

// Handle API requests
header('Content-Type: application/json');

try {
    $bookingManager = new BookingManager();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_booking':
                $result = $bookingManager->createBooking(
                    $_POST['full_name'] ?? '',
                    $_POST['email'] ?? '',
                    $_POST['movie_title'] ?? '',
                    $_POST['booking_date'] ?? '',
                    $_POST['number_of_tickets'] ?? 0,
                    $_POST['showtime'] ?? null,
                    $_POST['movie_id'] ?? null,
                    $_POST['ticket_price'] ?? 500
                );
                echo json_encode($result);
                break;

            case 'get_movies':
                $result = $bookingManager->getMoviesForBooking();
                echo json_encode($result);
                break;

            case 'get_stats':
                $result = $bookingManager->getBookingStats();
                echo json_encode($result);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_stats') {
        $result = $bookingManager->getBookingStats();
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
