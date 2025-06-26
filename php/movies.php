<?php
/**
 * Movies Handler
 * SSR Cinema Movie Management System
 */

require_once '../config/database.php';

class MovieManager {
    private $conn;
    private $table_name = "movies";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Get all movies currently showing
     */
    public function getNowShowingMovies() {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE is_showing = 1 
                 ORDER BY is_featured DESC, created_at DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            error_log("Error fetching now showing movies: " . $exception->getMessage());
            return [];
        }
    }

    /**
     * Get featured movies
     */
    public function getFeaturedMovies() {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE is_featured = 1 AND is_showing = 1 
                 ORDER BY created_at DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            error_log("Error fetching featured movies: " . $exception->getMessage());
            return [];
        }
    }

    /**
     * Get all movies (for admin)
     */
    public function getAllMovies() {
        $query = "SELECT * FROM " . $this->table_name . " 
                 ORDER BY created_at DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            error_log("Error fetching all movies: " . $exception->getMessage());
            return [];
        }
    }

    /**
     * Get movie by ID
     */
    public function getMovieById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $exception) {
            error_log("Error fetching movie by ID: " . $exception->getMessage());
            return false;
        }
    }

    /**
     * Search movies by title or genre
     */
    public function searchMovies($searchTerm) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE (title LIKE :search OR genre LIKE :search OR description LIKE :search) 
                 AND is_showing = 1 
                 ORDER BY title";

        try {
            $stmt = $this->conn->prepare($query);
            $searchParam = '%' . $searchTerm . '%';
            $stmt->bindParam(':search', $searchParam);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            error_log("Error searching movies: " . $exception->getMessage());
            return [];
        }
    }

    /**
     * Get movies by genre
     */
    public function getMoviesByGenre($genre) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE genre LIKE :genre AND is_showing = 1 
                 ORDER BY title";

        try {
            $stmt = $this->conn->prepare($query);
            $genreParam = '%' . $genre . '%';
            $stmt->bindParam(':genre', $genreParam);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $exception) {
            error_log("Error fetching movies by genre: " . $exception->getMessage());
            return [];
        }
    }

    /**
     * Format movie data for display
     */
    public function formatMovieForDisplay($movie) {
        if (!$movie) return null;

        // Parse show times from JSON
        $showTimes = json_decode($movie['show_times'], true) ?? [];
        
        return [
            'id' => $movie['id'],
            'title' => htmlspecialchars($movie['title']),
            'description' => htmlspecialchars($movie['description']),
            'genre' => htmlspecialchars($movie['genre']),
            'director' => htmlspecialchars($movie['director'] ?? ''),
            'cast' => htmlspecialchars($movie['cast'] ?? ''),
            'duration' => $movie['duration'],
            'rating' => $movie['rating'],
            'release_date' => $movie['release_date'],
            'language' => htmlspecialchars($movie['language']),
            'image_url' => htmlspecialchars($movie['image_url']),
            'trailer_url' => htmlspecialchars($movie['trailer_url'] ?? ''),
            'ticket_price' => number_format($movie['ticket_price'], 2),
            'show_times' => $showTimes,
            'is_featured' => $movie['is_featured'],
            'formatted_duration' => $this->formatDuration($movie['duration'])
        ];
    }

    /**
     * Format duration from minutes to hours and minutes
     */
    private function formatDuration($minutes) {
        if ($minutes <= 0) return 'N/A';
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $mins . 'm';
        } else {
            return $mins . 'm';
        }
    }

    /**
     * Get movie statistics
     */
    public function getMovieStats() {
        $stats = [];

        // Total movies
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_movies'] = $stmt->fetch()['total'];

        // Currently showing
        $query = "SELECT COUNT(*) as showing FROM " . $this->table_name . " WHERE is_showing = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['movies_showing'] = $stmt->fetch()['showing'];

        // Featured movies
        $query = "SELECT COUNT(*) as featured FROM " . $this->table_name . " WHERE is_featured = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['featured_movies'] = $stmt->fetch()['featured'];

        return $stats;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $movieManager = new MovieManager();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'now_showing':
            $movies = $movieManager->getNowShowingMovies();
            $formattedMovies = [];
            foreach ($movies as $movie) {
                $formattedMovies[] = $movieManager->formatMovieForDisplay($movie);
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'movies' => $formattedMovies]);
            break;

        case 'featured':
            $movies = $movieManager->getFeaturedMovies();
            $formattedMovies = [];
            foreach ($movies as $movie) {
                $formattedMovies[] = $movieManager->formatMovieForDisplay($movie);
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'movies' => $formattedMovies]);
            break;

        case 'search':
            $searchTerm = $_GET['q'] ?? '';
            if (!empty($searchTerm)) {
                $movies = $movieManager->searchMovies($searchTerm);
                $formattedMovies = [];
                foreach ($movies as $movie) {
                    $formattedMovies[] = $movieManager->formatMovieForDisplay($movie);
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'movies' => $formattedMovies]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Search term required']);
            }
            break;

        case 'movie_details':
            $movieId = $_GET['id'] ?? 0;
            $movie = $movieManager->getMovieById($movieId);
            if ($movie) {
                $formattedMovie = $movieManager->formatMovieForDisplay($movie);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'movie' => $formattedMovie]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Movie not found']);
            }
            break;

        case 'stats':
            $stats = $movieManager->getMovieStats();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
?>
