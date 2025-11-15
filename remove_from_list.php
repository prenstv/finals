<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php?return_url=".urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

if ($book_id > 0) {
    // Delete only from reading_list
    $stmt = $conn->prepare("DELETE FROM reading_list WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// Redirect back to reading list
header("Location: my_reading_list.php");
exit;
?>
