<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>College Admission</title>
<link rel="stylesheet" href="assets/styles.css">
<a href="/admin/login.php" class="admin-btn" style="float:right">
  College Login
</a>
</head>
<body>

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


<div class="col">
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
  </div>
</div>


  
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
<input type="text"
       name="sub_caste" required
       placeholder="Eg: Lingayat, Reddy"
       oninput="this.value=this.value.toUpperCase()">

<label>Father/Gaurdian Name</label>
  <input type="text" name="father_name" required oninput="this.value=this.value.toUpperCase()">

<label>Mother/Gaurdian Name</label>
  <input type="text" name="mother_name" required oninput="this.value=this.value.toUpperCase()">

<label>Email</label>
<input type="email" name="email" required placeholder="Email">

  <label>Mobile Number</label>
  <input type="text" name="mobile" pattern="\d{10}" required>

  <label>Guardian Mobile Number</label>
  <input type="text" name="guardian_mobile" pattern="\d{10}" required>

  <label>Previous College Name</label>
  <input type="text" name="prev_college" required oninput="this.value=this.value.toUpperCase()">


<label>Previous Combination</label>
<select name="prev_combination" required>
  <option value="">Select Combination</option>
  <option>PCMB</option>
  <option>PCMC</option>
  <option>DIPLOMA (LATERAL ENTRY)</option>
</select>


<div class="row">
  <div class="col">
    <label>Nationality</label>
    <select name="nationality" required>
      <option value="INDIAN">INDIAN</option>
      <option value="NEPAL">NEPAL</option>
      <option value="BANGLADESH">BANGLADESH</option>
      <option value="SRI LANKA">SRI LANKA</option>
      <option value="BHUTAN">BHUTAN</option>
      <option value="MYANMAR">MYANMAR</option>
      <option value="OTHER">OTHER</option>
    </select>
  </div>
  
<label>State</label>
<select name="state" required>
  <option value="KARNATAKA" selected>Karnataka</option>

  <option>ANDHRA PRADESH</option>
  <option>ARUNACHAL PRADESH</option>
  <option>ASSAM</option>
  <option>BIHAR</option>
  <option>CHHATTISGARH</option>
  <option>GOA</option>
  <option>GUJARAT</option>
  <option>HARYANA</option>
  <option>HIMACHAL PRADESH</option>
  <option>JHARKHAND</option>
  <option>KERALA</option>
  <option>MADHYA PRADESH</option>
  <option>MAHARASHTRA</option>
  <option>MANIPUR</option>
  <option>MEGHALAYA</option>
  <option>MIZORAM</option>
  <option>NAGALAND</option>
  <option>ODISHA</option>
  <option>PUNJAB</option>
  <option>RAJASTHAN</option>
  <option>SIKKIM</option>
  <option>TAMIL NADU</option>
  <option>TELANGANA</option>
  <option>TRIPURA</option>
  <option>UTTAR PRADESH</option>
  <option>UTTARAKHAND</option>
  <option>WEST BENGAL</option>

  <!-- Union Territories -->
  <option>ANDAMAN AND NICOBAR ISLANDS</option>
  <option>CHANDIGARH</option>
  <option>DADRA AND NAGAR HAVELI AND DAMAN AND DIU</option>
  <option>DELHI</option>
  <option>JAMMU AND KASHMIR</option>
  <option>LADAKH</option>
  <option>LAKSHADWEEP</option>
  <option>PUDUCHERRY</option>
</select>

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

  <label>Allotted Quota</label>
  <select name="seat_allotted">
    <option value="">Select Quota</option>
    <option>GM</option>
    <option>SNQ</option>
    <option>SC</option>
    <option>ST</option>
    <option>OBC</option>
    <option>GMR</option>
    <option>GMK</option>
    <option>KK / HK</option>
    <option>EWS</option>
    <option>SPL (NCC / SPORTS / DEFENCE / PWD)</option>
  </select>

  <label>Allotted Branch</label>
  <select name="allotted_branch">
    <option value="">Select Branch</option>
    <option>CSE</option>
    <option>AIML</option>
    <option>CS (AIML)</option>
    <option>CS (DS)</option>
    <option>EC</option>
    <option>CV</option>
    <option>ME</option>
  </select>

</div>

  <!-- MANAGEMENT DETAILS -->
<div id="management_section" class="hidden">
  <label>Allotted Branch</label>
  <select name="allotted_branch_management">
    <option value="">Select Branch</option>
    <option>CSE</option>
    <option>AIML</option>
    <option>CS (AIML)</option>
    <option>CS (DS)</option>
    <option>EC</option>
    <option>CV</option>
    <option>ME</option>
  </select>
</div>

  <button type="button" onclick="nextStep()">Next</button>
</div>

<!-- ================= STEP 2 ================= -->
<div class="step" id="step2">

 <label>Passport Size Photo *</label>
    <input type="file" name="passport_photo" accept=".jpg,.jpeg,.png" required>

  <label>10 + 12 / Equivalent Marks Card (PDF)</label>
  <input type="file" name="marks_12" accept="application/pdf" required>

  <label>Transfer Certificate (PDF)</label>
  <input type="file" name="transfer_certificate" accept="application/pdf" required>

<label>Study Certificate (PDF)</label>
<input type="file"
       name="study_certificate"
       accept="application/pdf"
       required>

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
