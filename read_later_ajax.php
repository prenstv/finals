<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$book_id = (int)($_POST['book_id'] ?? $_GET['book_id'] ?? 0);
if (!$book_id) {
    echo json_encode(['success' => false, 'error' => 'missing_book_id']);
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "book_library";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'db_connect']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("INSERT IGNORE INTO reading_list (user_id, book_id) VALUES (?, ?)");
$stmt->bind_param('ii', $user_id, $book_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['success' => false, 'error' => 'insert_failed']);
    $conn->close();
    exit;
}

// Check if it exists now
$chk = $conn->prepare("SELECT id FROM reading_list WHERE user_id = ? AND book_id = ? LIMIT 1");
$chk->bind_param('ii', $user_id, $book_id);
$chk->execute();
$res = $chk->get_result();
$exists = ($res && $res->num_rows > 0);
$chk->close();

$conn->close();

echo json_encode(['success' => true, 'added' => $exists]);
exit;
