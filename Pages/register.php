<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

require __DIR__ . '/../AutoLoader/autoload.php';
use App\Services\UserRepository;
use App\Services\EncryptionService;

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($username) < 3)                        $errors[] = 'Username must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Invalid email address.';
    if (strlen($password) < 6)                        $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                        $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $repo = new UserRepository();
        if ($repo->usernameExists($username)) $errors[] = 'Username already taken.';
        if ($repo->emailExists($email))       $errors[] = 'Email already registered.';
    }

    if (empty($errors)) {
        $enc        = new EncryptionService();
        $rawKey     = $enc->generateKey();          // random 256-bit key
        $wrappedKey = $enc->wrapKey($rawKey, $password); // encrypt key with plain password

        $repo = new UserRepository();
        $repo->create($username, $email, $password, $wrappedKey);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register — Password Manager</title>
<style>
<?php include 'style.css.php'; ?>
</style>
</head>
<body>
<div class="container">
  <h2>Create Account</h2>

  <?php if ($success): ?>
    <p class="success">Account created! <a href="login.php">Login here →</a></p>
  <?php else: ?>
    <?php foreach ($errors as $e): ?>
      <p class="error"><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>

    <form method="POST">
      <label>Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

      <label>Password <small>(min 6 chars)</small></label>
      <input type="password" name="password" required>

      <label>Confirm Password</label>
      <input type="password" name="confirm" required>

      <button type="submit">Register</button>
    </form>
    <p class="link">Already have an account? <a href="login.php">Login</a></p>
  <?php endif; ?>
</div>
</body>
</html>