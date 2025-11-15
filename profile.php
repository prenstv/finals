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

// Fetch current user data
$result = $conn->query("SELECT * FROM users WHERE id=$user_id");
$user = $result->fetch_assoc();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";

    $updateSQL = "UPDATE users SET username='$username', email='$email'";

    // Change password if provided
    if (!empty($password)) {
        if ($password !== $confirm_password) {
            $message = "❌ Passwords do not match!";
        } else {
            $uppercase = preg_match('@[A-Z]@', $password);
            $number    = preg_match('@[0-9]@', $password);
            $special   = preg_match('@[^\w]@', $password);
            $minLength = strlen($password) >= 8;

            if (!$uppercase || !$number || !$special || !$minLength) {
                $message = "❌ Password must be at least 8 characters long and include an uppercase letter, number, and special character.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateSQL .= ", password='$hashedPassword'";
            }
        }
    }

    // Handle profile picture upload
    if (empty($message) && !empty($_FILES['profile_picture']['name'])) {
        $picName = basename($_FILES['profile_picture']['name']);
        $picTmp  = $_FILES['profile_picture']['tmp_name'];
        $uploadDir = "uploads/profiles/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $picPath = $uploadDir . $picName;
        if (move_uploaded_file($picTmp, $picPath)) {
            $updateSQL .= ", profile_picture='$picPath'";
        } else {
            $message = "❌ Failed to upload profile picture.";
        }
    }

    $updateSQL .= " WHERE id=$user_id";

    if (empty($message)) {
        if ($conn->query($updateSQL) === TRUE) {
            $_SESSION['username'] = $username;
            $message = "✅ Profile updated successfully!";

            // Refresh user data so new picture shows immediately
            $result = $conn->query("SELECT * FROM users WHERE id=$user_id");
            $user = $result->fetch_assoc();
        } else {
            $message = "❌ Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/inc/header.php'; ?>
  <div class="container mt-5">
    <h2>⚙️ Profile Settings</h2>

    <?php if (!empty($message)): ?>
      <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">New Password (leave blank to keep current)</label>
        <input type="password" name="password" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Profile Picture</label><br>
        <?php
          $currentPic = !empty($user['profile_picture']) ? $user['profile_picture'] : '';
          // Cache-bust by adding timestamp if picture exists
          $picSrc = $currentPic ? ($currentPic . '?t=' . time()) : '#';
        ?>
        <?php if (!empty($currentPic)): ?>
          <img src="<?php echo htmlspecialchars($picSrc); ?>" id="profilePicPreview" class="img-thumbnail mb-2" style="max-height:100px;">
        <?php else: ?>
          <img id="profilePicPreview" src="#" alt="Profile Preview" class="img-thumbnail mb-2" style="max-height:100px; display:none;">
        <?php endif; ?>

        <input type="file" name="profile_picture" class="form-control" accept="image/*" onchange="previewProfilePic(event)">
      </div>

      <button type="submit" class="btn btn-success">Save Changes</button>
      <a href="gallery.php?user=<?php echo urlencode($_SESSION['username']); ?>" class="btn btn-secondary">Back to Gallery</a>
    </form>
  </div>

  <script>
    function previewProfilePic(event) {
      const file = event.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function() {
        const output = document.getElementById('profilePicPreview');
        output.src = reader.result;
        output.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  </script>
</body>
</html>
