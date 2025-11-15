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

$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_SESSION['user_id'];
        $title = $_POST['title'];
        $author = $_POST['author'];
        $synopsis = $_POST['synopsis'];
        $genre = $_POST['genre'];

        if ($genre === "Other" && !empty($_POST['other_genre'])) {
                $genre = $_POST['other_genre'];
                $stmt = $conn->prepare("INSERT IGNORE INTO genres (genre) VALUES (?)");
                $stmt->bind_param("s", $genre);
                $stmt->execute();
        }

        // Check for existing book
        $check = $conn->prepare("SELECT id FROM books WHERE user_id = ? AND title = ? AND author = ?");
        $check->bind_param("iss", $user_id, $title, $author);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
                $uploadMessage = "⚠️ This book already exists in your gallery.";
        } else {
                // Handle optional cover image
                $coverPath = "uploads/covers/default.png";
                if (!empty($_FILES['cover']['name'])) {
                        $coverName = basename($_FILES['cover']['name']);
                        $coverTmp = $_FILES['cover']['tmp_name'];
                        $uploadDir = "uploads/covers/";
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $coverPath = $uploadDir . $coverName;
                        move_uploaded_file($coverTmp, $coverPath);
                }

                // Handle optional attachment
                $attachmentPath = "";
                if (!empty($_FILES['attachment']['name'])) {
                        $attachName = basename($_FILES['attachment']['name']);
                        $attachTmp = $_FILES['attachment']['tmp_name'];
                        $uploadDir = "uploads/files/";
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $attachmentPath = $uploadDir . $attachName;
                        move_uploaded_file($attachTmp, $attachmentPath);
                }

                // Insert book record
                $stmt = $conn->prepare("INSERT INTO books (user_id, title, author, synopsis, genre, cover_image, attachment, created_at) 
                                                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issssss", $user_id, $title, $author, $synopsis, $genre, $coverPath, $attachmentPath);

                if ($stmt->execute()) {
                        $uploadMessage = "✅ Book uploaded successfully!";
                } else {
                        $uploadMessage = "❌ Error: " . $conn->error;
                }
                $stmt->close();
        }
}

// Load current user info for header
$userData = $conn->query("SELECT username, profile_picture FROM users WHERE id=".(int)$_SESSION['user_id'])->fetch_assoc() ?: [];
$listCount = $conn->query("SELECT COUNT(*) AS total FROM reading_list WHERE user_id=".(int)$_SESSION['user_id'])->fetch_assoc()['total'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/inc/header.php'; ?>

    <div class="container mt-5">
        <?php if ($uploadMessage): ?>
            <div class="alert <?php echo strpos($uploadMessage, '✅') !== false ? 'alert-success' : 'alert-warning'; ?>">
                <?php echo htmlspecialchars($uploadMessage); ?>
            </div>
        <?php endif; ?>

        <a href="gallery.php?user=<?php echo urlencode($_SESSION['username']); ?>" class="btn btn-primary mb-3">📚 Go to My Gallery</a>
    </div>


    <!-- logout confirmation handled by shared header include -->
</body>
</html>
