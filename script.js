// Custom notification function to replace browser alerts
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `custom-notification ${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);
  
  // Trigger animation
  setTimeout(() => notification.classList.add('show'), 10);
  
  // Auto remove after 4 seconds
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => notification.remove(), 300);
  }, 4000);
}

// Course slider with navigation arrows
let currentScrollPosition = 0;

function scrollCourses(direction) {
  const activeTab = document.querySelector('.tab-content.active');
  const coursesGrid = activeTab.querySelector('.courses-grid');
  const cardWidth = 340; // card width
  const gap = 24; // gap between cards
  const scrollAmount = (cardWidth + gap) * 3; // Scroll 3 cards at a time
  
  if (direction === 'next') {
    coursesGrid.scrollBy({
      left: scrollAmount,
      behavior: 'smooth'
    });
  } else {
    coursesGrid.scrollBy({
      left: -scrollAmount,
      behavior: 'smooth'
    });
  }
}

function enrollCourse(course, university){
  // Open the Apply Now modal and pre-select the course
  const courseSelect = document.getElementById('apply_course');
  if (courseSelect) {
    courseSelect.value = course;
  }
  openApplyNowModal();
}

// Tab switching function
function showTab(tabName) {
  // Hide all tab contents
  const tabContents = document.querySelectorAll('.tab-content');
  tabContents.forEach(content => {
    content.classList.remove('active');
  });
  
  // Remove active class from all buttons
  const tabButtons = document.querySelectorAll('.tab-btn');
  tabButtons.forEach(btn => {
    btn.classList.remove('active');
  });
  
  // Show selected tab content
  const selectedTab = document.getElementById(tabName);
  if (selectedTab) {
    selectedTab.classList.add('active');
  }
  
  // Add active class to clicked button
  event.target.classList.add('active');
}

// Form submission function for hero form
function submitLead(){
  const name = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();
  const phone = document.getElementById('phone').value.trim();
  const course = document.getElementById('course').value;
  const consent = document.getElementById('consent').checked;

  // Basic validation
  if(!name || !email || !phone || !course || !consent){
    showNotification('Please fill all fields and accept consent to proceed.', 'error');
    return;
  }
  
  // Validate name (at least 2 characters, letters only)
  if (name.length < 2 || !/^[a-zA-Z\s]+$/.test(name)) {
    showNotification('Please enter a valid name (letters only, minimum 2 characters)', 'error');
    return;
  }

  // Validate email
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showNotification('Please enter a valid email address.', 'error');
    return;
  }

  // Validate phone (exactly 10 digits)
  if (!/^[0-9]{10}$/.test(phone)) {
    showNotification('Please enter exactly 10 digits for mobile number.', 'error');
    return;
  }

  // Send data to server
  fetch('submit_lead.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: name,
      email: email,
      phone: phone,
      course: course,
      consent: consent
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('Thank you ' + name + '! Redirecting...', 'success');
      // Set access flag for thank you page
      sessionStorage.setItem('formSubmitted', 'true');
      // Redirect to thank you page after 1 second
      setTimeout(() => {
        window.location.href = 'thankyou.html';
      }, 1000);
    } else {
      showNotification('Error: ' + data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('An error occurred. Please try again later.', 'error');
  });
}

function submitFinal(){
  const name = document.getElementById('final_name').value.trim();
  const email = document.getElementById('final_email').value.trim();
  const phone = document.getElementById('final_phone').value.trim();
  const consent = document.getElementById('final_consent').checked;
  
  if(!name || !email || !phone || !consent){
    showNotification('Please fill name, email, phone and accept consent to apply.', 'error');
    return;
  }
  
  // Validate email
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showNotification('Please enter a valid email address.', 'error');
    return;
  }
  
  // Validate phone (10 digits)
  if (!/^[0-9]{10}$/.test(phone)) {
    showNotification('Please enter a valid 10-digit mobile number.', 'error');
    return;
  }
  
  // Send data to server
  fetch('submit_lead.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: name,
      email: email,
      phone: phone,
      consent: consent,
      form_type: 'final'
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('Thanks ' + name + '! Application received.', 'success');
      document.getElementById('final_name').value='';
      document.getElementById('final_email').value='';
      document.getElementById('final_phone').value='';
      document.getElementById('final_consent').checked=false;
    } else {
      showNotification('Error: ' + data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('An error occurred. Please try again later.', 'error');
  });
}

// small UX: allow pressing Enter in the lead form to submit
document.querySelectorAll('.lead-form input').forEach(inp=>{
  inp.addEventListener('keypress', (e)=>{
    if(e.key === 'Enter') { e.preventDefault(); submitLead(); }
  })
});

// Brochure Modal Functions
function openBrochureModal() {
  const modal = document.getElementById('brochureModal');
  modal.classList.add('show');
  document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeBrochureModal() {
  const modal = document.getElementById('brochureModal');
  modal.classList.remove('show');
  document.body.style.overflow = 'auto'; // Restore scrolling
}

// Close modal when clicking outside of it
window.onclick = function(event) {
  const modal = document.getElementById('brochureModal');
  if (event.target === modal) {
    closeBrochureModal();
  }
}

// Handle brochure download form submission
function downloadBrochure(event) {
  event.preventDefault();
  
  const name = document.getElementById('brochure_name').value.trim();
  const email = document.getElementById('brochure_email').value.trim();
  const phone = document.getElementById('brochure_phone').value.trim();
  const course = document.getElementById('brochure_course').value;
  const consent = document.getElementById('brochure_consent').checked;
  
  if (!name || !email || !phone || !course || !consent) {
    showNotification('Please fill all required fields and accept the consent.', 'error');
    return;
  }
  
  // Validate email
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showNotification('Please enter a valid email address.', 'error');
    return;
  }
  
  // Validate phone number (10 digits)
  if (phone.length !== 10 || !/^[0-9]{10}$/.test(phone)) {
    showNotification('Please enter a valid 10-digit mobile number.', 'error');
    return;
  }
  
  // Send data to server
  fetch('submit_brochure.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: name,
      email: email,
      phone: phone,
      course: course,
      consent: consent
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification(`Thank you, ${name}! ${data.message}`, 'success');
      // Reset form
      document.getElementById('brochureForm').reset();
      // Close modal
      closeBrochureModal();
      // Trigger brochure download if link provided
      if (data.download_link) {
        // window.location.href = data.download_link;
      }
    } else {
      showNotification('Error: ' + data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('An error occurred. Please try again later.', 'error');
  });
}

// Placement carousel scroll function
function scrollPlacement(direction) {
  const container = document.getElementById('placementScroll');
  const cardWidth = 320; // card width
  const gap = 30; // gap between cards
  const scrollAmount = (cardWidth + gap) * 2; // Scroll 2 cards at a time
  
  if (direction === 'right') {
    container.scrollBy({
      left: scrollAmount,
      behavior: 'smooth'
    });
  } else {
    container.scrollBy({
      left: -scrollAmount,
      behavior: 'smooth'
    });
  }
}

// Testimonial carousel scroll function
function scrollTestimonials(direction) {
  const container = document.getElementById('testimonialSlider');
  const cardWidth = 320; // card width
  const gap = 25; // gap between cards
  const scrollAmount = (cardWidth + gap); // Scroll 1 card at a time
  
  if (direction === 'right') {
    container.scrollBy({
      left: scrollAmount,
      behavior: 'smooth'
    });
  } else {
    container.scrollBy({
      left: -scrollAmount,
      behavior: 'smooth'
    });
  }
}

// Play testimonial video
function playTestimonialVideo(videoId) {
  // Open video modal or redirect to video
  console.log('Playing video:', videoId);
  // You can implement a video modal here
  alert('Video player will open: ' + videoId);
}

// Play admission process video
function playAdmissionVideo() {
  // Open admission process video modal or YouTube link
  console.log('Playing admission process video');
  // You can implement a video modal or link to YouTube video
  alert('Admission process video will play here. You can link this to a YouTube video or video modal.');
}

// Faculty carousel scroll function
function scrollFaculty(direction) {
  const container = document.getElementById('facultySlider');
  const cardWidth = 320; // card width
  const gap = 25; // gap between cards
  const scrollAmount = (cardWidth + gap) * 2; // Scroll 2 cards at a time
  
  if (direction === 'right') {
    container.scrollBy({
      left: scrollAmount,
      behavior: 'smooth'
    });
  } else {
    container.scrollBy({
      left: -scrollAmount,
      behavior: 'smooth'
    });
  }
}

// Show full testimonial text
function showFullTestimonial(testimonialId) {
  // Open a modal with full testimonial text
  console.log('Showing full testimonial:', testimonialId);
  // You can implement a modal to show the full testimonial
  alert('Full testimonial will be displayed in a modal (testimonial ' + testimonialId + ')');
}

// Testimonial Modal Functions
function openTestimonialModal(name, course, image, description) {
  console.log('Opening modal for:', name); // Debug log
  const modal = document.getElementById('testimonialModal');
  
  if (!modal) {
    console.error('Modal not found!');
    return;
  }
  
  document.getElementById('modalTestiName').textContent = name;
  document.getElementById('modalTestiCourse').textContent = course;
  document.getElementById('modalTestiImg').src = image;
  document.getElementById('modalTestiDesc').textContent = description;
  modal.classList.add('show');
  modal.style.display = 'flex';
}

function closeTestimonialModal() {
  const modal = document.getElementById('testimonialModal');
  modal.classList.remove('show');
  modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
  const brochureModal = document.getElementById('brochureModal');
  const testimonialModal = document.getElementById('testimonialModal');
  const applyNowModal = document.getElementById('applyNowModal');
  
  if (event.target == brochureModal) {
    closeBrochureModal();
  }
  
  if (event.target == testimonialModal) {
    closeTestimonialModal();
  }
  
  if (event.target == applyNowModal) {
    closeApplyNowModal();
  }
}

// Apply Now Modal Functions
function openApplyNowModal() {
  const modal = document.getElementById('applyNowModal');
  modal.classList.add('show');
  modal.style.display = 'flex';
}

function closeApplyNowModal() {
  const modal = document.getElementById('applyNowModal');
  modal.classList.remove('show');
  modal.style.display = 'none';
}

function submitApplyNow(event) {
  event.preventDefault();
  
  const name = document.getElementById('apply_name').value.trim();
  const email = document.getElementById('apply_email').value.trim();
  const mobile = document.getElementById('apply_mobile').value.trim();
  const course = document.getElementById('apply_course').value;
  const consent = document.getElementById('apply_consent').checked;
  
  if (!name || !email || !mobile || !course) {
    showNotification('Please fill all required fields', 'error');
    return;
  }
  
  if (!consent) {
    showNotification('Please accept the terms and conditions', 'error');
    return;
  }
  
  // Validate email
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showNotification('Please enter a valid email address.', 'error');
    return;
  }
  
  // Validate mobile (10 digits)
  if (!/^[0-9]{10}$/.test(mobile)) {
    showNotification('Please enter a valid 10-digit mobile number.', 'error');
    return;
  }
  
  // Send data to server
  fetch('submit_application.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: name,
      email: email,
      mobile: mobile,
      course: course,
      consent: consent
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification(`Thank you, ${name}! Redirecting...`, 'success');
      // Set access flag for thank you page
      sessionStorage.setItem('formSubmitted', 'true');
      // Redirect to thank you page after 1 second
      setTimeout(() => {
        window.location.href = 'thankyou.html';
      }, 1000);
      // Reset form
      document.getElementById('applyNowForm').reset();
      // Close modal
      closeApplyNowModal();
    } else {
      showNotification('Error: ' + data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('An error occurred. Please try again later.', 'error');
  });
}

// Read More functionality for testimonials (now handled via onclick in HTML)
document.addEventListener('DOMContentLoaded', function() {
  // Any additional initialization can go here
  
  // Auto-open Apply Now modal after 6 seconds (only once per session)
  if (!sessionStorage.getItem('popupShown')) {
    setTimeout(function() {
      // Only show if no modal is currently open and form hasn't been submitted
      const brochureModal = document.getElementById('brochureModal');
      const testimonialModal = document.getElementById('testimonialModal');
      const applyNowModal = document.getElementById('applyNowModal');
      
      const isBrochureOpen = brochureModal && brochureModal.classList.contains('show');
      const isTestimonialOpen = testimonialModal && (testimonialModal.classList.contains('show') || testimonialModal.style.display === 'flex');
      const isApplyNowOpen = applyNowModal && (applyNowModal.classList.contains('show') || applyNowModal.style.display === 'flex');
      
      // Don't show if any modal is already open or form was already submitted
      if (!isBrochureOpen && !isTestimonialOpen && !isApplyNowOpen && !sessionStorage.getItem('formSubmitted')) {
        openApplyNowModal();
        sessionStorage.setItem('popupShown', 'true');
      }
    }, 6000); // 6 seconds delay
  }
});

// Footer Form Submission
function submitFooterForm(event) {
  event.preventDefault();
  
  const name = document.getElementById('footer_name').value.trim();
  const email = document.getElementById('footer_email').value.trim();
  const phone = document.getElementById('footer_mobile').value.trim();
  const course = document.getElementById('footer_course').value;
  const consent = document.getElementById('footer_consent').checked;
  
  // Validation
  if (!name || !email || !phone || !course || !consent) {
    showNotification('Please fill all fields and accept consent', 'error');
    return;
  }
  
  if (!/^[a-zA-Z\s]+$/.test(name) || name.length < 2) {
    showNotification('Please enter a valid name (letters only, minimum 2 characters)', 'error');
    return;
  }
  
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showNotification('Please enter a valid email address', 'error');
    return;
  }
  
  if (!/^[0-9]{10}$/.test(phone)) {
    showNotification('Please enter exactly 10 digits for mobile number', 'error');
    return;
  }
  
  // Submit to server
  fetch('submit_lead.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      name: name,
      email: email,
      phone: phone,
      course: course,
      consent: consent,
      form_type: 'footer'
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('Thank you ' + name + '! Redirecting...', 'success');
      // Set access flag for thank you page
      sessionStorage.setItem('formSubmitted', 'true');
      // Redirect to thank you page after 1 second
      setTimeout(() => {
        window.location.href = 'thankyou.html';
      }, 1000);
      // Reset form
      document.getElementById('footerForm').reset();
    } else {
      showNotification('Error: ' + data.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('An error occurred. Please try again later.', 'error');
  });
}

// Rankings slider with auto-scroll
let rankingScrollInterval;
const rankingTrack = document.getElementById('rankingTrack');

function scrollRankings(direction) {
  if (!rankingTrack) return;
  
  const cardWidth = 200; // min-width of rank-item
  const gap = 20;
  const scrollAmount = (cardWidth + gap) * 3; // Scroll 3 cards at a time
  
  if (direction === 'right') {
    rankingTrack.scrollBy({
      left: scrollAmount,
      behavior: 'smooth'
    });
  } else {
    rankingTrack.scrollBy({
      left: -scrollAmount,
      behavior: 'smooth'
    });
  }
  
  // Reset auto-scroll timer when manually scrolling
  resetRankingAutoScroll();
}

function autoScrollRankings() {
  if (!rankingTrack) return;
  
  const maxScroll = rankingTrack.scrollWidth - rankingTrack.clientWidth;
  
  // If reached the end, scroll back to start
  if (rankingTrack.scrollLeft >= maxScroll - 10) {
    rankingTrack.scrollTo({
      left: 0,
      behavior: 'smooth'
    });
  } else {
    // Scroll right by one card
    rankingTrack.scrollBy({
      left: 220, // cardWidth + gap
      behavior: 'smooth'
    });
  }
}

function startRankingAutoScroll() {
  // Auto-scroll every 3 seconds
  rankingScrollInterval = setInterval(autoScrollRankings, 3000);
}

function resetRankingAutoScroll() {
  clearInterval(rankingScrollInterval);
  startRankingAutoScroll();
}

// Start auto-scroll when page loads
if (rankingTrack) {
  startRankingAutoScroll();
  
  // Pause auto-scroll on hover
  rankingTrack.addEventListener('mouseenter', () => {
    clearInterval(rankingScrollInterval);
  });
  
  // Resume auto-scroll when mouse leaves
  rankingTrack.addEventListener('mouseleave', () => {
    startRankingAutoScroll();
  });
}

// New ranking tab switching and scrolling functions
function switchRankingTab(university) {
  // Hide all tabs
  const allTabs = document.querySelectorAll('.ranking-tab-content');
  allTabs.forEach(tab => tab.classList.remove('active'));
  
  // Show selected tab
  const selectedTab = document.getElementById(university + '-rankings');
  if (selectedTab) {
    selectedTab.classList.add('active');
  }
  
  // Update button states
  const allButtons = document.querySelectorAll('.ranking-tab-btn');
  allButtons.forEach(btn => btn.classList.remove('active'));
  
  event.target.classList.add('active');
}

function scrollRankings(direction, university) {
  const container = document.querySelector(`#${university}-rankings .rankings-grid-scroll`);
  if (!container) return;
  
  const scrollAmount = 280; // card width + gap
  
  if (direction === 'right') {
    container.scrollBy({
      left: scrollAmount,
      behavior: 'smooth'
    });
  } else {
    container.scrollBy({
      left: -scrollAmount,
      behavior: 'smooth'
    });
  }
}

// BCom page specific functions
function handleApplyFormSubmit(event) {
  event.preventDefault();
  
  const formData = new FormData(event.target);
  const data = Object.fromEntries(formData.entries());
  
  // Validate
  if (!data.name || !data.email || !data.phone || !data.institution) {
    showNotification('Please fill all required fields', 'error');
    return;
  }
  
  // Validate email
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
    showNotification('Please enter a valid email address', 'error');
    return;
  }
  
  // Validate phone (10 digits)
  if (!/^[0-9]{10}$/.test(data.phone)) {
    showNotification('Please enter a valid 10-digit mobile number', 'error');
    return;
  }
  
  // Submit to server
  fetch('submit_application.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  })
  .then(response => response.json())
  .then(result => {
    if (result.success) {
      showNotification('Thank you! Redirecting...', 'success');
      sessionStorage.setItem('formSubmitted', 'true');
      setTimeout(() => {
        window.location.href = 'thankyou.html';
      }, 1000);
      event.target.reset();
    } else {
      showNotification('Error: ' + result.message, 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('An error occurred. Please try again later.', 'error');
  });
}

