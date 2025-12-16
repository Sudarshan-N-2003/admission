<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>VVIT Admission Registration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<div class="container">

  <!-- HEADER -->
  <div class="topbar">
    <h2>Admission Registration</h2>
    <a href="#" class="college-btn">College</a>
  </div>

  <!-- FLASH MESSAGE -->
  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash <?php echo $_SESSION['flash_type'] ?? 'info'; ?>">
      <?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?>
    </div>
  <?php endif; ?>

  <!-- FORM -->
  <form id="admissionForm" method="post" enctype="multipart/form-data" action="submit.php">

    <!-- ================= STEP 1 ================= -->
    <div class="step active" id="step1">

      <div class="row">
        <div class="col">
          <label>Student Name</label>
          <input type="text" name="student_name" required oninput="toUpper(this)">
        </div>
        <div class="col narrow">
          <label>Date of Birth</label>
          <input type="date" name="dob" required>
        </div>
      </div>

      <!-- Gender -->
      <div class="row">
        <div class="col">
          <label>Gender</label>
          <div class="radio-group">
            <label><input type="radio" name="gender" value="MALE" required> Male</label>
            <label><input type="radio" name="gender" value="FEMALE"> Female</label>
            <label><input type="radio" name="gender" value="OTHER"> Other</label>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Father Name</label>
          <input type="text" name="father_name" required oninput="toUpper(this)">
        </div>
        <div class="col">
          <label>Mother Name</label>
          <input type="text" name="mother_name" required oninput="toUpper(this)">
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Mobile Number</label>
          <input type="text" name="mobile" pattern="\d{10}" required>
        </div>
        <div class="col">
          <label>Aadhaar Number</label>
          <input type="text" name="aadhaar_number" pattern="\d{12}" required>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Email</label>
          <input type="email" name="email" required>
        </div>
        <div class="col">
          <label>State</label>
          <select name="state" required>
            <option value="">-- Select State --</option>
            <option>KARNATAKA</option>
            <option>TAMIL NADU</option>
            <option>KERALA</option>
            <option>ANDHRA PRADESH</option>
            <option>TELANGANA</option>
            <option>OTHER</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Admission Through</label>
          <select name="admission_through" id="admission_through" required onchange="onAdmissionChange()">
            <option value="">-- Select --</option>
            <option value="KEA">KEA</option>
            <option value="MANAGEMENT">MANAGEMENT</option>
          </select>
        </div>
      </div>

      <!-- KEA SECTION -->
      <div id="kea_section" style="display:none">

        <h4>KEA Details</h4>

        <div class="row">
          <div class="col">
            <label>CET Number</label>
            <input type="text" name="cet_number">
          </div>
          <div class="col">
            <label>CET Rank</label>
            <input type="text" name="cet_rank">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label>Seat Allotted Category</label>
            <select name="seat_allotted">
              <option>GM</option>
              <option>SC</option>
              <option>ST</option>
              <option>OBC</option>
              <option>EWS</option>
            </select>
          </div>
          <div class="col">
            <label>Allotted Branch</label>
            <select name="allotted_branch">
              <option value="">-- Select Branch --</option>
              <option>CSE</option>
              <option>AIML</option>
              <option>EC</option>
              <option>ME</option>
            </select>
          </div>
        </div>

      </div>

      <!-- MANAGEMENT SECTION -->
      <div id="management_section" style="display:none">

        <h4>Management Admission</h4>

        <div class="row">
          <div class="col">
            <label>Allotted Branch</label>
            <select name="allotted_branch_management">
              <option value="">-- Select Branch --</option>
              <option>CSE</option>
              <option>AIML</option>
              <option>EC</option>
              <option>ME</option>
            </select>
          </div>
        </div>

      </div>

      <div class="actions">
        <button type="button" class="btn-primary" onclick="nextStep()">Next</button>
        <button type="button" onclick="alert('JS WORKING')">Test JS</button>
      </div>

    </div>

    <!-- ================= STEP 2 ================= -->
    <div class="step" id="step2">

      <h3>Upload Documents</h3>
      <p class="muted">All files max size 1MB. Photo JPG/JPEG, documents PDF.</p>

      <div class="row">
        <div class="col">
          <label>Passport Size Photo</label>
          <input type="file" name="photo" accept="image/jpeg" required>
        </div>
        <div class="col">
          <label>Previous Year Marks Card (PDF)</label>
          <input type="file" name="marks" accept="application/pdf" required>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Aadhaar Front</label>
          <input type="file" name="aadhaar_front">
        </div>
        <div class="col">
          <label>Aadhaar Back</label>
          <input type="file" name="aadhaar_back">
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Caste & Income Certificate (PDF)</label>
          <input type="file" name="caste_income">
        </div>
      </div>

      <div class="actions">
        <button type="button" class="btn-grey" onclick="prevStep()">Back</button>
        <button type="submit" class="btn-primary">Submit</button>
      </div>

    </div>

  </form>

</div>

<script src="assets/app.js"></script>
</body>
</html>
