document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (!token) {
        showVerificationResult(false, 'Invalid verification link. Please check your email or request a new verification link.');
        return;
    }
    
    verifyEmail(token);
});

async function verifyEmail(token) {
    try {
        const response = await fetch('/nsikacart/api/auth/verify-email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ token: token })
        });

        const data = await response.json();
        
        if (data.success) {
            showVerificationResult(true, data.message || 'Email verified successfully! You can now log in to your account.');
        } else {
            showVerificationResult(false, data.message || 'Email verification failed. Please try again or request a new verification link.');
        }
    } catch (error) {
        console.error('Verification error:', error);
        showVerificationResult(false, 'Network error occurred. Please try again later.');
    }
}

function showVerificationResult(success, message) {
    const content = document.getElementById('verification-content');
    const messageDiv = document.getElementById('message');
    
    if (success) {
        content.innerHTML = `
            <div style="text-align: center;">
                <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
                <h3 style="color: #28a745; margin-bottom: 15px;">Email Verified!</h3>
                <p class="subtitle">${message}</p>
                <a href="./login.html" class="login-btn" style="display: inline-block; margin-top: 20px; text-decoration: none;">
                    Continue to Login
                </a>
            </div>
        `;
    } else {
        content.innerHTML = `
            <div style="text-align: center;">
                <i class="fas fa-times-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                <h3 style="color: #dc3545; margin-bottom: 15px;">Verification Failed</h3>
                <p class="subtitle">${message}</p>
                <div style="margin-top: 20px;">
                    <a href="./resend-verification.html" class="login-btn" style="display: inline-block; margin: 5px; text-decoration: none;">
                        Resend Verification
                    </a>
                    <a href="./signup.html" class="login-btn" style="display: inline-block; margin: 5px; text-decoration: none; background-color: #6c757d;">
                        Register Again
                    </a>
                </div>
            </div>
        `;
    }
}