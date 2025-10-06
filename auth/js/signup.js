const form = document.querySelector('#signup-form');

// Remove any existing event listeners to prevent duplicates
form.removeEventListener('submit', handleSignup);

function handleSignup(e) {
    e.preventDefault();

    // Validate password match before submitting
    const password = form.password.value;
    const confirmPassword = form.confirm_password.value;

    if (password !== confirmPassword) {
        if (typeof window.showToast === 'function') {
            window.showToast('Passwords do not match!', 'error');
        }
        return;
    }

    if (password.length < 6) {
        if (typeof window.showToast === 'function') {
            window.showToast('Password must be at least 6 characters long!', 'error');
        }
        return;
    }

    const formData = {
        name: form.name.value.trim(),
        email: form.email.value.trim(),
        phone: form.phone.value.trim(),
        password: password,
        confirm_password: confirmPassword
    };

    // Validate required fields
    if (!formData.name || !formData.email || !formData.phone) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please fill in all required fields!', 'error');
        }
        return;
    }

    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Creating Account...';

    fetch('./api/auth/register.php', {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Network error: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            if (typeof window.showToast === 'function') {
                window.showToast(result.message || 'Account created successfully! Please check your email to verify your account.', 'success');
            }
            
            // Clear form
            form.reset();
            
            // Show verification notice with 15-minute warning
            setTimeout(() => {
                alert('Registration successful! Please check your email and click the verification link within 15 minutes before logging in.');
                window.location.href = "./login.html";
            }, 2000);
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast(result.message || 'Registration failed', 'error');
            }
        }
    })
    .catch(err => {
        console.error('Signup error:', err);
        if (typeof window.showToast === 'function') {
            window.showToast("Network error. Please check your connection and try again.", 'error');
        }
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });
}

// Add single event listener
form.addEventListener('submit', handleSignup);