<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require 'D:\XAMPP\htdocs\PHPFinalTask\AutoLoader\autoload.php';
use App\Services\UserRepository;
use App\Services\EncryptionService;

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '') $errors[] = 'Current password is required.';
    if (strlen($new) < 6) $errors[] = 'New password must be at least 6 characters.';
    if ($new !== $confirm) $errors[] = 'New passwords do not match.';

    if (empty($errors)) {
        $repo = new UserRepository();
        $user = $repo->findById($_SESSION['user_id']);

        if (!$user || !password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            // Re-wrap the encryption key with the new password
            $enc = new EncryptionService();
            $newWrapped = $enc->reWrapKey($user['enc_key'], $current, $new);

            $repo->updatePassword($user['id'], $new, $newWrapped);

            // Update session: unwrap with new password and re-store
            $rawKey = $enc->unwrapKey($newWrapped, $new);
            $_SESSION['raw_key'] = base64_encode($rawKey);

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password</title>
<style>
<?php include 'style.css.php'; ?>
</style>
</head>
<body>
<div class="container">
  <h2>Change Login Password</h2>
  <p style="color:#666;font-size:13px;margin-bottom:16px">
    Your encryption key will be automatically re-wrapped with the new password.
    All saved passwords remain accessible.
  </p>

  <?php if ($success): ?>
    <p class="success">Password changed successfully! <a href="dashboard.php">Back to Dashboard</a></p>
  <?php else: ?>
    <?php foreach ($errors as $e): ?>
      <p class="error"><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
    <form method="POST">
      <label>Current Password</label>
      <input type="password" name="current_password" required autofocus>
      <label>New Password <small>(min 6 chars)</small></label>
      <input type="password" name="new_password" required>
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required>
      <button type="submit">Change Password</button>
    </form>
    <p class="link"><a href="dashboard.php">← Back to Dashboard</a></p>
  <?php endif; ?>
</div>
</body>
</html>
