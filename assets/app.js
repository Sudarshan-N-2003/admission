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

  const kea = document.getElementById("kea_section");
  const mgmt = document.getElementById("management_section");

  if (type === "KEA") {
    kea.style.display = "block";
    mgmt.style.display = "none";
  } else if (type === "MANAGEMENT") {
    kea.style.display = "none";
    mgmt.style.display = "block";
  } else {
    kea.style.display = "none";
    mgmt.style.display = "none";
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
