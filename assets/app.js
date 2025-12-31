/* =====================================================
   GLOBAL STATE
===================================================== */
let emailVerified = false;
const FORM_KEY = "vvit_admission_draft";

/* =====================================================
   STEP VISIBILITY RESET (FIX STEP-2 ISSUE)
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  const step1 = document.getElementById("step1");
  const step2 = document.getElementById("step2");

  if (step1 && step2) {
    step1.classList.add("active");
    step2.classList.remove("active");
  }

  updateProgress(1);
  restoreDraft();
  showAdmissionFields();
});

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

  [keaSection, mgmtSection, keaDoc, mgmtDoc].forEach(e => e?.classList.add("hidden"));
  if (keaInput) keaInput.required = false;
  if (mgmtInput) mgmtInput.required = false;

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

  let firstInvalid = null;
  step.querySelectorAll("[required]").forEach(field => {
    field.classList.remove("input-error");

    const invalid =
      (field.type === "file" && field.files.length === 0) ||
      (!field.value || field.value.trim() === "");

    if (invalid && !firstInvalid) {
      field.classList.add("input-error");
      firstInvalid = field;
    }
  });

  if (firstInvalid) {
    firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
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

  document.getElementById("step1").classList.remove("active");
  document.getElementById("step2").classList.add("active");
  updateProgress(2);
  showAdmissionFields();
}

function prevStep() {
  document.getElementById("step2").classList.remove("active");
  document.getElementById("step1").classList.add("active");
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

  bar.style.width = step === 1 ? "50%" : "100%";
  s1?.classList.toggle("active", step === 1);
  s2?.classList.toggle("active", step === 2);
}

/* =====================================================
   SUBMIT ENABLE (STEP-2 COMPLETE)
===================================================== */
function enableSubmitIfValid() {
  const btn = document.getElementById("submitBtn");
  if (!btn) return;

  let valid = true;
  document.getElementById("step2")
    ?.querySelectorAll("[required]")
    .forEach(f => {
      if (
        (f.type === "file" && f.files.length === 0) ||
        (!f.value || f.value.trim() === "")
      ) valid = false;
    });

  btn.disabled = !valid;
}

document.addEventListener("change", enableSubmitIfValid);
document.addEventListener("keyup", enableSubmitIfValid);

/* =====================================================
   PREVIEW MODAL
===================================================== */
function openPreview() {
  const modal = document.getElementById("previewModal");
  const content = document.getElementById("previewContent");

  let html = "";
  document.querySelectorAll("input, select, textarea").forEach(el => {
    if (el.name && el.type !== "file" && el.value) {
      html += `<p><b>${el.name.replace(/_/g, " ")}:</b> ${el.value}</p>`;
    }
  });

  content.innerHTML = html;
  modal.classList.remove("hidden");
}

function closePreview() {
  document.getElementById("previewModal").classList.add("hidden");
}

document.getElementById("submitBtn")?.addEventListener("click", e => {
  e.preventDefault();
  openPreview();
});

/* =====================================================
   AUTO SAVE DRAFT
===================================================== */
function saveDraft() {
  const data = {};
  document.querySelectorAll("input, select, textarea").forEach(el => {
    if (el.name && el.type !== "file") data[el.name] = el.value;
  });
  localStorage.setItem(FORM_KEY, JSON.stringify(data));
}

function restoreDraft() {
  const saved = localStorage.getItem(FORM_KEY);
  if (!saved) return;

  const data = JSON.parse(saved);
  Object.keys(data).forEach(k => {
    const el = document.querySelector(`[name="${k}"]`);
    if (el) el.value = data[k];
  });
}

document.querySelectorAll("input, select, textarea")
  .forEach(el => el.addEventListener("change", saveDraft));

/* =====================================================
   DUPLICATE CHECK (AJAX)
===================================================== */
function checkDuplicate() {
  const mobile = document.querySelector('[name="mobile"]')?.value;
  const email = document.querySelector('[name="email"]')?.value;
  const msg = document.getElementById("dupMessage");

  if (!mobile && !email) return;

  fetch("check_duplicate.php", {
    method: "POST",
    body: new URLSearchParams({ mobile, email })
  })
  .then(r => r.json())
  .then(d => {
    msg.textContent = d.status === "exists"
      ? d.message
      : "✓ Available";
    msg.style.color = d.status === "exists" ? "#dc2626" : "#16a34a";
  });
}

document.querySelector('[name="mobile"]')?.addEventListener("blur", checkDuplicate);
document.querySelector('[name="email"]')?.addEventListener("blur", checkDuplicate);

/* =====================================================
   OTP RESEND TIMER
===================================================== */
let otpTimerInt = null;
function startOtpTimer() {
  let sec = 60;
  const btn = document.getElementById("sendOtpBtn");
  const t = document.getElementById("otpTimer");

  btn.disabled = true;
  t.textContent = `Resend OTP in ${sec}s`;

  otpTimerInt = setInterval(() => {
    sec--;
    t.textContent = `Resend OTP in ${sec}s`;
    if (sec <= 0) {
      clearInterval(otpTimerInt);
      btn.disabled = false;
      t.textContent = "";
    }
  }, 1000);
}

/* =====================================================
   SEND OTP
===================================================== */
document.getElementById("sendOtpBtn")?.addEventListener("click", () => {
  const email = document.querySelector('[name="email"]').value;
  const msg = document.getElementById("otpMsg");

  if (!email) {
    msg.textContent = "Enter email first";
    msg.style.color = "red";
    return;
  }

  fetch("send_otp.php", {
    method: "POST",
    body: new URLSearchParams({ email })
  })
  .then(() => {
    document.getElementById("otpBox").classList.remove("hidden");
    msg.textContent = "OTP sent to email";
    msg.style.color = "green";
    startOtpTimer();
  });
});

/* =====================================================
   VERIFY OTP
===================================================== */
function verifyOtp() {
  const email = document.querySelector('[name="email"]').value;
  const otp = document.getElementById("otpInput").value;
  const msg = document.getElementById("otpMsg");

  fetch("verify_otp.php", {
    method: "POST",
    body: new URLSearchParams({ email, otp })
  })
  .then(r => r.json())
  .then(res => {
    if (res.status === "ok") {
      if (!document.querySelector('[name="email_verified"]')) {
        document.querySelector("form")
          .insertAdjacentHTML("beforeend",
            '<input type="hidden" name="email_verified" value="1">');
      }

      msg.textContent = "Email verified ✓";
      msg.style.color = "green";
      document.getElementById("otpBox").classList.add("hidden");
      document.getElementById("sendOtpBtn").disabled = true;
    } else {
      msg.textContent = res.msg || "Invalid OTP";
      msg.style.color = "red";
    }
  });
}
