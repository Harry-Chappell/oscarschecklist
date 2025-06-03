document.addEventListener('DOMContentLoaded', function () {
    const usernameInput = document.getElementById('signup_username');
    
    if (usernameInput) {
        // Notify user about space characters
        usernameInput.addEventListener('input', function(event) {
            if (/\s/.test(this.value)) { // If a space is detected
                alert('Spaces are not allowed in usernames. They will be removed.');
                this.value = this.value.replace(/\s+/g, ''); // Remove spaces
            }
        });
    } else {
        console.log("Input field not found");
    }
});

// Ensure the DOM is fully loaded before running the script
document.addEventListener('DOMContentLoaded', function () {
    const usernameInput = document.getElementById('signup_username');
    
    if (usernameInput) {
        // Prevent spaces from being entered
        usernameInput.addEventListener('input', function(event) {
            this.value = this.value.replace(/\s+/g, '-'); // Replace all spaces with hyphens
        });
    } else {
        console.log("Input field not found");
    }
});