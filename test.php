<?php
// SSR Cinema - Database Configuration File

$servername = "localhost";
$username = "root";
$password = "root"; // Change to "" if your root user has no password
$dbname = "ssr_cinema";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
if ($conn->query("CREATE DATABASE IF NOT EXISTS $dbname") === TRUE) {
    echo "✅ Database '$dbname' is ready.<br>";
} else {
    die("❌ Failed to create database: " . $conn->error);
}

// Select the database
if ($conn->select_db($dbname)) {
    echo "✅ Connected to MySQL and using database '$dbname'.";
} else {
    die("❌ Could not select database '$dbname': " . $conn->error);
}
?>
