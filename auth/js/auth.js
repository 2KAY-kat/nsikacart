// Unified toast notification function
function showToast(message, type = 'success') {
    // Remove any existing toasts first
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.textContent = message;
    
    // Add styles inline to ensure they work
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease-in-out;
        max-width: 350px;
        word-wrap: break-word;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    
    // Set background color based on type
    switch(type) {
        case 'success':
            toast.style.backgroundColor = '#4CAF50';
            break;
        case 'error':
            toast.style.backgroundColor = '#f44336';
            break;
        case 'info':
            toast.style.backgroundColor = '#2196F3';
            break;
        default:
            toast.style.backgroundColor = '#333';
    }
    
    document.body.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove toast after delay
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

// Make showToast globally available
window.showToast = showToast;

document.addEventListener('DOMContentLoaded', function () {
    // Initialize password toggle functionality
    initializePasswordToggle();
    
    // Initialize password strength checker
    initializePasswordStrength();
});

function initializePasswordToggle() {
    const passwordInputs = document.querySelectorAll('.password-input');
    
    passwordInputs.forEach(input => {
        const inputGroup = input.closest('.input-group');
        if (!inputGroup) return;
        
        let toggleButton = inputGroup.querySelector('.toggle-password');
        
        // Create toggle button if it doesn't exist
        if (!toggleButton) {
            toggleButton = document.createElement('i');
            toggleButton.className = 'fas fa-eye-slash toggle-password';
            inputGroup.appendChild(toggleButton);
        }
        
        // Initialize button state
        toggleButton.style.display = 'none';
        
        // Toggle password visibility
        toggleButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });

        // Show toggle button on focus
        input.addEventListener('focus', () => {
            toggleButton.style.display = 'block';
        });

        // Hide toggle button on blur if input is empty
        input.addEventListener('blur', () => {
            if (input.value === '') {
                toggleButton.style.display = 'none';
            }
        });

        // Show toggle button when user starts typing
        input.addEventListener('input', () => {
            if (input.value.length > 0) {
                toggleButton.style.display = 'block';
            } else {
                toggleButton.style.display = 'none';
                // Reset to password type when empty
                input.type = 'password';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            }
        });
    });
}

function initializePasswordStrength() {
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            const meter = document.querySelector('.strength-meter');
            const text = document.querySelector('.strength-text');

            if (meter && text) {
                const strength = checkPasswordStrength(password);
                meter.className = 'strength-meter ' + strength;
                text.textContent = strength.charAt(0).toUpperCase() + strength.slice(1);
            }
        });
    }
}

// Password strength checker
function checkPasswordStrength(password) {
    if (password.length < 6) return 'weak';
    
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChars = /[!@#$~`?%^&*]/.test(password);
    
    const strength = hasUpperCase + hasLowerCase + hasNumbers + hasSpecialChars;
    
    if (strength >= 4) return 'strong';
    if (strength >= 2) return 'medium';
    return 'weak';
}

// Password match checker
function checkPasswordMatch() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm-password');
    const matchIcon = document.querySelector('.password-match-icon');
    const mismatchIcon = document.querySelector('.password-mismatch-icon');

    if (!password || !confirmPassword || !matchIcon || !mismatchIcon) return;

    const passwordValue = password.value;
    const confirmPasswordValue = confirmPassword.value;

    if (passwordValue === '' || confirmPasswordValue === '') {
        matchIcon.style.display = 'none';
        mismatchIcon.style.display = 'none';
        return;
    }

    if (passwordValue === confirmPasswordValue) {
        matchIcon.style.display = 'block';
        mismatchIcon.style.display = 'none';
    } else {
        matchIcon.style.display = 'none';
        mismatchIcon.style.display = 'block';
    }
}

// Make checkPasswordMatch globally available
window.checkPasswordMatch = checkPasswordMatch;