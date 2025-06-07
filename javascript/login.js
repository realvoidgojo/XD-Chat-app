const form = document.querySelector(".login form"),
continueBtn = form.querySelector(".button input"),
errorText = form.querySelector(".error-text");

form.onsubmit = (e)=>{
    e.preventDefault();
}

continueBtn.onclick = ()=>{
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "php/login.php", true);
    xhr.onload = ()=>{
      if(xhr.readyState === XMLHttpRequest.DONE){
          if(xhr.status === 200){
              let data = xhr.response.trim(); // Trim any whitespace
              console.log("Login response:", data); // Debug log
              
              if(data === "success"){
                  console.log("Redirecting to users.php"); // Debug log
                  window.location.href = "users.php";
              }else{
                  errorText.style.display = "block";
                  errorText.textContent = data;
                  console.log("Login failed:", data); // Debug log
              }
          } else {
              errorText.style.display = "block";
              errorText.textContent = "Request failed. Please try again.";
              console.log("Request failed with status:", xhr.status); // Debug log
          }
      }
    }
    
    // Add error handling
    xhr.onerror = () => {
        errorText.style.display = "block";
        errorText.textContent = "Network error. Please try again.";
        console.log("Network error occurred"); // Debug log
    }
    
    let formData = new FormData(form);
    xhr.send(formData);
}