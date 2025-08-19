let isSubmitting = false;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#reset-password-form');
    const urlParams = new URLSearchParams(window.location.search);
    const token = decodeURIComponent(urlParams.get('token'));
    
    if (!token) {
        if (typeof window.showToast === 'function') {
            window.showToast('Invalid reset link', 'error');
        }
        setTimeout(() => {
            window.location.href = '/nsikacart/auth/forgot-password.html';
        }, 2000);
        return;
    }

    if (token.length < 64) { // tokens are 64 chars (32 bytes hex encoded)
        window.showToast('Invalid reset token format', 'error');
        setTimeout(() => {
            window.location.href = '/nsikacart/auth/forgot-password.html';
        }, 2000);
        return;
    }
    
    if (!form) {
        console.error('Reset form not found');
        return;
    }

    form.addEventListener('submit', function(e) {
        handlePasswordReset(e, token);
    });
});

function handlePasswordReset(e, token) {
    e.preventDefault();
    
    if (isSubmitting) return false;
    isSubmitting = true;
    
    const form = e.target;
    const password = form.querySelector('input[name="password"]').value.trim();
    const confirmPassword = form.querySelector('input[name="confirm_password"]').value.trim();
    
    if (!password || !confirmPassword) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please fill in all fields', 'error');
        }
        isSubmitting = false;
        return false;
    }
    
    if (password !== confirmPassword) {
        if (typeof window.showToast === 'function') {
            window.showToast('Passwords do not match', 'error');
        }
        isSubmitting = false;
        return false;
    }
    
    if (password.length < 6) {
        if (typeof window.showToast === 'function') {
            window.showToast('Password must be at least 6 characters long', 'error');
        }
        isSubmitting = false;
        return false;
    }
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Resetting Password...';
    
    fetch('/nsikacart/api/auth/reset-password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            token: token,
            password: password
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (typeof window.showToast === 'function') {
                window.showToast(result.message, 'success');
            }
            setTimeout(() => {
                window.location.href = '/nsikacart/auth/login.html';
            }, 2000);
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast(result.message, 'error');
            }
        }
    })
    .catch(err => {
        console.error('Reset error:', err);
        if (typeof window.showToast === 'function') {
            window.showToast('Network error occurred', 'error');
        }
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        isSubmitting = false;
    });
    
    return false;
}