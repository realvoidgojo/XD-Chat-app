const searchInput = document.querySelector("#searchInput"),
  searchBtn = document.querySelector("#searchBtn"),
  clearSearchBtn = document.querySelector("#clearSearch"),
  usersList = document.querySelector(".users-list"),
  onlineCountElement = document.querySelector("#onlineUsersCount");

let searchActive = false;

// Search button
if (searchBtn) {
  searchBtn.onclick = () => {
    const searchTerm = searchInput.value.trim();
    if (searchTerm !== "") {
      performSearch(searchTerm);
    }
  };
}

// Clear search
if (clearSearchBtn) {
  clearSearchBtn.onclick = () => {
    searchInput.value = "";
    clearSearchBtn.style.display = "none";
    searchActive = false;
    loadUsers();
  };
}

// Search input
if (searchInput) {
  searchInput.onkeyup = (e) => {
    let searchTerm = searchInput.value.trim();
    
    if (searchTerm !== "") {
      searchActive = true;
      clearSearchBtn.style.display = "block";
      
      // Enter key search
      if (e.key === 'Enter') {
        performSearch(searchTerm);
      }
      // Auto-search with debounce
      clearTimeout(searchInput.searchTimeout);
      searchInput.searchTimeout = setTimeout(() => {
        performSearch(searchTerm);
      }, 300);
    } else {
      searchActive = false;
      clearSearchBtn.style.display = "none";
      clearTimeout(searchInput.searchTimeout);
      loadUsers();
    }
  };
}

function performSearch(searchTerm) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "php/search.php", true);
  xhr.onload = () => {
    if (xhr.readyState === XMLHttpRequest.DONE) {
      if (xhr.status === 200) {
        let data = xhr.response;
        usersList.innerHTML = data;
      } else {
        usersList.innerHTML = '<div class="no-users"><p>Search failed. Please try again.</p></div>';
      }
    }
  };
  xhr.onerror = () => {
    usersList.innerHTML = '<div class="no-users"><p>Network error. Please try again.</p></div>';
  };
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhr.send("searchTerm=" + encodeURIComponent(searchTerm));
}

function loadUsers() {
  let xhr = new XMLHttpRequest();
  xhr.open("GET", "php/users.php", true);
  xhr.onload = () => {
    if (xhr.readyState === XMLHttpRequest.DONE) {
      if (xhr.status === 200) {
        let data = xhr.response;
        if (!searchActive) {
          usersList.innerHTML = data;
          updateOnlineCount();
        }
      } else {
        if (!searchActive) {
          usersList.innerHTML = '<div class="no-users"><p>Failed to load users.</p></div>';
        }
      }
    }
  };
  xhr.onerror = () => {
    if (!searchActive) {
      usersList.innerHTML = '<div class="no-users"><p>Network error.</p></div>';
    }
  };
  xhr.send();
}

function updateOnlineCount() {
  if (!onlineCountElement) return;
  
  // Count online users
  const userItems = usersList.querySelectorAll('.user-item:not(.hide)');
  let onlineCount = 0;
  
  userItems.forEach(item => {
    const statusDot = item.querySelector('.status-dot');
    if (statusDot && !statusDot.classList.contains('offline')) {
      onlineCount++;
    }
  });
  
  // Update display
  if (onlineCount === 0) {
    onlineCountElement.textContent = 'No users online';
  } else if (onlineCount === 1) {
    onlineCountElement.textContent = '1 user online';
  } else {
    onlineCountElement.textContent = `${onlineCount} users online`;
  }
}

// Load users initially
loadUsers();

// Refresh every 5 seconds
setInterval(() => {
  if (!searchActive) {
    loadUsers();
  }
}, 5000);

// Update status every 30s
setInterval(() => {
  fetch('php/update-status.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'status=Active now'
  }).catch(error => {
    console.log('Status update failed:', error);
  });
}, 30000);

// Handle page visibility
document.addEventListener('visibilitychange', function() {
  if (document.hidden) {
    // User away
    fetch('php/update-status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'status=Away'
    }).catch(error => {
      console.log('Status update failed:', error);
    });
  } else {
    // User back
    fetch('php/update-status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'status=Active now'
    }).catch(error => {
      console.log('Status update failed:', error);
    });
    // Reload users
    if (!searchActive) {
      loadUsers();
    }
  }
});

// Update on page leave
window.addEventListener('beforeunload', function() {
  navigator.sendBeacon('php/update-status.php', 'status=Offline');
});
