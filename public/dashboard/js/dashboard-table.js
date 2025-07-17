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
                    <div class="product-cell edit"><a class="delete-link" href="./delete.html"><i class="fa fa-trash-can"></i> Delete</a></div>
                </div>
            `;
        }).join('');

        table.innerHTML = html + productsHTML;

    } catch (error) {
        console.error('Fetch error:', error);
        table.innerHTML = html + `
            <div class="error-message">
                Failed to load your products. Please try again later.
            </div>
        `;
    }
}

/*
// Grid view rendering
export function renderProductsGrid() {
    const table = document.getElementById('dashboard-products-table');
    if (!table) return;
    table.style.maxHeight = "";
    table.style.overflowY = "";
    table.style.display = "grid";
    table.style.gridTemplateColumns = "repeat(auto-fill, minmax(220px, 1fr))";
    table.style.gap = "16px";
    table.innerHTML = products.map(product => {
        let imgSrc = product.image;
        if (imgSrc.startsWith('./')) {
            imgSrc = '../' + imgSrc.substring(2);
        } else if (!/^https?:\/\//.test(imgSrc) && !imgSrc.startsWith('/')) {
            imgSrc = '../' + imgSrc;
        }
        return `
        <div class="product-grid-card" style="border:1px solid #eee; border-radius:8px; padding:16px; background:#fff;">
            <div style="display:flex; align-items:center; margin-bottom:8px;">
                <img src="${imgSrc}" alt="product" style="width:48px; height:48px; object-fit:cover; border-radius:4px; margin-right:8px;">
                <span style="font-weight:bold;">${product.name}</span>
            </div>
            <div>Category: <b>${product.category}</b></div>
            <div>Status: <span class="status ${product.status}">${product.status}</span></div>
            <div>Sales: 0</div>
            <div>Stock: 0</div>
            <div>Price: <b>MK${formatCurrency(product.dollar)}</b></div>
        </div>
        `;
    }).join('');
}
    */