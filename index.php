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

$uploadMessage = "";

// ✅ Handle book upload
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

    // Check for duplicates
    $check = $conn->prepare("SELECT id FROM books WHERE user_id = ? AND title = ? AND author = ?");
    $check->bind_param("iss", $user_id, $title, $author);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        $uploadMessage = "⚠️ This book already exists in your gallery.";
    } else {
        // Cover image
        $coverPath = "uploads/covers/default.png";
        if (!empty($_FILES['cover']['name'])) {
            $coverName = basename($_FILES['cover']['name']);
            $coverTmp = $_FILES['cover']['tmp_name'];
            $uploadDir = "uploads/covers/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $coverPath = $uploadDir . $coverName;
            move_uploaded_file($coverTmp, $coverPath);
        }

        // Attachment
        $attachmentPath = "";
        if (!empty($_FILES['attachment']['name'])) {
            $attachName = basename($_FILES['attachment']['name']);
            $attachTmp = $_FILES['attachment']['tmp_name'];
            $uploadDir = "uploads/files/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $attachmentPath = $uploadDir . $attachName;
            move_uploaded_file($attachTmp, $attachmentPath);
        }

        // Insert book
        $stmt = $conn->prepare("INSERT INTO books (user_id, title, author, synopsis, genre, cover_image, attachment, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssss", $user_id, $title, $author, $synopsis, $genre, $coverPath, $attachmentPath);

        if ($stmt->execute()) {
            $uploadMessage = "✅ Book uploaded successfully!";
        } else {
            $uploadMessage = "❌ Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Book Upload</title>


</head>
<body class="bg-light">

  <div class="container mt-5">
    <?php if (!empty($uploadMessage)): ?>
      <div class="alert <?php echo strpos($uploadMessage, '✅') !== false ? 'alert-success' : 'alert-warning'; ?>">
        <?php echo htmlspecialchars($uploadMessage); ?>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>📚 Upload a Book</h2>
      <div>
        <a href="gallery.php" class="btn btn-success me-2">📖 View Book Gallery</a>
        <a href="logout.php" class="btn btn-outline-danger logout-link">🚪 Logout</a>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="title" class="form-label">Book Title</label>
        <input type="text" id="title" name="title" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="genreSelect" class="form-label">Genre</label>
        <select name="genre" id="genreSelect" class="form-select" required>
          <option value="">-- Select Genre --</option>
          <?php
          $genres = $conn->query("SELECT genre FROM genres ORDER BY genre ASC");
          while ($g = $genres->fetch_assoc()) {
              echo "<option value='" . htmlspecialchars($g['genre']) . "'>" . htmlspecialchars($g['genre']) . "</option>";
          }
          ?>
          <option value="Other">Other</option>
        </select>
        <input type="text" id="otherGenre" name="other_genre" class="form-control mt-2"
               placeholder="Enter custom genre" style="display:none;">
      </div>

      <div class="mb-3">
        <label for="author" class="form-label">Author</label>
        <input type="text" id="author" name="author" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="synopsis" class="form-label">Synopsis</label>
        <textarea id="synopsis" name="synopsis" class="form-control" rows="4" required></textarea>
      </div>

      <div class="mb-3">
        <label for="cover" class="form-label">Book Cover Image</label>
        <input type="file" id="cover" name="cover" class="form-control" accept="image/*" onchange="previewImage(event)">
        <img id="coverPreview" src="#" alt="Cover Preview" class="img-fluid mt-2" style="max-height: 200px; display: none;">
      </div>

      <div class="mb-3">
        <label for="attachment" class="form-label">Book File (PDF/ePub)</label>
        <input type="file" id="attachment" name="attachment" class="form-control" accept=".pdf,.epub">
      </div>

      <button type="submit" class="btn btn-primary">Upload Book</button>
    </form>
  </div>

  <!-- Scripts -->
  <script>
    function previewImage(event) {
      const reader = new FileReader();
      reader.onload = function () {
        const output = document.getElementById('coverPreview');
        output.src = reader.result;
        output.style.display = 'block';
      };
      reader.readAsDataURL(event.target.files[0]);
    }

    document.getElementById('genreSelect').addEventListener('change', function () {
      const otherInput = document.getElementById('otherGenre');
      otherInput.style.display = this.value === 'Other' ? 'block' : 'none';
      otherInput.required = this.value === 'Other';
    });

    document.querySelector("form").addEventListener("submit", function () {
      const btn = this.querySelector("button[type='submit']");
      btn.disabled = true;
      btn.innerText = "Uploading...";
    });
  </script>

</body>
</html>
