//  show username nav and the id is the link that show the profile of the currnet looged in user profiel when clicked
document.addEventListener('DOMContentLoaded', function() {
    function setWelcomeUsername() {
        const usernameEl = document.getElementById('username');
        const dashboardUsernameEl = document.getElementById('dashboard-username');
        if (usernameEl && dashboardUsernameEl) {
            const username = usernameEl.textContent;
            if (username && username.trim() !== "") {
                dashboardUsernameEl.textContent = username;
            } else {
                setTimeout(setWelcomeUsername, 50);
            }
        }
    }
    setWelcomeUsername();

    
    function showAdminLinkWhenReady() {
        const adminLink = document.getElementById('admin-panel-link');
        if (typeof window.currentUserRole !== "undefined" && adminLink) {
            if (window.currentUserRole === 'admin' || window.currentUserRole === 'monitor') {
                adminLink.style.display = '';
            } else {
                adminLink.style.display = 'none';
            }
        } else {
            setTimeout(showAdminLinkWhenReady, 50);
        }
    }
    showAdminLinkWhenReady();
});

    document.querySelectorAll('.account-info.dropup .nav-icon').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const dropup = this.parentElement;
            dropup.classList.toggle('open');
        });
    });
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.account-info.dropup').forEach(function(dropup) {
            if (!dropup.contains(e.target)) {
                dropup.classList.remove('open');
            }
        });
    });