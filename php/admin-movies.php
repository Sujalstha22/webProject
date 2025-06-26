<?php
/**
 * Admin Movie Management System
 * Handles adding, editing, deleting, and managing movies
 */

session_start();

// Include database connection
$db_path = __DIR__ . '/../config/database.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    require_once '../config/database.php';
}

class AdminMovieManager {
    private $db;

    public function __construct() {
        $this->db = getDB();
        $this->setupMoviesTable();
    }

    private function setupMoviesTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS movies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(150) NOT NULL,
                description TEXT,
                genre VARCHAR(100),
                director VARCHAR(100) DEFAULT '',
                duration INT DEFAULT 120,
                rating VARCHAR(10) DEFAULT 'PG-13',
                language VARCHAR(50) DEFAULT 'English',
                image_url VARCHAR(255),
                ticket_price DECIMAL(10,2) DEFAULT 500.00,
                show_times JSON,
                is_showing BOOLEAN DEFAULT TRUE,
                is_featured BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $this->db->query($sql);
            
        } catch (Exception $e) {
            error_log("Movies table setup failed: " . $e->getMessage());
        }
    }

    /**
     * Add a new movie
     */
    public function addMovie($movieData) {
        // Validation
        if (empty($movieData['title'])) {
            return ['success' => false, 'message' => 'Movie title is required.'];
        }

        if ($movieData['duration'] < 60 || $movieData['duration'] > 300) {
            return ['success' => false, 'message' => 'Duration must be between 60 and 300 minutes.'];
        }

        if ($movieData['ticket_price'] < 100 || $movieData['ticket_price'] > 2000) {
            return ['success' => false, 'message' => 'Ticket price must be between Rs. 100 and Rs. 2000.'];
        }

        try {
            // Check if movie with same title already exists
            $stmt = $this->db->prepare("SELECT id FROM movies WHERE title = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("s", $movieData['title']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'A movie with this title already exists.'];
            }
            $stmt->close();

            // Insert new movie
            $stmt = $this->db->prepare("INSERT INTO movies (title, description, genre, director, duration, rating, language, image_url, ticket_price, show_times, is_showing, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("ssssississdii", 
                $movieData['title'],
                $movieData['description'],
                $movieData['genre'],
                $movieData['director'],
                $movieData['duration'],
                $movieData['rating'],
                $movieData['language'],
                $movieData['image_url'],
                $movieData['ticket_price'],
                $movieData['show_times'],
                $movieData['is_showing'],
                $movieData['is_featured']
            );

            if ($stmt->execute()) {
                $movie_id = $this->db->insert_id;
                $stmt->close();
                return [
                    'success' => true, 
                    'message' => 'Movie added successfully!',
                    'movie_id' => $movie_id
                ];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Failed to add movie. Please try again.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get all movies for admin management
     */
    public function getAllMovies() {
        try {
            $result = $this->db->query("SELECT * FROM movies ORDER BY created_at DESC");
            
            if (!$result) {
                throw new Exception("Query failed: " . $this->db->error);
            }
            
            $movies = [];
            while ($row = $result->fetch_assoc()) {
                $showTimes = [];
                if (!empty($row['show_times'])) {
                    $decoded = json_decode($row['show_times'], true);
                    $showTimes = is_array($decoded) ? $decoded : [];
                }
                
                $movies[] = [
                    'id' => (int)$row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'] ?? '',
                    'genre' => $row['genre'] ?? 'Unknown',
                    'director' => $row['director'] ?? 'Unknown',
                    'duration' => (int)($row['duration'] ?? 120),
                    'rating' => $row['rating'] ?? 'PG-13',
                    'language' => $row['language'] ?? 'English',
                    'image_url' => $row['image_url'] ?? 'image/placeholder.jpg',
                    'ticket_price' => number_format($row['ticket_price'] ?? 500, 2),
                    'show_times' => $showTimes,
                    'is_showing' => (bool)($row['is_showing'] ?? 0),
                    'is_featured' => (bool)($row['is_featured'] ?? 0),
                    'created_at' => $row['created_at']
                ];
            }
            
            return ['success' => true, 'movies' => $movies];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error loading movies: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle movie showing status
     */
    public function toggleShowing($movie_id, $is_showing) {
        try {
            $stmt = $this->db->prepare("UPDATE movies SET is_showing = ? WHERE id = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("ii", $is_showing, $movie_id);
            
            if ($stmt->execute() && $this->db->affected_rows > 0) {
                $stmt->close();
                $status = $is_showing ? 'now showing' : 'hidden';
                return ['success' => true, 'message' => "Movie is $status."];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Movie not found or no changes made.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured($movie_id, $is_featured) {
        try {
            $stmt = $this->db->prepare("UPDATE movies SET is_featured = ? WHERE id = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("ii", $is_featured, $movie_id);
            
            if ($stmt->execute() && $this->db->affected_rows > 0) {
                $stmt->close();
                $status = $is_featured ? 'featured' : 'unfeatured';
                return ['success' => true, 'message' => "Movie is now $status."];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Movie not found or no changes made.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a movie
     */
    public function deleteMovie($movie_id) {
        try {
            // First check if movie exists
            $stmt = $this->db->prepare("SELECT title FROM movies WHERE id = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("i", $movie_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $stmt->close();
                return ['success' => false, 'message' => 'Movie not found.'];
            }
            
            $movie = $result->fetch_assoc();
            $stmt->close();
            
            // Delete the movie
            $stmt = $this->db->prepare("DELETE FROM movies WHERE id = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'Database error occurred.'];
            }
            
            $stmt->bind_param("i", $movie_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => "Movie '{$movie['title']}' deleted successfully."];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Failed to delete movie.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if user is admin
     */
    private function isAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['is_admin'];
    }
}

// Handle API requests
header('Content-Type: application/json');

try {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user']) || !$_SESSION['user']['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Admin access required.']);
        exit;
    }

    $adminMovieManager = new AdminMovieManager();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add_movie':
                // Prepare movie data
                $movieData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'genre' => trim($_POST['genre'] ?? ''),
                    'director' => trim($_POST['director'] ?? ''),
                    'duration' => (int)($_POST['duration'] ?? 120),
                    'rating' => $_POST['rating'] ?? 'PG-13',
                    'language' => $_POST['language'] ?? 'English',
                    'image_url' => trim($_POST['image_url'] ?? ''),
                    'ticket_price' => (float)($_POST['ticket_price'] ?? 500),
                    'show_times' => $_POST['show_times'] ?? '[]',
                    'is_showing' => (int)($_POST['is_showing'] ?? 1),
                    'is_featured' => (int)($_POST['is_featured'] ?? 0)
                ];
                
                $result = $adminMovieManager->addMovie($movieData);
                echo json_encode($result);
                break;

            case 'toggle_showing':
                $movie_id = (int)($_POST['movie_id'] ?? 0);
                $is_showing = (int)($_POST['is_showing'] ?? 0);
                
                if ($movie_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid movie ID.']);
                } else {
                    $result = $adminMovieManager->toggleShowing($movie_id, $is_showing);
                    echo json_encode($result);
                }
                break;

            case 'toggle_featured':
                $movie_id = (int)($_POST['movie_id'] ?? 0);
                $is_featured = (int)($_POST['is_featured'] ?? 0);
                
                if ($movie_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid movie ID.']);
                } else {
                    $result = $adminMovieManager->toggleFeatured($movie_id, $is_featured);
                    echo json_encode($result);
                }
                break;

            case 'delete_movie':
                $movie_id = (int)($_POST['movie_id'] ?? 0);
                
                if ($movie_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid movie ID.']);
                } else {
                    $result = $adminMovieManager->deleteMovie($movie_id);
                    echo json_encode($result);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'get_all_movies':
                $result = $adminMovieManager->getAllMovies();
                echo json_encode($result);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
