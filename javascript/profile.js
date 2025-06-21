// Profile update script
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Show loading
            submitBtn.textContent = 'Updating...';
            submitBtn.disabled = true;
            
            // Clear messages
            clearMessages();
            
            fetch('php/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Profile updated successfully!', 'success');
                    
                    // Update UI
                    if (data.data) {
                        updateUIElements(data.data);
                    }
                    
                    // Close modal
                    setTimeout(() => {
                        document.getElementById('profileModal').style.display = 'none';
                    }, 1500);
                } else {
                    showMessage(data.error || 'Failed to update profile', 'error');
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                showMessage('Network error. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    // Image preview
    const profileImageInput = document.getElementById('profile-image');
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size
                if (file.size > 5 * 1024 * 1024) {
                    showMessage('Image size must be less than 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showMessage('Only JPEG, PNG, and GIF images are allowed', 'error');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

function showMessage(message, type) {
    // Remove existing
    clearMessages();
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        font-weight: 500;
        text-align: center;
        ${type === 'success' ? 
            'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 
            'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'}
    `;
    
    const form = document.getElementById('profileForm');
    if (form) {
        form.insertBefore(messageDiv, form.firstChild);
        
        // Auto remove
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 5000);
    }
}

function clearMessages() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(msg => {
        if (msg.parentNode) {
            msg.parentNode.removeChild(msg);
        }
    });
}

function updateUIElements(data) {
    // Update header info
    const headerName = document.querySelector('header .details span');
    if (headerName && data.fname && data.lname) {
        headerName.textContent = `${data.fname} ${data.lname}`;
    }
    
    const headerStatus = document.querySelector('header .details p');
    if (headerStatus && data.status) {
        headerStatus.textContent = data.status;
    }
    
    // Update images
    if (data.image) {
        // Update header image
        const headerImage = document.querySelector('header .content img');
        if (headerImage) {
            headerImage.src = `uploads/${data.image}?t=${Date.now()}`;
        }
        
        // Update preview
        const previewImage = document.getElementById('profile-preview');
        if (previewImage) {
            previewImage.src = `uploads/${data.image}?t=${Date.now()}`;
        }
        
        // Update all profile images
        const profileImages = document.querySelectorAll('img[alt="Profile Picture"]');
        profileImages.forEach(img => {
            img.src = `uploads/${data.image}?t=${Date.now()}`;
        });
    }
} 