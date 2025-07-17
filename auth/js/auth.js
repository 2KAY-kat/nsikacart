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
    // Password toggle functionality
    const toggleButtons = document.querySelectorAll('.toggle-password');
    const passwordInputs = document.querySelectorAll('.password-input');

    toggleButtons.forEach((button, index) => {
        // Initially hide toggle buttons
        button.style.display = 'none';
        
        // Find the corresponding password input
        const inputGroup = button.closest('.input-group');
        const passwordInput = inputGroup ? inputGroup.querySelector('.password-input') : null;
        
        if (!passwordInput) {
            console.warn('Password input not found for toggle button', button);
            return;
        }

        // Toggle password visibility
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });

        // Show/hide toggle button based on input focus and content
        passwordInput.addEventListener('focus', () => {
            button.style.display = 'block';
        });

        passwordInput.addEventListener('blur', () => {
            // Keep button visible if input has content
            if (passwordInput.value === '') {
                button.style.display = 'none';
            }
        });

        // Show button when user starts typing
        passwordInput.addEventListener('input', () => {
            if (passwordInput.value.length > 0) {
                button.style.display = 'block';
            } else {
                button.style.display = 'none';
            }
        });
    });

    // Password strength checker
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
});

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
        matchIcon.style.display = 'inline-block';
        mismatchIcon.style.display = 'none';
    } else {
        matchIcon.style.display = 'none';
        mismatchIcon.style.display = 'inline-block';
    }
}

// Make checkPasswordMatch globally available
window.checkPasswordMatch = checkPasswordMatch;