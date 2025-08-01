import { formatCurrency } from '../../scripts/utilities/calculate_cash.js';
import { getCurrentUser, validateSession } from './session-manager.js';

// Global pagination state for products
let currentProductsPage = 1;
let currentProductsPageSize = 5;
let totalProductsPages = 1;
let currentSearchTerm = '';
let searchDebounceTimer = null;

export async function renderProductsTable(page = 1, pageSize = 5, searchTerm = '') {
    // First validate session
    await validateSession();
    
    // Get current user data
    const user = getCurrentUser();
    if (!user) {
        console.error('No user session found');
        return;
    }

    const table = document.getElementById('dashboard-products-table');
    const paginationContainer = document.getElementById('products-pagination-container');
    if (!table) return;

    table.style.maxHeight = "400px";
    table.style.overflowY = "auto";
    table.style.display = "block";

    let html = `
        <div class="products-header">
            <div class="product-cell image">Items</div>
            <div class="product-cell category">Category</div>
            <div class="product-cell status-cell">Status</div>
            <div class="product-cell price">Price</div>
            <div class="product-cell edit">Update</div>
            <div class="product-cell delete">Delete</div>
        </div>
    `;

    try {
        // Add search parameter to the request
        let apiUrl = `/nsikacart/api/products/get-products-individual.php?page=${page}&limit=${pageSize}`;
        if (searchTerm) {
            apiUrl += `&search=${encodeURIComponent(searchTerm)}`;
        }
        
        const response = await fetch(apiUrl);
        const contentType = response.headers.get('content-type');
        
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch products');
        }

        const products = data.products;
        
        // Update pagination state
        if (data.pagination) {
            currentProductsPage = data.pagination.current_page;
            currentProductsPageSize = data.pagination.limit;
            totalProductsPages = data.pagination.total_pages;
        }

        const productsHTML = products.map(product => {
            const imgSrc = `uploads/${product.main_image}`;
            return `
                <div class="products-row">
                    <a class="product-cell image" href="../../public/details.html?id=${product.id}">
                        <img src="${imgSrc}" alt="product">
                        <span>${product.name}</span>
                    </a>
                    <div class="product-cell category">${product.category}</div>
                    <div class="product-cell status-cell">
                        <span class="status ${product.status}">${product.status}</span>
                    </div>
                    <div class="product-cell price">MK${formatCurrency(product.price)}</div>
                    <div class="product-cell edit">
                        <a class="edit-link" href="./upload.html?edit=${product.id}">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                    </div>
                    <div class="product-cell edit">
                        <a class="delete-link" data-product-id="${product.id}" data-product-name="${product.name}">
                            <i class="fa fa-trash-can"></i> Delete
                        </a>
                    </div>
                </div>
            `;
        }).join('');

        table.innerHTML = html + productsHTML;

        // Show pagination if we have data
        if (data.pagination && data.pagination.total_records > 0) {
            updateProductsPagination(data.pagination);
            if (paginationContainer) {
                paginationContainer.style.display = 'flex';
            }
        } else {
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
        }

        // Add event listeners for delete buttons
        setupDeleteHandlers();
        
        // Setup pagination events
        setupProductsPaginationEvents();

    } catch (error) {
        console.error('Fetch error:', error);
        table.innerHTML = html + `
            <div class="error-message">
                Failed to load your products. Please try again later.
            </div>
        `;
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
    }
}

// Setup search functionality
export function setupProductsSearch() {
    const searchInput = document.getElementById('products-search');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        // Clear previous timer
        if (searchDebounceTimer) {
            clearTimeout(searchDebounceTimer);
        }
        
        // Debounce search to avoid too many API calls
        searchDebounceTimer = setTimeout(() => {
            renderProductsTable(1, currentProductsPageSize, searchTerm);
        }, 300); 
    });
    
    // Clear search button functionality
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            renderProductsTable(1, currentProductsPageSize, '');
        }
    });
}

function updateProductsPagination(pagination) {
    const infoText = document.getElementById('products-pagination-info-text');
    const buttonsContainer = document.getElementById('products-pagination-buttons');
    const pageSizeSelect = document.getElementById('products-page-size');
    
    if (!pagination) return;
    
    // Update info text
    const start = ((pagination.current_page - 1) * pagination.limit) + 1;
    const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
    if (infoText) {
        infoText.textContent = `Showing ${start}-${end} of ${pagination.total_records} products`;
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
        prevBtn.onclick = () => pagination.has_prev && renderProductsTable(pagination.current_page - 1, pagination.limit, currentSearchTerm);
        buttonsContainer.appendChild(prevBtn);
        
        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        // First page if not in range
        if (startPage > 1) {
            const firstBtn = createProductsPageButton(1, pagination.current_page, pagination.limit);
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
            const pageBtn = createProductsPageButton(i, pagination.current_page, pagination.limit);
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
            
            const lastBtn = createProductsPageButton(pagination.total_pages, pagination.current_page, pagination.limit);
            buttonsContainer.appendChild(lastBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.textContent = 'Next ›';
        nextBtn.disabled = !pagination.has_next;
        nextBtn.onclick = () => pagination.has_next && renderProductsTable(pagination.current_page + 1, pagination.limit, currentSearchTerm);
        buttonsContainer.appendChild(nextBtn);
    }
}

function createProductsPageButton(pageNumber, currentPage, pageSize) {
    const btn = document.createElement('button');
    btn.className = `pagination-btn ${pageNumber === currentPage ? 'active' : ''}`;
    btn.textContent = pageNumber;
    btn.onclick = () => renderProductsTable(pageNumber, pageSize, currentSearchTerm);
    return btn;
}

// Setup pagination event listeners for products
function setupProductsPaginationEvents() {
    const pageSizeSelect = document.getElementById('products-page-size');
    if (pageSizeSelect) {
        pageSizeSelect.value = currentProductsPageSize;
        
        pageSizeSelect.addEventListener('change', function() {
            const newPageSize = parseInt(this.value);
            renderProductsTable(1, newPageSize, currentSearchTerm);
        });
    }
}

// Setup delete button event handlers
function setupDeleteHandlers() {
    document.querySelectorAll('.delete-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = link.dataset.productId;
            const productName = link.dataset.productName;
            showDeleteModal(productId, productName);
        });
    });
}

// Modal logic for delete confirmation
function showDeleteModal(productId, productName) {
    const modal = document.getElementById('delete-modal');
    const modalMessage = document.getElementById('modal-message');
    
    modalMessage.textContent = `Are you sure you want to permanently delete "${productName}"? This action cannot be undone.`;
    
    modal.style.display = 'flex';
    modal.dataset.productId = productId;
}

function hideDeleteModal() {
    const modal = document.getElementById('delete-modal');
    modal.style.display = 'none';
    modal.dataset.productId = '';
}

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}

// Delete product function with comprehensive error handling
async function deleteProduct(productId) {
    try {
        // Validate input
        if (!productId || isNaN(productId)) {
            showToast('Invalid product ID', 'error');
            return;
        }

        const response = await fetch('/nsikacart/api/products/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ product_id: parseInt(productId) }),
            credentials: 'include'
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        // Handle different HTTP status codes
        if (response.status === 401) {
            showToast('Please log in to delete products', 'error');
            // Redirect to login if needed
            setTimeout(() => {
                window.location.href = '../auth/login.html';
            }, 2000);
            return;
        }

        if (response.status === 500) {
            showToast('Server error occurred. Please try again later.', 'error');
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Check content type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            showToast('Server returned an invalid response', 'error');
            return;
        }

        // Get response text first to debug empty responses
        const responseText = await response.text();
        console.log('Response text:', responseText);

        if (!responseText.trim()) {
            showToast('Server returned empty response', 'error');
            return;
        }

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text that failed to parse:', responseText);
            showToast('Invalid response from server', 'error');
            return;
        }
        
        if (data.success) {
            showToast('Product deleted successfully', 'success');
            // Refresh the products table
            setTimeout(() => {
                renderProductsTable();
            }, 1000);
        } else {
            showToast(data.message || 'Failed to delete product', 'error');
        }

    } catch (error) {
        console.error('Delete error:', error);
        
        // Network or other errors
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            showToast('Network error. Please check your connection.', 'error');
        } else {
            showToast('An unexpected error occurred', 'error');
        }
    }
}

// Initialize modal event handlers when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('delete-modal');
    
    if (modal) {
        // Confirm delete
        document.getElementById('modal-confirm-btn').onclick = function() {
            const productId = modal.dataset.productId;
            if (productId) {
                deleteProduct(productId);
                hideDeleteModal();
            }
        };

        // Cancel delete
        document.getElementById('modal-cancel-btn').onclick = hideDeleteModal;
        
        // Close (X) button
        document.getElementById('modal-close-btn').onclick = hideDeleteModal;
        
        // Close modal when clicking outside content
        modal.onclick = function(e) {
            if (e.target === modal) hideDeleteModal();
        };
    }
});