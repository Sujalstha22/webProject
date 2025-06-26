<?php
/**
 * Movie Booking Handler
 * SSR Cinema Ticket Booking System
 */

session_start();
require_once __DIR__ . '/../config/database.php';

class MovieBooking {
    private $conn;
    private $bookings_table = "bookings";
    private $movies_table = "movies";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Create a new booking
     */
    public function createBooking($full_name, $email, $movie_title, $booking_date, $number_of_tickets) {
        // Validate input
        if (empty($full_name) || empty($email) || empty($movie_title) || empty($booking_date) || empty($number_of_tickets)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        if ($number_of_tickets <= 0 || $number_of_tickets > 10) {
            return ['success' => false, 'message' => 'Number of tickets must be between 1 and 10.'];
        }

        // Validate booking date (must be today or future)
        $today = date('Y-m-d');
        if ($booking_date < $today) {
            return ['success' => false, 'message' => 'Booking date cannot be in the past.'];
        }

        // Get movie ID and validate movie exists
        $movie_id = $this->getMovieIdByTitle($movie_title);
        if (!$movie_id) {
            return ['success' => false, 'message' => 'Selected movie not found.'];
        }

        // Calculate total amount (assuming ticket price of Rs. 500 each)
        $ticket_price = 500;
        $total_amount = $number_of_tickets * $ticket_price;

        // Insert booking
        $query = "INSERT INTO " . $this->bookings_table . " 
                 (full_name, email, movie_id, movie_title, booking_date, number_of_tickets, total_amount) 
                 VALUES (:full_name, :email, :movie_id, :movie_title, :booking_date, :number_of_tickets, :total_amount)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':movie_id', $movie_id);
            $stmt->bindParam(':movie_title', $movie_title);
            $stmt->bindParam(':booking_date', $booking_date);
            $stmt->bindParam(':number_of_tickets', $number_of_tickets);
            $stmt->bindParam(':total_amount', $total_amount);

            if ($stmt->execute()) {
                $booking_id = $this->conn->lastInsertId();
                return [
                    'success' => true, 
                    'message' => 'Booking confirmed successfully!',
                    'booking_id' => $booking_id,
                    'total_amount' => $total_amount
                ];
            } else {
                return ['success' => false, 'message' => 'Booking failed. Please try again.'];
            }
        } catch (PDOException $exception) {
            return ['success' => false, 'message' => 'Database error: ' . $exception->getMessage()];
        }
    }

    /**
     * Get all bookings (for admin)
     */
    public function getAllBookings() {
        $query = "SELECT b.*, m.title as movie_title_full, m.genre 
                 FROM " . $this->bookings_table . " b 
                 LEFT JOIN " . $this->movies_table . " m ON b.movie_id = m.id 
                 ORDER BY b.created_at DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            return [];
        }
    }

    /**
     * Get bookings by email
     */
    public function getBookingsByEmail($email) {
        $query = "SELECT b.*, m.title as movie_title_full, m.genre 
                 FROM " . $this->bookings_table . " b 
                 LEFT JOIN " . $this->movies_table . " m ON b.movie_id = m.id 
                 WHERE b.email = :email 
                 ORDER BY b.created_at DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            return [];
        }
    }

    /**
     * Get booking statistics
     */
    public function getBookingStats() {
        $stats = [];

        // Total bookings
        $query = "SELECT COUNT(*) as total_bookings FROM " . $this->bookings_table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_bookings'] = $stmt->fetch()['total_bookings'];

        // Total tickets sold today
        $query = "SELECT SUM(number_of_tickets) as tickets_today 
                 FROM " . $this->bookings_table . " 
                 WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['tickets_today'] = $stmt->fetch()['tickets_today'] ?? 0;

        // Total revenue
        $query = "SELECT SUM(total_amount) as total_revenue FROM " . $this->bookings_table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_revenue'] = $stmt->fetch()['total_revenue'] ?? 0;

        // Movies currently showing
        $query = "SELECT COUNT(*) as movies_showing FROM " . $this->movies_table . " WHERE is_showing = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['movies_showing'] = $stmt->fetch()['movies_showing'];

        return $stats;
    }

    /**
     * Get all movies
     */
    public function getAllMovies() {
        $query = "SELECT * FROM " . $this->movies_table . " WHERE is_showing = 1 ORDER BY title";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            return [];
        }
    }

    /**
     * Get movie ID by title
     */
    private function getMovieIdByTitle($title) {
        $query = "SELECT id FROM " . $this->movies_table . " WHERE title = :title AND is_showing = 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch()['id'];
            }
            return false;
        } catch (PDOException $exception) {
            return false;
        }
    }

    /**
     * Cancel booking
     */
    public function cancelBooking($booking_id, $email) {
        $query = "UPDATE " . $this->bookings_table . " 
                 SET booking_status = 'cancelled' 
                 WHERE id = :booking_id AND email = :email";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->bindParam(':email', $email);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Booking cancelled successfully.'];
            } else {
                return ['success' => false, 'message' => 'Booking not found or already cancelled.'];
            }
        } catch (PDOException $exception) {
            return ['success' => false, 'message' => 'Database error: ' . $exception->getMessage()];
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = new MovieBooking();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_booking':
            $result = $booking->createBooking(
                $_POST['full_name'] ?? '',
                $_POST['email'] ?? '',
                $_POST['movie_title'] ?? '',
                $_POST['booking_date'] ?? '',
                $_POST['number_of_tickets'] ?? 0
            );
            break;

        case 'get_bookings':
            $email = $_POST['email'] ?? '';
            $bookings = $booking->getBookingsByEmail($email);
            $result = ['success' => true, 'bookings' => $bookings];
            break;

        case 'cancel_booking':
            $result = $booking->cancelBooking(
                $_POST['booking_id'] ?? 0,
                $_POST['email'] ?? ''
            );
            break;

        case 'get_stats':
            $stats = $booking->getBookingStats();
            $result = ['success' => true, 'stats' => $stats];
            break;

        case 'get_movies':
            $movies = $booking->getAllMovies();
            $result = ['success' => true, 'movies' => $movies];
            break;

        default:
            $result = ['success' => false, 'message' => 'Invalid action.'];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Handle GET requests for admin stats
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_stats') {
    $booking = new MovieBooking();
    $stats = $booking->getBookingStats();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}
?>
