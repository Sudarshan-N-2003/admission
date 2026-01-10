<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>College Admission</title>

<link rel="stylesheet" href="assets/styles.css">

<style>
.admin-btn{
  position:fixed;
  top:15px;
  right:15px;
  background:#1e40af;
  color:#fff;
  padding:8px 14px;
  border-radius:6px;
  text-decoration:none;
  font-size:13px;
  z-index:9999;
}
</style>
</head>

<body>

<a href="/admin/login.php" class="admin-btn">College Login</a>

<div class="container">

<!-- Progress -->
<div class="progress-wrap">
  <div class="progress">
    <div class="progress-bar" id="progressBar"></div>
  </div>
  <div class="progress-steps">
    <span class="step-label active" id="labelStep1">Step 1: Details</span>
    <span class="step-label" id="labelStep2">Step 2: Uploads</span>
  </div>
</div>

<h2>College Admission Form</h2>

<?php if (!empty($_SESSION['flash'])): ?>
<div class="flash <?= $_SESSION['flash_type'] ?? '' ?>">
  <?= $_SESSION['flash']; unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
</div>
<?php endif; ?>

<form method="post" action="submit.php" enctype="multipart/form-data">

<!-- ================= STEP 1 ================= -->
<div id="step1" class="step active">

<label>Student Name</label>
<input type="text" name="student_name" required oninput="this.value=this.value.toUpperCase()">

<label>Date of Birth</label>
<input type="date" name="dob" required>

<label>Gender</label>
<select name="gender" required>
  <option value="">Select</option>
  <option>MALE</option>
  <option>FEMALE</option>
</select>

<label>Religion</label>
<select name="religion" required>
  <option value="">Select</option>
  <option>HINDU</option>
  <option>MUSLIM</option>
  <option>CHRISTIAN</option>
  <option>JAIN</option>
  <option>BUDDHIST</option>
  <option>SIKH</option>
  <option>OTHER</option>
</select>

<label>Category</label>
<select name="category" required>
  <option value="">Select</option>
  <option>CAT 1</option>
  <option>2A</option>
  <option>2B</option>
  <option>3A</option>
  <option>3B</option>
  <option>SC</option>
  <option>ST</option>
  <option>NOT APPLICABLE</option>
</select>

<label>Sub Caste</label>
<input type="text" name="sub_caste" required>

<label>Father / Guardian Name</label>
<input type="text" name="father_name" required>

<label>Mother / Guardian Name</label>
<input type="text" name="mother_name" required>

<label>Email</label>
<input type="email" name="email" required>

<label>Mobile Number</label>
<input type="text" name="mobile" pattern="[0-9]{10}" required>
<small id="dupMessage" class="muted"></small>

<label>Guardian Mobile</label>
<input type="text" name="guardian_mobile" pattern="[0-9]{10}" required>

<label>Previous College</label>
<input type="text" name="prev_college" required>

<label>Previous Combination</label>
<select name="prev_combination" required>
  <option value="">Select</option>
  <option>PCMB</option>
  <option>PCMC</option>
  <option>DIPLOMA (LATERAL ENTRY)</option>
</select>

<label>Nationality</label>
<select name="nationality" id="nationality" required>
  <option value="">Select Nationality</option>
  <option value="INDIAN">INDIAN</option>
  <option value="NEPAL">NEPAL</option>
  <option value="SRI LANKA">SRI LANKA</option>
  <option value="BANGLADESH">BANGLADESH</option>
  <option value="BHUTAN">BHUTAN</option>
  <option value="MYANMAR">MYANMAR</option>
  <option value="OTHER">OTHER</option>
</select>

<label>State / Province</label>
<select name="state" id="state" required>
  <option value="">Select State / Province</option>
</select>


<label>Permanent Address</label>
<textarea name="permanent_address" required></textarea>

<label>Admission Through</label>
<select name="admission_through" id="admission_through" required>
  <option value="">Select</option>
  <option value="KEA">KEA</option>
  <option value="MANAGEMENT">MANAGEMENT</option>
</select>

<!-- KEA -->
<div id="kea_section" class="hidden">
  <label>CET Number</label>
  <input type="text" name="cet_number">

  <label>CET Rank</label>
  <input type="text" name="cet_rank">

  <label>Allotted Quota</label>
  <select name="seat_allotted">
    <option value="">Select</option>
    <option>GM</option>
    <option>SNQ</option>
    <option>SC</option>
    <option>ST</option>
    <option>OBC</option>
    <option>GMR</option>
    <option>GMK</option>
    <option>EWS</option>
    <option>OTHER</option>
  </select>

  <label>Allotted Branch</label>
  <select name="allotted_branch">
    <option value="">Select</option>
    <option>CSE</option>
    <option>CS (AIML)</option>
    <option>CS (DS)</option>
    <option>AIML</option>
    <option>EC</option>
    <option>ME</option>
    <option>CIVIL</option>
  </select>
</div>

<!-- MANAGEMENT -->
<div id="management_section" class="hidden">
  <label>Allotted Branch</label>
  <select name="allotted_branch_management">
    <option value="">Select</option>
    <option>CSE</option>
    <option>CS (AIML)</option>
    <option>CS (DS)</option>
    <option>AIML</option>
    <option>EC</option>
    <option>ME</option>
    <option>CIVIL</option>
  </select>
</div>

<button type="button" onclick="nextStep()">Next</button>

</div>

<!-- ================= STEP 2 ================= -->
<div id="step2" class="step">

<label>Passport Photo</label>
<input type="file" name="passport_photo" required>

<label>10 + 12 Marks Card (PDF)</label>
<input type="file" name="marks_12" required>

<label>Transfer Certificate</label>
<input type="file" name="transfer_certificate" required>

<label>Study Certificate</label>
<input type="file" name="study_certificate" required>

<label>Student Signature</label>
<input type="file" name="student_signature" required>

<div id="kea_doc" class="hidden">
  <label>KEA Acknowledgement</label>
  <input type="file" name="kea_acknowledgement">
</div>

<div id="management_doc" class="hidden">
  <label>College Fee Receipt</label>
  <input type="file" name="management_receipt">
</div>

<button type="button" class="btn-primary" onclick="openPreview()">Preview</button>

</div>

<!-- PREVIEW MODAL -->
<div id="previewModal" class="modal hidden">
  <div class="modal-content">
    <h3>Confirm Details</h3>
    <div id="previewContent"></div>

    <div class="actions">
      <button type="button" class="btn-grey" onclick="closePreview()">Edit</button>
      <button type="submit" class="btn-primary" id="submitBtn">Confirm & Submit</button>
    </div>
  </div>
</div>

</form>
</div>

<script src="assets/app.js"></script>
</body>
</html>
