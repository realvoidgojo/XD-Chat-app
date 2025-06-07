/**
 * Signup Form Handler
 * XD Chat App
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector("#signupForm");
    const continueBtn = form.querySelector("input[type='submit']");
    const errorText = form.querySelector(".error-text");

    if (!form || !continueBtn || !errorText) {
        console.error('Required form elements not found');
        return;
    }

    // Prevent default form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        handleSubmit();
    });

    function handleSubmit() {
        console.log('Signup form submission started');
        
        // Clear previous messages
        hideError();
        
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
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(responseText => {
            console.log('Raw response:', responseText);
            
            try {
                const data = JSON.parse(responseText);
                console.log('Parsed response:', data);
                
                if (data.success) {
                    console.log('Registration successful! Redirecting...');
                    
                    // Show success feedback briefly then redirect
                    continueBtn.value = 'Success! Redirecting...';
                    continueBtn.style.background = '#4CAF50';
                    
                    setTimeout(() => {
                        window.location.href = data.redirect || 'users.php';
                    }, 1000);
                    
                } else {
                    showError(data.error || 'Registration failed');
                    enableButton();
                }
            } catch (e) {
                console.error('JSON parsing error:', e);
                console.log('Response text:', responseText);
                
                // Check if it's a simple text response indicating success
                if (responseText.trim() === 'success') {
                    continueBtn.value = 'Success! Redirecting...';
                    continueBtn.style.background = '#4CAF50';
                    setTimeout(() => {
                        window.location.href = 'users.php';
                    }, 1000);
                } else {
                    showError(responseText || 'Registration failed');
                    enableButton();
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showError('Network error. Please check your connection and try again.');
            enableButton();
        });
    }

    function validateForm() {
        let isValid = true;
        let firstError = null;
        
        // Get form fields
        const fname = form.querySelector('input[name="fname"]');
        const lname = form.querySelector('input[name="lname"]');
        const email = form.querySelector('input[name="email"]');
        const password = form.querySelector('input[name="password"]');
        const image = form.querySelector('input[name="image"]');
        
        // Validate first name
        if (!fname || !fname.value.trim()) {
            showError('First name is required');
            firstError = fname;
            isValid = false;
        }
        
        // Validate last name
        if (!lname || !lname.value.trim()) {
            showError('Last name is required');
            if (!firstError) firstError = lname;
            isValid = false;
        }
        
        // Validate email
        if (!email || !email.value.trim()) {
            showError('Email address is required');
            if (!firstError) firstError = email;
            isValid = false;
        } else if (!isValidEmail(email.value)) {
            showError('Please enter a valid email address');
            if (!firstError) firstError = email;
            isValid = false;
        }
        
        // Validate password
        if (!password || password.value.length < 8) {
            showError('Password must be at least 8 characters long');
            if (!firstError) firstError = password;
            isValid = false;
        }
        
        // Validate image
        if (!image || !image.files || !image.files[0]) {
            showError('Please select a profile image');
            if (!firstError) firstError = image;
            isValid = false;
        } else {
            const file = image.files[0];
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            
            if (!allowedTypes.includes(file.type)) {
                showError('Please select a valid image file (JPEG, PNG, GIF)');
                if (!firstError) firstError = image;
                isValid = false;
            } else if (file.size > 1048576) { // 1MB
                showError('Image size must be less than 1MB');
                if (!firstError) firstError = image;
                isValid = false;
            }
        }
        
        // Focus on first error field
        if (firstError) {
            firstError.focus();
        }
        
        return isValid;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showError(message) {
        errorText.textContent = message;
        errorText.style.display = 'block';
    }

    function hideError() {
        errorText.style.display = 'none';
    }

    function enableButton() {
        continueBtn.disabled = false;
        continueBtn.value = 'Continue to Chat';
        continueBtn.style.background = '';
    }

    console.log('Signup form handler initialized');
}); 