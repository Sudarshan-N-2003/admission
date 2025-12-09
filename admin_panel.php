<?php
// admin_panel.php - lists submissions with basic search and pagination
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: admin_login.php'); exit;
}

$dir = __DIR__ . '/submissions';
$files = [];
if (is_dir($dir)) {
    $all = scandir($dir);
    foreach ($all as $f) {
        if (preg_match('/^1VJ\\d{2}\\d{3}\\.json$/', $f)) {
            $files[] = $f;
        }
    }
    // newest first
    usort($files, function($a,$b) use ($dir){ return filemtime("$dir/$b") - filemtime("$dir/$a"); });
}

// simple search by id or student name
$query = trim($_GET['q'] ?? '');
$filtered = [];
foreach ($files as $f) {
    $path = "$dir/$f";
    $json = json_decode(file_get_contents($path), true);
    if (!$json) continue;
    $name = $json['data']['student_name'] ?? '';
    $id = $json['id'] ?? '';
    if ($query === '' || stripos($id, $query) !== false || stripos($name, $query) !== false) {
        $filtered[] = ['file'=>$f,'id'=>$id,'name'=>$name,'created'=>($json['created_at'] ?? date('c', filemtime($path)))];
    }
}

// pagination
$perPage = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$total = count($filtered);
$pages = max(1, ceil($total / $perPage));
$start = ($page-1)*$perPage;
$paginated = array_slice($filtered, $start, $perPage);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Panel — VVIT</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="admin-header">
    <h1>Admissions — Admin Panel</h1>
    <div class="right">
      Logged in as <?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'admin'); ?> |
      <a href="admin_actions.php?action=logout">Logout</a>
    </div>
  </div>

  <div class="admin-controls">
    <form method="get" style="display:flex;gap:8px;align-items:center">
      <input name="q" placeholder="Search by ID or Name" value="<?php echo htmlspecialchars($query); ?>">
      <button type="submit">Search</button>
    </form>
  </div>

  <div class="admin-list">
    <table>
      <thead><tr><th>ID</th><th>Student</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($paginated as $row): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['id']); ?></td>
          <td><?php echo htmlspecialchars($row['name']); ?></td>
          <td><?php echo htmlspecialchars($row['created']); ?></td>
          <td>
            <a href="view_submission.php?id=<?php echo urlencode($row['id']); ?>">View</a> |
            <a href="admin_actions.php?action=download&id=<?php echo urlencode($row['id']); ?>">Download JSON</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="pagination">
      <?php if ($page>1): ?><a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page-1; ?>">&laquo; Prev</a><?php endif; ?>
      Page <?php echo $page; ?> of <?php echo $pages; ?>
      <?php if ($page<$pages): ?><a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page+1; ?>">Next &raquo;</a><?php endif; ?>
    </div>
  </div>
</body>
</html>
