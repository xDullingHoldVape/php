<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

require 'D:\XAMPP\htdocs\PHPFinalTask\AutoLoader\autoload.php';
use App\Services\UserRepository;
use App\Services\EncryptionService;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $repo = new UserRepository();
    $user = $repo->findByUsername($username);

    if ($user && password_verify($password, $user['password'])) {
        // Unwrap the encryption key and keep it in session (raw binary to base64 for storage)
        $enc = new EncryptionService();
        $rawKey = $enc->unwrapKey($user['enc_key'], $password);

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['raw_key']  = base64_encode($rawKey); // store safely in session
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login — Password Manager</title>
<style>
<?php include 'style.css.php'; ?>
</style>
</head>
<body>
<div class="container">
  <h2>Login</h2>

  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST">
    <label>Username</label>
    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
  </form>
  <p class="link">No account? <a href="register.php">Register</a></p>
</div>
</body>
</html>
