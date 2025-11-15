<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
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

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $conn->query("DELETE FROM books WHERE id = $id");
  header("Location: dashboard.php");
  exit;
}

$result = $conn->query("SELECT * FROM books ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>


</head>
<body class="bg-light">
<?php require_once __DIR__ . '/inc/header.php'; ?>

  <div class="container mt-5">
    <h2 class="mb-4">📋 Manage Uploaded Books</h2>
    <a href="gallery.php" class="btn btn-success mt-3">📖 View Book Gallery</a>
    <table class="table table-bordered table-hover">
      <thead class="table-dark">
        <tr>
          <th>Cover</th>
          <th>Title</th>
          <th>Author</th>
          <th>Genre</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><img src="<?php echo $row['cover_image']; ?>" style="height: 60px;"></td>
          <td><?php echo htmlspecialchars($row['title']); ?></td>
          <td><?php echo htmlspecialchars($row['author']); ?></td>
          <td><?php echo htmlspecialchars($row['genre']); ?></td>
          <td>
            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
            <a href="dashboard.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this book?')">🗑️ Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>

<?php $conn->close(); ?>
