document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resend-verification-form');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = form.email.value.trim();
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        
        // Basic email validation
        if (!email) {
            showToast('Please enter your email address', 'error');
            return;
        }

        // Email format validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showToast('Please enter a valid email address', 'error');
            return;
        }

        try {
            // Disable button and show loading state
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

            const response = await fetch('../api/auth/resend-verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: email })
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message || 'Verification email sent successfully!', 'success');
                form.reset();
                
                // Redirect to login page after 3 seconds
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 3000);
            } else {
                showToast(data.message || 'Failed to send verification email', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred. Please try again later.', 'error');
        } finally {
            // Reset button state
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    });
});