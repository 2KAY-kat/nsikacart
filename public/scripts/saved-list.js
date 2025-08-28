import { cart } from './cart.js';
import { renderOrderSummary } from './saved-list/orderSummery.js';
import { renderPaymentSummary } from './saved-list/paymentsummary.js';

// Function to update the header count
function updateHeaderCount(count) {
    const returnLink = document.querySelector('.js-return-to-home-link');
    if (returnLink) {
        returnLink.textContent = count || 0;
    }
}

// Initialize cart and render saved items
async function initializeSavedList() {
    await cart.state.initialize();
    
    // Subscribe to cart state changes to update the header count
    cart.subscribe(updateHeaderCount);
    
    renderOrderSummary();
    renderPaymentSummary();
}

// Call initialization when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeSavedList);