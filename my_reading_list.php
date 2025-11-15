<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: home.php?return_url=".urlencode($_SERVER['REQUEST_URI'])); exit; }

$conn = new mysqli("localhost", "root", "", "book_library");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = $_SESSION['user_id'];

// Fetch reading list with book details
$sql = "SELECT rl.book_id, rl.progress, rl.created_at,
               b.title, b.author, b.genre, b.cover_image, b.attachment
        FROM reading_list rl
        JOIN books b ON rl.book_id = b.id
        WHERE rl.user_id = ?
        ORDER BY rl.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($_SESSION['username']); ?>'s Reading List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/inc/header.php'; ?>
  <div class="container mt-5">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="mb-0">📖 My Reading List</h2>
      <a href="gallery.php?user=<?php echo urlencode($_SESSION['username']); ?>" class="btn btn-outline-primary">📚 Back to Gallery</a>
    </div>

    <?php if ($result->num_rows === 0): ?>
      <div class="alert alert-info">You haven’t added any books to your reading list yet.</div>
    <?php else: ?>
      <div class="row">
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $bookId     = (int)$row['book_id'];
            $title      = htmlspecialchars($row['title']);
            $author     = htmlspecialchars($row['author']);
            $genre      = htmlspecialchars($row['genre']);
            $cover      = htmlspecialchars($row['cover_image']);
            $attachment = htmlspecialchars($row['attachment']);
            $progress   = (int)$row['progress'];
          ?>
          <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
              <!-- Cover triggers modal -->
              <img src="<?php echo $cover; ?>" class="card-img-top" alt="Book Cover"
                   style="cursor:pointer;"
                   data-bs-toggle="modal" data-bs-target="#readModal<?php echo $bookId; ?>">
              <div class="card-body">
                <h5 class="card-title"><?php echo $title; ?></h5>
                <h6 class="card-subtitle mb-2 text-muted"><?php echo $author; ?></h6>
                <p><strong>Genre:</strong> <span class="badge bg-secondary"><?php echo $genre; ?></span></p>

                <!-- Remove from Reading List -->
                <form method="POST" action="remove_from_list.php" class="mt-2"
                      onsubmit="return confirm('Are you sure you want to remove this book from your reading list?');">
                  <input type="hidden" name="book_id" value="<?php echo $bookId; ?>">
                  <button type="submit" class="btn btn-outline-danger">🗑️ Remove</button>
                </form>

                <!-- Read Now (in-page modal) -->
                <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#readModal<?php echo $bookId; ?>">
                  📖 Read Now
                </button>
              </div>
            </div>
          </div>

          <!-- Read Now Modal -->
          <div class="modal fade" id="readModal<?php echo $bookId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title"><?php echo $title; ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <iframe src="<?php echo $attachment; ?>" style="width:100%;height:80vh;" frameborder="0"></iframe>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
