const form = document.querySelector(".typing-area"),
incoming_id = form.querySelector(".incoming_id").value,
inputField = form.querySelector(".input-field"),
sendBtn = form.querySelector("button"),
chatBox = document.querySelector(".chat-box");

let isLoading = false;
let lastMessageCount = 0;
let messageQueue = [];
let lastSentTime = 0;
const minMessageInterval = 500; // Minimum 500ms between messages

form.onsubmit = (e) => {
    e.preventDefault();
    sendMessage();
}

inputField.focus();

inputField.oninput = () => {
    const messageText = inputField.value.trim();
    updateSendButton();
    
    // Show character count for long messages
    if (messageText.length > 800) {
        showCharacterCount(messageText.length);
    } else {
        hideCharacterCount();
    }
}

function updateSendButton() {
    const messageText = inputField.value.trim();
    const now = Date.now();
    const timeSinceLastSent = now - lastSentTime;
    
    if (messageText !== "" && 
        messageText.length <= 1000 && 
        !isLoading &&
        timeSinceLastSent >= minMessageInterval) {
        sendBtn.classList.add("active");
        sendBtn.disabled = false;
        sendBtn.style.cursor = "pointer";
    } else {
        sendBtn.classList.remove("active");
        if (isLoading) {
            sendBtn.disabled = true;
            sendBtn.style.cursor = "not-allowed";
        } else if (timeSinceLastSent < minMessageInterval) {
            sendBtn.disabled = true;
            sendBtn.style.cursor = "wait";
            // Re-enable after cooldown
            setTimeout(() => {
                if (!isLoading) {
                    updateSendButton();
                }
            }, minMessageInterval - timeSinceLastSent);
        } else {
            sendBtn.disabled = true;
            sendBtn.style.cursor = "not-allowed";
        }
    }
}

// Handle Enter key to send message
inputField.onkeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (!sendBtn.disabled && !isLoading) {
            sendMessage();
        }
    }
}

sendBtn.onclick = (e) => {
    e.preventDefault();
    if (!sendBtn.disabled && !isLoading) {
        sendMessage();
    }
}

function sendMessage() {
    const messageText = inputField.value.trim();
    const now = Date.now();
    
    if (isLoading || messageText === "" || messageText.length > 1000) {
        return false;
    }
    
    // Check rate limiting
    if (now - lastSentTime < minMessageInterval) {
        showError(`Please wait ${Math.ceil((minMessageInterval - (now - lastSentTime)) / 1000)} seconds before sending another message.`);
        return false;
    }
    
    isLoading = true;
    lastSentTime = now;
    sendBtn.disabled = true;
    sendBtn.style.cursor = "not-allowed";
    
    // Store original button content
    const originalContent = sendBtn.innerHTML;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "php/insert-chat.php", true);
    xhr.onload = () => {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                let response = xhr.response.trim();
                if (response === "success") {
                    inputField.value = "";
                    hideCharacterCount();
                    scrollToBottom();
                    // Immediately fetch new messages
                    fetchMessages();
                } else {
                    showError("Failed to send message. Please try again.");
                }
            } else {
                showError("Network error. Please check your connection.");
            }
        }
        
        // Reset button state
        isLoading = false;
        sendBtn.innerHTML = originalContent;
        
        // Small delay before allowing next message
        setTimeout(() => {
            updateSendButton();
        }, 100);
    }
    
    xhr.onerror = () => {
        showError("Network error. Please try again.");
        isLoading = false;
        sendBtn.innerHTML = originalContent;
        setTimeout(() => {
            updateSendButton();
        }, 100);
    }
    
    xhr.ontimeout = () => {
        showError("Request timeout. Please try again.");
        isLoading = false;
        sendBtn.innerHTML = originalContent;
        setTimeout(() => {
            updateSendButton();
        }, 100);
    }
    
    xhr.timeout = 10000; // 10 second timeout
    
    let formData = new FormData(form);
    xhr.send(formData);
    
    return true;
}

chatBox.onmouseenter = () => {
    chatBox.classList.add("active");
}

chatBox.onmouseleave = () => {
    chatBox.classList.remove("active");
}

// Auto-scroll handling
let autoScroll = true;
chatBox.onscroll = () => {
    const threshold = 50;
    autoScroll = (chatBox.scrollTop + chatBox.clientHeight + threshold) >= chatBox.scrollHeight;
}

// Fetch messages periodically
function fetchMessages() {
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "php/get-chat.php", true);
    xhr.onload = () => {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                let data = xhr.response;
                
                // Count messages to detect new ones
                let messageCount = (data.match(/class="chat"/g) || []).length;
                
                chatBox.innerHTML = data;
                
                // Only auto-scroll if user hasn't manually scrolled up or if there are new messages
                if (autoScroll || messageCount > lastMessageCount) {
                    scrollToBottom();
                }
                
                lastMessageCount = messageCount;
                
                // Remove loading state
                chatBox.classList.remove("loading");
            }
        }
    }
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send("incoming_id=" + incoming_id);
}

function scrollToBottom() {
    setTimeout(() => {
        chatBox.scrollTop = chatBox.scrollHeight;
    }, 100);
}

function showCharacterCount(count) {
    let countDisplay = document.querySelector('.character-count');
    if (!countDisplay) {
        countDisplay = document.createElement('div');
        countDisplay.className = 'character-count';
        form.appendChild(countDisplay);
    }
    
    const remaining = 1000 - count;
    countDisplay.textContent = `${remaining} characters remaining`;
    countDisplay.style.color = remaining < 100 ? '#ff4757' : '#747d8c';
}

function hideCharacterCount() {
    const countDisplay = document.querySelector('.character-count');
    if (countDisplay) {
        countDisplay.remove();
    }
}

function showError(message) {
    // Create or update error notification
    let errorDiv = document.querySelector('.error-notification');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-notification';
        document.body.appendChild(errorDiv);
    }
    
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }, 3000);
}

// Start fetching messages
chatBox.classList.add("loading");
fetchMessages();

// Set up periodic message fetching (increased to 1.5 seconds for better performance)
setInterval(fetchMessages, 1500);

// Update user activity status
setInterval(() => {
    fetch('api/update-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'status=Active now'
    }).catch(error => {
        console.log('Status update failed:', error);
    });
}, 30000); // Every 30 seconds

// Update send button state periodically
setInterval(() => {
    if (!isLoading) {
        updateSendButton();
    }
}, 1000);
  