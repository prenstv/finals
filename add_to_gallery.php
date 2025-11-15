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
$book_id = (int)($_POST['book_id'] ?? $_GET['book_id'] ?? 0);

if ($book_id) {
    // Fetch the book details
    $stmt = $conn->prepare("SELECT id, title, author, synopsis, genre, cover_image, attachment FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    if ($book) {
        // Check if user already has this book in their gallery
        $checkStmt = $conn->prepare("SELECT id FROM books WHERE user_id = ? AND title = ? AND author = ?");
        $checkStmt->bind_param("iss", $user_id, $book['title'], $book['author']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkStmt->close();

        if ($checkResult->num_rows > 0) {
            // Book already exists in user's gallery
            header("Location: gallery.php?user=" . urlencode($_SESSION['username']) . "&msg=exists");
            exit;
        } else {
            // Add book to user's gallery
            $insertStmt = $conn->prepare("INSERT INTO books (user_id, title, author, synopsis, genre, cover_image, attachment, created_at) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $insertStmt->bind_param("issssss", $user_id, $book['title'], $book['author'], $book['synopsis'], $book['genre'], $book['cover_image'], $book['attachment']);
            
            if ($insertStmt->execute()) {
                $insertStmt->close();
                $conn->close();
                header("Location: gallery.php?user=" . urlencode($_SESSION['username']) . "&msg=added");
                exit;
            } else {
                $insertStmt->close();
                $conn->close();
                header("Location: gallery.php?user=" . urlencode($_SESSION['username']) . "&msg=error");
                exit;
            }
        }
    }
}

$conn->close();
header("Location: home.php");
exit;
?>
