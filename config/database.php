<?php
// SSR Cinema - Enhanced Database Configuration and Setup with Initialization

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'ssr_cinema');

class Database {
    private static $connection = null;
    private static $pdo = null;

    public static function connect() {
        if (self::$connection === null) {
            try {
                // First connect without database to create it
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);

                if ($mysqli->connect_error) {
                    throw new Exception("Connection failed: " . $mysqli->connect_error);
                }

                // Create database if it doesn't exist
                $mysqli->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
                $mysqli->close();

                // Now connect to the specific database
                self::$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

                if (self::$connection->connect_error) {
                    throw new Exception("Database connection failed: " . self::$connection->connect_error);
                }

                // Set charset
                self::$connection->set_charset("utf8mb4");

                // Create PDO connection for modern operations
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

            } catch (Exception $e) {
                die("Database Error: " . $e->getMessage());
            }
        }

        return self::$connection;
    }

    public static function query($sql) {
        $conn = self::connect();
        return $conn->query($sql);
    }

    public static function prepare($sql) {
        $conn = self::connect();
        return $conn->prepare($sql);
    }

    public static function escape($string) {
        $conn = self::connect();
        return $conn->real_escape_string($string);
    }

    public static function insertId() {
        $conn = self::connect();
        return $conn->insert_id;
    }

    public static function affectedRows() {
        $conn = self::connect();
        return $conn->affected_rows;
    }

    public static function getConnection() {
        return self::$pdo;
    }
}

class CinemaSetup {
    private $conn;
    private $pdo;

    public function __construct($conn, $pdo = null) {
        $this->conn = $conn;
        $this->pdo = $pdo;
    }

    public function setup() {
        echo "<h2>🎬 Setting up SSR Cinema Database...</h2>";
        
        $this->cleanExistingTables();
        echo "<p>🧹 Cleaned existing tables</p>";
        
        $this->createUsersTable();
        echo "<p>✅ Users table created</p>";
        
        $this->createUserSessionsTable();
        echo "<p>✅ User sessions table created</p>";
        
        $this->createMoviesTable();
        echo "<p>✅ Movies table created</p>";
        
        $this->createBookingsTable();
        echo "<p>✅ Bookings table created</p>";
        
        $this->insertAdminUser();
        echo "<p>✅ Admin user created</p>";
        
        $this->ensureTestUser();
        echo "<p>✅ Test user created</p>";
        
        $this->insertSampleMovies();
        echo "<p>✅ Sample movies inserted</p>";
        
        echo "<h3>🎉 Setup Complete!</h3>";
    }

    public function setupSilent() {
        try {
            $this->cleanExistingTables();
            $this->createUsersTable();
            $this->createUserSessionsTable();
            $this->createMoviesTable();
            $this->createBookingsTable();
            $this->insertAdminUser();
            $this->ensureTestUser();
            $this->insertSampleMovies();
            return ['success' => true, 'message' => 'Database setup completed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Setup failed: ' . $e->getMessage()];
        }
    }

    private function cleanExistingTables() {
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->conn->query("DROP TABLE IF EXISTS bookings");
        $this->conn->query("DROP TABLE IF EXISTS movies");
        $this->conn->query("DROP TABLE IF EXISTS user_sessions");
        $this->conn->query("DROP TABLE IF EXISTS users");
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    private function createUsersTable() {
        $query = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->conn->query($query);
    }

    private function createUserSessionsTable() {
        $query = "CREATE TABLE user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $this->conn->query($query);
    }

    private function createMoviesTable() {
        $query = "CREATE TABLE movies (
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
        )";
        $this->conn->query($query);
    }

    private function createBookingsTable() {
        $query = "CREATE TABLE bookings (
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
        )";
        $this->conn->query($query);
    }

    private function insertAdminUser() {
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@ssrcinema.com';
        $name = 'Admin User';

        $stmt = $this->conn->prepare("INSERT IGNORE INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $name, $email, $username, $password);
        $stmt->execute();
    }

    private function ensureTestUser() {
        $testUsername = 'testuser';
        $testPassword = password_hash('test123', PASSWORD_DEFAULT);
        $testEmail = 'test@example.com';
        $testName = 'Test User';

        $stmt = $this->conn->prepare("INSERT IGNORE INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $testName, $testEmail, $testUsername, $testPassword);
        $stmt->execute();
    }

    private function insertSampleMovies() {
        // Clear existing movies first
        $this->conn->query("DELETE FROM movies");
        
        $movies = [
            [
                'title' => 'Avengers: Endgame',
                'description' => 'The epic conclusion to the Infinity Saga that became a defining moment in cinematic history. After the devastating events of Avengers: Infinity War, the universe is in ruins.',
                'genre' => 'Action, Adventure, Drama',
                'director' => 'Anthony Russo, Joe Russo',
                'cast' => 'Robert Downey Jr., Chris Evans, Mark Ruffalo, Chris Hemsworth, Scarlett Johansson',
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
                'description' => 'Peter Parker seeks help from Doctor Strange when his secret identity is revealed, but when a spell goes wrong, dangerous foes from other worlds start to appear.',
                'genre' => 'Action, Adventure, Sci-Fi',
                'director' => 'Jon Watts',
                'cast' => 'Tom Holland, Zendaya, Benedict Cumberbatch, Jacob Batalon, Marisa Tomei',
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
                'description' => 'Batman ventures into Gotham City\'s underworld when a sadistic killer leaves behind a trail of cryptic clues. As the evidence begins to lead closer to home.',
                'genre' => 'Action, Crime, Drama',
                'director' => 'Matt Reeves',
                'cast' => 'Robert Pattinson, Zoë Kravitz, Paul Dano, Jeffrey Wright, Colin Farrell',
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

        foreach ($movies as $movie) {
            $stmt = $this->conn->prepare("INSERT INTO movies (title, description, genre, director, cast, duration, rating, release_date, language, image_url, trailer_url, ticket_price, show_times, is_showing, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssississsdii", 
                $movie['title'], $movie['description'], $movie['genre'], $movie['director'], 
                $movie['cast'], $movie['duration'], $movie['rating'], $movie['release_date'], 
                $movie['language'], $movie['image_url'], $movie['trailer_url'], 
                $movie['ticket_price'], $movie['show_times'], $movie['is_showing'], $movie['is_featured']
            );
            $stmt->execute();
        }
    }

    public function getStats() {
        $stats = [];
        
        // Get user count
        $result = $this->conn->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $result->fetch_assoc()['count'];
        
        // Get movie count
        $result = $this->conn->query("SELECT COUNT(*) as count FROM movies WHERE is_showing = 1");
        $stats['showing_movies'] = $result->fetch_assoc()['count'];
        
        // Get total movies
        $result = $this->conn->query("SELECT COUNT(*) as count FROM movies");
        $stats['total_movies'] = $result->fetch_assoc()['count'];
        
        // Get booking count
        $result = $this->conn->query("SELECT COUNT(*) as count FROM bookings");
        $stats['total_bookings'] = $result->fetch_assoc()['count'];
        
        return $stats;
    }
}

// Simple function to get database connection
function getDB() {
    return Database::connect();
}

// Enhanced initialization interface when accessed directly
if (basename($_SERVER['PHP_SELF']) == 'database.php') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>SSR Cinema - Database Setup</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                background: #1a1a1a; 
                color: white; 
            }
            .container { 
                max-width: 800px; 
                margin: 0 auto; 
            }
            .success { 
                color: #28a745; 
                background: #d4edda; 
                padding: 10px; 
                border-radius: 5px; 
                margin: 10px 0; 
                color: #155724; 
            }
            .error { 
                color: #dc3545; 
                background: #f8d7da; 
                padding: 10px; 
                border-radius: 5px; 
                margin: 10px 0; 
                color: #721c24; 
            }
            .info { 
                color: #17a2b8; 
                background: #d1ecf1; 
                padding: 10px; 
                border-radius: 5px; 
                margin: 10px 0; 
                color: #0c5460; 
            }
            .step { 
                background: #2c2c2c; 
                padding: 15px; 
                margin: 10px 0; 
                border-radius: 5px; 
            }
            button, .btn { 
                background: #fa7e61; 
                color: white; 
                padding: 10px 20px; 
                border: none; 
                border-radius: 5px; 
                cursor: pointer; 
                margin: 5px; 
                text-decoration: none;
                display: inline-block;
            }
            button:hover, .btn:hover { 
                background: #e66a4d; 
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .stat-card {
                background: #2c2c2c;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                border-left: 4px solid #fa7e61;
            }
            .stat-number {
                font-size: 2em;
                font-weight: bold;
                color: #fa7e61;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎬 SSR Cinema Database Setup & Management</h1>
            
            <?php
            try {
                $database = new Database();
                $setup = new CinemaSetup($database->connect(), $database->getConnection());
                
                // Check if setup is requested
                if (isset($_GET['action']) && $_GET['action'] === 'setup') {
                    echo "<div class='step'>";
                    $setup->setup();
                    echo "</div>";
                } else {
                    // Show current status
                    echo "<div class='step'>";
                    echo "<h3>📊 Current Database Status</h3>";
                    
                    try {
                        $stats = $setup->getStats();
                        echo "<div class='stats-grid'>";
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-number'>{$stats['total_users']}</div>";
                        echo "<div>Total Users</div>";
                        echo "</div>";
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-number'>{$stats['showing_movies']}</div>";
                        echo "<div>Movies Showing</div>";
                        echo "</div>";
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-number'>{$stats['total_movies']}</div>";
                        echo "<div>Total Movies</div>";
                        echo "</div>";
                        echo "<div class='stat-card'>";
                        echo "<div class='stat-number'>{$stats['total_bookings']}</div>";
                        echo "<div>Total Bookings</div>";
                        echo "</div>";
                        echo "</div>";
                        
                        echo "<div class='info'>";
                        echo "<h4>✅ Database is operational</h4>";
                        echo "<p>All tables exist and contain data. You can reinitialize if needed.</p>";
                        echo "</div>";
                        
                    } catch (Exception $e) {
                        echo "<div class='error'>";
                        echo "<h4>⚠️ Database needs setup</h4>";
                        echo "<p>Some tables may be missing or corrupted: " . $e->getMessage() . "</p>";
                        echo "</div>";
                    }
                    echo "</div>";
                }
                
                echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>";
                echo "<h3>🎉 SSR Cinema Database Manager</h3>";
                echo "<p><strong>Admin Login:</strong> username: <code>admin</code> | password: <code>admin123</code></p>";
                echo "<p><strong>Test User:</strong> username: <code>testuser</code> | password: <code>test123</code></p>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<h3>❌ Setup Failed</h3>";
                echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
                echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
                echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
                echo "</div>";
            }
            ?>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="?action=setup" class="btn">🔄 Reinitialize Database</a>
                <a href="../index.html" class="btn">🏠 Go to Homepage</a>
                <a href="../php/movies.php?action=test" target="_blank" class="btn">🧪 Test Movies API</a>
            </div>
            
            <div class="step">
                <h3>📋 Available Actions</h3>
                <ul style="color: #ccc; line-height: 1.8;">
                    <li><strong>Reinitialize Database:</strong> Drops all tables and recreates them with fresh sample data</li>
                    <li><strong>Test Movies API:</strong> Verifies that the movies API is working correctly</li>
                    <li><strong>Go to Homepage:</strong> Navigate back to the main cinema website</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
