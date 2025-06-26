<?php
/**
 * Complete Database Repair and Setup Script
 * This will fix all database connection issues and setup the cinema system
 */

header('Content-Type: text/html; charset=utf-8');

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = ''; // Try empty password first, then 'root'
$dbname = 'ssr_cinema';

?>
<!DOCTYPE html>
<html>
<head>
    <title>SSR Cinema - Database Repair</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #121212, #1f1f1f);
            color: white;
            min-height: 100vh;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto;
            background: #2c2c2c;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        h1 {
            text-align: center;
            color: #fa7e61;
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .success { 
            color: #155724;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid #28a745;
            font-weight: 500;
        }
        .error { 
            color: #721c24;
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid #dc3545;
            font-weight: 500;
        }
        .info { 
            color: #0c5460;
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid #17a2b8;
            font-weight: 500;
        }
        .warning {
            color: #856404;
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            padding: 15px 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid #ffc107;
            font-weight: 500;
        }
        .step { 
            background: linear-gradient(135deg, #1e1e1e, #2b2b2b);
            padding: 25px;
            margin: 20px 0;
            border-radius: 12px;
            border: 1px solid #444;
        }
        .step h3 {
            color: #fa7e61;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }
        .btn { 
            background: linear-gradient(135deg, #fa7e61, #e66a4d);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(250, 126, 97, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #218838);
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        pre {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            color: #00ff00;
            border: 1px solid #444;
            font-family: 'Courier New', monospace;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #444;
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #fa7e61, #e66a4d);
            width: 0%;
            transition: width 0.5s ease;
        }
        .config-box {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            border: 1px solid #444;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #333;
        }
        .config-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-success {
            background: #28a745;
            color: white;
        }
        .status-error {
            background: #dc3545;
            color: white;
        }
        .status-warning {
            background: #ffc107;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 SSR Cinema Database Repair Tool</h1>
        
        <?php
        $repairSteps = [
            'Testing MySQL Connection',
            'Creating Database',
            'Setting Up Tables',
            'Adding Default Users',
            'Inserting Sample Movies',
            'Verifying Setup'
        ];
        
        $currentStep = 0;
        $totalSteps = count($repairSteps);
        
        try {
            echo "<div class='info'>🚀 Starting database repair process...</div>";
            echo "<div class='progress-bar'><div class='progress-fill' id='progressBar'></div></div>";
            echo "<div id='currentStep'>Step 1 of $totalSteps: {$repairSteps[0]}</div>";
            
            // Step 1: Test different connection configurations
            echo "<div class='step'>";
            echo "<h3>Step 1: Testing MySQL Connection</h3>";
            
            $connectionConfigs = [
                ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
                ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
                ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
                ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'root'],
            ];
            
            $workingConfig = null;
            
            foreach ($connectionConfigs as $config) {
                echo "<div class='info'>🔍 Testing: {$config['host']} with user '{$config['user']}' and password '" . ($config['pass'] ? '***' : 'empty') . "'</div>";
                
                try {
                    $testConn = new mysqli($config['host'], $config['user'], $config['pass']);
                    if (!$testConn->connect_error) {
                        $workingConfig = $config;
                        echo "<div class='success'>✅ Connection successful!</div>";
                        $testConn->close();
                        break;
                    } else {
                        echo "<div class='warning'>⚠️ Failed: " . $testConn->connect_error . "</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='warning'>⚠️ Failed: " . $e->getMessage() . "</div>";
                }
            }
            
            if (!$workingConfig) {
                throw new Exception("Could not establish MySQL connection with any configuration. Please check if MySQL is running.");
            }
            
            // Use working configuration
            $host = $workingConfig['host'];
            $user = $workingConfig['user'];
            $pass = $workingConfig['pass'];
            
            echo "</div>";
            
            // Step 2: Create database
            $currentStep++;
            echo "<script>
                document.getElementById('progressBar').style.width = '" . ($currentStep/$totalSteps*100) . "%';
                document.getElementById('currentStep').textContent = 'Step " . ($currentStep+1) . " of $totalSteps: {$repairSteps[$currentStep]}';
            </script>";
            
            echo "<div class='step'>";
            echo "<h3>Step 2: Creating Database</h3>";
            
            $mysqli = new mysqli($host, $user, $pass);
            if ($mysqli->connect_error) {
                throw new Exception("Connection failed: " . $mysqli->connect_error);
            }
            
            // Drop and recreate database for clean setup
            $mysqli->query("DROP DATABASE IF EXISTS `$dbname`");
            if ($mysqli->query("CREATE DATABASE `$dbname`")) {
                echo "<div class='success'>✅ Database '$dbname' created successfully</div>";
            } else {
                throw new Exception("Error creating database: " . $mysqli->error);
            }
            
            $mysqli->select_db($dbname);
            echo "<div class='success'>✅ Using database '$dbname'</div>";
            echo "</div>";
            
            // Step 3: Create tables
            $currentStep++;
            echo "<script>
                document.getElementById('progressBar').style.width = '" . ($currentStep/$totalSteps*100) . "%';
                document.getElementById('currentStep').textContent = 'Step " . ($currentStep+1) . " of $totalSteps: {$repairSteps[$currentStep]}';
            </script>";
            
            echo "<div class='step'>";
            echo "<h3>Step 3: Setting Up Tables</h3>";
            
            // Users table
            $sql_users = "CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                is_admin BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if ($mysqli->query($sql_users)) {
                echo "<div class='success'>✅ Users table created</div>";
            } else {
                throw new Exception("Error creating users table: " . $mysqli->error);
            }
            
            // Movies table
            $sql_movies = "CREATE TABLE movies (
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
            
            if ($mysqli->query($sql_movies)) {
                echo "<div class='success'>✅ Movies table created</div>";
            } else {
                throw new Exception("Error creating movies table: " . $mysqli->error);
            }
            
            // Bookings table
            $sql_bookings = "CREATE TABLE bookings (
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
            
            if ($mysqli->query($sql_bookings)) {
                echo "<div class='success'>✅ Bookings table created</div>";
            } else {
                throw new Exception("Error creating bookings table: " . $mysqli->error);
            }
            
            // User sessions table
            $sql_sessions = "CREATE TABLE user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_id VARCHAR(128) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            
            if ($mysqli->query($sql_sessions)) {
                echo "<div class='success'>✅ User sessions table created</div>";
            } else {
                throw new Exception("Error creating user sessions table: " . $mysqli->error);
            }
            
            echo "</div>";
            
            // Step 4: Add default users
            $currentStep++;
            echo "<script>
                document.getElementById('progressBar').style.width = '" . ($currentStep/$totalSteps*100) . "%';
                document.getElementById('currentStep').textContent = 'Step " . ($currentStep+1) . " of $totalSteps: {$repairSteps[$currentStep]}';
            </script>";
            
            echo "<div class='step'>";
            echo "<h3>Step 4: Adding Default Users</h3>";
            
            // Admin user
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 1)");
            $admin_name = 'Admin User';
            $admin_email = 'admin@ssrcinema.com';
            $admin_username = 'admin';
            $stmt->bind_param("ssss", $admin_name, $admin_email, $admin_username, $admin_password);
            
            if ($stmt->execute()) {
                echo "<div class='success'>✅ Admin user created (username: admin, password: admin123)</div>";
            } else {
                throw new Exception("Error creating admin user: " . $mysqli->error);
            }
            $stmt->close();
            
            // Test user
            $test_password = password_hash('test123', PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 0)");
            $test_name = 'Test User';
            $test_email = 'test@example.com';
            $test_username = 'testuser';
            $stmt->bind_param("ssss", $test_name, $test_email, $test_username, $test_password);
            
            if ($stmt->execute()) {
                echo "<div class='success'>✅ Test user created (username: testuser, password: test123)</div>";
            } else {
                throw new Exception("Error creating test user: " . $mysqli->error);
            }
            $stmt->close();
            
            echo "</div>";
            
            // Step 5: Insert sample movies
            $currentStep++;
            echo "<script>
                document.getElementById('progressBar').style.width = '" . ($currentStep/$totalSteps*100) . "%';
                document.getElementById('currentStep').textContent = 'Step " . ($currentStep+1) . " of $totalSteps: {$repairSteps[$currentStep]}';
            </script>";
            
            echo "<div class='step'>";
            echo "<h3>Step 5: Inserting Sample Movies</h3>";
            
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
            
            $stmt = $mysqli->prepare("INSERT INTO movies (title, description, genre, director, cast, duration, rating, release_date, language, image_url, trailer_url, ticket_price, show_times, is_showing, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($movies as $movie) {
                $stmt->bind_param("sssssississsdii", 
                    $movie['title'], $movie['description'], $movie['genre'], $movie['director'], 
                    $movie['cast'], $movie['duration'], $movie['rating'], $movie['release_date'], 
                    $movie['language'], $movie['image_url'], $movie['trailer_url'], 
                    $movie['ticket_price'], $movie['show_times'], $movie['is_showing'], $movie['is_featured']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting movie '{$movie['title']}': " . $mysqli->error);
                }
            }
            $stmt->close();
            
            echo "<div class='success'>✅ " . count($movies) . " sample movies added successfully</div>";
            echo "</div>";
            
            // Step 6: Verification
            $currentStep++;
            echo "<script>
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('currentStep').textContent = 'Step $totalSteps of $totalSteps: {$repairSteps[$currentStep-1]} - Complete!';
            </script>";
            
            echo "<div class='step'>";
            echo "<h3>Step 6: Verifying Setup</h3>";
            
            // Verify data
            $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
            $userCount = $result->fetch_assoc()['count'];
            echo "<div class='info'>👥 Users in database: $userCount</div>";
            
            $result = $mysqli->query("SELECT COUNT(*) as count FROM movies WHERE is_showing = 1");
            $movieCount = $result->fetch_assoc()['count'];
            echo "<div class='info'>🎬 Movies showing: $movieCount</div>";
            
            $result = $mysqli->query("SELECT COUNT(*) as count FROM bookings");
            $bookingCount = $result->fetch_assoc()['count'];
            echo "<div class='info'>🎟️ Bookings: $bookingCount</div>";
            
            // Show table structure
            echo "<h4>📋 Database Tables Created:</h4>";
            $result = $mysqli->query("SHOW TABLES");
            echo "<pre>";
            while ($row = $result->fetch_array()) {
                echo "✅ " . $row[0] . "\n";
            }
            echo "</pre>";
            
            echo "</div>";
            
            // Create updated config file
            $configContent = "<?php
/**
 * SSR Cinema Database Configuration - Auto-generated
 * Generated on: " . date('Y-m-d H:i:s') . "
 */

// Database configuration
define('DB_HOST', '$host');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_NAME', '$dbname');

class Database {
    private static \$connection = null;
    private static \$pdo = null;
    
    public static function connect() {
        if (self::\$connection === null) {
            try {
                // Create database if it doesn't exist
                \$mysqli_temp = new mysqli(DB_HOST, DB_USER, DB_PASS);
                if (\$mysqli_temp->connect_error) {
                    throw new Exception('Connection failed: ' . \$mysqli_temp->connect_error);
                }
                \$mysqli_temp->query('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '`');
                \$mysqli_temp->close();
                
                // Connect to the specific database
                self::\$connectio
