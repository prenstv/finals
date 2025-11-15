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
$book_id = $_POST['book_id'] ?? $_GET['book_id'] ?? 0;
$book_id = (int)$book_id;

if ($book_id) {
    $stmt = $conn->prepare("INSERT IGNORE INTO reading_list (user_id, book_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
}

header("Location: gallery.php?user=" . urlencode($_SESSION['username']));
exit;
?>
