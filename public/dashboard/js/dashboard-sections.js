/* from the old frontend stracrures and product/ category mapping approach ... we go backe d now
import { products } from '../../scripts/data.js';

const productCategories = Array.from(new Set(products.map(p => p.category)));
productCategories.unshift('All');

const productsStatus = Array.from(new Set(products.map(p => p.status)));
productsStatus.unshift('All');

*/
//import {loadUsers} from './dashboard-render';
export const sections = {
    // home dashboard section layout
    home: {
        header: 'Dashboard Home',
        content: `
            <h2>Welcome to your dashboard!</h2>
            <p>Select a section from the sidebar to get started.</p>
        `
    },
    // products dashboard section layout
    products: {
        header: 'Products',
        content: `
            <div class="app-content-actions">
                <input class="search-bar" id="products-search" placeholder="Search products..." type="text">
                
                <div class="app-content-actions-wrapper">
                    <button class="action-button list" title="The products are highly secured">
                        <i class="fa-solid fa-lock"></i>
                    </button>
                </div>
            
            </div>
            <div class="products-area-wrapper tableView" id="dashboard-products-table"></div>
            <div class="pagination-container" id="products-pagination-container" style="display: none;">
                <div class="pagination-info">
                    <span id="products-pagination-info-text">Showing 0-0 of 0 products</span>
                </div>
                <div class="pagination-controls">
                    <div class="page-size-selector">
                        <label for="products-page-size">Show:</label>
                        <select id="products-page-size">
                            <option value="5" selected>5</option>
                            <option value="6">6</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        <span>products per page</span>
                    </div>
                    <div class="pagination-buttons" id="products-pagination-buttons">
                        <!-- Pagination buttons will be generated here -->
                    </div>
                </div>
            </div>
        `
    },
    // admin section user-management, analytics section, activity statics, system settings
    admin: {
        header: 'Welcome to Your Admin Panel, <span id="username"></span> ',
        content: `
            <div class="admin-section">
                <div class="admin-nav">
                    <button class="admin-nav-btn active" data-admin-section="statistics">
                        <i class="fa-solid fa-chart-pie"></i>
                        Statistics
                    </button>
                    <button class="admin-nav-btn" data-admin-section="activity-analytics">
                        <i class="fa-solid fa-chart-line"></i>
                        Activity Analytics
                    </button>
                    <button class="admin-nav-btn" data-admin-section="users">
                        <i class="fa-solid fa-users"></i>
                        User Management
                    </button>
                    <button class="admin-nav-btn" data-admin-section="settings">
                        <i class="fa-solid fa-cog"></i>
                        System Settings
                    </button>
                </div>
                
                <div class="admin-content">
                    <div class="admin-subsection" id="admin-statistics" style="display: block;">
                        <h3>System Statistics</h3>
                        <button class="app-content-headerButton"><a href="./logs/activity.log">View Statistics</a></button>
                        <button class="app-content-headerButton"><a href="">Download Data</a></button>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <div class="stat-info">
                                    <h4 id="total-users">Loading...</h4>
                                    <p>Total Users</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </div>
                                <div class="stat-info">
                                    <h4 id="total-products">Loading...</h4>
                                    <p>Total Products</p>
                                </div>
                            </div>
                        
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-signal"></i>
                                </div>
                                <div class="stat-info">
                                    <h4 id="active-sessions">Loading...</h4>
                                    <p>Active Sessions</p>
                                </div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h4 id="active-users">Loading...</h4>
                                    <p>Active Users</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-subsection" id="admin-activity-analytics" style="display: none;">
                        <div class="analytics-container">

                        <h3>Site Activity Analytics</h3>

                            <!-- this is fucking up the whole ui
                            <div class="analytics-header">
                                
                                <div class="analytics-controls">
                                    <select id="analytics-period" class="form-control">
                                        <option value="7">Last 7 days</option>
                                        <option value="30" selected>Last 30 days</option>
                                        <option value="90">Last 90 days</option>
                                    </select>
                                    <button id="refresh-analytics" class="app-content-headerButton">
                                        <i class="fa-solid fa-refresh"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            -->
                            
                            <div class="analytics-content">
                                <div class="analytics-overview">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fa-solid fa-activity"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4 id="total-activities">0</h4>
                                            <p>Total Activities</p>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fa-solid fa-users"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h4 id="unique-users">0</h4>
                                            <p>Active Users</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="analytics-charts">
                                    <div class="chart-container">
                                        <h4>Activity by Hour</h4>
                                        <canvas id="hourly-chart"></canvas>
                                    </div>
                                    <div class="chart-container">
                                        <h4>Daily Activity Trend</h4>
                                        <canvas id="daily-chart"></canvas>
                                    </div>
                                    <div class="chart-container">
                                        <h4>Actions Breakdown</h4>
                                        <canvas id="actions-chart"></canvas>
                                    </div>
                                    <div class="chart-container">
                                        <h4>Browser Usage</h4>
                                        <canvas id="browsers-chart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="analytics-tables">
                                    <div class="table-section">
                                        <h4>Top Users</h4>
                                        <div id="top-users-table"></div>
                                    </div>
                                    <div class="table-section">
                                        <h4>Popular Endpoints</h4>
                                        <div id="top-endpoints-table"></div>
                                    </div>
                                    <div class="table-section">
                                        <h4>Recent Activities</h4>
                                        <div id="recent-activities-table"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-subsection" id="admin-users" style="display: none;">
                        <h3></h3>
                        <button class="app-content-headerButton"><a href="user-management/add-user.html"><i class="fa-solid fa-user-plus"></i> Add User</a></button>
                        <button class="app-content-headerButton"><a href="">View User Activity</a></button>
                        <div class="users-management">
                            <div class="users-table-container">
                                <div id="users-table" class="loading-message">Loading users...</div>
                                <div class="table-wrapper">
                                    <table id="userTable" class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Registered On</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="pagination-container" id="pagination-container" style="display: none;">
                                    <div class="pagination-info">
                                        <span id="pagination-info-text">Showing 0-0 of 0 users</span>
                                    </div>
                                    <div class="pagination-controls">
                                        <div class="page-size-selector">
                                            <label for="page-size">Show:</label>
                                            <select id="page-size">
                                                <option value="5" selected>5</option>
                                                <option value="6">6</option>
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="50">50</option>
                                            </select>
                                            <span>users per page</span>
                                        </div>
                                        <div class="pagination-buttons" id="pagination-buttons">
                                            <!-- Pagination buttons will be generated here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-subsection" id="admin-settings" style="display: none;">
                        <h3>System Settings</h3>
                        <div class="settings-panel">
                            <p>System configuration options will be available here.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Delete User Modal -->
            <div id="delete-user-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="modal-close" onclick="document.getElementById('delete-user-modal').style.display='none'">&times;</span>
                    <div class="modal-body">
                        <i class="fa fa-trash"></i>
                        <h3>Delete User?</h3>
                        <p>Are you sure you want to permanently delete user "<span id="delete-user-name"></span>"?</p>
                        <p><strong>This action cannot be undone.</strong></p>
                        <div class="modal-buttons">
                            <button id="confirm-delete-user" class="modal-confirm-btn">Delete User</button>
                            <button onclick="document.getElementById('delete-user-modal').style.display='none'" class="modal-cancel-btn">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Change Modal -->
            <div id="role-change-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="modal-close" onclick="document.getElementById('role-change-modal').style.display='none'">&times;</span>
                    <div class="modal-body">
                        <i class="fa fa-user-cog"></i>
                        <h3>Change User Role</h3>
                        <p>Change role for user "<span id="role-change-username"></span>"</p>
                        <p>Current role: <strong><span id="role-change-current"></span></strong></p>
                        
                        <div class="form-group">
                            <label for="new-role-select">New Role:</label>
                            <select id="new-role-select" class="form-control">
                                <option value="user">User</option>
                                <option value="monitor">Monitor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="role-descriptions">
                            <small>
                                <strong>User:</strong> Basic access to shopping features<br>
                                <strong>Monitor:</strong> Can view admin statistics and user data<br>
                                <strong>Admin:</strong> Full administrative access
                            </small>
                        </div>
                        
                        <div class="modal-buttons">
                            <button onclick="changeUserRole()" class="modal-confirm-btn">Change Role</button>
                            <button onclick="document.getElementById('role-change-modal').style.display='none'" class="modal-cancel-btn">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `,
        adminOnly: true
    }
};
