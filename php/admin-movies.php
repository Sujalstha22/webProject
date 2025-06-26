<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "root";
$dbname = "ssr_cinema";

// DB connection
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'add_movie') {
    $title = $_POST['title'] ?? '';
    $director = $_POST['director'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $language = $_POST['language'] ?? '';
    $duration = intval($_POST['duration'] ?? 0);
    $rating = $_POST['rating'] ?? '';
    $ticket_price = floatval($_POST['ticket_price'] ?? 0);
    $image_url = $_POST['image_url'] ?? '';
    $description = $_POST['description'] ?? '';
    $is_showing = intval($_POST['is_showing'] ?? 0);
    $is_featured = intval($_POST['is_featured'] ?? 0);
    $show_times = $_POST['show_times'] ?? '[]';

    $stmt = $conn->prepare("INSERT INTO movies (title, director, genre, language, duration, rating, ticket_price, image_url, description, is_showing, is_featured, show_times) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssisdssiis", $title, $director, $genre, $language, $duration, $rating, $ticket_price, $image_url, $description, $is_showing, $is_featured, $show_times);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Movie added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
    }
    exit;
}

if ($action === 'get_all_movies') {
    $sql = "SELECT * FROM movies ORDER BY id DESC";
    $result = $conn->query($sql);

    $movies = [];
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }

    echo json_encode(['success' => true, 'movies' => $movies]);
    exit;
}

// Add more actions here if needed

echo json_encode(['success' => false, 'message' => 'No valid action provided']);
