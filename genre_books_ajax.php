<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection error']);
    exit;
}

$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
if (empty($genre)) {
    echo json_encode(['error' => 'No genre specified']);
    exit;
}

// Prevent absolute URL open-redirect style values (simple guard)
if (strpos($genre, 'http') !== false) {
    echo json_encode(['error' => 'Invalid genre']);
    exit;
}

// Fetch books for the genre
$books = [];
$stmt = $conn->prepare("SELECT id, title, author, synopsis, cover_image, attachment FROM books WHERE genre = ? ORDER BY created_at DESC");
$stmt->bind_param('s', $genre);
$stmt->execute();
$res = $stmt->get_result();
while ($b = $res->fetch_assoc()) {
    $books[] = $b;
}

echo json_encode(['books' => $books]);
$conn->close();
?>
