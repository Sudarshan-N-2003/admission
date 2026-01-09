/* ================= VALIDATION ================= */
function validateVisibleFields(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return true;

  let firstInvalid = null;
  let valid = true;

  container.querySelectorAll("[required]").forEach(field => {
    field.classList.remove("input-error");

    if (field.closest(".hidden")) return;

    const empty =
      (field.type === "file" && field.files.length === 0) ||
      (!field.value || field.value.trim() === "");

    if (empty) {
      valid = false;
      field.classList.add("input-error");
      if (!firstInvalid) firstInvalid = field;
    }
  });

  if (firstInvalid) {
    firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
    firstInvalid.focus();
  }

  return valid;
}

/* ================= STEP NAVIGATION ================= */
function nextStep() {
  if (!validateVisibleFields("step1")) return;

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

/* ================= PROGRESS BAR ================= */
function updateProgress(step) {
  const bar = document.getElementById("progressBar");
  if (!bar) return;
  bar.style.width = step === 1 ? "50%" : "100%";
}

/* ================= PREVIEW ================= */
function openPreview() {
  if (!validateVisibleFields("step2")) return;

  const modal = document.getElementById("previewModal");
  const content = document.getElementById("previewContent");

  let html = "";
  document
    .querySelectorAll("#step1 input, #step1 select, #step1 textarea")
    .forEach(el => {
      if (el.name && el.value && el.type !== "file") {
        html += `<p><b>${el.name.replaceAll("_", " ")}:</b> ${el.value}</p>`;
      }
    });

  content.innerHTML = html;
  modal.classList.remove("hidden");
}

function closePreview() {
  document.getElementById("previewModal").classList.add("hidden");
}

/* ================= ADMISSION TYPE ================= */
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

/* ================= INIT ================= */
document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("admission_through")
    ?.addEventListener("change", showAdmissionFields);

  showAdmissionFields();
});
