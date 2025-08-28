import { cart, removeFromCart, clearCart, toggleDeleteButton } from '../cart.js';
import { formatCurrency } from '../utilities/calculate_cash.js';
import dayjs from '../../package/esm/index.js';
import { renderPaymentSummary } from './paymentsummary.js';

export function renderOrderSummary() {
    let cartSummaryHTML = '';
    const items = cart.getItems();

    // Handle empty cart state
    if (!items || items.length === 0) {
        cartSummaryHTML = `
            <div class="no-products-message">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shopping-cart-off" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="#007bff" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M7 10h14l-1.5 9h-11z" />
                    <path d="M3 3l18 18" />
                </svg>
                <div class="message">
                    You don't have saved items in your cart. Start adding some products
                </div>
            </div>
        `;
    } else {
        items.forEach((item) => {
            const postedDate = dayjs(item.postedDate);
            const dateString = postedDate.format('dddd, MMMM D, YYYY');

            cartSummaryHTML += `
                <div class="cart-item-container js-cart-item-container-${item.productId}">
                    <div class="delivery-date">
                        Posted on: ${dateString}
                    </div>

                    <div class="cart-item-details-grid">
                        <a href="details.html?id=${item.productId}">
                            <img class="product-image" src="${item.image}">
                        </a>

                        <div class="cart-item-details">
                            <div class="product-name">
                                <a href="details.html?id=${item.productId}">${item.name}</a>
                            </div>
                            <div class="product-price">
                                MK ${formatCurrency(item.dollar)}
                            </div>
                            <div class="product-quantity">
                                <span class="delete-quantity-link link-primary js-delete-link" data-product-id="${item.productId}">
                                    <i class="fa fa-trash"></i> Delete
                                </span>
                            </div>
                        </div>

                        <div class="delivery-options">
                            <div class="delivery-options-title">
                                <h4 class="saved-list-descriptionH3">Description <i class="fa fa-info-circle" style="color: #6d2b2b"></i></h4>
                            </div>
                            <p class="saved-list-descriptionP">${item.description}</p>
                        </div>
                    </div>
                    <div class="saved-list-description">
                        <h3 class="top"><i class="fa fa-user"></i> ${item.sellerName}</h3>
                        <p class="location">${item.location}</p>
                    </div>
                </div>
            `;
        });
    }

    const element = document.querySelector('.js-order-summary');
    if (element) {
        element.innerHTML = cartSummaryHTML;
        toggleDeleteButton();
        
        // Add event listeners for delete buttons
        document.querySelectorAll('.js-delete-link').forEach(button => {
            button.addEventListener('click', async (event) => {
                const productId = event.currentTarget.dataset.productId;
                if (await removeFromCart(productId)) {
                    await cart.state.refreshItems();
                    renderOrderSummary();
                    renderPaymentSummary();
                }
            });
        });

        // Add event listener for clear cart button
        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', async () => {
                if (await clearCart()) {
                    renderOrderSummary();
                    renderPaymentSummary();
                }
            });
        }
    }
}