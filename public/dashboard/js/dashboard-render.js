import { sidebarItems } from './dashboard-data.js';
import { sections } from './dashboard-sections.js';
import { getCurrentUser } from './session-manager.js';

// Add this function to handle URL hash navigation
function getActiveStateFromURL() {
    const hash = window.location.hash.substring(1); 
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

    // Add click event listeners to update URL
    sidebarList.addEventListener('click', function(e) {
        const link = e.target.closest('a[data-section]');
        if (link) {
            e.preventDefault();
            const section = link.getAttribute('data-section');
            updateURL(section);
            localStorage.setItem('dashboard-active-section', section);
        }
    });
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
                
                // Update URL with admin subsection
                updateURL('admin', targetSection);
                
                // Load data based on section
                if (targetSection === 'statistics') {
                    loadStatistics();
                } else if (targetSection === 'users') {
                    loadUsers(1, 5); 
                    setupPaginationEvents(); 
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
                loadUsers(1, 5);
                setupPaginationEvents();
            }, 100);
        }
    }
}

// Global pagination state
let currentPage = 1;
let currentPageSize = 5;
let totalPages = 1;

export async function loadUsers(page = 1, pageSize = 5) { 
    const tableBody = document.querySelector('#userTable tbody');
    const usersTable = document.querySelector('#users-table');
    const paginationContainer = document.getElementById('pagination-container');
    
    if (usersTable) {
        usersTable.textContent = 'Loading users...';
    }
    
    try {
        const response = await fetch(`/nsikacart/api/admin/users.php?page=${page}&limit=${pageSize}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        // console.log('Users loaded successfully:', data);
        
        if (usersTable) {
            usersTable.style.display = 'none';
        }
        
        if (!data.success) {
            console.error('API Error:', data.message);
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="5" class="error-message">Error: ${data.message}</td></tr>`;
            }
            return;
        }
        
        if (!tableBody) return;
        
        // Update pagination state
        currentPage = data.pagination.current_page;
        currentPageSize = data.pagination.limit;
        totalPages = data.pagination.total_pages;
        
        tableBody.innerHTML = ''; 
        
        if (data.data && data.data.length > 0) {
            data.data.forEach(user => {
                const statusClass = user.status === 'active' ? 'status-active' : 'status-inactive';
                const actionText = user.status === 'active' ? 'Suspend' : 'Activate';
                const actionClass = user.status === 'active' ? 'btn-warning' : 'btn-success';
                
                // Check current user permissions
                const currentUser = getCurrentUser();
                const isCurrentUserAdmin = currentUser?.role === 'admin';
                const isCurrentUserMonitor = currentUser?.role === 'monitor';
                const isCurrentUser = user.id == currentUser?.id;

                let roleToggleButton = '';
                let deleteButton = '';
                let statusButton = '';
                
                // Only admins can change roles and only for other users
                if (isCurrentUserAdmin && !isCurrentUser) {
                    roleToggleButton = `<button onclick="showRoleModal(${user.id}, '${user.role}', '${user.name}')" class="action-btn btn-info">Promote</button>`;
                }
                
                // Both admins and monitors can suspend/activate users (except themselves)
                if ((isCurrentUserAdmin || isCurrentUserMonitor) && !isCurrentUser) {
                    statusButton = `<button onclick="toggleUserStatus(${user.id}, '${user.status}')" class="action-btn ${actionClass}">${actionText}</button>`;
                }
                
                // Only admins can delete users (except themselves)
                if (isCurrentUserAdmin && !isCurrentUser) {
                    deleteButton = `<button onclick="deleteUser(${user.id}, '${user.name}')" class="action-btn btn-danger">Delete</button>`;
                }
                
                // Show different messages for current user's own row
                if (isCurrentUser) {
                    statusButton = '<span class="current-user-label">Current</span>';
                }
                
                // Format the created date
                let createdDate = 'N/A';
                if (user.created_at) {
                    const dateObj = new Date(user.created_at);
                    createdDate = dateObj.toLocaleDateString(undefined, {
                        year: '2-digit',
                        month: 'short',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
                const row = `
                    <tr>
                        <td data-label="Username">${user.name || 'N/A'}</td>
                        <td data-label="Email">${user.email || 'N/A'}</td>
                         <td data-label="Created">${createdDate}</td>
                        <td data-label="Role"><span class="role-badge role-${user.role}">${user.role || 'N/A'}</span></td>
                        <td data-label="Status"><span class="status-badge ${statusClass}">${user.status || 'N/A'}</span></td>
                        <td data-label="Actions" class="actions-cell">
                            ${roleToggleButton}
                            ${statusButton}
                            ${deleteButton}
                        </td>
                    </tr>`;
                tableBody.innerHTML += row;
            });
            
            // Show pagination
            updatePagination(data.pagination);
            if (paginationContainer) {
                paginationContainer.style.display = 'flex';
            }
        } else {
            tableBody.innerHTML = '<tr><td colspan="5" class="no-data">No users found</td></tr>';
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
        }
        
    } catch (error) {
        console.error('Error fetching users:', error);
        if (usersTable) {
            usersTable.style.display = 'none';
        }
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="5" class="error-message">Error loading users: ${error.message}</td></tr>`;
        }
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
    }
}

function updatePagination(pagination) {
    const infoText = document.getElementById('pagination-info-text');
    const buttonsContainer = document.getElementById('pagination-buttons');
    const pageSizeSelect = document.getElementById('page-size');
    
    if (!pagination) return;

    const start = ((pagination.current_page - 1) * pagination.limit) + 1;
    const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
    if (infoText) {
        infoText.textContent = `Showing ${start}-${end} of ${pagination.total_records} users`;
    }
    
    // Update page size selector
    if (pageSizeSelect && pageSizeSelect.value != pagination.limit) {
        pageSizeSelect.value = pagination.limit;
    }
    
    // Generate pagination buttons
    if (buttonsContainer) {
        buttonsContainer.innerHTML = '';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.textContent = '‹ Previous';
        prevBtn.disabled = !pagination.has_prev;
        prevBtn.onclick = () => pagination.has_prev && loadUsers(pagination.current_page - 1, pagination.limit);
        buttonsContainer.appendChild(prevBtn);
        
        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        // First page if not in range
        if (startPage > 1) {
            const firstBtn = createPageButton(1, pagination.current_page, pagination.limit);
            buttonsContainer.appendChild(firstBtn);
            
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'pagination-ellipsis';
                buttonsContainer.appendChild(ellipsis);
            }
        }
        
        // Page range
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = createPageButton(i, pagination.current_page, pagination.limit);
            buttonsContainer.appendChild(pageBtn);
        }
        
        // Last page if not in range
        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'pagination-ellipsis';
                buttonsContainer.appendChild(ellipsis);
            }
            
            const lastBtn = createPageButton(pagination.total_pages, pagination.current_page, pagination.limit);
            buttonsContainer.appendChild(lastBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.textContent = 'Next ›';
        nextBtn.disabled = !pagination.has_next;
        nextBtn.onclick = () => pagination.has_next && loadUsers(pagination.current_page + 1, pagination.limit);
        buttonsContainer.appendChild(nextBtn);
    }
}

function createPageButton(pageNumber, currentPage, pageSize) {
    const btn = document.createElement('button');
    btn.className = `pagination-btn ${pageNumber === currentPage ? 'active' : ''}`;
    btn.textContent = pageNumber;
    btn.onclick = () => loadUsers(pageNumber, pageSize);
    return btn;
}

// Setup pagination event listeners
function setupPaginationEvents() {
    const pageSizeSelect = document.getElementById('page-size');
    if (pageSizeSelect) {

        pageSizeSelect.value = currentPageSize;
        
        pageSizeSelect.addEventListener('change', function() {
            const newPageSize = parseInt(this.value);
            loadUsers(1, newPageSize); 
        });
    }
}

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
            loadUsers(currentPage, currentPageSize); 
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
            loadUsers(currentPage, currentPageSize); 
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
            window.performDeleteUser(userId);
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
            loadUsers(currentPage, currentPageSize);
        } else {
            showToast(result.message || 'Failed to delete user', 'error');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showToast('An error occurred while deleting user', 'error');
    }
};

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