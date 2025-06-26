<?php
// Database repair script to fix missing columns and setup
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Repair - SSR Cinema</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #1a1a1a; color: white; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; color: #155724; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; color: #721c24; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; color: #0c5460; }
        .step { background: #2c2c2c; padding: 15px; margin: 10px 0; border-radius: 5px; }
        button { background: #fa7e61; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #e66a4d; }
        pre { background: #333; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 SSR Cinema Database Repair</h1>
        
        <?php
        try {
            // Database connection
            $host = 'localhost';
            $user = 'root';
            $pass = 'root';
            $dbname = 'ssr_cinema';
            
            echo "<div class='step'><h3>Step 1: Connecting to Database</h3>";
            
            // Create database if it doesn't exist
            $pdo_temp = new PDO("mysql:host=$host", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS $dbname");
            echo "<div class='success'>✅ Database '$dbname' created/verified</div>";
            
            // Connect to the specific database
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            echo "<div class='success'>✅ Connected to database successfully</div></div>";
            
            echo "<div class='step'><h3>Step 2: Creating/Repairing Tables</h3>";
            
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
            echo "<div class='success'>✅ Users table created/verified</div>";
            
            // Drop and recreate movies table to ensure all columns exist
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("DROP TABLE IF EXISTS bookings");
            $pdo->exec("DROP TABLE IF EXISTS movies");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $pdo->exec("CREATE TABLE movies (
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
            echo "<div class='success'>✅ Movies table recreated with all columns</div>";
            
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
            echo "<div class='success'>✅ Bookings table created/verified</div>";
            
            // Create user sessions table
            $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_id VARCHAR(128) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
            echo "<div class='success'>✅ User sessions table created/verified</div></div>";
            
            echo "<div class='step'><h3>Step 3: Creating Default Users</h3>";
            
            // Insert admin user
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute(['Admin User', 'admin@ssrcinema.com', 'admin', $adminPassword]);
            echo "<div class='success'>✅ Admin user created (admin/admin123)</div>";
            
            // Insert test user
            $testPassword = password_hash('test123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (full_name, email, username, password, is_admin) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute(['Test User', 'test@example.com', 'testuser', $testPassword]);
            echo "<div class='success'>✅ Test user created (testuser/test123)</div></div>";
            
            echo "<div class='step'><h3>Step 4: Adding Sample Movies</h3>";
            
            // Clear existing movies
            $pdo->exec("DELETE FROM movies");
            
            // Insert sample movies
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
            
            $stmt = $pdo->prepare("INSERT INTO movies (title, description, genre, director, cast, duration, rating, release_date, language, image_url, trailer_url, ticket_price, show_times, is_showing, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($movies as $movie) {
                $stmt->execute([
                    $movie['title'], $movie['description'], $movie['genre'], $movie['director'], 
                    $movie['cast'], $movie['duration'], $movie['rating'], $movie['release_date'], 
                    $movie['language'], $movie['image_url'], $movie['trailer_url'], 
                    $movie['ticket_price'], $movie['show_times'], $movie['is_showing'], $movie['is_featured']
                ]);
            }
            
            echo "<div class='success'>✅ " . count($movies) . " sample movies added</div></div>";
            
            echo "<div class='step'><h3>Step 5: Verification</h3>";
            
            // Verify tables and data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch()['count'];
            echo "<div class='info'>👥 Users in database: $userCount</div>";
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM movies WHERE is_showing = 1");
            $movieCount = $stmt->fetch()['count'];
            echo "<div class='info'>🎬 Movies showing: $movieCount</div>";
            
            // Test the movies API
            echo "<div class='info'>🧪 Testing movies API...</div>";
            $testUrl = 'php/movies.php?action=test';
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $testResult = @file_get_contents($testUrl, false, $context);
            if ($testResult) {
                $testData = json_decode($testResult, true);
                if ($testData && $testData['success']) {
                    echo "<div class='success'>✅ Movies API is working</div>";
                } else {
                    echo "<div class='error'>❌ Movies API test failed</div>";
                }
            } else {
                echo "<div class='error'>❌ Could not reach movies API</div>";
            }
            
            echo "</div>";
            
            echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>";
            echo "<h2>🎉 Database Repair Complete!</h2>";
            echo "<p><strong>Admin Login:</strong> username: <code>admin</code> | password: <code>admin123</code></p>";
            echo "<p><strong>Test User:</strong> username: <code>testuser</code> | password: <code>test123</code></p>";
            echo "<p><strong>Movies:</strong> $movieCount movies are now available</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h3>❌ Repair Failed</h3>";
            echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "</div>";
        }
        ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="index.html" style="background: #fa7e61; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;">🏠 Go to Homepage</a>
            <a href="php/movies.php?action=test" target="_blank" style="background: #17a2b8; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin-left: 10px;">🧪 Test Movies API</a>
        </div>
    </div>
</body>
</html>
