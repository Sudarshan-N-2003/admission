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
