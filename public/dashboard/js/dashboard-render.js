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
                
                // Load data based on section
                if (targetSection === 'statistics') {
                    loadStatistics();
                } else if (targetSection === 'users') {
                    loadUsers();
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
        } else if (activeSubsection === 'users') {
            setTimeout(() => {
                loadUsers();
            }, 100);
        }
    }
}

// Add the loadStatistics function
async function loadStatistics() {
    try {
        const response = await fetch('/nsikacart/api/admin/get-statistics.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            console.error('Statistics API Error:', data.message);
            // Set error state for all stats
            document.getElementById('total-users').textContent = 'Error';
            document.getElementById('total-products').textContent = 'Error';
            document.getElementById('active-sessions').textContent = 'Error';
            document.getElementById('active-users').textContent = 'Error';
            return;
        }
        
        // Update the statistics in the DOM
        const stats = data.stats;
        document.getElementById('total-users').textContent = stats.totalUsers || '0';
        document.getElementById('total-products').textContent = stats.totalProducts || '0';
        document.getElementById('active-sessions').textContent = stats.activeSessions || '0';
        document.getElementById('active-users').textContent = stats.activeUsers || '0';
        
    } catch (error) {
        console.error('Error loading statistics:', error);
        // Set error state for all stats
        document.getElementById('total-users').textContent = 'Error';
        document.getElementById('total-products').textContent = 'Error';
        document.getElementById('active-sessions').textContent = 'Error';
        document.getElementById('active-users').textContent = 'Error';
    }
}

// Update the loadUsers function to include role toggle
async function loadUsers() {
    const tableBody = document.querySelector('#userTable tbody');
    const usersTable = document.querySelector('#users-table');
    
    if (usersTable) {
        usersTable.textContent = 'Loading users...';
    }
    
    try {
        const response = await fetch('/nsikacart/api/admin/users.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (usersTable) {
            usersTable.style.display = 'none';
        }
        
        if (!data.success) {
            console.error('API Error:', data.message);
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="6" class="error-message">Error: ${data.message}</td></tr>`;
            }
            return;
        }
        
        if (!tableBody) return;
        
        tableBody.innerHTML = ''; // Clear existing content
        
        if (data.data && data.data.length > 0) {
            data.data.forEach(user => {
                const statusClass = user.status === 'active' ? 'status-active' : 'status-inactive';
                const actionText = user.status === 'active' ? 'Suspend' : 'Activate';
                const actionClass = user.status === 'active' ? 'btn-warning' : 'btn-success';
                
                // Check if current user is admin
                const currentUser = getCurrentUser();
                const isCurrentUserAdmin = currentUser?.role === 'admin';
                const isCurrentUser = user.id == currentUser?.id;
                
                let roleToggleButton = '';
                if (isCurrentUserAdmin && !isCurrentUser) {
                    roleToggleButton = `<button onclick="showRoleModal(${user.id}, '${user.role}', '${user.username || user.name}')" class="action-btn btn-info">Change Role</button>`;
                }
                
                const row = `
                    <tr>
                        <td>${user.username || user.name || 'N/A'}</td>
                        <td>${user.email || 'N/A'}</td>
                        <td><span class="role-badge role-${user.role}">${user.role || 'N/A'}</span></td>
                        <td><span class="status-badge ${statusClass}">${user.status || 'N/A'}</span></td>
                        <td class="actions-cell">
                            ${roleToggleButton}
                            <button onclick="toggleUserStatus(${user.id}, '${user.status}')" class="action-btn ${actionClass}">${actionText}</button>
                            <button onclick="deleteUser(${user.id}, '${user.username || user.name}')" class="action-btn btn-danger">Delete</button>
                        </td>
                    </tr>`;
                tableBody.innerHTML += row;
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="5" class="no-data">No users found</td></tr>';
        }
        
    } catch (error) {
        console.error('Error fetching users:', error);
        if (usersTable) {
            usersTable.style.display = 'none';
        }
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="5" class="error-message">Error loading users: ${error.message}</td></tr>`;
        }
    }
}

// Add role management functions
window.showRoleModal = function(userId, currentRole, username) {
    const modal = document.getElementById('role-change-modal');
    if (modal) {
        modal.style.display = 'flex';
        modal.dataset.userId = userId;
        modal.dataset.currentRole = currentRole;
        
        document.getElementById('role-change-username').textContent = username;
        document.getElementById('role-change-current').textContent = currentRole;
        
        // Set the select value
        const roleSelect = document.getElementById('new-role-select');
        if (roleSelect) {
            roleSelect.value = currentRole;
        }
    }
};

window.changeUserRole = async function() {
    const modal = document.getElementById('role-change-modal');
    const userId = modal.dataset.userId;
    const currentRole = modal.dataset.currentRole;
    const newRole = document.getElementById('new-role-select').value;
    
    if (newRole === currentRole) {
        showToast('No changes made', 'info');
        modal.style.display = 'none';
        return;
    }
    
    try {
        const response = await fetch('/nsikacart/api/admin/change-user-role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                user_id: userId, 
                new_role: newRole,
                current_role: currentRole
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            loadUsers(); // Reload the users table
            modal.style.display = 'none';
        } else {
            showToast(result.message || 'Failed to change user role', 'error');
        }
    } catch (error) {
        console.error('Error changing user role:', error);
        showToast('An error occurred while changing user role', 'error');
    }
};

// Update the user action functions
window.toggleUserStatus = async function(userId, currentStatus) {
    try {
        const response = await fetch('/nsikacart/api/admin/toggle-user-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId, current_status: currentStatus })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            loadUsers(); // Reload the users table
        } else {
            showToast(result.message || 'Failed to update user status', 'error');
        }
    } catch (error) {
        console.error('Error toggling user status:', error);
        showToast('An error occurred while updating user status', 'error');
    }
};

window.deleteUser = function(userId, username) {
    const modal = document.getElementById('delete-user-modal');
    if (modal) {
        modal.style.display = 'flex';
        modal.dataset.userId = userId;
        const nameElement = document.getElementById('delete-user-name');
        if (nameElement) {
            nameElement.textContent = username;
        }
    } else {
        if (confirm(`Are you sure you want to delete user "${username}"?`)) {
            performDeleteUser(userId);
        }
    }
};

window.performDeleteUser = async function(userId) {
    try {
        const response = await fetch('/nsikacart/api/admin/delete-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            loadUsers(); // Reload the users table
        } else {
            showToast(result.message || 'Failed to delete user', 'error');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showToast('An error occurred while deleting user', 'error');
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) {
        console.log(message);
        return;
    }
    
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}