// Password show/hide toggle
document.addEventListener('DOMContentLoaded', function() {
    console.log('Password toggle script loaded');
    
    // Init password toggles
    function initPasswordToggle() {
        // Get password fields
        const passwordFields = document.querySelectorAll('input[type="password"]');
        
        passwordFields.forEach(function(passwordField) {
            // Check for existing toggle
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
                
                // Make container relative
                passwordField.parentElement.style.position = 'relative';
                
                // Add toggle icon
                passwordField.parentElement.appendChild(toggleIcon);
                
                // Add padding for icon
                passwordField.style.paddingRight = '40px';
            }
        });
        
        // Add event listeners
        const toggleIcons = document.querySelectorAll('.toggle-password');
        
        toggleIcons.forEach(function(toggleIcon) {
            // Remove existing listeners
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
    
    // Initialize toggles
    initPasswordToggle();
    
    // Re-init for dynamic content
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

// Manual init fallback
window.initPasswordToggle = function() {
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);
};
