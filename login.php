<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "book_library";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
  $return_url = $_POST['return_url'] ?? '';

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['username'] = $user['username'];
          // Validate return_url to avoid open redirects
          if (!empty($return_url) && strpos($return_url, 'http') === false) {
            header("Location: " . $return_url);
          } else {
            header("Location: gallery.php?user=" . urlencode($user['username']));
          }
          exit;
        } else {
            $error = "❌ Invalid password!";
        }
    } else {
        $error = "❌ User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>


</head>
<body class="bg-light">
  <div class="container mt-5">
    <h2>🔐 Login</h2>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
      <div class="mt-3">
        <a href="register.php" class="btn btn-link">Register</a>
        <a href="forgot_password.php" class="btn btn-link text-danger">Forgot Password?</a>
      </div>
    </form>
  </div>
</body>
</html>
