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
                id: product.id.toString(), 
                name: product.name,
                dollar: parseFloat(product.price),
                category: product.category,
                description: product.description,
                location: product.location,
                images: product.images || [],
                image: product.main_image || (product.images && product.images[0]) || '/nsikacart/public/assets/placeholder.png',
                seller_phone: product.seller_phone,
                seller_name: product.seller_name
            }));

            // Find the product
            const product = products.find(p => p.id === productId);

            if (product) {
                displayProductDetails(product);
                // open product globally for cart functionality
                window.currentProduct = product;
                window.products = products;
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

// Helper function to format phone for display
function formatPhoneForDisplay(phone) {
    if (!phone) return '';
    
    // Remove all non-digits
    const digits = phone.replace(/\D/g, '');
    
    // Format as (265) XXX XXX XXX for Malawi numbers
    if (digits.startsWith('265')) {
        return `(265) ${digits.slice(3, 6)} ${digits.slice(6, 9)} ${digits.slice(9)}`;
    } else if (digits.startsWith('0')) {
        // Local format starting with 0
        return `(265) ${digits.slice(1, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
    } else {
        // Assume it's already without country code
        return `(265) ${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6)}`;
    }
}

// Helper function to format phone for WhatsApp
function formatPhoneForWhatsApp(phone) {
    if (!phone) return '';
    
    // Remove all non-digits
    const digits = phone.replace(/\D/g, '');
    
    // Ensure it starts with 265 (Malawi country code)
    if (digits.startsWith('265')) {
        return digits;
    } else if (digits.startsWith('0')) {
        return '265' + digits.slice(1);
    } else {
        return '265' + digits;
    }
}

function displayProductDetails(product) {
    const productDetails = document.getElementById('productDetails');
    
    // Prepare additional images for gallery
    const allImages = [product.image, ...product.images].filter(img => img && img !== product.image);
    const uniqueImages = [...new Set(allImages)]; // Remove duplicates
    
    // Generate small image gallery
    let smallImagesHTML = '';
    uniqueImages.forEach(image => {
        smallImagesHTML += `
            <div class="small-img-col">
                <img src="${image}" alt="${product.name}" class="small-img">
            </div>
        `;
    });
    
    // Fallback if no additional images
    if (smallImagesHTML === '') {
        smallImagesHTML = `
            <div class="small-img-col">
                <img src="${product.image}" alt="${product.name}" class="small-img">
            </div>
        `;
    }

    // Format phone number for display and WhatsApp link
    const displayPhone = formatPhoneForDisplay(product.seller_phone);
    const whatsappPhone = formatPhoneForWhatsApp(product.seller_phone);
    const whatsappMessage = encodeURIComponent(`Hi! I'm interested in "${product.name}" listed on Nsikacart. Is it still available?`);

    productDetails.innerHTML = `
        <div class="row">
            <div class="col-2">
                <img src="${product.image}" alt="${product.name}" id="productImage">
                <div class="small-img-row">
                    ${smallImagesHTML}
                </div>
            </div>

            <div class="col-2">
                <p><a href="index.html">Home</a> / ${product.category || 'General'}</p>
                <h1>${product.name}</h1>
                <h4 class="view-details-price">MK ${formatCurrency(product.dollar)}</h4>
                <button class="btn btn1 js-add-to-cart" data-product-id="${product.id}">
                    <i class="fa fa-heart"></i> Add to Saved List
                </button>
                
                <div class="account-info-name details-seller-name">
                    <h3>Product Details <i class="fa fa-indent"></i></h3>
                    <br>
                    <h3><i class="fa fa-user"></i> Seller Information</h3>
                    ${product.seller_name ? `<p><strong>Seller:</strong> ${product.seller_name}</p>` : ''}
                    <p class="location">
                        <i class="fa fa-map-marker-alt"></i> ${product.location || 'Location not specified'}
                    </p>
                    <p>
                        <ul class="seller-socails">
                            <li> 
                                <i class="fab fa-whatsapp"></i> 
                                ${product.seller_phone ? 
                                    `<a href="https://wa.me/${whatsappPhone}?text=${whatsappMessage}" target="_blank" class="whatsapp-link">
                                        Contact Seller: ${displayPhone}
                                    </a>` : 
                                    'Contact Seller: Phone not available'
                                }
                            </li>
                        </ul>
                    </p>
                    <div class="product-description">
                        <h4>Description:</h4>
                        <p>${product.description || 'No description available for this product.'}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add event listener for add to cart button
    const addToCartBtn = document.querySelector('.js-add-to-cart');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', () => {
            addToCartBtn.classList.add('animate__animated', 'animate__pulse');
            const result = addToCart(productId);
            
            if (result) {
                showToast(`${product.name} has been added to your saved list!`);
                updateCartQuantity();
            }
            
            setTimeout(() => {
                addToCartBtn.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        });
    }

    // Add image gallery functionality
    setupImageGallery();
}

function setupImageGallery() {
    const productImg = document.querySelector('#productImage');
    const smallImgs = document.querySelectorAll('.small-img');

    smallImgs.forEach((img, index) => {
        img.addEventListener('click', function() {
            productImg.src = this.src;
            
            // Remove active class from all thumbnails
            smallImgs.forEach(thumb => thumb.classList.remove('active'));
            
            // Add active class to clicked thumbnail
            this.classList.add('active');
        });
    });

    // Set first image as active by default
    if (smallImgs.length > 0) {
        smallImgs[0].classList.add('active');
    }
}

function displayProductNotFound() {
    const productDetails = document.getElementById('productDetails');
    productDetails.innerHTML = `
        <div class="product-not-found">
            <div class="not-found-icon">
                <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #ff6b6b;"></i>
            </div>
            <h2>Product Not Found</h2>
            <p>Sorry, the product you're looking for doesn't exist or is no longer available.</p>
            <p>It may have been removed or the link is incorrect.</p>
            <div class="not-found-actions">
                <a href="index.html" class="btn btn2">
                    <i class="fa fa-home"></i> Back to Shop
                </a>
                <a href="javascript:history.back()" class="btn btn1">
                    <i class="fa fa-arrow-left"></i> Go Back
                </a>
            </div>
        </div>
    `;
}

// Cart quantity update function
function updateCartQuantity() {
    let cartQuantity = 0;
    cart.forEach((cartItem) => {
        cartQuantity += cartItem.quantity;
    });

    const cartCountElement = document.querySelector('.js-cart-quantity');
    if (cartCountElement) {
        cartCountElement.innerHTML = cartQuantity;
    }
}

// Initialize when page loads
if (productId) {
    fetchAndDisplayProduct(productId);
} else {
    displayProductNotFound();
}

// Toast notification function
function showToast(message) {
    const toast = document.getElementById('toast') || createToastElement();
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

// Create toast element if it doesn't exist
function createToastElement() {
    const toast = document.createElement('div');
    toast.id = 'toast';
    toast.className = 'toast';
    document.body.appendChild(toast);
    return toast;
}

// Dashboard redirect functionality
document.addEventListener('DOMContentLoaded', () => {
    updateCartQuantity();
    
    // Dashboard link handler
    document.querySelectorAll('.js-dashboard-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            // Add loading state or redirect logic here
            window.location.href = '/nsikacart/public/dashboard/index.html';
        });
    });
});



