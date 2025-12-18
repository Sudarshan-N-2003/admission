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
