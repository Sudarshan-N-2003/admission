<!-- STEP 1 -->
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

  <!-- NEW: Gender -->
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
      <input type="text" name="mobile" required pattern="\d{10}">
    </div>
    <div class="col">
      <label>Aadhaar Number</label>
      <input type="text" name="aadhaar_number" required pattern="\d{12}" placeholder="12 digit Aadhaar">
    </div>
  </div>

  <!-- NEW: State -->
  <div class="row">
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
    <div class="col">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
  </div>

  <div class="row">
    <div class="col">
      <label>Admission Through</label>
      <select name="admission_through" id="admission_through" onchange="onAdmissionChange()" required>
        <option value="">-- Select --</option>
        <option value="KEA">KEA</option>
        <option value="MANAGEMENT">MANAGEMENT</option>
      </select>
    </div>
  </div>

  <!-- KEA DETAILS -->
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
          <option>CSE</option>
          <option>AIML</option>
          <option>EC</option>
          <option>ME</option>
        </select>
      </div>
    </div>
  </div>

  <!-- MANAGEMENT DETAILS -->
  <div id="management_section" style="display:none">
    <h4>Management Admission</h4>
    <div class="row">
      <div class="col">
        <label>Allotted Branch</label>
        <select name="allotted_branch_management">
          <option>CSE</option>
          <option>AIML</option>
          <option>EC</option>
          <option>ME</option>
        </select>
      </div>
    </div>
  </div>

  <div class="actions">
    <button type="button" onclick="nextStep()">Next</button>
  </div>

</div>
