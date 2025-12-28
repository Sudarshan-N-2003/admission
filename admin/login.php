<?php
session_start();

/* ===============================
   LOAD DATABASE FUNCTION
================================ */
require_once __DIR__ . '/../db.php';

/* ✅ THIS LINE IS CRITICAL */
$pdo = get_db();

/* ===============================
   HANDLE LOGIN
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT id, email, password_hash
         FROM admin_users
         WHERE email = :email"
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {

        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_email'] = $user['email'];

        header("Location: dashboard.php");
        exit;
    }

    $error = "Invalid email or password";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>College Login</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<div class="container">
    <h2>College Login</h2>

    <?php if (!empty($error)): ?>
        <div class="flash error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <div class="actions">
            <button type="submit" class="btn-primary">Login</button>
        </div>
    </form>
</div>

</body>
</html>
