<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in', 'message' => 'You must be logged in to submit a testimonial.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$name = trim($input['name'] ?? '');
$role = trim($input['role'] ?? '');
$message = trim($input['message'] ?? '');
$rating = (int)($input['rating'] ?? 5);

if (empty($name) || empty($role) || empty($message) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'All fields are required and rating must be between 1 and 5']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$name = $conn->real_escape_string($name);
$role = $conn->real_escape_string($role);
$message = $conn->real_escape_string($message);

$user_id = (int)$_SESSION['user_id'];
$sql = "INSERT INTO testimonials (user_id, name, role, message, rating) VALUES ($user_id, '$name', '$role', '$message', $rating)";
if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save testimonial']);
}

$conn->close();
?>
