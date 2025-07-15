// simple toast notification function
function showToast(message, type = 'info') {
    let toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function () {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    const passwordInputs = document.querySelectorAll('.password-input');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function () {
            const passwordInput = this.previousElementSibling;

            // toggling password visibility
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

        toggleButtons.forEach(button => {
            button.style.display = 'none';
        });

        passwordInputs.forEach((input, index) => {
            input.addEventListener('focus', () => {
                toggleButtons[index].style.display = 'block';
            });

            input.addEventListener('blur', () => {
                if (input.value === '') {
                    toggleButtons[index].style.display = 'none';
                }
            });

        });
    });
});

// checking password strength has never been this easy


document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const meter = document.querySelector('.strength-meter');
    const text = document.querySelector('.strength-text');

    const strength = checkPasswordStrength(password);
    meter.className = 'strength-meter ' + strength;
    text.textContent = strength.charAt(0).toUpperCase() + strength.slice(1);
});

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

// checking password matchibility
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const matchIcon = document.querySelector('.password-match-icon');
    const mismatchIcon = document.querySelector('.password-mismatch-icon');

    if (password === '' || confirmPassword === '') {
        matchIcon.style.display = 'none';
        mismatchIcon.style.display = 'none';
        return;
    }

    if (password === confirmPassword) {
        matchIcon.style.display = 'inline-block';
        mismatchIcon.style.display = 'none';
    } else {
        matchIcon.style.display = 'none';
        mismatchIcon.style.display = 'inline-block';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    
    if (!loginForm) {
        console.warn('Login form not found');
        return;
    }

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        try {
            const formData = new FormData(loginForm);
            const response = await fetch('/nsikacart/api/auth/login.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const data = await response.json();

            if (data.success) {
                // store user data in session storage
                sessionStorage.setItem('user', JSON.stringify(data.user));
                
                // redirect to dashboard after authentication/ login
                window.location.href = '/nsikacart/public/dashboard/index.html';
            } else {
                showToast(data.message || 'Login failed', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            showToast('Login failed. Please try again.', 'error');
        }
    });
});