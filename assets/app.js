function toUpper(el) {
  el.value = el.value.toUpperCase();
}

function toggleAdmission() {
  const type = document.getElementById("admission_through").value;

  document.getElementById("kea_section").classList.add("hidden");
  document.getElementById("management_section").classList.add("hidden");
  document.getElementById("kea_doc").classList.add("hidden");
  document.getElementById("management_doc").classList.add("hidden");

  if (type === "KEA") {
    document.getElementById("kea_section").classList.remove("hidden");
    document.getElementById("kea_doc").classList.remove("hidden");
  }

  if (type === "MANAGEMENT") {
    document.getElementById("management_section").classList.remove("hidden");
    document.getElementById("management_doc").classList.remove("hidden");
  }
}

function nextStep() {
  document.getElementById("step1").classList.remove("active");
  document.getElementById("step2").classList.add("active");
  toggleAdmission();
}

function prevStep() {
  document.getElementById("step2").classList.remove("active");
  document.getElementById("step1").classList.add("active");
}

document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("admission_through").addEventListener("change", toggleAdmission);
});
