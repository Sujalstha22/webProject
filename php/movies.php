<?php
/**
 * Movies API - Enhanced with Database and Table Creation
 */
header('Content-Type: application/json');

// Capture any output that might interfere with JSON
ob_start();

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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Silently ensure tables exist
    ensureTablesExist($pdo);
    
    $action = $_GET['action'] ?? '';
    
    // Clear any output buffer before sending JSON
    ob_clean();
    
    switch ($action) {
        case 'now_showing':
            // Get movies
            $stmt = $pdo->query("SELECT * FROM movies WHERE is_showing = 1 ORDER BY is_featured DESC, id DESC");
            $movies = $stmt->fetchAll();
            
            $formattedMovies = [];
            foreach ($movies as $movie) {
                $showTimes = [];
                if (!empty($movie['show_times'])) {
                    $decoded = json_decode($movie['show_times'], true);
                    $showTimes = is_array($decoded) ? $decoded : [];
                }
                
                $formattedMovies[] = [
                    'id' => (int)$movie['id'],
                    'title' => $movie['title'],
                    'description' => $movie['description'] ?? 'No description available',
                    'genre' => $movie['genre'] ?? 'Unknown',
                    'director' => $movie['director'] ?? 'Unknown',
                    'cast' => $movie['cast'] ?? 'Unknown',
                    'duration' => (int)($movie['duration'] ?? 120),
                    'rating' => $movie['rating'] ?? 'PG-13',
                    'release_date' => $movie['release_date'] ?? '',
                    'language' => $movie['language'] ?? 'English',
                    'image_url' => $movie['image_url'] ?? 'image/placeholder.jpg',
                    'trailer_url' => $movie['trailer_url'] ?? '',
                    'ticket_price' => number_format($movie['ticket_price'] ?? 500, 2),
                    'show_times' => $showTimes,
                    'is_featured' => (bool)($movie['is_featured'] ?? 0),
                    'formatted_duration' => formatDuration($movie['duration'] ?? 120)
                ];
            }
            
            echo json_encode([
                'success' => true,
                'movies' => $formattedMovies,
                'count' => count($formattedMovies),
                'message' => 'Movies loaded successfully'
            ]);
            break;
            
        case 'movie_details':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid movie ID']);
                break;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
            $stmt->execute([$id]);
            $movie = $stmt->fetch();
            
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
                    'cast' => $movie['cast'] ?? 'Unknown',
                    'duration' => (int)($movie['duration'] ?? 120),
                    'rating' => $movie['rating'] ?? 'PG-13',
                    'release_date' => $movie['release_date'] ?? '',
                    'language' => $movie['language'] ?? 'English',
                    'image_url' => $movie['image_url'] ?? 'image/placeholder.jpg',
                    'trailer_url' => $movie['trailer_url'] ?? '',
                    'ticket_price' => number_format($movie['ticket_price'] ?? 500, 2),
                    'show_times' => $showTimes,
                    'is_featured' => (bool)($movie['is_featured'] ?? 0),
                    'formatted_duration' => formatDuration($movie['duration'] ?? 120)
                ];
                
                echo json_encode(['success' => true, 'movie' => $formattedMovie]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Movie not found']);
            }
            break;
            
        case 'test':
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM movies");
                $totalMovies = $stmt->fetch()['total'];
                
                $stmt = $pdo->query("SELECT COUNT(*) as showing FROM movies WHERE is_showing = 1");
                $showingMovies = $stmt->fetch()['showing'];
                
                $stmt = $pdo->query("SELECT COUNT(*) as users FROM users");
                $totalUsers = $stmt->fetch()['users'];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Database test completed successfully',
                    'stats' => [
                        'total_movies' => $totalMovies,
                        'showing_movies' => $showingMovies,
                        'total_users' => $totalUsers
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Test failed: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'setup':
            $setupResult = setupDatabase($pdo);
            echo json_encode($setupResult);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action. Use: now_showing, movie_details, test, or setup']);
    }
    
} catch (PDOException $e) {
    ob_clean(); // Clear any output
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    ob_clean(); // Clear any output
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// Helper function to ensure tables exist silently
function ensureTablesExist($pdo) {
    try {
        // Check if movies table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'movies'");
        if ($stmt->rowCount() == 0) {
            // Tables don't exist, create them
            setupDatabase($pdo);
        } else {
            // Check if movies table has data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM movies");
            if ($stmt->fetch()['count'] == 0) {
                // Table exists but no data, add sample movies
                addSampleMovies($pdo);
            }
        }
    } catch (Exception $e) {
        // If there's any error, try to setup database
        setupDatabase($pdo);
    }
}

// Helper function to setup database silently
function setupDatabase($pdo) {
    try {
        // Create users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create movies table
        $pdo->exec("CREATE TABLE IF NOT EXISTS movies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            description TEXT,
            genre VARCHAR(100),
            director VARCHAR(100) DEFAULT '',
            cast TEXT DEFAULT '',
            duration INT DEFAULT 120,
            rating VARCHAR(10) DEFAULT 'PG-13',
            release_date DATE,
            language VARCHAR(50) DEFAULT 'English',
            image_url VARCHAR(255),
            trailer_url VARCHAR(255) DEFAULT '',
            ticket_price DECIMAL(10,2) DEFAULT 500.00,
            show_times JSON,
            is_showing BOOLEAN DEFAULT TRUE,
            is_featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create bookings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            movie_id INT,
            movie_title VARCHAR(150),
            number_of_tickets INT NOT NULL,
            booking_date DATE NOT NULL,
            total_amount DECIMAL(10,2) DEFAULT 0.00,
            booking_status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE SET NULL
        )");
        
        // Create user sessions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Add sample data
        addSampleUsers($pdo);
        addSampleMovies($pdo);
        
        return ['success' => true, 'message' => 'Database setup completed'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Setup failed: ' . $e->getMessage()];
    }
}

// Helper function to add sample users
function addSampleUsers($pdo) {
    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    if ($stmt->fetch()['count'] == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute(['Admin User', 'admin@ssrcinema.com', 'admin', $adminPassword]);
    }
    
    // Check if test user exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'testuser'");
    if ($stmt->fetch()['count'] == 0) {
        $testPassword = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute(['Test User', 'test@example.com', 'testuser', $testPassword]);
    }
}

// Helper function to add sample movies
function addSampleMovies($pdo) {
    // Clear existing movies
    $pdo->exec("DELETE FROM movies");
    
    $movies = [
        [
            'title' => 'Avengers: Endgame',
            'description' => 'The epic conclusion to the Infinity Saga that became a defining moment in cinematic history.',
            'genre' => 'Action, Adventure, Drama',
            'director' => 'Anthony Russo, Joe Russo',
            'cast' => 'Robert Downey Jr., Chris Evans, Mark Ruffalo, Chris Hemsworth',
            'duration' => 181,
            'rating' => 'PG-13',
            'release_date' => '2019-04-26',
            'language' => 'English',
            'image_url' => 'image/poster1.jpg',
            'trailer_url' => 'https://www.youtube.com/watch?v=TcMBFSGVi1c',
            'ticket_price' => 500.00,
            'show_times' => '["10:00 AM", "2:00 PM", "6:00 PM", "9:30 PM"]',
            'is_showing' => 1,
            'is_featured' => 1
        ],
        [
            'title' => 'Spider-Man: No Way Home',
            'description' => 'Peter Parker seeks help from Doctor Strange when his secret identity is revealed.',
            'genre' => 'Action, Adventure, Sci-Fi',
            'director' => 'Jon Watts',
            'cast' => 'Tom Holland, Zendaya, Benedict Cumberbatch',
            'duration' => 148,
            'rating' => 'PG-13',
            'release_date' => '2021-12-17',
            'language' => 'English',
            'image_url' => 'image/poster2.jpg',
            'trailer_url' => 'https://www.youtube.com/watch?v=JfVOs4VSpmA',
            'ticket_price' => 500.00,
            'show_times' => '["11:00 AM", "3:00 PM", "7:00 PM", "10:00 PM"]',
            'is_showing' => 1,
            'is_featured' => 1
        ],
        [
            'title' => 'The Batman',
            'description' => 'Batman ventures into Gotham City\'s underworld when a sadistic killer leaves behind a trail of cryptic clues.',
            'genre' => 'Action, Crime, Drama',
            'director' => 'Matt Reeves',
            'cast' => 'Robert Pattinson, Zoë Kravitz, Paul Dano',
            'duration' => 176,
            'rating' => 'PG-13',
            'release_date' => '2022-03-04',
            'language' => 'English',
            'image_url' => 'image/poster3.jpg',
            'trailer_url' => 'https://www.youtube.com/watch?v=mqqft2x_Aa4',
            'ticket_price' => 500.00,
            'show_times' => '["12:00 PM", "4:00 PM", "8:00 PM"]',
            'is_showing' => 1,
            'is_featured' => 0
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO movies (title, description, genre, director, cast, duration, rating, release_date, language, image_url, trailer_url, ticket_price, show_times, is_showing, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($movies as $movie) {
        $stmt->execute([
            $movie['title'], $movie['description'], $movie['genre'], $movie['director'], 
            $movie['cast'], $movie['duration'], $movie['rating'], $movie['release_date'], 
            $movie['language'], $movie['image_url'], $movie['trailer_url'], 
            $movie['ticket_price'], $movie['show_times'], $movie['is_showing'], $movie['is_featured']
        ]);
    }
}

function formatDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours > 0 ? $hours . 'h ' . $mins . 'm' : $mins . 'm';
}
?>
