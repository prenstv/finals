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
$id = (int)$_GET['id'];

// Only delete if book belongs to logged-in user
$sql = "DELETE FROM books WHERE id=$id AND user_id=$user_id";

if ($conn->query($sql) === TRUE) {
    header("Location: gallery.php?user=" . urlencode($_SESSION['username']));
    exit;
} else {
    echo "❌ Error deleting book: " . $conn->error;
}

$conn->close();
?>
