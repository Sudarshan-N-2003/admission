<?php
require_once 'auth.php';
require_once __DIR__ . '/../db.php';

$pdo = get_db();

/* ===============================
   INPUTS (OLD + NEW)
================================ */
$q        = trim($_GET['q'] ?? '');
$branch   = $_GET['branch'] ?? '';
$quota    = $_GET['quota'] ?? '';
$through  = $_GET['through'] ?? '';

/* ===============================
   QUERY (NOT REMOVING ANYTHING)
================================ */
$sql = "
SELECT application_id, student_name, mobile,
       allotted_branch, allotted_quota,
       admission_through, created_at
FROM admissions
WHERE 1=1
";

$params = [];

/* SEARCH BY ID OR MOBILE */
if ($q !== '') {
    $sql .= " AND (application_id ILIKE :q OR mobile ILIKE :q)";
    $params[':q'] = "%$q%";
}

/* OLD FILTERS — KEPT */
if ($branch !== '') {
    $sql .= " AND allotted_branch = :branch";
    $params[':branch'] = $branch;
}

if ($quota !== '') {
    $sql .= " AND allotted_quota = :quota";
    $params[':quota'] = $quota;
}

if ($through !== '') {
    $sql .= " AND admission_through = :through";
    $params[':through'] = $through;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>College Dashboard</title>
<link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<div class="container">

<div class="topbar">
  <h2>College Dashboard</h2>
  <a href="logout.php" class="college-btn">Logout</a>
</div>

<!-- SEARCH + OLD FILTERS (UNCHANGED) -->
<form method="get">

<div class="row">
  <div class="col">
    <label>Search (Application ID / Mobile)</label>
    <input type="text" name="q"
      placeholder="Enter Application ID or Mobile"
      value="<?= htmlspecialchars($q) ?>">
  </div>

  <div class="col">
    <label>Branch</label>
    <select name="branch">
      <option value="">All</option>
      <?php
      $branches = ['CSE','AIML','CS (AIML)','CS (DS)','EC','ME','CV'];
      foreach ($branches as $b) {
        $sel = ($branch === $b) ? 'selected' : '';
        echo "<option $sel>$b</option>";
      }
      ?>
    </select>
  </div>

  <div class="col">
    <label>Quota</label>
    <select name="quota">
      <option value="">All</option>
      <?php
      $quotas = ['GM','SNQ','SC','ST','OBC','EWS'];
      foreach ($quotas as $qv) {
        $sel = ($quota === $qv) ? 'selected' : '';
        echo "<option $sel>$qv</option>";
      }
      ?>
    </select>
  </div>

  <div class="col">
    <label>Admission Through</label>
    <select name="through">
      <option value="">All</option>
      <option <?= $through==='KEA'?'selected':'' ?>>KEA</option>
      <option <?= $through==='MANAGEMENT'?'selected':'' ?>>MANAGEMENT</option>
    </select>
  </div>
</div>

<div class="actions">
  <button class="btn-primary">Search</button>
</div>

</form>

<!-- RESULT TABLE (UNCHANGED) -->
<table class="table" style="margin-top:20px">
<tr>
  <th>Application ID</th>
  <th>Student Name</th>
  <th>Mobile</th>
  <th>Branch</th>
  <th>Quota</th>
  <th>Admission</th>
  <th>Date & Time</th>
  <th>Actions</th>
</tr>

<?php if (!$rows): ?>
<tr>
  <td colspan="8" class="center">No records found</td>
</tr>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['application_id']) ?></td>
  <td><?= htmlspecialchars($r['student_name']) ?></td>
  <td><?= htmlspecialchars($r['mobile']) ?></td>
  <td><?= htmlspecialchars($r['allotted_branch']) ?></td>
  <td><?= htmlspecialchars($r['allotted_quota']) ?></td>
  <td><?= htmlspecialchars($r['admission_through']) ?></td>
  <td><?= htmlspecialchars($r['created_at']) ?></td>
  <td>
    <a class="college-btn"
       href="checklist.php?id=<?= urlencode($r['application_id']) ?>">
       Checklist
    </a>
    <a class="college-btn"
       href="print_pdf.php?id=<?= urlencode($r['application_id']) ?>">
       Print
    </a>
  </td>
</tr>
<?php endforeach; ?>

</table>

</div>
</body>
</html>
