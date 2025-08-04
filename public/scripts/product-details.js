import { otherHeader } from './otherheader-data.js';
import { formatCurrency } from './utilities/calculate_cash.js';
import { cart, addToCart } from './cart.js';


let otherHeaderHTML = '';

otherHeader.forEach((otherHeader) => {
    otherHeaderHTML += `
        <div class="header-content">
            <div class="checkout-header-left-section">
                <a href="${otherHeader.link}">
                    <img class="dambwe-logo" src="${otherHeader.image}">
                    <img class="dambwe-mobile-logo" src="${otherHeader.image}">
                </a>
            </div>
    
            <div class="checkout-header-middle-section">
            <a href="saved-list.html" class="icon-bag btn"><i class="fas fa-shopping-bag"></i> View Basket</a>

            </div>
    
            <div class="checkout-header-right-section">
    
                <a href="dashboard/index.html" class="js-dashboard-link">
                    <div class="fa-solid fa-gauge"> </div>
                </a>
    
            </div>
        </div>
    `;
});

document.querySelector('.checkout-header').innerHTML = otherHeaderHTML;

// Get the product ID from URL parameters
const urlParams = new URLSearchParams(window.location.search);
const productId = urlParams.get('id');

// Fetch and display product details
async function fetchAndDisplayProduct(productId) {
    try {
        const response = await fetch('/nsikacart/api/products/get-public-products.php');
        const data = await response.json();

        if (data.success) {
            // change API data to match expected format
            const products = data.products.map(product => ({
                id: product.id,
                name: product.name,
                dollar: parseFloat(product.price),
                category: product.category,
                description: product.description,
                image: product.main_image || (product.images && product.images[0]) || 'assets/placeholder.png'
            }));

            // Find the product
            const product = products.find(p => p.id === productId);

            if (product) {
                displayProductDetails(product);
            } else {
                displayProductNotFound();
            }
        } else {
            console.error('Failed to fetch products:', data.message);
            displayProductNotFound();
        }
    } catch (error) {
        console.error('Error fetching product details:', error);
        displayProductNotFound();
    }
}

function displayProductDetails(product) {
    const productDetails = document.getElementById('productDetails');
    productDetails.innerHTML = `
        <div class="row">
            <div class="col-2">
                <img src="${product.image}" alt="${product.name}" id="productImage">
                <div class="small-img-row">
                    <div class="small-img-col">
                        <img src="${product.image}" alt="${product.name}" class="small-img">
                    </div>
                </div>
            </div>

            <div class="col-2">
                <p><a href="index.html">Home</a> / ${product.category}</p>
                <h1>${product.name}</h1>
                <h4 class="view-details-price">MK ${formatCurrency(product.dollar)}</h4>
                <button class="btn btn1 js-add-to-cart" data-product-id="${product.id}"><i class="fa fa-heart"></i> Add Saved List</button>
                
                <div class="account-info-name details-seller-name">
                    <h3>Product Details<i class="fa fa-indent"></i></h3>
                    <br>
                    <h3><i class="fa fa-user"> </i> Seller Name</h3>
                    <p class="location">Blantyre</p>
                    <p>
                        <ul class="seller-socails">
                            <li> 
                                <i class="fab fa-whatsapp"></i> - (265) XXX XXX XXX
                            </li>
                        </ul>
                    </p>
                    <p>${product.description || 'No description available'}</p>
                </div>
            </div>
        </div>
    `;

    // Add event listener for add to cart button
    const addToCartBtn = document.querySelector('.js-add-to-cart');
    addToCartBtn.addEventListener('click', () => {
        addToCartBtn.classList.add('animate__animated', 'animate__pulse');
        addToCart(productId);
        showToast(`${product.name} Has been added to cart!`);
        
        setTimeout(() => {
            addToCartBtn.classList.remove('animate__animated', 'animate__pulse');
        }, 1000);
    });
}

function displayProductNotFound() {
    const productDetails = document.getElementById('productDetails');
    productDetails.innerHTML = `
        <div class="product-not-found">
            <h2>Product Not Found</h2>
            <p>Sorry, the product you're looking for doesn't exist or is no longer available.</p>
            <a href="index.html" class="btn btn2">Back to Shop</a>
        </div>
    `;
}

// Initialize when page loads
if (productId) {
    fetchAndDisplayProduct(productId);
} else {
    displayProductNotFound();
}

function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show', 'animate__animated', 'animate__fadeInUp');
    
    setTimeout(() => {
        toast.classList.remove('show', 'animate__fadeInUp');
        toast.classList.add('animate__fadeOutDown');
        
        setTimeout(() => {
            toast.classList.remove('animate__animated', 'animate__fadeOutDown');
        }, 300);
    }, 3000);
}

const productImg = document.querySelector('#productImage');
const smallImg = document.querySelectorAll('.small-img');

for (let i = 0; i < smallImg.length; i++) {
    smallImg[i].onclick = function () {
        productImg.src = smallImg[i].src;
    };
}



