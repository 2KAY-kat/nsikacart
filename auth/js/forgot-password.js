let isSubmitting = false;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#forgot-password-form');
    
    if (!form) {
        console.error('Form not found');
        return;
    }

    form.addEventListener('submit', handleResetPassword);
});

function handleResetPassword(e) {
    e.preventDefault();
    e.stopPropagation();
    
    if (isSubmitting) {
        console.log('Form submission already in progress');
        return false;
    }
    
    isSubmitting = true;
    
    const form = e.target;
    const emailInput = form.querySelector('input[name="email"]');
    
    if (!emailInput) {
        console.error('Email input not found');
        isSubmitting = false;
        return false;
    }
    
    const email = emailInput.value.trim();

    const formData = {
        email: email
    };

    console.log('Sending reset data:', { email: formData.email });

    // Validate email
    if (!formData.email) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please enter your email address', 'error');
        } else {
            alert('Please enter your email address');
        }
        isSubmitting = false;
        return false;
    }

    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please enter a valid email address', 'error');
        } else {
            alert('Please enter a valid email address');
        }
        isSubmitting = false;
        return false;
    }

    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Sending reset email...';

    fetch('../api/auth/forgot-password.php', {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify(formData),
        credentials: 'include'
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(responseText => {
        console.log('Raw response:', responseText);
        
        if (!responseText.trim()) {
            throw new Error('Empty response from server');
        }
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response that failed to parse:', responseText);
            throw new Error('Invalid response format from server');
        }
        
        console.log('Parsed result:', result);
        
        if (result.success) {
            if (typeof window.showToast === 'function') {
                window.showToast(result.message || 'Password reset link sent successfully!', 'success');
            } else {
                alert(result.message || 'Password reset link sent successfully!');
            }
            
            // Redirect after success
            setTimeout(() => {
                window.location.href = "login.html";
            }, 2000);
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast(result.message || 'Failed to send reset link', 'error');
            } else {
                alert(result.message || 'Failed to send reset link');
            }
        }
    })
    .catch(err => {
        console.error('Reset error:', err);
        if (typeof window.showToast === 'function') {
            window.showToast("Network error: " + err.message, 'error');
        } else {
            alert("Network error: " + err.message);
        }
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        isSubmitting = false;
    });
    
    return false;
}