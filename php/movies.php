<?php
/**
 * Movies Management System
 * Handles movie data, creation, and display for the cinema
 */

// Include database connection with fallback
$db_path = __DIR__ . '/../config/database.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    require_once '../config/database.php';
}

class MovieManager {
    private $db;

    public function __construct() {
        $this->db = getDB();
        $this->setupMoviesTable();
    }

    private function setupMoviesTable() {
        try {
            // Create movies table
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
            
            if (!$this->db->query($sql)) {
                throw new Exception("Failed to create movies table: " . $this->db->error);
            }
            
            // Add sample movies if table is empty
            $this->addSampleMoviesIfEmpty();
            
        } catch (Exception $e) {
            error_log("Movies table setup failed: " . $e->getMessage());
        }
    }

    private function addSampleMoviesIfEmpty() {
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM movies");
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row['count'] == 0) {
                    $this->insertSampleMovies();
                }
            }
        } catch (Exception $e) {
            error_log("Sample movies check failed: " . $e->getMessage());
        }
    }

    private function insertSampleMovies() {
        $movies = [
            [
                'title' => 'Avengers: Endgame',
                'description' => 'The epic conclusion to the Infinity Saga. Heroes assemble for the final battle.',
                'genre' => 'Action, Adventure',
                'director' => 'Russo Brothers',
                'duration' => 181,
                'rating' => 'PG-13',
                'language' => 'English',
                'image_url' => 'image/poster1.jpg',
                'ticket_price' => 500.00,
                'show_times' => '["10:00 AM", "2:00 PM", "6:00 PM", "9:30 PM"]',
                'is_showing' => 1,
                'is_featured' => 1
            ],
            [
                'title' => 'Spider-Man: No Way Home',
                'description' => 'Peter Parker\'s identity is revealed. Multiverse chaos ensues.',
                'genre' => 'Action, Adventure',
                'director' => 'Jon Watts',
                'duration' => 148,
                'rating' => 'PG-13',
                'language' => 'English',
                'image_url' => 'image/poster2.jpg',
                'ticket_price' => 500.00,
                'show_times' => '["11:00 AM", "3:00 PM", "7:00 PM", "10:00 PM"]',
                'is_showing' => 1,
                'is_featured' => 1
            ],
            [
                'title' => 'The Batman',
                'description' => 'Batman investigates corruption in Gotham City.',
                'genre' => 'Action, Crime',
                'director' => 'Matt Reeves',
                'duration' => 176,
                'rating' => 'PG-13',
                'language' => 'English',
                'image_url' => 'image/poster3.jpg',
                'ticket_price' => 500.00,
                'show_times' => '["12:00 PM", "4:00 PM", "8:00 PM"]',
                'is_showing' => 1,
                'is_featured' => 0
            ]
        ];

        try {
            $stmt = $this->db->prepare("INSERT INTO movies (title, description, genre, director, duration, rating, language, image_url, ticket_price, show_times, is_showing, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt) {
                foreach ($movies as $movie) {
                    $stmt->bind_param("ssssississdii", 
                        $movie['title'], $movie['description'], $movie['genre'], $movie['director'], 
                        $movie['duration'], $movie['rating'], $movie['language'], $movie['image_url'], 
                        $movie['ticket_price'], $movie['show_times'], $movie['is_showing'], $movie['is_featured']
                    );
                    $stmt->execute();
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Sample movies insertion failed: " . $e->getMessage());
        }
    }

    public function getNowShowing() {
        try {
            $result = $this->db->query("SELECT * FROM movies WHERE is_showing = 1 ORDER BY is_featured DESC, id DESC");
            
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
                    'description' => $row['description'] ?? 'No description available',
                    'genre' => $row['genre'] ?? 'Unknown',
                    'director' => $row['director'] ?? 'Unknown',
                    'duration' => (int)($row['duration'] ?? 120),
                    'rating' => $row['rating'] ?? 'PG-13',
                    'language' => $row['language'] ?? 'English',
                    'image_url' => $row['image_url'] ?? 'image/placeholder.jpg',
                    'ticket_price' => number_format($row['ticket_price'] ?? 500, 2),
                    'show_times' => $showTimes,
                    'is_featured' => (bool)($row['is_featured'] ?? 0),
                    'formatted_duration' => $this->formatDuration($row['duration'] ?? 120)
                ];
            }
            
            return [
                'success' => true,
                'movies' => $movies,
                'count' => count($movies),
                'message' => 'Movies loaded successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error loading movies: ' . $e->getMessage()
            ];
        }
    }

    public function getMovieDetails($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM movies WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $movie = $result->fetch_assoc();
            $stmt->close();
            
            if ($movie) {
                $showTimes = [];
                if (!empty($movie['show_times'])) {
                    $decoded = json_decode($movie['show_times'], true);
                    $showTimes = is_array($decoded) ? $decoded : [];
                }
                
                $formattedMovie = [
                    'id' => (int)$movie['id'],
                    'title' => $movie['title'],
                    'description' => $movie['description'] ?? 'No description available',
                    'genre' => $movie['genre'] ?? 'Unknown',
                    'director' => $movie['director'] ?? 'Unknown',
                    'duration' => (int)($movie['duration'] ?? 120),
                    'rating' => $movie['rating'] ?? 'PG-13',
                    'language' => $movie['language'] ?? 'English',
                    'image_url' => $movie['image_url'] ?? 'image/placeholder.jpg',
                    'ticket_price' => number_format($movie['ticket_price'] ?? 500, 2),
                    'show_times' => $showTimes,
                    'is_featured' => (bool)($movie['is_featured'] ?? 0),
                    'formatted_duration' => $this->formatDuration($movie['duration'] ?? 120)
                ];
                
                return ['success' => true, 'movie' => $formattedMovie];
            } else {
                return ['success' => false, 'message' => 'Movie not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error loading movie: ' . $e->getMessage()];
        }
    }

    public function getAllMovies() {
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

    private function formatDuration($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours > 0 ? $hours . 'h ' . $mins . 'm' : $mins . 'm';
    }

    public function testDatabase() {
        try {
            $result = $this->db->query("SELECT COUNT(*) as total FROM movies");
            if (!$result) {
                throw new Exception("Test query failed: " . $this->db->error);
            }
            
            $totalMovies = $result->fetch_assoc()['total'];
            
            $result = $this->db->query("SELECT COUNT(*) as showing FROM movies WHERE is_showing = 1");
            $showingMovies = $result ? $result->fetch_assoc()['showing'] : 0;
            
            return [
                'success' => true,
                'message' => 'Database test completed successfully',
                'stats' => [
                    'total_movies' => $totalMovies,
                    'showing_movies' => $showingMovies
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ];
        }
    }
}

// Handle API requests
header('Content-Type: application/json');

try {
    $movieManager = new MovieManager();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'now_showing':
            $result = $movieManager->getNowShowing();
            echo json_encode($result);
            break;
            
        case 'movie_details':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid movie ID']);
            } else {
                $result = $movieManager->getMovieDetails($id);
                echo json_encode($result);
            }
            break;
            
        case 'get_movies':
            $result = $movieManager->getAllMovies();
            echo json_encode($result);
            break;
            
        case 'test':
            $result = $movieManager->testDatabase();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action. Available: now_showing, movie_details, get_movies, test'
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
