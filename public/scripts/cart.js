import { showToast } from './utilities/toast.js';
import { TOAST_MESSAGES } from './utilities/messages.js';
import { cartState } from './utilities/cartState.js';

// Initialize cart state
cartState.initialize();

// Export cart instance with additional methods
class Cart {
    constructor(state) {
        this.state = state;
    }

    getCount() {
        return this.state.getCount();
    }

    getItems() {
        return this.state.getItems();
    }

    forEach(callback) {
        this.getItems().forEach(callback);
    }

    reduce(callback, initialValue) {
        return this.getItems().reduce(callback, initialValue);
    }

    // Add subscription methods
    subscribe(callback) {
        return this.state.subscribe(callback);
    }

    unsubscribe(callback) {
        return this.state.unsubscribe(callback);
    }
}

// Create and export cart instance
export const cart = new Cart(cartState);

// Move updateCartIcon into Cart class methods
function updateCartIcon(count) {
    const cartCounter = document.querySelector('.cart-counter');
    if (cartCounter) {
        cartCounter.textContent = count;
        cartCounter.style.display = count > 0 ? 'block' : 'none';
    }
}

// Subscribe to cart state changes using the Cart instance
cart.subscribe(updateCartIcon);

export async function addToCart(productId) {
    try {
        const response = await fetch('./api/products/saved-list/add-to-saved.php', {
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
        const response = await fetch('./api/products/saved-list/remove-saved-item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId
            })
        });

        const text = await response.text();
        
        try {
            const data = JSON.parse(text);
            if (data.success) {
                await cartState.refreshCount();
                await cartState.refreshItems();
                showToast(TOAST_MESSAGES.REMOVED_FROM_LIST(data.product_name));
                return true;
            } else {
                showToast(data.message);
                return false;
            }
        } catch (parseError) {
            console.error('Invalid JSON response:', text);
            showToast('Server returned invalid response');
            return false;
        }
    } catch (error) {
        console.error('Error removing from saved list:', error);
        showToast('Failed to remove item from saved list');
        return false;
    }
}
export async function clearCart() {
    try {
        const response = await fetch('./api/products/saved-list/clear-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const text = await response.text();
        
        try {
            const data = JSON.parse(text);
            if (data.success) {
                await cartState.refreshCount();
                await cartState.refreshItems();
                showToast('All items removed from saved list');
                return true;
            } else {
                showToast(data.message);
                return false;
            }
        } catch (parseError) {
            console.error('Invalid JSON response:', text);
            showToast('Server returned invalid response');
            return false;
        }
    } catch (error) {
        console.error('Error clearing cart:', error);
        showToast('Failed to clear saved list');
        return false;
    }
}

export function toggleDeleteButton() {
    const clearCartBtn = document.getElementById('clear-cart-btn');
    if (clearCartBtn) {
        const hasItems = cart.getCount() > 0;
        clearCartBtn.style.display = hasItems ? 'inline-block' : 'none';
    }
}