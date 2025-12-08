// Course slider simple logic
const slider = document.getElementById('courseSlider');
let pos = 0;
function slideCourses(dir){
  const step = 280; // approx width of a card + gap
  const maxScroll = slider.scrollWidth - slider.clientWidth;
  pos = Math.max(0, Math.min(maxScroll, pos + dir * step));
  slider.parentElement.scrollTo({left: pos, behavior: 'smooth'});
}

function enrollCourse(course){
  document.getElementById('course').value = course;
  window.scrollTo({top:0,behavior:'smooth'});
  alert('Selected course: ' + course + '. Please fill the form to enroll.');
}

function submitLead(){
  const name = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();
  const phone = document.getElementById('phone').value.trim();
  const course = document.getElementById('course').value;
  const consent = document.getElementById('consent').checked;

  if(!name || !email || !phone || !consent){
    alert('Please fill name, email, phone and accept consent to proceed.');
    return;
  }
  // simulate submission
  console.log({name,email,phone,course});
  alert('Thank you ' + name + '! Your request has been received. Our team will contact you.');
  // reset minimal
  document.getElementById('name').value='';
  document.getElementById('email').value='';
  document.getElementById('phone').value='';
  document.getElementById('course').value='';
  document.getElementById('consent').checked=false;
}

function submitFinal(){
  const name = document.getElementById('final_name').value.trim();
  const email = document.getElementById('final_email').value.trim();
  const phone = document.getElementById('final_phone').value.trim();
  const consent = document.getElementById('final_consent').checked;
  if(!name || !email || !phone || !consent){
    alert('Please fill name, email, phone and accept consent to apply.');
    return;
  }
  alert('Thanks ' + name + '! Application received.');
  document.getElementById('final_name').value='';
  document.getElementById('final_email').value='';
  document.getElementById('final_phone').value='';
  document.getElementById('final_consent').checked=false;
}

// small UX: allow pressing Enter in the lead form to submit
document.querySelectorAll('.lead-form input').forEach(inp=>{
  inp.addEventListener('keypress', (e)=>{
    if(e.key === 'Enter') { e.preventDefault(); submitLead(); }
  })
});
