import { renderProductsTable, setupProductsSearch } from './dashboard-table.js';
import { getCurrentUser } from './session-manager.js';

export function setupSidebarNavigation() {
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-section]')) {
            e.preventDefault();
            const section = e.target.closest('[data-section]').dataset.section;
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
            e.target.closest('.sidebar-list-item').classList.add('active');
            document.querySelectorAll('.dashboard-section').forEach(sec => sec.style.display = 'none');
            
            if (section) {
                const el = document.getElementById('section-' + section);
                if (el) {
                    el.style.display = '';
                    // Setup section-specific functionality
                    if (section === 'products') {
                        setTimeout(() => {
                            renderProductsTable(1, 5);
                            setupProductsSearch();
                        }, 100);
                    }
                }
            }
        }
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
