function showAdmissionFields() {
  const type = document.getElementById("admission_through").value;

  const keaSection = document.getElementById("kea_section");
  const mgmtSection = document.getElementById("management_section");

  const keaDoc = document.getElementById("kea_doc");
  const mgmtDoc = document.getElementById("management_doc");

  const keaInput = document.getElementById("kea_acknowledgement");
  const mgmtInput = document.getElementById("management_receipt");

  // Hide all
  keaSection.classList.add("hidden");
  mgmtSection.classList.add("hidden");
  keaDoc.classList.add("hidden");
  mgmtDoc.classList.add("hidden");

  // Remove required
  keaInput.required = false;
  mgmtInput.required = false;

  // Show + require based on admission type
  if (type === "KEA") {
    keaSection.classList.remove("hidden");
    keaDoc.classList.remove("hidden");
    keaInput.required = true;
  }

  if (type === "MANAGEMENT") {
    mgmtSection.classList.remove("hidden");
    mgmtDoc.classList.remove("hidden");
    mgmtInput.required = true;
  }
}

function nextStep() {
  document.getElementById("step1").classList.remove("active");
  document.getElementById("step2").classList.add("active");
  showAdmissionFields();
}

function prevStep() {
  document.getElementById("step2").classList.remove("active");
  document.getElementById("step1").classList.add("active");
}

document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("admission_through")
    .addEventListener("change", showAdmissionFields);
});
