/* ===============================
   FINAL SUBMIT FROM PREVIEW
================================ */
function confirmAndSubmit() {
  const form = document.querySelector("form");
  const overlay = document.getElementById("uploadOverlay");

  if (!form) return;

  // Close preview
  closePreview();

  // Show uploading overlay
  overlay.classList.remove("hidden");

  // Disable submit button
  const submitBtn = document.getElementById("submitBtn");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Uploading...";
  }

  // Submit form AFTER small delay (UI smooth)
  setTimeout(() => {
    form.submit();
  }, 300);
}
