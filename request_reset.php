<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "book_library";
$conn = new mysqli($host, $user, $pass, $db);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32)); // secure token
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Store token
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token=?, expires=?");
        $stmt->bind_param("sssss", $email, $token, $expires, $token, $expires);
        $stmt->execute();

        // Send reset link (for now just display)
        $resetLink = "http://localhost/finals/reset_password.php?token=" . $token;
        $message = "✅ Reset link generated: <a href='$resetLink'>$resetLink</a>";
        // In production: send via mail() or PHPMailer
    } else {
        $message = "❌ Email not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Request Password Reset</title></head>
<body>
  <h2>🔑 Request Password Reset</h2>
  <?php if (!empty($message)) echo "<p>$message</p>"; ?>
  <form method="POST">
    <label>Email:</label>
    <input type="email" name="email" required>
    <button type="submit">Send Reset Link</button>
  </form>
</body>
</html>
