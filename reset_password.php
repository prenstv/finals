<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "book_library";
$conn = new mysqli($host, $user, $pass, $db);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $message = "❌ Passwords do not match!";
    } else {
        // Check token
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token=? AND expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $email = $row['email'];

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
            $stmt->bind_param("ss", $hashedPassword, $email);
            $stmt->execute();

            // Delete token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token=?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $message = "✅ Password reset successfully!";
        } else {
            $message = "❌ Invalid or expired token!";
        }
    }
} else {
    $token = $_GET['token'] ?? '';
}
?>
<!DOCTYPE html>
<html>
<head><title>Reset Password</title></head>
<body>
  <h2>🔑 Reset Your Password</h2>
  <?php if (!empty($message)) echo "<p>$message</p>"; ?>
  <form method="POST">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <label>New Password:</label>
    <input type="password" name="new_password" required>
    <label>Confirm Password:</label>
    <input type="password" name="confirm_password" required>
    <button type="submit">Reset Password</button>
  </form>
</body>
</html>
