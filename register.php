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

$message = "";

// Capture return_url from GET (when arriving) so we can include it in the form
$return_url = $_GET['return_url'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $return_url = $_POST['return_url'] ?? $return_url;

  // ✅ Check for duplicate username or email
  $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
  $check->bind_param("ss", $username, $email);
  $check->execute();
  $checkResult = $check->get_result();

  if ($checkResult->num_rows > 0) {
    $message = "⚠️ Username or email already exists. Please choose another.";
  } else {
    // ✅ Password validation
    $uppercase = preg_match('@[A-Z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $special   = preg_match('@[^\w]@', $password);
    $minLength = strlen($password) >= 8;

    if (!$uppercase || !$number || !$special || !$minLength) {
      $message = "❌ Password must be at least 8 characters long and include an uppercase letter, number, and special character.";
    } elseif ($password !== $confirm_password) {
      $message = "❌ Passwords do not match!";
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      // ✅ Handle profile picture upload
      $profilePicPath = "";
      if (!empty($_FILES['profile_picture']['name'])) {
        $picName = $_FILES['profile_picture']['name'];
        $picTmp = $_FILES['profile_picture']['tmp_name'];
        $profilePicPath = "uploads/profiles/" . basename($picName);
        move_uploaded_file($picTmp, $profilePicPath);
      }

      $stmt = $conn->prepare("INSERT INTO users (username, email, password, profile_picture) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $username, $email, $hashedPassword, $profilePicPath);

      if ($stmt->execute()) {
        // Auto-login the new user
        $newId = $conn->insert_id;
        $_SESSION['user_id'] = $newId;
        $_SESSION['username'] = $username;
        // Redirect to return_url if provided (and not an absolute http(s) URL)
        if (!empty($return_url) && strpos($return_url, 'http') === false) {
          header("Location: " . $return_url);
          exit;
        } else {
          header("Location: gallery.php?user=" . urlencode($username));
          exit;
        }
      } else {
        $message = "❌ Error: " . $conn->error;
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>


</head>
<body class="bg-light">
  <div class="container mt-5">
    <h2>📝 Register</h2>
    <?php if (!empty($message)): ?>
  <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
    <?php echo $message; ?>
  </div>
<?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($return_url); ?>">
  <div class="mb-3">
    <label class="form-label">Username</label>
    <input type="text" name="username" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control"
           pattern="(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}"
           title="Must contain at least 8 characters, one uppercase, one number, and one special character"
           required>
  </div>
  <div class="mb-3">
    <label class="form-label">Confirm Password</label>
    <input type="password" name="confirm_password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Profile Picture</label>
    <input type="file" name="profile_picture" class="form-control" accept="image/*">
  </div>
  <button type="submit" class="btn btn-success">Register</button>
  <a href="home.php" class="btn btn-secondary ms-2">Back to Homepage</a>
</form>

  </div>
</body>
</html>
