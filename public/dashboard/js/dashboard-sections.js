import { products } from '../../scripts/data.js';

const productCategories = Array.from(new Set(products.map(p => p.category)));
productCategories.unshift('All');

const productsStatus = Array.from(new Set(products.map(p => p.status)));
productsStatus.unshift('All');

export const sections = {
    products: {
        header: 'Products',
        content: `
            <div class="app-content-actions">
                <input class="search-bar" placeholder="Search..." type="text">
                
                <div class="app-content-actions-wrapper">
                    <button class="action-button list" title="The products are highly secured">
                        <i class="fa-solid fa-lock"></i>
                    </button>
                </div>
              
            </div>
            <div class="products-area-wrapper tableView" id="dashboard-products-table"></div>
        `
    },
    admin: {
        header: 'Admin Panel',
        content: `
            <div class="admin-section">
                <div class="admin-nav">
                    <button class="admin-nav-btn active" data-admin-section="statistics">
                        <i class="fa-solid fa-chart-pie"></i>
                        Statistics
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
                                    <i class="fa-solid fa-bag-shopping"></i>
                                </div>
                                <div class="stat-info">
                                    <h4 id="total-products">Loading...</h4>
                                    <p>Total Products</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <div class="stat-info">
                                    <h4 id="monthly-sales">Loading...</h4>
                                    <p>Monthly Sales</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fa-solid fa-eye"></i>
                                </div>
                                <div class="stat-info">
                                    <h4 id="active-sessions">Loading...</h4>
                                    <p>Active Sessions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="admin-subsection" id="admin-users" style="display: none;">
                        <h3>User Management</h3>
                        <div class="users-management">
                            <div id="users-table">Loading users...</div>
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
        `,
        adminOnly: true
    }
};
