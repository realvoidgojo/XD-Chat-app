/**
 * Signup Form Handler - Fixed Version
 * XD Chat App
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector("#signupForm");
    const continueBtn = form.querySelector(".button input");
    const errorText = form.querySelector(".error-text");
    const successText = form.querySelector(".success-text");

    // Prevent default form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        handleSubmit();
    });

    // Button click handler
    continueBtn.addEventListener('click', function(e) {
        e.preventDefault();
        handleSubmit();
    });

    function handleSubmit() {
        console.log('Form submission started');
        
        // Clear previous messages
        showError('');
        showSuccess('');
        
        // Client-side validation
        if (!validateForm()) {
            console.log('Form validation failed');
            return;
        }
        
        // Disable button to prevent double submission
        continueBtn.disabled = true;
        continueBtn.value = 'Creating Account...';
        
        const formData = new FormData(form);
        
        fetch('php/signup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(responseText => {
            console.log('Raw response:', responseText);
            
            try {
                const data = JSON.parse(responseText);
                console.log('Parsed response:', data);
                
                if (data.success) {
                    showSuccess(data.message || 'Registration successful!');
                    console.log('Success! Redirecting to:', data.redirect);
                    
                    // Immediate redirect using window.location.replace
                    window.location.replace(data.redirect || 'users.php');
                    
                } else {
                    showError(data.error || 'Registration failed');
                    continueBtn.disabled = false;
                    continueBtn.value = 'Continue to Chat';
                }
            } catch (e) {
                console.error('JSON parsing error:', e);
                console.log('Response text:', responseText);
                
                // Check if response contains success indicator
                if (responseText.includes('"success":true') || responseText.includes('success')) {
                    showSuccess('Registration successful! Redirecting...');
                    window.location.replace('users.php');
                } else {
                    showError('Invalid response from server');
                    continueBtn.disabled = false;
                    continueBtn.value = 'Continue to Chat';
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showError('Network error. Please try again.');
            continueBtn.disabled = false;
            continueBtn.value = 'Continue to Chat';
        });
    }

    function validateForm() {
        let isValid = true;
        
        // Validate name fields
        const fname = document.getElementById('fname');
        const lname = document.getElementById('lname');
        
        if (!fname.value.trim()) {
            showFieldError(fname, 'First name is required');
            isValid = false;
        }
        
        if (!lname.value.trim()) {
            showFieldError(lname, 'Last name is required');
            isValid = false;
        }
        
        // Validate email
        const email = document.getElementById('email');
        if (!email.value.trim() || !isValidEmail(email.value)) {
            showFieldError(email, 'Please enter a valid email address');
            isValid = false;
        }
        
        // Validate password
        const password = document.getElementById('password');
        if (password.value.length < 8) {
            showFieldError(password, 'Password must be at least 8 characters');
            isValid = false;
        }
        
        // Validate confirm password
        const confirmPassword = document.getElementById('confirm-password');
        if (password.value !== confirmPassword.value) {
            showFieldError(confirmPassword, 'Passwords do not match');
            isValid = false;
        }
        
        // Validate image
        const image = document.getElementById('image');
        if (!image.files[0]) {
            showFieldError(image, 'Please select a profile image');
            isValid = false;
        }
        
        return isValid;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showError(message) {
        if (message) {
            errorText.textContent = message;
            errorText.style.display = 'block';
            successText.style.display = 'none';
        } else {
            errorText.style.display = 'none';
        }
    }

    function showSuccess(message) {
        if (message) {
            successText.textContent = message;
            successText.style.display = 'block';
            errorText.style.display = 'none';
        } else {
            successText.style.display = 'none';
        }
    }

    function showFieldError(field, message) {
        const errorField = field.parentElement.querySelector('.error-field');
        if (errorField) {
            errorField.textContent = message;
            errorField.style.display = 'block';
        }
    }

    // Clear field errors on input
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            const errorField = this.parentElement.querySelector('.error-field');
            if (errorField) {
                errorField.style.display = 'none';
            }
        });
    });
}); 