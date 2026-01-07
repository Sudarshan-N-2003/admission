<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>College Admission</title>
<link rel="stylesheet" href="assets/styles.css">
</head>

<body>

<a href="/admin/login.php" class="admin-btn" style="float:right">
  College Login
</a>

<div class="container">

<!-- Progress Bar -->
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
<select name="gender" id="gender" required>
  <option value="">Select</option>
  <option value="MALE">Male</option>
  <option value="FEMALE">Female</option>
</select>

<label>Religion</label>
<select name="religion" required>
  <option value="">-- Select Religion --</option>
  <option value="HINDU">HINDU</option>
  <option value="MUSLIM">MUSLIM</option>
  <option value="CHRISTIAN">CHRISTIAN</option>
  <option value="JAIN">JAIN</option>
  <option value="BUDDHIST">BUDDHIST</option>
  <option value="SIKH">SIKH</option>
  <option value="OTHER">OTHER</option>
</select>

<label>Category</label>
<select name="category" required>
  <option value="">Select Category</option>
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
<input type="text" name="sub_caste" required
       placeholder="Eg: Lingayat, Reddy"
       oninput="this.value=this.value.toUpperCase()">

<label>Father / Guardian Name</label>
<input type="text" name="father_name" required oninput="this.value=this.value.toUpperCase()">

<label>Mother / Guardian Name</label>
<input type="text" name="mother_name" required oninput="this.value=this.value.toUpperCase()">

<label>Email</label>
<input type="email" name="email" required>

<label>Mobile Number</label>
<input type="text" name="mobile" pattern="^[0-9]{10}$" required>

<label>Guardian Mobile Number</label>
<input type="text" name="guardian_mobile" pattern="^[0-9]{10}$" required>

<label>Previous College Name</label>
<input type="text" name="prev_college" required oninput="this.value=this.value.toUpperCase()">

<label>Previous Combination</label>
<select name="prev_combination" required>
  <option value="">Select Combination</option>
  <option>PCMB</option>
  <option>PCMC</option>
  <option>DIPLOMA (LATERAL ENTRY)</option>
</select>

<label>Nationality</label>
<select name="nationality" required>
  <option value="INDIAN">INDIAN</option>
  <option>NEPAL</option>
  <option>BANGLADESH</option>
  <option>SRI LANKA</option>
  <option>BHUTAN</option>
  <option>MYANMAR</option>
  <option>OTHER</option>
</select>

<label>State</label>
<select name="state" required>
  <option value="KARNATAKA" selected>Karnataka</option>
  <option>ANDHRA PRADESH</option>
  <option>TAMIL NADU</option>
  <option>KERALA</option>
  <option>MAHARASHTRA</option>
  <option>DELHI</option>
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
</div>

<!-- MANAGEMENT -->
<div id="management_section" class="hidden">
  <label>Allotted Branch</label>
  <select name="allotted_branch_management">
    <option value="">Select Branch</option>
    <option>CSE</option>
    <option>AIML</option>
    <option>EC</option>
    <option>ME</option>
  </select>
</div>

<button type="button" onclick="nextStep()">Next</button>
</div>

<!-- ================= STEP 2 ================= -->
<div id="step2" class="step">

<label>Passport Size Photo</label>
<input type="file" name="passport_photo" accept=".jpg,.jpeg,.png" required>

<label>12th Marks Card (PDF)</label>
<input type="file" name="marks_12" accept="application/pdf" required>

<label>Transfer Certificate (PDF)</label>
<input type="file" name="transfer_certificate" accept="application/pdf" required>

<label>Student Signature</label>
<input type="file" name="student_signature" accept=".jpg,.jpeg,.png" required>

<button type="submit" id="submitBtn" class="btn-primary">
  Submit Application
</button>

</div>

</form>
</div>

<script src="assets/app.js"></script>
<script>
localStorage.removeItem('vvit_admission_draft');
</script>

</body>
</html>
