import { renderSidebar, renderSections } from './dashboard-render.js';
import { setupSidebarNavigation } from './dashboard-navigation.js';
import { renderProductsTable, setupProductsSearch } from './dashboard-table.js';

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

function setupRoleChangeModal() {
    // Close modal when clicking outside
    const modal = document.getElementById('role-change-modal');
    if (modal) {
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
}

function initDashboard() {
    renderSidebar();
    renderSections();
    setupSidebarNavigation();
    
    // Setup modals after a short delay to ensure DOM is ready
    setTimeout(() => {
        setupDeleteUserModal();
        setupRoleChangeModal();
    }, 100);
    
    // Get current active section from localStorage
    const currentSection = localStorage.getItem('dashboard-active-section') || 'products';
    
    // Render products table if products section is active
    if (currentSection === 'products') {
        const productsSection = document.getElementById('section-products');
        if (productsSection && productsSection.style.display !== 'none') {
            renderProductsTable(1, 5); 
            setupProductsSearch();
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}

