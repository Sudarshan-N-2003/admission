// assets/app.js

function toUpper(el) {
  if (el && el.value) el.value = el.value.toUpperCase();
}

function showConditionalUploads() {
  const select = document.getElementById("admission_through");
  const kea = document.getElementById("kea_doc");
  const mgmt = document.getElementById("management_doc");

  if (!select || !kea || !mgmt) return;

  const value = select.value;

  // Hide both first
  kea.style.display = "none";
  mgmt.style.display = "none";

  // Remove required flags
  const keaInput = kea.querySelector("input");
  const mgmtInput = mgmt.querySelector("input");

  if (keaInput) keaInput.required = false;
  if (mgmtInput) mgmtInput.required = false;

  // Show based on selection
  if (value === "KEA") {
    kea.style.display = "block";
    if (keaInput) keaInput.required = true;
  }

  if (value === "MANAGEMENT") {
    mgmt.style.display = "block";
    if (mgmtInput) mgmtInput.required = true;
  }
}

function nextStep() {
  const step1 = document.getElementById("step1");
  const step2 = document.getElementById("step2");

  // validate step1
  const required = step1.querySelectorAll("input[required], select[required]");
  for (let el of required) {
    if (!el.checkValidity()) {
      el.reportValidity();
      return;
    }
  }

  // switch steps
  step1.classList.remove("active");
  step2.classList.add("active");

  // 🔥 FORCE show correct uploads
  setTimeout(showConditionalUploads, 50);
}

function prevStep() {
  document.getElementById("step2").classList.remove("active");
  document.getElementById("step1").classList.add("active");
}

// Run when admission type changes
document.addEventListener("DOMContentLoaded", function () {
  const select = document.getElementById("admission_through");
  if (select) {
    select.addEventListener("change", showConditionalUploads);
  }
});
