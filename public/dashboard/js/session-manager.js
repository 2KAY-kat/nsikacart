async function validateSession() {
    console.log('Starting session validation...');
    
    // Check if session exists in storage
    const user = JSON.parse(sessionStorage.getItem('user'));
    console.log('User from sessionStorage:', user);
    
    // If no user in session storage, try to fetch from server
    if (!user || !user.id) {
        console.log('No user in sessionStorage, checking server...');
        try {
            const response = await fetch('../../../api/auth/check-session.php');
            console.log('Server response status:', response.status);
            
            const data = await response.json();
            console.log('Server response data:', data);
            
            if (data.success && data.user) {
                sessionStorage.setItem('user', JSON.stringify(data.user));
                console.log('Session refreshed successfully');
                return true;
            } else {
                console.log('No valid session found on server');
                window.location.href = '../../auth/login.html';
                return false;
            }
        } catch (error) {
            console.error('Session validation error:', error);
            window.location.href = '../../auth/login.html';
            return false;
        }
    }
    
    console.log('Session valid, user found in sessionStorage');
    return true;
}

// Get current user from session
export function getCurrentUser() {
    return JSON.parse(sessionStorage.getItem('user'));
}

export { validateSession };