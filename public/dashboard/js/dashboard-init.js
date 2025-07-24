import { renderSidebar, renderSections } from './dashboard-render.js';
import { setupSidebarNavigation } from './dashboard-navigation.js';
import { renderProductsTable } from './dashboard-table.js';

function setupDeleteUserModal() {
    const confirmBtn = document.getElementById('confirm-delete-user');
    if (confirmBtn) {
        confirmBtn.onclick = function() {
            const modal = document.getElementById('delete-user-modal');
            const userId = modal.dataset.userId;
            if (userId) {
                // Call the global performDeleteUser function
                if (typeof window.performDeleteUser === 'function') {
                    window.performDeleteUser(userId);
                }
                modal.style.display = 'none';
            }
        };
    }
}

function initDashboard() {
    renderSidebar();
    renderSections();
    setupSidebarNavigation();
    
    // Setup delete user modal after a short delay to ensure DOM is ready
    setTimeout(() => {
        setupDeleteUserModal();
    }, 100);
    
    // Get current active section from localStorage
    const currentSection = localStorage.getItem('dashboard-active-section') || 'products';
    
    // Render products table if products section is active
    if (currentSection === 'products') {
        const productsSection = document.getElementById('section-products');
        if (productsSection && productsSection.style.display !== 'none') {
            renderProductsTable();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}

