<?php
// admin_login.php
session_start();

// Simple env-based admin credentials. Set in .env or environment variables in Render:
// ADMIN_USER and ADMIN_PASS
$adminUser = getenv('ADMIN_USER') ?: 'admin';
$adminPass = getenv('ADMIN_PASS') ?: 'changeme';

if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: admin_panel.php'); exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if ($u === $adminUser && $p === $adminPass) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user'] = $u;
        header('Location: admin_panel.php'); exit;
    } else {
        $err = 'Invalid credentials';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Login — VVIT</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="admin-box">
    <h2>VVIT — Admin Login</h2>
    <?php if ($err): ?><div class="flash error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
    <form method="post">
      <label>Username</label>
      <input name="username" required>
      <label>Password</label>
      <input name="password" type="password" required>
      <div class="actions">
        <button type="submit">Login</button>
      </div>
    </form>
  </div>
</body>
</html>
