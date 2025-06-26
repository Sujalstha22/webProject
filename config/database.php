<?php
/**
 * Database Configuration File
 * SSR Cinema Database Connection
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'ssr_cinema';
    private $username = 'root';
    private $password = '';
    private $conn;
    private $charset = 'utf8mb4';

    /**
     * Get database connection
     */
    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }

    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return ['success' => true, 'message' => 'Database connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get database configuration
     */
    public function getConfig() {
        return [
            'host' => $this->host,
            'database' => $this->db_name,
            'username' => $this->username,
            'charset' => $this->charset
        ];
    }
}

/**
 * Database Setup and Table Creation
 */
class DatabaseSetup {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Create all necessary tables
     */
    public function createTables() {
        $this->createUsersTable();
        $this->createUserSessionsTable();
        $this->createMoviesTable();
        $this->createBookingsTable();
        $this->createUserActivityTable();
        $this->insertSampleMovies();
    }

    /**
     * Create users table
     */
    private function createUsersTable() {
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NULL,
            date_of_birth DATE NULL,
            gender ENUM('male', 'female', 'other') NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            email_verified BOOLEAN DEFAULT FALSE,
            last_login TIMESTAMP NULL,
            failed_login_attempts INT DEFAULT 0,
            account_locked_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_username (username),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->conn->exec($query);
            echo "<p class='success'>✅ Users table created successfully.</p>";
        } catch(PDOException $exception) {
            echo "<p class='error'>❌ Error creating users table: " . $exception->getMessage() . "</p>";
        }
    }

    /**
     * Create user sessions table for better session management
     */
    private function createUserSessionsTable() {
        $query = "CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_session_id (session_id),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->conn->exec($query);
            echo "<p class='success'>✅ User sessions table created successfully.</p>";
        } catch(PDOException $exception) {
            echo "<p class='error'>❌ Error creating user sessions table: " . $exception->getMessage() . "</p>";
        }
    }

    /**
     * Create user activity table for tracking user actions
     */
    private function createUserActivityTable() {
        $query = "CREATE TABLE IF NOT EXISTS user_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            activity_type ENUM('login', 'logout', 'register', 'booking', 'profile_update') NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_activity_type (activity_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->conn->exec($query);
            echo "<p class='success'>✅ User activity table created successfully.</p>";
        } catch(PDOException $exception) {
            echo "<p class='error'>❌ Error creating user activity table: " . $exception->getMessage() . "</p>";
        }
    }

    /**
     * Create movies table
     */
    private function createMoviesTable() {
        $query = "CREATE TABLE IF NOT EXISTS movies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            description TEXT,
            genre VARCHAR(100),
            director VARCHAR(100),
            cast TEXT,
            duration INT DEFAULT 0 COMMENT 'Duration in minutes',
            rating ENUM('G', 'PG', 'PG-13', 'R', 'NC-17') DEFAULT 'PG-13',
            release_date DATE,
            language VARCHAR(50) DEFAULT 'English',
            image_url VARCHAR(255),
            trailer_url VARCHAR(255),
            ticket_price DECIMAL(8,2) DEFAULT 500.00,
            is_showing BOOLEAN DEFAULT TRUE,
            is_featured BOOLEAN DEFAULT FALSE,
            show_times JSON COMMENT 'Array of show times',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_title (title),
            INDEX idx_is_showing (is_showing),
            INDEX idx_is_featured (is_featured),
            INDEX idx_release_date (release_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->conn->exec($query);
            echo "<p class='success'>✅ Movies table created successfully.</p>";
        } catch(PDOException $exception) {
            echo "<p class='error'>❌ Error creating movies table: " . $exception->getMessage() . "</p>";
        }
    }

    /**
     * Create bookings table
     */
    private function createBookingsTable() {
        $query = "CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            movie_id INT,
            movie_title VARCHAR(100) NOT NULL,
            booking_date DATE NOT NULL,
            number_of_tickets INT NOT NULL,
            total_amount DECIMAL(10,2) DEFAULT 0.00,
            booking_status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE SET NULL
        )";

        try {
            $this->conn->exec($query);
            echo "Bookings table created successfully.\n";
        } catch(PDOException $exception) {
            echo "Error creating bookings table: " . $exception->getMessage() . "\n";
        }
    }

    /**
     * Insert sample movies
     */
    private function insertSampleMovies() {
        $movies = [
            [
                'title' => 'Thunderbolts',
                'description' => 'A Marvel anti-hero team is formed by the government to take on covert missions.',
                'genre' => 'Action, Adventure',
                'director' => 'Jake Schreier',
                'cast' => 'Florence Pugh, Sebastian Stan, David Harbour, Wyatt Russell',
                'duration' => 135,
                'rating' => 'PG-13',
                'release_date' => '2025-05-02',
                'language' => 'English',
                'image_url' => 'image/nowshowing1.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=example1',
                'ticket_price' => 500.00,
                'is_showing' => 1,
                'is_featured' => 1,
                'show_times' => '["10:00", "13:30", "17:00", "20:30"]'
            ],
            [
                'title' => 'Kubera',
                'description' => 'A mysterious character rises from the underground, drawing attention from law and order.',
                'genre' => 'Action, Thriller',
                'director' => 'Sekhar Kammula',
                'cast' => 'Dhanush, Nagarjuna, Rashmika Mandanna',
                'duration' => 150,
                'rating' => 'PG-13',
                'release_date' => '2025-03-15',
                'language' => 'Telugu',
                'image_url' => 'image/nowshowing2.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=example2',
                'ticket_price' => 450.00,
                'is_showing' => 1,
                'is_featured' => 1,
                'show_times' => '["11:00", "14:30", "18:00", "21:30"]'
            ],
            [
                'title' => 'Ballerina',
                'description' => 'A young assassin trained under the High Table seeks revenge, from the John Wick universe.',
                'genre' => 'Action, Thriller',
                'director' => 'Len Wiseman',
                'cast' => 'Ana de Armas, Keanu Reeves, Ian McShane',
                'duration' => 110,
                'rating' => 'R',
                'release_date' => '2025-06-06',
                'language' => 'English',
                'image_url' => 'image/nowshowing3.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=example3',
                'ticket_price' => 550.00,
                'is_showing' => 1,
                'is_featured' => 1,
                'show_times' => '["12:00", "15:30", "19:00", "22:00"]'
            ],
            [
                'title' => 'Avengers: Endgame',
                'description' => 'The epic conclusion to the Infinity Saga that brings together all Marvel heroes.',
                'genre' => 'Action, Adventure, Sci-Fi',
                'director' => 'Anthony Russo, Joe Russo',
                'cast' => 'Robert Downey Jr., Chris Evans, Mark Ruffalo, Chris Hemsworth',
                'duration' => 181,
                'rating' => 'PG-13',
                'release_date' => '2019-04-26',
                'language' => 'English',
                'image_url' => 'image/poster1.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=TcMBFSGVi1c',
                'ticket_price' => 500.00,
                'is_showing' => 0,
                'is_featured' => 0,
                'show_times' => '[]'
            ],
            [
                'title' => 'Spider-Man: No Way Home',
                'description' => 'Spider-Man faces villains from across the multiverse in this epic adventure.',
                'genre' => 'Action, Adventure, Sci-Fi',
                'director' => 'Jon Watts',
                'cast' => 'Tom Holland, Zendaya, Benedict Cumberbatch, Willem Dafoe',
                'duration' => 148,
                'rating' => 'PG-13',
                'release_date' => '2021-12-17',
                'language' => 'English',
                'image_url' => 'image/poster2.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=JfVOs4VSpmA',
                'ticket_price' => 500.00,
                'is_showing' => 0,
                'is_featured' => 0,
                'show_times' => '[]'
            ],
            [
                'title' => 'The Batman',
                'description' => 'A dark and gritty take on the Dark Knight as he investigates corruption in Gotham.',
                'genre' => 'Action, Crime, Drama',
                'director' => 'Matt Reeves',
                'cast' => 'Robert Pattinson, Zoë Kravitz, Paul Dano, Jeffrey Wright',
                'duration' => 176,
                'rating' => 'PG-13',
                'release_date' => '2022-03-04',
                'language' => 'English',
                'image_url' => 'image/poster3.jpg',
                'trailer_url' => 'https://www.youtube.com/watch?v=mqqft2x_Aa4',
                'ticket_price' => 500.00,
                'is_showing' => 0,
                'is_featured' => 0,
                'show_times' => '[]'
            ]
        ];

        foreach ($movies as $movie) {
            $query = "INSERT IGNORE INTO movies (title, description, genre, director, cast, duration, rating, release_date, language, image_url, trailer_url, ticket_price, is_showing, is_featured, show_times)
                     VALUES (:title, :description, :genre, :director, :cast, :duration, :rating, :release_date, :language, :image_url, :trailer_url, :ticket_price, :is_showing, :is_featured, :show_times)";

            try {
                $stmt = $this->conn->prepare($query);
                $stmt->execute($movie);
            } catch(PDOException $exception) {
                echo "<p class='error'>❌ Error inserting movie: " . $exception->getMessage() . "</p>";
            }
        }
        echo "<p class='success'>✅ Sample movies inserted successfully.</p>";
    }
}
?>
