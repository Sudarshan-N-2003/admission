// ===== BASIC STEP CONTROL =====

function nextStep() {
  console.log("Next clicked");

  document.getElementById("step1").classList.remove("active");
  document.getElementById("step2").classList.add("active");
}

function prevStep() {
  document.getElementById("step2").classList.remove("active");
  document.getElementById("step1").classList.add("active");
}

// ===== ADMISSION TYPE CONTROL =====
function onAdmissionChange() {
  const type = document.getElementById("admission_through").value;

  // Admission detail sections
  document.getElementById("kea_section").style.display =
    type === "KEA" ? "block" : "none";

  document.getElementById("management_section").style.display =
    type === "MANAGEMENT" ? "block" : "none";

  // Document sections
  document.getElementById("kea_doc").style.display =
    type === "KEA" ? "block" : "none";

  document.getElementById("management_doc").style.display =
    type === "MANAGEMENT" ? "block" : "none";

  // Make required dynamically
  if (type === "KEA") {
    document.querySelector("input[name='kea_acknowledgement']").required = true;
    document.querySelector("input[name='management_receipt']").required = false;
  }

  if (type === "MANAGEMENT") {
    document.querySelector("input[name='management_receipt']").required = true;
    document.querySelector("input[name='kea_acknowledgement']").required = false;
  }
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
