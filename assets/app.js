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







/* ===============================
   NATIONALITY → STATE LINKING
================================ */

const stateData = {
  INDIAN: [
    "ANDHRA PRADESH","ARUNACHAL PRADESH","ASSAM","BIHAR","CHHATTISGARH",
    "GOA","GUJARAT","HARYANA","HIMACHAL PRADESH","JHARKHAND",
    "KARNATAKA","KERALA","MADHYA PRADESH","MAHARASHTRA","MANIPUR",
    "MEGHALAYA","MIZORAM","NAGALAND","ODISHA","PUNJAB","RAJASTHAN",
    "SIKKIM","TAMIL NADU","TELANGANA","TRIPURA","UTTAR PRADESH",
    "UTTARAKHAND","WEST BENGAL",
    "ANDAMAN AND NICOBAR ISLANDS","CHANDIGARH",
    "DADRA AND NAGAR HAVELI AND DAMAN AND DIU","DELHI",
    "JAMMU AND KASHMIR","LADAKH","LAKSHADWEEP","PUDUCHERRY"
  ],

  NEPAL: [
    "KOSHI","MADHESH","BAGMATI","GANDaki",
    "LUMBINI","KARNALI","SUDURPASHCHIM"
  ],

  "SRI LANKA": [
    "CENTRAL","EASTERN","NORTHERN","NORTH CENTRAL",
    "NORTH WESTERN","SABARAGAMUWA","SOUTHERN","UVA","WESTERN"
  ]
};

function updateStateOptions() {
  const nationality = document.getElementById("nationality");
  const stateSelect = document.getElementById("state");

  if (!nationality || !stateSelect) return;

  const value = nationality.value;
  stateSelect.innerHTML = '<option value="">Select State / Province</option>';

  if (stateData[value]) {
    stateData[value].forEach(state => {
      const opt = document.createElement("option");
      opt.value = state;
      opt.textContent = state;
      stateSelect.appendChild(opt);
    });
  } else {
    const opt = document.createElement("option");
    opt.value = "NOT APPLICABLE";
    opt.textContent = "NOT APPLICABLE";
    stateSelect.appendChild(opt);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("nationality")
    ?.addEventListener("change", updateStateOptions);
});






/* ===============================
   SHOW UPLOADING ANIMATION
================================ */
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  const overlay = document.getElementById("uploadOverlay");
  const submitBtn = document.getElementById("submitBtn");

  if (!form || !overlay) return;

  form.addEventListener("submit", () => {
    // Disable submit button
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = "Uploading...";
    }

    // Show overlay
    overlay.classList.remove("hidden");

    // Blur form
    document.querySelector(".container")?.classList.add("form-blur");
  });
});
