<?php
/**
 * SSR Cinema - Complete Setup & Testing Suite
 * Combined: Database Setup, Connection Testing, Authentication Testing, phpMyAdmin Fix
 * Run this file to set up the database and test all functionality
 */

require_once 'config/database.php';

// Get the action parameter
$action = $_GET['action'] ?? 'setup';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSR Cinema - Setup & Testing Suite</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background-color: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        h2 { color: #333; border-bottom: 2px solid #fa7e61; padding-bottom: 10px; }
        h3 { color: #555; margin-top: 25px; }
        .step { margin: 15px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #fa7e61; }
        .credentials { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #fa7e61; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; text-decoration: none; }
        .btn:hover { background: #e66a4d; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .nav-tabs { display: flex; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
        .nav-tab { padding: 10px 20px; background: #f8f9fa; border: none; cursor: pointer; margin-right: 5px; }
        .nav-tab.active { background: #fa7e61; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th, table td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f8f9fa; }
        textarea { width: 100%; font-family: monospace; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .test-pass { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-fail { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🎬 SSR Cinema - Complete Setup & Testing Suite</h2>

        <div class="nav-tabs">
            <button class="nav-tab <?= $action === 'setup' ? 'active' : '' ?>" onclick="showTab('setup')">🔧 Database Setup</button>
            <button class="nav-tab <?= $action === 'test' ? 'active' : '' ?>" onclick="showTab('test')">🧪 Connection Test</button>
            <button class="nav-tab <?= $action === 'auth' ? 'active' : '' ?>" onclick="showTab('auth')">🔐 Auth Test</button>
            <button class="nav-tab <?= $action === 'phpmyadmin' ? 'active' : '' ?>" onclick="showTab('phpmyadmin')">🗄️ phpMyAdmin Fix</button>
        </div>

        <!-- Database Setup Tab -->
        <div id="setup" class="tab-content <?= $action === 'setup' ? 'active' : '' ?>">
            <?php if ($action === 'setup'): ?>
            <?php
            try {
                // Database configuration
                $host = 'localhost';
                $username = 'root';
                $password = '';
                $db_name = 'ssr_cinema';

                echo "<div class='step'><strong>Step 1:</strong> Connecting to MySQL server...</div>";

                // Connect without specifying database
                $pdo = new PDO("mysql:host=$host", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<p class='success'>✅ Connected to MySQL server successfully!</p>";

                echo "<div class='step'><strong>Step 2:</strong> Creating database...</div>";

                // Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<p class='success'>✅ Database '$db_name' created or already exists.</p>";

                echo "<div class='step'><strong>Step 3:</strong> Creating tables...</div>";

                // Now use the database setup class
                $setup = new DatabaseSetup();
                $setup->createTables();

                echo "<div class='step'><strong>Step 4:</strong> Creating admin user...</div>";

                // Create admin user
                $admin_query = "INSERT IGNORE INTO users (full_name, email, username, password, is_admin, created_at)
                               VALUES ('Admin User', 'admin@ssrcinema.com', 'admin', :password, 1, NOW())";

                $database = new Database();
                $conn = $database->getConnection();
                $stmt = $conn->prepare($admin_query);
                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt->bindParam(':password', $admin_password);
                $stmt->execute();

                echo "<p class='success'>✅ Admin user created successfully!</p>";

                // Create some sample users for testing
                echo "<div class='step'><strong>Step 5:</strong> Creating sample users...</div>";

                $sample_users = [
                    ['John Doe', 'john@example.com', 'john_doe', 'password123'],
                    ['Jane Smith', 'jane@example.com', 'jane_smith', 'password123'],
                    ['Mike Johnson', 'mike@example.com', 'mike_j', 'password123']
                ];

                $user_query = "INSERT IGNORE INTO users (full_name, email, username, password, created_at)
                              VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($user_query);

                foreach ($sample_users as $user) {
                    $hashed_password = password_hash($user[3], PASSWORD_DEFAULT);
                    $stmt->execute([$user[0], $user[1], $user[2], $hashed_password]);
                }

                echo "<p class='success'>✅ Sample users created for testing!</p>";

                echo "<div class='step'><strong>✅ Setup Complete!</strong></div>";
                echo "<p class='success'>🎉 Database setup completed successfully!</p>";

                echo "<div class='credentials'>";
                echo "<h3>🔐 Login Credentials</h3>";
                echo "<p><strong>Admin Account:</strong></p>";
                echo "<ul>";
                echo "<li>Username: <code>admin</code></li>";
                echo "<li>Password: <code>admin123</code></li>";
                echo "</ul>";
                echo "<p><strong>Test User Accounts:</strong></p>";
                echo "<ul>";
                echo "<li>Username: <code>john_doe</code> | Password: <code>password123</code></li>";
                echo "<li>Username: <code>jane_smith</code> | Password: <code>password123</code></li>";
                echo "<li>Username: <code>mike_j</code> | Password: <code>password123</code></li>";
                echo "</ul>";
                echo "</div>";

                echo "<div class='step'>";
                echo "<h3>📋 Next Steps:</h3>";
                echo "<ol>";
                echo "<li>Update database credentials in <code>config/database.php</code> if needed</li>";
                echo "<li>Test the login functionality with the provided credentials</li>";
                echo "<li>Create new user accounts through the signup form</li>";
                echo "<li>Access admin dashboard with admin credentials</li>";
                echo "</ol>";
                echo "</div>";

            } catch (PDOException $e) {
                echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
                echo "<div class='step'>";
                echo "<h3>🔧 Troubleshooting:</h3>";
                echo "<ul>";
                echo "<li>Make sure MySQL/MariaDB is running</li>";
                echo "<li>Check database credentials in config/database.php</li>";
                echo "<li>Ensure PHP has PDO MySQL extension enabled</li>";
                echo "<li>Verify database user has CREATE privileges</li>";
                echo "</ul>";
                echo "</div>";
            }
            ?>
            <?php else: ?>
                <p>Click "Run Database Setup" to create the database and tables.</p>
                <a href="?action=setup" class="btn">🔧 Run Database Setup</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
