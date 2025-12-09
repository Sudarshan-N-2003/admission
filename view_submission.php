<?php
// view_submission.php - shows full submission and allows marking documents received
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: admin_login.php'); exit;
}

$id = $_GET['id'] ?? '';
if (!$id) { echo 'Missing id'; exit; }

$path = __DIR__ . '/submissions/' . basename($id) . '.json';
if (!file_exists($path)) { echo 'Submission not found'; exit; }

$record = json_decode(file_get_contents($path), true);
if (!$record) { echo 'Invalid record'; exit; }

// Handle mark received form (posted back here)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_docs'])) {
    // Expect an array like submitted_dates[field] = date or empty
    $submitted = $_POST['submitted_date'] ?? [];
    // Build submitted docs array with date values
    $record['submitted_documents'] = $record['submitted_documents'] ?? [
        'marks_cards' => '',
        'transfer_certificate' => '',
        'study_certificate' => '',
        'cast_income' => '',
        'passport_photo' => ''
    ];
    foreach ($record['submitted_documents'] as $k=>$v) {
        if (!empty($submitted[$k])) {
            $record['submitted_documents'][$k] = $submitted[$k];
        }
    }
    // Save back
    file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT));
    $saved_msg = 'Document dates updated.';
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Submission <?php echo htmlspecialchars($id); ?></title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="admin-header">
    <h1>Submission: <?php echo htmlspecialchars($record['id']); ?></h1>
    <div class="right">
      <a href="admin_panel.php">Back to list</a> |
      <a href="admin_actions.php?action=download&id=<?php echo urlencode($record['id']); ?>">Download JSON</a> |
      <a href="admin_actions.php?action=download_pdf&id=<?php echo urlencode($record['id']); ?>">Download PDF</a>
    </div>
  </div>

  <?php if (!empty($saved_msg)): ?><div class="flash success"><?php echo htmlspecialchars($saved_msg); ?></div><?php endif; ?>

  <div class="submission-block">
    <h2>Student Details</h2>
    <table class="detail">
      <?php foreach ($record['data'] as $k=>$v): ?>
        <tr><th><?php echo htmlspecialchars(str_replace('_',' ', ucfirst($k))); ?></th><td><?php echo htmlspecialchars(is_array($v)? json_encode($v): $v); ?></td></tr>
      <?php endforeach; ?>
    </table>

    <h2>Files</h2>
    <ul>
      <?php foreach ($record['files'] as $k=>$v): ?>
        <li><?php echo htmlspecialchars($k); ?>:
          <?php if ($v && file_exists($v)): ?>
            <a target="_blank" href="<?php echo htmlspecialchars($v); ?>">Open</a>
          <?php else: ?>
            <?php echo htmlspecialchars($v ?: 'N/A'); ?>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <h2>Submitted Documents (mark dates)</h2>
    <form method="post">
      <?php
        $defaults = $record['submitted_documents'] ?? [];
        $keys = [
          'marks_cards'=>'Marks cards',
          'transfer_certificate'=>'Transfer Certificate',
          'study_certificate'=>'Study certificate',
          'cast_income'=>'Cast & income',
          'passport_photo'=>'Passport size photo'
        ];
        foreach ($keys as $field=>$label):
      ?>
        <div class="doc-row">
          <label><?php echo $label; ?></label>
          <input type="date" name="submitted_date[<?php echo $field; ?>]" value="<?php echo htmlspecialchars($defaults[$field] ?? ''); ?>">
        </div>
      <?php endforeach; ?>
      <div style="margin-top:12px">
        <button name="mark_docs" type="submit">Save Dates</button>
      </div>
    </form>
  </div>
</body>
</html>
