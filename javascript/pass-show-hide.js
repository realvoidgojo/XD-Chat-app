/**
 * Password Show/Hide Toggle - Enhanced Version
 * Handles password fields with toggle icons
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Password toggle script loaded');
    
    // Function to initialize password toggles
    function initPasswordToggle() {
        // Get all password fields
        const passwordFields = document.querySelectorAll('input[type="password"]');
        
        passwordFields.forEach(function(passwordField) {
            // Check if this field already has a toggle
            const existingToggle = passwordField.parentElement.querySelector('.toggle-password');
            
            if (!existingToggle) {
                // Create toggle icon
                const toggleIcon = document.createElement('i');
                toggleIcon.className = 'fas fa-eye toggle-password';
                toggleIcon.setAttribute('title', 'Show password');
                toggleIcon.style.cssText = `
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    cursor: pointer;
                    color: #999;
                    font-size: 16px;
                    z-index: 10;
                `;
                
                // Make the field container relative
                passwordField.parentElement.style.position = 'relative';
                
                // Add toggle icon to field container
                passwordField.parentElement.appendChild(toggleIcon);
                
                // Add padding to password field so text doesn't overlap with icon
                passwordField.style.paddingRight = '40px';
            }
        });
        
        // Add event listeners to all toggle icons
        const toggleIcons = document.querySelectorAll('.toggle-password');
        
        toggleIcons.forEach(function(toggleIcon) {
            // Remove existing listeners to prevent duplicates
            toggleIcon.removeEventListener('click', togglePassword);
            toggleIcon.addEventListener('click', togglePassword);
        });
    }
    
    // Toggle password function
    function togglePassword(event) {
        const toggleIcon = event.currentTarget;
        const passwordField = toggleIcon.parentElement.querySelector('input[type="password"], input[type="text"]');
        
        if (passwordField) {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                toggleIcon.setAttribute('title', 'Hide password');
                toggleIcon.style.color = '#007bff';
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleIcon.setAttribute('title', 'Show password');
                toggleIcon.style.color = '#999';
            }
        }
    }
    
    // Initialize password toggles
    initPasswordToggle();
    
    // Re-initialize if new content is loaded dynamically
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                initPasswordToggle();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('Password toggle initialized');
});

// Fallback for manual initialization
window.initPasswordToggle = function() {
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);
};
