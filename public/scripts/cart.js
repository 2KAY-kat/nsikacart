import { showToast } from './utilities/toast.js';
import { TOAST_MESSAGES } from './utilities/messages.js';
import { cartState } from './utilities/cartState.js';

// Initialize cart state
cartState.initialize();

// Export cart state for other modules
export const cart = cartState;

// Update the cart icon count
function updateCartIcon(count) {
    const cartCounter = document.querySelector('.cart-counter');
    if (cartCounter) {
        cartCounter.textContent = count;
        cartCounter.style.display = count > 0 ? 'block' : 'none';
    }
}

// Subscribe to cart state changes
cartState.subscribe(updateCartIcon);

export async function addToCart(productId) {
    try {
        const response = await fetch('/nsikacart/api/products/saved-list/add-to-saved.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });

        const data = await response.json();
        
        if (data.success) {
            await cartState.refreshCount();
            showToast(TOAST_MESSAGES.ADDED_TO_LIST(data.product_name));
            return true;
        } else {
            showToast(data.message);
            return false;
        }
    } catch (error) {
        console.error('Error adding to saved list:', error);
        showToast('Failed to add item to saved list');
        return false;
    }
}

export async function removeFromCart(productId) {
    try {
        const response = await fetch('/nsikacart/api/products/saved-list/remove-saved-item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId
            })
        });

        const data = await response.json();
        
        if (data.success) {
            await cartState.refreshCount();
            showToast(TOAST_MESSAGES.REMOVED_FROM_LIST(data.product_name));
            return true;
        } else {
            showToast(data.message);
            return false;
        }
    } catch (error) {
        console.error('Error removing from saved list:', error);
        showToast('Failed to remove item from saved list');
        return false;
    }
}