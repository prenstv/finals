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
$id = (int)($_GET['id'] ?? 0);

// Fetch book only if it belongs to the logged-in user
$stmt = $conn->prepare("SELECT * FROM books WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    echo "❌ Book not found or you don't have permission to edit it.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = $_POST['title'] ?? '';
    $author   = $_POST['author'] ?? '';
    $synopsis = $_POST['synopsis'] ?? '';
    $genre    = $_POST['genre'] ?? '';

    // Handle optional cover update
    $coverPath = $book['cover_image'];
    if (!empty($_FILES['cover']['name'])) {
        $coverName = basename($_FILES['cover']['name']);
        $coverTmp  = $_FILES['cover']['tmp_name'];
        $coverPath = "uploads/covers/" . $coverName;
        move_uploaded_file($coverTmp, $coverPath);
    }

    // Handle optional attachment update
    $attachPath = $book['attachment'];
    if (!empty($_FILES['attachment']['name'])) {
        $attachName = basename($_FILES['attachment']['name']);
        $attachTmp  = $_FILES['attachment']['tmp_name'];
        $attachPath = "uploads/files/" . $attachName;
        move_uploaded_file($attachTmp, $attachPath);
    }

    // Update using prepared statement
    $stmt = $conn->prepare("UPDATE books 
                            SET title=?, author=?, synopsis=?, genre=?, cover_image=?, attachment=? 
                            WHERE id=? AND user_id=?");
    $stmt->bind_param("ssssssii", $title, $author, $synopsis, $genre, $coverPath, $attachPath, $id, $user_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: gallery.php?user=" . urlencode($_SESSION['username']));
        exit;
    } else {
        echo "❌ Error updating record: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Book</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/inc/header.php'; ?>
  <div class="container mt-5">
    <h2>Edit Book</h2>
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($book['title']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Author</label>
        <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($book['author']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Synopsis</label>
        <textarea name="synopsis" class="form-control" rows="4" required><?php echo htmlspecialchars($book['synopsis']); ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Genre</label>
        <input type="text" name="genre" class="form-control" value="<?php echo htmlspecialchars($book['genre']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Cover Image (optional)</label>
        <input type="file" name="cover" class="form-control" accept="image/*">
      </div>
      <div class="mb-3">
        <label class="form-label">Attachment (optional)</label>
        <input type="file" name="attachment" class="form-control" accept=".pdf,.epub">
      </div>
      <button type="submit" class="btn btn-success">Save Changes</button>
      <a href="gallery.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</body>
</html>
