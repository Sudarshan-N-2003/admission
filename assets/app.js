/* =====================================================
   ADMISSION TYPE TOGGLE (KEA / MANAGEMENT)
===================================================== */
function showAdmissionFields() {
  const type = document.getElementById("admission_through")?.value;

  const keaSection = document.getElementById("kea_section");
  const mgmtSection = document.getElementById("management_section");

  const keaDoc = document.getElementById("kea_doc");
  const mgmtDoc = document.getElementById("management_doc");

  const keaInput = document.getElementById("kea_acknowledgement");
  const mgmtInput = document.getElementById("management_receipt");

  if (!type) return;

  /* Hide all sections */
  keaSection?.classList.add("hidden");
  mgmtSection?.classList.add("hidden");
  keaDoc?.classList.add("hidden");
  mgmtDoc?.classList.add("hidden");

  /* Remove required flags */
  if (keaInput) keaInput.required = false;
  if (mgmtInput) mgmtInput.required = false;

  /* Show based on admission type */
  if (type === "KEA") {
    keaSection?.classList.remove("hidden");
    keaDoc?.classList.remove("hidden");
    if (keaInput) keaInput.required = true;
  }

  if (type === "MANAGEMENT") {
    mgmtSection?.classList.remove("hidden");
    mgmtDoc?.classList.remove("hidden");
    if (mgmtInput) mgmtInput.required = true;
  }
}

/* =====================================================
   STEP VALIDATION + AUTO SCROLL
===================================================== */
function validateStep(stepId) {
  const step = document.getElementById(stepId);
  if (!step) return true;

  const requiredFields = step.querySelectorAll("[required]");
  let firstInvalid = null;

  requiredFields.forEach(field => {
    field.classList.remove("input-error");

    if (
      (field.type === "file" && field.files.length === 0) ||
      (!field.value || field.value.trim() === "")
    ) {
      field.classList.add("input-error");
      if (!firstInvalid) firstInvalid = field;
    }
  });

  if (firstInvalid) {
    firstInvalid.scrollIntoView({
      behavior: "smooth",
      block: "center"
    });
    firstInvalid.focus();
    return false;
  }

  return true;
}

/* =====================================================
   STEP NAVIGATION
===================================================== */
function nextStep() {
  if (!validateStep("step1")) return;

  document.getElementById("step1")?.classList.remove("active");
  document.getElementById("step2")?.classList.add("active");

  showAdmissionFields();
  updateProgress(2);
}

function prevStep() {
  document.getElementById("step2")?.classList.remove("active");
  document.getElementById("step1")?.classList.add("active");

  updateProgress(1);
}

/* =====================================================
   PROGRESS BAR
===================================================== */
function updateProgress(step) {
  const bar = document.getElementById("progressBar");
  const s1 = document.getElementById("labelStep1");
  const s2 = document.getElementById("labelStep2");

  if (!bar) return;

  if (step === 1) {
    bar.style.width = "50%";
    s1?.classList.add("active");
    s2?.classList.remove("active");
  }

  if (step === 2) {
    bar.style.width = "100%";
    s1?.classList.remove("active");
    s2?.classList.add("active");
  }
}

/* =====================================================
   INIT
===================================================== */
document.addEventListener("DOMContentLoaded", function () {
  const admissionSelect = document.getElementById("admission_through");

  if (admissionSelect) {
    admissionSelect.addEventListener("change", showAdmissionFields);
    showAdmissionFields(); // run once on load
  }
});


function enableSubmitIfValid() {
  const step2 = document.getElementById("step2");
  const submitBtn = document.getElementById("submitBtn");
  if (!step2 || !submitBtn) return;

  const requiredFields = step2.querySelectorAll("[required]");
  let valid = true;

  requiredFields.forEach(field => {
    if (
      (field.type === "file" && field.files.length === 0) ||
      (!field.value || field.value.trim() === "")
    ) {
      valid = false;
    }
  });

  submitBtn.disabled = !valid;
}

/* Listen to changes in step 2 */
document.addEventListener("change", enableSubmitIfValid);
document.addEventListener("keyup", enableSubmitIfValid);












function openPreview() {
  const modal = document.getElementById("previewModal");
  const content = document.getElementById("previewContent");

  let html = "";
  document.querySelectorAll("input, select, textarea").forEach(el => {
    if (el.name && el.type !== "file" && el.value) {
      html += `<p><b>${el.name.replaceAll('_',' ')}:</b> ${el.value}</p>`;
    }
  });

  content.innerHTML = html;
  modal.classList.remove("hidden");
}

function closePreview() {
  document.getElementById("previewModal").classList.add("hidden");
}

/* Replace normal submit */
document.getElementById("submitBtn")?.addEventListener("click", function (e) {
  e.preventDefault();
  openPreview();
});





const FORM_KEY = "vvit_admission_draft";

/* Save */
document.querySelectorAll("input, select, textarea").forEach(el => {
  el.addEventListener("change", () => {
    const data = {};
    document.querySelectorAll("input, select, textarea").forEach(f => {
      if (f.name && f.type !== "file") {
        data[f.name] = f.value;
      }
    });
    localStorage.setItem(FORM_KEY, JSON.stringify(data));
  });
});

/* Restore */
window.addEventListener("DOMContentLoaded", () => {
  const saved = localStorage.getItem(FORM_KEY);
  if (!saved) return;

  const data = JSON.parse(saved);
  Object.keys(data).forEach(k => {
    const el = document.querySelector(`[name="${k}"]`);
    if (el) el.value = data[k];
  });
});







function checkDuplicate() {
  const mobile = document.querySelector('[name="mobile"]')?.value;
  const email  = document.querySelector('[name="email"]')?.value;
  const msg    = document.getElementById("dupMessage");

  if (!mobile && !email) return;

  const formData = new FormData();
  formData.append("mobile", mobile);
  formData.append("email", email);

  fetch("check_duplicate.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === "exists") {
      msg.textContent = data.message;
      msg.style.color = "#dc2626";
    } else {
      msg.textContent = "✓ Available";
      msg.style.color = "#16a34a";
    }
  });
}

document.querySelector('[name="mobile"]')
  ?.addEventListener("blur", checkDuplicate);

document.querySelector('[name="email"]')
  ?.addEventListener("blur", checkDuplicate);




















let emailVerified = false;

document.getElementById("sendOtpBtn")?.addEventListener("click", () => {
  const email = document.querySelector('[name="email"]').value;
  if (!email) return alert("Enter email first");

  fetch("send_otp.php", {
    method: "POST",
    body: new URLSearchParams({ email })
  })
  .then(r=>r.json())
  .then(() => {
    document.getElementById("otpBox").classList.remove("hidden");
    document.getElementById("otpMsg").textContent = "OTP sent to email";
  });
});

function verifyOtp() {
  const email = document.querySelector('[name="email"]').value;
  const otp = document.getElementById("otpInput").value;

  fetch("verify_otp.php", {
    method: "POST",
    body: new URLSearchParams({ email, otp })
  })
  .then(r => r.json())
  .then(res => {
    const msg = document.getElementById("otpMsg");

    if (res.status === "ok") {

      // ✅ MARK EMAIL AS VERIFIED
      let hidden = document.querySelector('input[name="email_verified"]');
      if (!hidden) {
        document.querySelector('form').insertAdjacentHTML(
          'beforeend',
          '<input type="hidden" name="email_verified" value="1">'
        );
      }

      msg.textContent = "Email verified ✓";
      msg.style.color = "green";

      // Optional UX improvements
      document.getElementById("otpBox").classList.add("hidden");
      document.getElementById("sendOtpBtn").disabled = true;

    } else {
      msg.textContent = res.msg || "Invalid OTP";
      msg.style.color = "red";
    }
  });
}
