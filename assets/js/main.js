// Main JavaScript for Online Learning System

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function () {
   var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
   var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
   });
});

// Course enrollment confirmation
function confirmEnrollment(courseId) {
   if (confirm('Are you sure you want to enroll in this course?')) {
      window.location.href = `modules/enroll.php?course_id=${courseId}`;
   }
}

// Progress bar animation
function animateProgress(progressElement) {
   const progress = progressElement.getAttribute('data-progress');
   progressElement.style.width = progress + '%';
}

// Form validation
function validateForm(formId) {
   const form = document.getElementById(formId);
   if (form) {
      form.addEventListener('submit', function (event) {
         let isValid = true;
         const requiredFields = form.querySelectorAll('[required]');

         requiredFields.forEach(field => {
            if (!field.value.trim()) {
               isValid = false;
               field.classList.add('is-invalid');
            } else {
               field.classList.remove('is-invalid');
            }
         });

         if (!isValid) {
            event.preventDefault();
         }
      });
   }
}

// Initialize form validation for all forms
document.addEventListener('DOMContentLoaded', function () {
   validateForm('loginForm');
   validateForm('registerForm');
   validateForm('courseForm');
}); 