<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php?return_url=".urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "book_library";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$book_id = $_POST['book_id'] ?? 0;
$progress = $_POST['progress'] ?? null;

if ($book_id && is_numeric($progress) && $progress >= 0 && $progress <= 100) {
    // Check if book is in reading list
    $check = $conn->prepare("SELECT id FROM reading_list WHERE user_id = ? AND book_id = ?");
    $check->bind_param("ii", $user_id, $book_id);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        // Update progress
        $stmt = $conn->prepare("UPDATE reading_list SET progress = ? WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("iii", $progress, $user_id, $book_id);
        $stmt->execute();
    } else {
        // Add to reading list if not already there
        $stmt = $conn->prepare("INSERT INTO reading_list (user_id, book_id, progress) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $book_id, $progress);
        $stmt->execute();
    }
}

$conn->close();
header("Location: gallery.php?user=" . urlencode($_SESSION['username']));
exit;
?>
