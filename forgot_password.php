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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $message = "❌ Passwords do not match!";
    } else {
        // Basic strength check
        $uppercase = preg_match('@[A-Z]@', $newPassword);
        $number    = preg_match('@[0-9]@', $newPassword);
        $special   = preg_match('@[^\w]@', $newPassword);
        $minLength = strlen($newPassword) >= 8;

        if (!$uppercase || !$number || !$special || !$minLength) {
            $message = "❌ Password must be at least 8 characters long and include an uppercase letter, number, and special character.";
        } else {
            // Verify username and email match
            $verify = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
            $verify->bind_param("ss", $username, $email);
            $verify->execute();
            $verifyResult = $verify->get_result();

            if ($verifyResult->num_rows === 0) {
                $message = "❌ Username and email do not match any account.";
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ? AND email = ?");
                $stmt->bind_param("sss", $hashedPassword, $username, $email);

                if ($stmt->execute()) {
                    $message = "✅ Password updated successfully!";
                } else {
                    $message = "❌ Update failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>


</head>
<body class="bg-light">
  <div class="container mt-5">
    <h2>🔑 Reset Your Password</h2>
    <?php if (!empty($message)): ?>
      <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Reset Password</button>
      <a href="home.php" class="btn btn-link">🔙 Back to Login</a>
    </form>
  </div>
</body>
</html>
