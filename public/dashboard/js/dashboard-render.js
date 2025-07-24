import { sidebarItems } from './dashboard-data.js';
import { sections } from './dashboard-sections.js';
import { getCurrentUser } from './session-manager.js';

// Add this function to handle URL hash navigation
function getActiveStateFromURL() {
    const hash = window.location.hash.substring(1); // Remove #
    if (hash) {
        const [section, subsection] = hash.split('/');
        return { section, subsection };
    }
    return {
        section: localStorage.getItem('dashboard-active-section') || 'products',
        subsection: localStorage.getItem('dashboard-admin-subsection') || 'statistics'
    };
}

function updateURL(section, subsection = null) {
    const hash = subsection ? `${section}/${subsection}` : section;
    window.history.replaceState(null, null, `#${hash}`);
}

export function renderSidebar() {
    const sidebarList = document.querySelector('.sidebar-list');
    if (!sidebarList) return;
    
    const user = getCurrentUser();
    const userRole = user?.role;
    
    // Get current active section from URL or localStorage
    const { section: currentSection } = getActiveStateFromURL();
    
    // Filter sidebar items based on user role
    const filteredItems = sidebarItems.filter(item => {
        if (item.adminOnly) {
            return userRole === 'admin' || userRole === 'monitor';
        }
        return true;
    });
    
    sidebarList.innerHTML = filteredItems.map((item) => `
        <li class="sidebar-list-item${item.section === currentSection ? ' active' : ''}" style="margin: 0 4px;">
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
    
    // Get current active section and admin subsection from localStorage
    const currentSection = localStorage.getItem('dashboard-active-section') || 'products';
    const currentAdminSection = localStorage.getItem('dashboard-admin-subsection') || 'statistics';
    
    // Filter sections based on user role
    const filteredSections = Object.entries(sections).filter(([key, section]) => {
        if (section.adminOnly) {
            return userRole === 'admin' || userRole === 'monitor';
        }
        return true;
    });
    
    appContent.innerHTML = filteredSections.map(([key, section]) => `
        <div class="dashboard-section" id="section-${key}" style="${key === currentSection ? '' : 'display:none;'}">
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
        setupAdminNavigation(currentAdminSection);
    }
}

function setupAdminNavigation(activeSubsection = 'statistics') {
    document.querySelectorAll('.admin-nav-btn').forEach(btn => {
        // Set active state based on saved subsection
        const btnSection = btn.getAttribute('data-admin-section');
        if (btnSection === activeSubsection) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
        
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
                
                // Save current admin subsection to localStorage
                localStorage.setItem('dashboard-admin-subsection', targetSection);
                
                // Load statistics if statistics section is selected
                if (targetSection === 'statistics') {
                    loadStatistics();
                }
            }
        });
    });
    
    // Show the active subsection on load
    document.querySelectorAll('.admin-subsection').forEach(section => {
        section.style.display = 'none';
    });
    
    const activeElement = document.getElementById(`admin-${activeSubsection}`);
    if (activeElement) {
        activeElement.style.display = 'block';
        if (activeSubsection === 'statistics') {
            setTimeout(() => {
                loadStatistics();
            }, 100);
        }
    }
}

async function loadStatistics() {
    try {
        const response = await fetch('/nsikacart/api/admin/get-statistics.php');
        const data = await response.json();

        if (data.success) {
            const totalUsersEl = document.getElementById('total-users');
            const totalProductsEl = document.getElementById('total-products');
            const activeSessionsEl = document.getElementById('active-sessions');
            const activeUsersEl = document.getElementById('active-users');

            if (totalUsersEl) totalUsersEl.textContent = data.stats.totalUsers || '0';
            if (totalProductsEl) totalProductsEl.textContent = data.stats.totalProducts || '0';
            if (activeSessionsEl) activeSessionsEl.textContent = data.stats.activeSessions || '0';
            if (activeUsersEl) activeUsersEl.textContent = data.stats.activeUsers || '0';
        } else {
            console.error('API Error:', data.message);
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// Add a flag to control ping requests
let pingInterval = null;

function startPing() {
    // Clear any existing interval
    if (pingInterval) {
        clearInterval(pingInterval);
    }
    
    pingInterval = setInterval(() => {
        fetch('/nsikacart/api/auth/ping.php', {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                console.log('Session expired, redirecting to login');
                clearInterval(pingInterval);
                window.location.href = '/nsikacart/auth/login.html';
            }
        })
        .catch(error => {
            console.error('Ping error:', error);
            // Don't redirect on network errors, just log them
        });
    }, 5 * 60 * 1000); // 5 minutes
}

// Start ping when page loads
startPing();

fetch('/nsikacart/api/admin/users.php')
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    if (!data.success) {
      console.error('API Error:', data.message);
      return;
    }
    
    const tableBody = document.querySelector('#userTable tbody');
    if (!tableBody) return;
    
    tableBody.innerHTML = ''; // Clear existing content
    
    data.data.forEach(user => {
      const row = `
        <tr>
          <td>${user.username}</td>
          <td>${user.email}</td>
          <td>${user.role}</td>
          <td>${user.status}</td>
          <td>
            <button onclick="toggleStatus(${user.id})">${user.status === 'active' ? 'Suspend' : 'Activate'}</button>
            <button onclick="deleteUser(${user.id})">Delete</button>
          </td>
        </tr>`;
      tableBody.innerHTML += row;
    });
  })
  .catch(error => {
    console.error('Error fetching users:', error);
    const tableBody = document.querySelector('#userTable tbody');
    if (tableBody) {
      tableBody.innerHTML = '<tr><td colspan="5">Error loading users</td></tr>';
    }
  });

function toggleStatus(userId) {
  fetch('/api/admin/suspend_user.php', {
    method: 'POST',
    body: JSON.stringify({ user_id: userId })
  }).then(response => response.json()).then(() => location.reload());
}

function deleteUser(userId) {
  fetch('/api/admin/delete_user.php', {
    method: 'POST',
    body: JSON.stringify({ user_id: userId })
  }).then(response => response.json()).then(() => location.reload());
}