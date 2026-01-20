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
      (field.type !== "file" && !field.value.trim());

    if (invalid && !firstInvalid) {
      firstInvalid = field;
      field.classList.add("input-error");
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

  if (step === 1) {
    bar.style.width = "50%";
    s1.classList.add("active");
    s2.classList.remove("active");
  } else {
    bar.style.width = "100%";
    s1.classList.remove("active");
    s2.classList.add("active");
  }
}

/* =====================================================
   ADMISSION TYPE TOGGLE
===================================================== */
function showAdmissionFields() {
  const type = document.getElementById("admission_through")?.value;

  const kea = document.getElementById("kea_section");
  const mgmt = document.getElementById("management_section");
  const keaDoc = document.getElementById("kea_doc");
  const mgmtDoc = document.getElementById("management_doc");

  kea?.classList.add("hidden");
  mgmt?.classList.add("hidden");
  keaDoc?.classList.add("hidden");
  mgmtDoc?.classList.add("hidden");

  if (type === "KEA") {
    kea?.classList.remove("hidden");
    keaDoc?.classList.remove("hidden");
  }

  if (type === "MANAGEMENT") {
    mgmt?.classList.remove("hidden");
    mgmtDoc?.classList.remove("hidden");
  }
}

/* =====================================================
   PREVIEW MODAL
===================================================== */
function openPreview() {
  if (!validateStep("step2")) return;

  const modal = document.getElementById("previewModal");
  const content = document.getElementById("previewContent");

  let html = "";
  document.querySelectorAll("input, select, textarea").forEach(el => {
    if (el.name && el.type !== "file" && el.value) {
      html += `<p><b>${el.name.replaceAll("_"," ")}:</b> ${el.value}</p>`;
    }
  });

  content.innerHTML = html;
  modal.classList.remove("hidden");
}

function closePreview() {
  document.getElementById("previewModal").classList.add("hidden");
}

/* =====================================================
   FINAL SUBMIT FROM PREVIEW (FIXED)
===================================================== */
function confirmAndSubmit() {
  const form = document.querySelector("form");
  const overlay = document.getElementById("uploadOverlay");
  const submitBtn = document.getElementById("submitBtn");

  if (!validateStep("step2")) return;

  closePreview();

  // Show uploading animation
  overlay.classList.remove("hidden");

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Uploading…";
  }

  // Submit after UI update
  setTimeout(() => {
    form.submit();
  }, 300);
}

/* =====================================================
   INIT
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("admission_through")
    ?.addEventListener("change", showAdmissionFields);

  showAdmissionFields();
});




document.querySelectorAll('input[type="file"]').forEach(input => {
  input.addEventListener('change', () => {
    if (input.files[0] && input.files[0].size > 5 * 1024 * 1024) {
      alert("Each file must be below 5MB");
      input.value = "";
    }
  });
});

