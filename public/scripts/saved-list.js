import { cart } from './cart.js';
import { renderOrderSummary } from './saved-list/orderSummery.js';
import { renderPaymentSummary } from './saved-list/paymentsummary.js';

// Initialize cart and render saved items
async function initializeSavedList() {
    await cart.state.initialize();
    renderOrderSummary();
    renderPaymentSummary();
}

// Call initialization when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeSavedList);