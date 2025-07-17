let isSubmitting = false;

// Wait for DOM to be fully loaded before attaching listeners
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#login-form');
    
    if (!form) {
        console.error('Login form not found');
        return;
    }

    // Remove any existing listeners and clone the form to remove all attached events
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    
    // Now attach a single, clean event listener
    newForm.addEventListener('submit', handleLogin);
});

function handleLogin(e) {
    e.preventDefault();
    e.stopPropagation(); // Prevent event bubbling
    
    // Prevent multiple submissions
    if (isSubmitting) {
        console.log('Form submission already in progress');
        return false;
    }
    
    isSubmitting = true;
    
    const form = e.target;
    
    // Get form values with better validation
    const email = form.email ? form.email.value.trim() : '';
    const password = form.password ? form.password.value.trim() : '';
    const remember = form.remember ? form.remember.checked : false;

    const formData = {
        email: email,
        password: password,
        remember: remember ? 1 : 0
    };

    // Debug: Log what we're sending (without sensitive data)
    console.log('Sending login data:', { 
        email: formData.email, 
        password: '***', 
        remember: formData.remember 
    });

    // Validate inputs before sending
    if (!formData.email || !formData.password) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please fill in all fields', 'error');
        }
        isSubmitting = false;
        return false;
    }

    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Signing In...';

    // Ensure JSON is properly stringified
    let jsonData;
    try {
        jsonData = JSON.stringify(formData);
        console.log('JSON data to send:', jsonData);
    } catch (jsonError) {
        console.error('Error creating JSON:', jsonError);
        if (typeof window.showToast === 'function') {
            window.showToast('Error preparing login data', 'error');
        }
        isSubmitting = false;
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        return false;
    }

    fetch('/nsikacart/api/auth/login.php', {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: jsonData,
        credentials: 'include'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(responseText => {
        console.log('Raw response:', responseText);
        
        // Check if response is empty
        if (!responseText.trim()) {
            throw new Error('Empty response from server');
        }
        
        // Try to parse as JSON
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
            // Store user data in session storage
            sessionStorage.setItem('user', JSON.stringify(result.user));
            
            // Show success toast
            if (typeof window.showToast === 'function') {
                window.showToast(result.message || 'Login successful!', 'success');
            }
            
            // Redirect after a short delay
            setTimeout(() => {
                window.location.href = "/nsikacart/public/dashboard/index.html";
            }, 1500);
        } else {
            // Show error toast
            if (typeof window.showToast === 'function') {
                window.showToast(result.message || 'Login failed', 'error');
            }
        }
    })
    .catch(err => {
        console.error('Login error:', err);
        if (typeof window.showToast === 'function') {
            window.showToast("Network error: " + err.message, 'error');
        }
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        isSubmitting = false;
    });
    
    return false; // Prevent any default form submission
}