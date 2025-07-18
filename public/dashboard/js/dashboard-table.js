import { formatCurrency } from '../../scripts/utilities/calculate_cash.js';
import { getCurrentUser, validateSession } from './session-manager.js';

export async function renderProductsTable() {
    // First validate session
    await validateSession();
    
    // Get current user data
    const user = getCurrentUser();
    if (!user) {
        console.error('No user session found');
        return;
    }

    const table = document.getElementById('dashboard-products-table');
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
        // add user_id to the request
        const response = await fetch(`/nsikacart/api/products/get-products-individual.php`);
        const contentType = response.headers.get('content-type');
        
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch products');
        }

        const products = data.products;

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

        // Add event listeners for delete buttons
        setupDeleteHandlers();

    } catch (error) {
        console.error('Fetch error:', error);
        table.innerHTML = html + `
            <div class="error-message">
                Failed to load your products. Please try again later.
            </div>
        `;
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