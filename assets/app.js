/* =====================================================
   FORCE INITIAL STATE (IMPORTANT)
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  hideAllAdmissionSections();
});

/* =====================================================
   HIDE ALL KEA / MANAGEMENT FIELDS
===================================================== */
function hideAllAdmissionSections() {
  const keaSection = document.getElementById("kea_section");
  const mgmtSection = document.getElementById("management_section");
  const keaDoc = document.getElementById("kea_doc");
  const mgmtDoc = document.getElementById("management_doc");

  keaSection?.classList.add("hidden");
  mgmtSection?.classList.add("hidden");
  keaDoc?.classList.add("hidden");
  mgmtDoc?.classList.add("hidden");

  const keaInput = document.getElementById("kea_acknowledgement");
  const mgmtInput = document.getElementById("management_receipt");

  if (keaInput) keaInput.required = false;
  if (mgmtInput) mgmtInput.required = false;
}

/* =====================================================
   SHOW BASED ON ADMISSION TYPE
===================================================== */
function showAdmissionFields() {
  hideAllAdmissionSections();

  const type = document.getElementById("admission_through")?.value;
  if (!type) return;

  if (type === "KEA") {
    document.getElementById("kea_section")?.classList.remove("hidden");
    document.getElementById("kea_doc")?.classList.remove("hidden");
    document.getElementById("kea_acknowledgement").required = true;
  }

  if (type === "MANAGEMENT") {
    document.getElementById("management_section")?.classList.remove("hidden");
    document.getElementById("management_doc")?.classList.remove("hidden");
    document.getElementById("management_receipt").required = true;
  }
}

/* =====================================================
   STEP NAVIGATION
===================================================== */
function nextStep() {
  if (!validateStep("step1")) return;

  document.getElementById("step1").classList.remove("active");
  document.getElementById("step2").classList.add("active");

  updateProgress(2);
}

function prevStep() {
  document.getElementById("step2").classList.remove("active");
  document.getElementById("step1").classList.add("active");

  updateProgress(1);
}

/* =====================================================
   STEP VALIDATION + AUTO SCROLL
===================================================== */
function validateStep(stepId) {
  const step = document.getElementById(stepId);
  const required = step.querySelectorAll("[required]");
  let firstError = null;

  required.forEach(el => {
    el.classList.remove("input-error");

    if (
      (el.type === "file" && el.files.length === 0) ||
      (!el.value || el.value.trim() === "")
    ) {
      el.classList.add("input-error");
      if (!firstError) firstError = el;
    }
  });

  if (firstError) {
    firstError.scrollIntoView({ behavior: "smooth", block: "center" });
    firstError.focus();
    return false;
  }
  return true;
}

/* =====================================================
   PROGRESS BAR
===================================================== */
function updateProgress(step) {
  const bar = document.getElementById("progressBar");
  const s1 = document.getElementById("labelStep1");
  const s2 = document.getElementById("labelStep2");

  if (step === 1) {
    bar.style.width = "50%";
    s1.classList.add("active");
    s2.classList.remove("active");
  }

  if (step === 2) {
    bar.style.width = "100%";
    s1.classList.remove("active");
    s2.classList.add("active");
  }
}

/* =====================================================
   LISTENERS
===================================================== */
document
  .getElementById("admission_through")
  ?.addEventListener("change", showAdmissionFields);