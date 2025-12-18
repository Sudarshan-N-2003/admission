<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VVIT Admission</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<div class="container">

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash <?= $_SESSION['flash_type'] ?? '' ?>">
    <?= $_SESSION['flash']; unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
  </div>
<?php endif; ?>

<form method="post" action="submit.php" enctype="multipart/form-data">

<!-- ================= STEP 1 ================= -->
<div class="step active" id="step1">

  <h2>Admission Details</h2>

  <label>Student Name</label>
  <input type="text" name="student_name" required oninput="toUpper(this)">

  <label>Date of Birth</label>
  <input type="date" name="dob" required>

  <label>Gender</label>
  <select name="gender" required>
    <option value="">Select</option>
    <option>MALE</option>
    <option>FEMALE</option>
    <option>OTHER</option>
  </select>

  <label>Father Name</label>
  <input type="text" name="father_name" required oninput="toUpper(this)">

  <label>Mother Name</label>
  <input type="text" name="mother_name" required oninput="toUpper(this)">

  <label>Mobile</label>
  <input type="text" name="mobile" pattern="\d{10}" required>

  <label>Guardian Mobile</label>
  <input type="text" name="guardian_mobile" pattern="\d{10}" required>

  <label>Email</label>
  <input type="email" name="email" required>

  <label>State</label>
  <input type="text" name="state" required oninput="toUpper(this)">

  <label>Previous Combination</label>
  <select name="prev_combination" required>
    <option value="">Select</option>
    <option>PCMB</option>
    <option>PCMC</option>
    <option>DIPLOMA</option>
  </select>

  <label>Previous College Name</label>
  <input type="text" name="prev_college" required oninput="toUpper(this)">

  <label>Permanent Address</label>
  <textarea name="permanent_address" required></textarea>

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

    <label>Seat Category</label>
    <select name="seat_allotted">
      <option>GM</option><option>SC</option><option>ST</option>
      <option>OBC</option><option>SNQ</option>
    </select>

    <label>Allotted Branch</label>
    <select name="allotted_branch">
      <option>CSE</option><option>AIML</option><option>EC</option>
      <option>ME</option><option>CV</option>
    </select>
  </div>

  <!-- MANAGEMENT DETAILS -->
  <div id="management_section" class="hidden">
    <label>Branch</label>
    <select name="allotted_branch_management">
      <option>CSE</option><option>AIML</option><option>EC</option>
      <option>ME</option><option>CV</option>
    </select>
  </div>

  <button type="button" onclick="nextStep()">Next</button>
</div>

<!-- ================= STEP 2 ================= -->
<div class="step" id="step2">

  <h2>Upload Documents</h2>

  <label>10+12 / Equivalent Marks Card (PDF)</label>
  <input type="file" name="marks_12" accept="application/pdf" required>

  <label>Transfer Certificate (PDF)</label>
  <input type="file" name="transfer_certificate" accept="application/pdf" required>

  <label>Study Certificate (PDF)</label>
  <input type="file" name="study_certificate" accept="application/pdf" required>

  <label>Student Signature (JPG / PNG)</label>
  <input type="file" name="student_signature" accept="image/*" required>

  <div id="kea_doc" class="hidden">
    <label>KEA Payment Acknowledgement (PDF)</label>
    <input type="file" name="kea_acknowledgement" accept="application/pdf">
  </div>

  <div id="management_doc" class="hidden">
    <label>College Fees Receipt (PDF)</label>
    <input type="file" name="management_receipt" accept="application/pdf">
  </div>

  <button type="button" onclick="prevStep()">Back</button>
  <button type="submit">Submit</button>
</div>

</form>
</div>

<script src="assets/app.js"></script>
</body>
</html>
