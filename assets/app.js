function onAdmissionChange() {
  const type = document.getElementById('admission_through').value;

  document.getElementById('kea_section').style.display =
    type === 'KEA' ? 'block' : 'none';

  document.getElementById('management_section').style.display =
    type === 'MANAGEMENT' ? 'block' : 'none';
}
