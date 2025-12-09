<?php
<option>OBC</option>
<option>GMR</option>
<option>GMK</option>
<option>KK / HK</option>
<option>EWS</option>
<option>SPL</option>
</select>
</div>
<div style="flex:1"><label>Allotted branch</label>
<select name="allotted_branch">
<option>CSE</option>
<option>AIML</option>
<option>CS (AIML)</option>
<option>CS (DS)</option>
<option>EC</option>
<option>CV</option>
<option>ME</option>
</select>
</div></div>
<div class="row"><div style="flex:1"><label>CET rank</label><input type="text" name="cet_rank"></div></div>
</div>


<div class="actions"><button type="button" onclick="nextStep()">Next</button></div>
</div>


<!-- STEP 2: uploads -->
<div class="step" id="step2">
<h3>Upload Documents</h3>
<p>File limits: 1MB each for photos/marks, Aadhaar sides 1MB each. Marks and caste must be PDF. Photo must be JPG/JPEG.</p>


<div class="row"><div style="flex:1"><label>Passport size photo (jpg/jpeg)</label><input type="file" name="photo" accept="image/jpeg" required></div>
<div style="flex:1"><label>Upload previous year marks cards (PDF)</label><input type="file" name="marks" accept="application/pdf" required></div></div>


<div class="row"><div style="flex:1"><label>Aadhaar - Front (jpg/pdf)</label><input type="file" name="aadhaar_front" accept="image/jpeg,application/pdf"></div>
<div class="flex:1"><label>Aadhaar - Back (jpg/pdf)</label><input type="file" name="aadhaar_back" accept="image/jpeg,application/pdf"></div></div>


<div class="row"><div style="flex:1"><label>Cast & Income (PDF)</label><input type="file" name="caste_income" accept="application/pdf"></div></div>


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
