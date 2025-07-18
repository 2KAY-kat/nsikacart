import { sidebarItems } from './dashboard-data.js';
import { sections } from './dashboard-sections.js';
import { getCurrentUser } from './session-manager.js';

export function renderSidebar() {
    const sidebarList = document.querySelector('.sidebar-list');
    if (!sidebarList) return;
    
    const user = getCurrentUser();
    const userRole = user?.role;
    
    // Filter sidebar items based on user role
    const filteredItems = sidebarItems.filter(item => {
        if (item.adminOnly) {
            return userRole === 'admin' || userRole === 'monitor';
        }
        return true;
    });
    
    sidebarList.innerHTML = filteredItems.map((item, idx) => `
        <li class="sidebar-list-item${idx === 0 ? ' active' : ''}" style="margin: 0 4px;">
            <a href="#" data-section="${item.section}" style="padding: 10px 18px; margin: 0 2px;">
                <i class="fa-solid ${item.icon}" style="margin-right: 0;"></i>
                <span style="margin-left: 2px;">${item.name}</span>
            </a>
        </li>
    `).join('');
}

export function renderSections() {
    const appContent = document.querySelector('.app-content');
    if (!appContent) return;
    
    const user = getCurrentUser();
    const userRole = user?.role;
    
    // Filter sections based on user role
    const filteredSections = Object.entries(sections).filter(([key, section]) => {
        if (section.adminOnly) {
            return userRole === 'admin' || userRole === 'monitor';
        }
        return true;
    });
    
    appContent.innerHTML = filteredSections.map(([key, section], idx) => `
        <div class="dashboard-section" id="section-${key}" style="${idx === 0 ? '' : 'display:none;'}">
            <div class="app-content-header" style="padding: 12px 12px 2px 12px; margin-bottom: 2px;">
                <h1 class="app-content-headerText" style="margin: 0 0 0 4px;">${section.header}</h1>
                ${key === 'products' ? '<button class="app-content-headerButton" style="margin-left: 12px;"><a href="./upload.html">Add Product</a></button>' : ''}
            </div>
            <div style="padding: 24px 12px; color: var(--app-content-main-color);">
                ${section.content}
            </div>
        </div>
    `).join('');
    
    // Setup admin navigation if admin section is rendered
    if (filteredSections.some(([key]) => key === 'admin')) {
        setupAdminNavigation();
    }
}

function setupAdminNavigation() {
    document.querySelectorAll('.admin-nav-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.admin-nav-btn').forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Hide all admin subsections
            document.querySelectorAll('.admin-subsection').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected subsection
            const targetSection = this.getAttribute('data-admin-section');
            const targetElement = document.getElementById(`admin-${targetSection}`);
            if (targetElement) {
                targetElement.style.display = 'block';
                
                // Load statistics if statistics section is selected
                if (targetSection === 'statistics') {
                    loadStatistics();
                }
            }
        });
    });
    
    // Load statistics on initial render
    loadStatistics();
}

async function loadStatistics() {
    try {
        const response = await fetch('/nsikacart/api/admin/get-statistics.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-users').textContent = data.stats.totalUsers || '0';
            document.getElementById('total-products').textContent = data.stats.totalProducts || '0';
            document.getElementById('monthly-sales').textContent = data.stats.monthlySales || '0';
            document.getElementById('active-sessions').textContent = data.stats.activeSessions || '0';
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}
