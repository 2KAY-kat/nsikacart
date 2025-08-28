import { cart } from '../cart.js';
import { formatCurrency } from '../utilities/calculate_cash.js';


export function renderPaymentSummary() {
    let productDollar = 0;
    let cartQuantity = 0;

    // Get items directly from cart state
    const items = cart.getItems();

    items.forEach((item) => {
        productDollar += item.dollar * (item.quantity || 1);
        cartQuantity += (item.quantity || 1);
    });

    const paymentsummaryHTML = `
        <div class="payment-summary-title">
            Saved Items Total
        </div>

        <div class="payment-summary-row total-row">
            <div>Items (${cartQuantity}):</div>
            <div class="payment-summary-money">MK${formatCurrency(productDollar)}</div>
        </div>            
    `;

    const element = document.querySelector('.js-payment-summary');
    if (element) {
        element.innerHTML = paymentsummaryHTML;
    }
}

/****
 * 
 * 
 * 
 * 
 * <!--
            <div class="payment-summary-row">
                <div>Shipping &amp; handling:</div>
                <div class="payment-summary-money">MK${formatCurrency(ShippingDollar)}</div>
            </div>

            <div class="payment-summary-row subtotal-row">
                <div>Total before tax:</div>
                <div class="payment-summary-money">MK${formatCurrency(totalBeforeTaxDollar)}</div>
            </div>

            <div class="payment-summary-row">
                <div>Estimated tax (10%):</div>
                <div class="payment-summary-money">MK${formatCurrency(taxDollar)}</div>
            </div>

            <div class="payment-summary-row total-row">
                <div>Order total:</div>
                <div class="payment-summary-money">MK${formatCurrency(totalDollar)}</div>
            </div>

            <button class="place-order-button button-primary">
                Place your order
            </button>
            -->
 */