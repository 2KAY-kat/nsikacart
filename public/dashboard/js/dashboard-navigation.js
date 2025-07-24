import { renderProductsTable } from './dashboard-table.js';
import { getCurrentUser } from './session-manager.js';

export function setupSidebarNavigation() {
    document.querySelectorAll('.sidebar-list-item a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const section = this.getAttribute('data-section');
            const user = getCurrentUser();
            
            // Check if user has permission to access admin section
            if (section === 'admin' && user?.role !== 'admin' && user?.role !== 'monitor') {
                showToast('Access denied. Admin privileges required.', 'error');
                return;
            }
            
            // Save current section to localStorage
            localStorage.setItem('dashboard-active-section', section);
            
            // If switching to admin, reset to statistics subsection
            if (section === 'admin') {
                localStorage.setItem('dashboard-admin-subsection', 'statistics');
            }
            
            document.querySelectorAll('.sidebar-list-item').forEach(li => li.classList.remove('active'));
            this.parentElement.classList.add('active');
            document.querySelectorAll('.dashboard-section').forEach(sec => sec.style.display = 'none');
            
            if (section) {
                const el = document.getElementById('section-' + section);
                if (el) {
                    el.style.display = '';
                    if (section === 'products') {
                        renderProductsTable();
                    }
                }
            }
        });
    });
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return alert(message);
    
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}
