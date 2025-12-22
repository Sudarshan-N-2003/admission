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

<div class="container">

<h2>College Admission Form</h2>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash <?= $_SESSION['flash_type'] ?? '' ?>">
    <?= $_SESSION['flash']; unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
  </div>
<?php endif; ?>

<form method="post" action="submit.php" enctype="multipart/form-data">

<!-- ================= STEP 1 ================= -->
<div class="step active" id="step1">

  <label>Student Name</label>
  <input type="text" name="student_name" required oninput="this.value=this.value.toUpperCase()">

  <label>Date of Birth</label>
  <input type="date" name="dob" required>

<label>Gender</label>
  <select name="gender" id=gender" required>
    <option value="">Select</option>
    <option value="MALE">Male</option>
    <option value="FEMALE">Female</option>
  </select>

  <label>Mobile Number</label>
  <input type="text" name="mobile" pattern="\d{10}" required>

  <label>Guardian Mobile Number</label>
  <input type="text" name="guardian_mobile" pattern="\d{10}" required>

  <label>Previous College Name</label>
  <input type="text" name="prev_college" required oninput="this.value=this.value.toUpperCase()">

  <label>Permanent Address</label>
  <textarea name="permanent_address" required></textarea>

  <label>Admission Through</label>
  <select name="admission_through" id="admission_through" required>
    <option value="">Select</option>
    <option value="KEA">KEA</option>
    <option value="MANAGEMENT">MANAGEMENT</option>
  </select>

  <!-- KEA DETAILS -->
  <div id="kea_section" class="hidden">
    <label>CET Number</label>
    <input type="text" name="cet_number">

    <label>CET Rank</label>
    <input type="text" name="cet_rank">
  </div>

  <!-- MANAGEMENT DETAILS -->
  <div id="management_section" class="hidden">
    <label>Allotted Branch</label>
    <select name="management_branch">
      <option>CSE</option>
      <option>AIML</option>
      <option>EC</option>
      <option>ME</option>
    </select>
  </div>

  <button type="button" onclick="nextStep()">Next</button>
</div>

<!-- ================= STEP 2 ================= -->
<div class="step" id="step2">

  <label>10 + 12 / Equivalent Marks Card (PDF)</label>
  <input type="file" name="marks_12" accept="application/pdf" required>

  <label>Transfer Certificate (PDF)</label>
  <input type="file" name="transfer_certificate" accept="application/pdf" required>

  <label>Student Signature (JPG / PNG)</label>
  <input type="file" name="student_signature" accept="image/*" required>

  <!-- KEA DOC -->
  <div id="kea_doc" class="hidden">
    <label>KEA Payment Acknowledgement (PDF)</label>
    <input type="file"
           name="kea_acknowledgement"
           id="kea_acknowledgement"
           accept="application/pdf">
  </div>

  <!-- MANAGEMENT DOC -->
  <div id="management_doc" class="hidden">
    <label>College Fees Receipt (PDF)</label>
    <input type="file"
           name="management_receipt"
           id="management_receipt"
           accept="application/pdf">
  </div>

  <button type="button" class="secondary" onclick="prevStep()">Back</button>
  <button type="submit">Submit</button>
</div>

</form>
</div>

<script src="assets/app.js"></script>
</body>
</html>
