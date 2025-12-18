// ===== BASIC STEP CONTROL =====

function nextStep() {
  // Validate step 1 required fields first
  const step1 = document.getElementById("step1");
  const required = step1.querySelectorAll("input[required], select[required]");

  for (let field of required) {
    if (!field.checkValidity()) {
      field.reportValidity();
      return;
    }
  }

  // Move to step 2
  step1.classList.remove("active");
  document.getElementById("step2").classList.add("active");

  // 🔥 VERY IMPORTANT
  // Re-check admission type and show correct upload section
  onAdmissionChange();
}


// ===== ADMISSION TYPE CONTROL =====
function onAdmissionChange() {
  const type = document.getElementById("admission_through").value;

  const keaDoc = document.getElementById("kea_doc");
  const mgmtDoc = document.getElementById("management_doc");

  if (keaDoc) keaDoc.style.display = type === "KEA" ? "block" : "none";
  if (mgmtDoc) mgmtDoc.style.display = type === "MANAGEMENT" ? "block" : "none";

  // Toggle required attribute
  const keaInput = document.querySelector("input[name='kea_acknowledgement']");
  const mgmtInput = document.querySelector("input[name='management_receipt']");

  if (keaInput) keaInput.required = (type === "KEA");
  if (mgmtInput) mgmtInput.required = (type === "MANAGEMENT");
}


// ===== AUTO UPPERCASE =====
function toUpper(el) {
  if (el && el.value) {
    el.value = el.value.toUpperCase();
  }
}

// ===== AUTO INIT =====
document.addEventListener("DOMContentLoaded", function () {
  console.log("JS Loaded");
  onAdmissionChange();
});
