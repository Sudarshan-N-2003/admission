// assets/app.js - small UI helpers
function toUpper(el){ el.value = el.value.toUpperCase(); }
function nextStep(){ document.getElementById('step1').classList.remove('active'); document.getElementById('step2').classList.add('active'); }
function prevStep(){ document.getElementById('step2').classList.remove('active'); document.getElementById('step1').classList.add('active'); }
function onAdmissionChange(){ var v = document.getElementById('admission_through').value; document.getElementById('keaOnly').style.display = (v==='KEA')?'block':'none'; }
function onCategoryChange(){ var cat = document.getElementById('category').value; /* can hide caste upload if NOT APPLICABLE */ }

Array.from(document.querySelectorAll('input[type=text], textarea')).forEach(i=>{
  i.addEventListener('blur', ()=>{ i.value = i.value.toUpperCase(); });
});
