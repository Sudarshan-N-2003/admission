<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>VVIT — Admission Registration</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container">
    <header>
      <h2>Admission Registration</h2>
      <a class="college-btn" href="#">College</a>
    </header>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="flash <?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?>"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
    <?php endif; ?>

    <form id="admissionForm" method="post" enctype="multipart/form-data" action="submit.php">
      <!-- STEP 1 -->
      <div class="step active" id="step1">
        <div class="row">
          <div style="flex:1">
            <label>Student name</label>
            <input type="text" name="student_name" required oninput="toUpper(this)">
          </div>
          <div style="width:190px">
            <label>Date of birth</label>
            <input type="date" name="dob" required>
          </div>
        </div>

        <div class="row">
          <div style="flex:1">
            <label>Father name</label>
            <input type="text" name="father_name" required oninput="toUpper(this)">
          </div>
          <div style="flex:1">
            <label>Mother name</label>
            <input type="text" name="mother_name" required oninput="toUpper(this)">
          </div>
        </div>

        <div class="row">
          <div style="flex:1">
            <label>Mobile number</label>
            <input type="text" name="mobile" required pattern="\d{10}" placeholder="10 digits">
          </div>
          <div style="flex:1">
            <label>Guardian Mobile</label>
            <input type="text" name="guardian_mobile" pattern="\d{10}" placeholder="optional">
          </div>
        </div>

        <div class="row">
          <div style="flex:1">
            <label>Mail address</label>
            <input type="email" name="email" required>
          </div>
          <div style="flex:1">
            <label>Previous year student college name</label>
            <input type="text" name="prev_college" oninput="toUpper(this)">
          </div>
        </div>

        <div class="row">
          <div style="flex:1">
            <label>Previous year Combination</label>
            <select name="prev_combination" required>
              <option value="">-- Select --</option>
              <option>PCMB</option>
              <option>PCMC</option>
              <option>DIPLOMA (LATERAL ENTRY)</option>
            </select>
          </div>
          <div style="flex:1">
            <label>Permanent Address</label>
            <textarea name="permanent_address" rows="2" oninput="toUpper(this)"></textarea>
          </div>
        </div>

        <div class="row">
          <div style="flex:1">
            <label>Category</label>
            <select name="category" id="category" onchange="onCategoryChange()">
              <option>CAT 1</option>
              <option>2A</option>
              <option>2B</option>
              <option>3A</option>
              <option>3B</option>
              <option>SC</option>
              <option>ST</option>
              <option>NOT APPLICABLE</option>
            </select>
          </div>
          <div style="flex:1">
            <label>Sub Caste</label>
            <input type="text" name="sub_caste" oninput="toUpper(this)">
          </div>
        </div>

        <div class="row">
          <div style="flex:1">
            <label>Admission through</label>
            <select name="admission_through" id="admission_through" onchange="onAdmissionChange()">
              <option value="KEA">KEA</option>
              <option value="MANAGEMENT">MANAGEMENT</option>
            </select>
          </div>
          <div style="flex:1" id="keaFields" style="display:none">
            <label>CET number</label>
            <input type="text" name="cet_number">
          </div>
        </div>

        <div id="keaOnly" style="display:none">
          <h4>KEA allotment</h4>
          <div class="row">
            <div style="flex:1">
              <label>Seat Allotted</label>
              <select name="seat_allotted">
                <option>SNQ</option>
                <option>GM</option>
                <option>SC</option>
                <option>ST</option>
                <option>OBC</option>
                <option>GMR</option>
                <option>GMK</option>
                <option>KK / HK</option>
                <option>EWS</option>
                <option>SPL</option>
              </select>
            </div>
            <div style="flex:1">
              <label>Allotted branch</label>
              <select name="allotted_branch">
                <option>CSE</option>
                <option>AIML</option>
                <option>CS (AIML)</option>
                <option>CS (DS)</option>
                <option>EC</option>
                <option>CV</option>
                <option>ME</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div style="flex:1">
              <label>CET rank</label>
              <input type="text" name="cet_rank">
            </div>
          </div>
        </div>

        <div class="actions">
          <button type="button" onclick="nextStep()">Next</button>
        </div>
      </div>

      <!-- STEP 2: uploads -->
      <div class="step" id="step2">
        <h3>Upload Documents</h3>
        <p>File limits: 1MB each for photos/marks, Aadhaar sides 1MB each. Marks and caste must be PDF. Photo must be JPG/JPEG.</p>

        <div class="row">
          <div style="flex:1">
            <label>Passport size photo (jpg/jpeg)</label>
            <input type="file" name="photo" accept="image/jpeg" required>
          </div>
          <div style="flex:1">
            <label>Upload previous year marks cards (PDF)</label>
            <input type="file" name="marks" accept="application/pdf" required>
          </div>
        </div>

        <div class="row">
          <div style="flex:1">
            <label>Aadhaar - Front (jpg/pdf)</label>
            <input type="file" name="aadhaar_front" accept="image/jpeg,application/pdf">
          </div>
          <div class="flex:1">
            <label>Aadhaar - Back (jpg/pdf)</label>
            <input type="file" name="aadhaar_back" accept="image/jpeg,application/pdf">
          </div>
        </div>

        <div class="row">
          <div style="flex:1">
            <label>Cast & Income (PDF)</label>
            <input type="file" name="caste_income" accept="application/pdf">
          </div>
        </div>

        <div class="actions">
          <button type="button" onclick="prevStep()" style="background:#777">Back</button>
          <button type="submit" name="final_submit">Submit</button>
        </div>
      </div>
    </form>
  </div>

<script src="assets/app.js"></script>
</body>
</html>
