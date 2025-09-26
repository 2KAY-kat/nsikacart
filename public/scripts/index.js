import { cart, addToCart } from './cart.js';
import { header, hero, /*nav*/ } from './data.js';
import { formatCurrency } from './utilities/calculate_cash.js';

// Now cart is cartState instance with methods like:
// cart.getCount() - get current count
// cart.getItems() - get saved items
// cart.subscribe() - subscribe to changes

// initialise the variables for the queries in the loop function
let headerHTML = '';
let heroHTML = '';
//let navHTML = '';

// looping for the header, hero, products

header.forEach((header) => {
    headerHTML += `
    <a href="index.html">
        <div class="nav-logo">
            <img class="logo-navlogo-nav" src="${header.image}" alt="" />
            <p class="dambwe">${header.name}</p>
            <span class="beta-tag">Beta</span>
        </div>
    </a>
    <div class="nav-login-cart">
        <div class="search-wrapper" title="Search products">
            <i class="search-icon fa-solid fa-search" aria-hidden="true"></i>
            <input type="search" class="search-input" placeholder="Search products..." aria-label="Search products" />
        </div>
        <a href="${header.link}"><i class="fa fa-bag-shopping"></i></a>
        <div class="nav-cart-count cart-quantity js-cart-quantity">0</div>
        <a href="/nsikacart/public/dashboard/index.html" class="js-dashboard-link">
            <div class="fa-solid fa-gauge"> </div>
        </a>
    </div>`;
})


document.querySelector('.navbar').innerHTML = headerHTML;

hero.forEach((hero) => {
    heroHTML += `
    
    <div class="hero-text">
            <h1>${hero.hero_text_h1}</h1>
            <p>${hero.hero_text_p}</p>
            <a href="${hero.hero_link}"><button class="btn2">${hero.hero_btn_value}<i class="fa  ${hero.hero_cart_icon}"></i> &rarr;</a></button></a>
        </div>
    `;

})

document.querySelector('.hero').innerHTML = heroHTML;


// NOTED UPDATE: Removed the initial products.forEach rendering engine
// Instead, defined a function to render products by category we going places and yeah its rough...

let products = []; 

async function fetchPublicProducts() {
    try {
        const response = await fetch('/nsikacart/api/products/get-public-products.php');
        const data = await response.json();

        if (data.success) {
            // change API data to match expected format
            products = data.products.map(product => ({
                id: product.id,
                name: product.name,
                dollar: parseFloat(product.price),
                category: product.category,
                description: product.description,
                image: product.main_image || (product.images && product.images[0]) || 'assets/placeholder.png'
            }));
            
            // Render products after fetching
            renderProductsByCategory('All');
            
            // resturn state category selection after products are loaded
            if (window.restoreCategorySelection) {
                window.restoreCategorySelection();
            }
        } else {
            console.error('Failed to fetch products:', data.message);
        }
    } catch (error) {
        console.error('Error fetching public products:', error);
    }
}

function renderProductsByCategory(categoryName) {
    let productsHTML = '';
    let filteredProducts;

    if (categoryName === 'All') {
        filteredProducts = products;
    } else {
        filteredProducts = products.filter(product => product.category === categoryName);
    }

    if (filteredProducts.length === 0) {
        productsHTML = `
            <div class="no-products-message">
                <div class="no-products-svg">
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                        <circle cx="60" cy="60" r="56" stroke="#007bff" stroke-width="8" fill="#333"/>
                        <ellipse cx="60" cy="85" rx="28" ry="10" fill="#0056b3" opacity="0.5"/>
                        <path d="M40 60 Q60 80 80 60" stroke="#007bff" stroke-width="4" fill="none" />
                        <circle cx="50" cy="55" r="4" fill="#007bff"/>
                        <circle cx="70" cy="55" r="4" fill="#007bff"/>
                        <path d="M55 75 Q60 78 65 75" stroke="#333" stroke-width="2" fill="#007bff"/>
                    </svg>
                </div>
                <div class="no-products-text">
                    <h3>No products found</h3>
                    <p>Sorry, there are no products in this category yet.</p>
                </div>
            </div>
        `;
    } else {
        filteredProducts.forEach((product) => {
            productsHTML += `
                <div class="product-card">
                    <img class="product-image" src="${product.image}" alt="">
                    <h3 class="product-name">${product.name}</h3>
                    <!--- <p>${product.category}</p> ---->
                    <p class="product-price">MK${formatCurrency(product.dollar)}</p>
                    <div class="view-details">
                        <button class="btn1 add-to-cart js-add-to-cart"
                            data-product-id="${product.id}"><i
                            class="fa fa-bag-shopping"></i></button>
                        <button class="btn2"><a href="details.html?id=${product.id}">Details</a></button>
                    </div>
                </div>
            `;
        });
    }

    document.querySelector('.js-products-grid').innerHTML = productsHTML;

    // Re-attach add-to-cart event listeners if
    if (filteredProducts.length > 0) {
        document.querySelectorAll('.js-add-to-cart')
            .forEach((button) => {
                button.addEventListener('click', () => {
                    button.classList.add('animate__animated', 'animate__pulse');
                    const productId = button.dataset.productId;
                    const result = addToCart(productId);
                    
                    if (result) {
                        updateCartQuantity();
                    }

                    setTimeout(() => {
                        button.classList.remove('animate__animated', 'animate__pulse');
                    }, 1000);
                });
            });
    }
}

// Expose to window for categories.js to call
window.renderProductsByCategory = renderProductsByCategory;
window.products = products; // Expose products globally

// Initialize products on page load
document.addEventListener('DOMContentLoaded', () => {
    fetchPublicProducts();
    updateCartQuantity();
});

// Remove this line (let restoreCategorySelection handle initial render):
// renderProductsByCategory('All');

/*
nav.forEach((nav) => {
    navHTML += `
    <a href="${nav.link}" class="nav-link">
            <i class="fa fa-${nav.icon} nav-icon"></i>
            <span class="nav-text">${nav.name}</span>
        </a>
    `;
    
})

document.querySelector('nav').innerHTML = navHTML;
*/
//document.querySelector('.js-products-grid').innerHTML = productsHTML;

// end of the forEach loop of the components of teh store

// Loading the DOM for the cart quantity functions and all its scripts 
document.addEventListener('DOMContentLoaded', () => {
    updateCartQuantity();
});

// by default the cart count be 0 (zero) 
function updateCartQuantity() {
    const cartCountElement = document.querySelector('.js-cart-quantity');
    if (cartCountElement) {
        cartCountElement.innerHTML = cart.getCount();
    }
}

// Subscribe to cart state changes
cart.subscribe(count => {
    const cartCountElement = document.querySelector('.js-cart-quantity');
    if (cartCountElement) {
        cartCountElement.innerHTML = count;
    }
});

// Update event listener to use cart state
document.querySelectorAll('.js-add-to-cart')
    .forEach((button) => {
        button.addEventListener('click', async () => {
            button.classList.add('animate__animated', 'animate__pulse');
            const productId = button.dataset.productId;
            await addToCart(productId);

            // Find the product name
            const product = products.find(p => p.id === productId);

            setTimeout(() => {
                button.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        });
    });

    // the back to top button logic 
const backToTop = document.getElementById('back-to-top');

window.addEventListener('scroll', () => {
    if (window.scrollY > 200) {
        backToTop.style.display = 'block';
    } else {
        backToTop.style.display = 'none'
    }
});

backToTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
window.addEventListener('scroll', () => {
    if (window.scrollY > 200) {
        backToTop.style.display = 'block';
    } else {
        backToTop.style.display = 'none'
    }
});

backToTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

backToTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// emd of back to top

// search logic and rendering and querying hundles
function renderProductCards(filteredProducts) {
    let productsHTML = '';

    if (!filteredProducts || filteredProducts.length === 0) {
        productsHTML = `
            <div class="no-products-message">
                <div class="no-products-svg">
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                        <circle cx="60" cy="60" r="56" stroke="#007bff" stroke-width="8" fill="#333"/>
                        <ellipse cx="60" cy="85" rx="28" ry="10" fill="#0056b3" opacity="0.5"/>
                        <path d="M40 60 Q60 80 80 60" stroke="#007bff" stroke-width="4" fill="none" />
                        <circle cx="50" cy="55" r="4" fill="#007bff"/>
                        <circle cx="70" cy="55" r="4" fill="#007bff"/>
                        <path d="M55 75 Q60 78 65 75" stroke="#333" stroke-width="2" fill="#007bff"/>
                    </svg>
                </div>
                <div class="no-products-text">
                    <h3>No products found</h3>
                    <p>Sorry, there are no products matching your search.</p>
                </div>
            </div>
        `;
    } else {
        filteredProducts.forEach((product) => {
            productsHTML += `
                <div class="product-card">
                    <img class="product-image" src="${product.image}" alt="">
                    <h3 class="product-name">${product.name}</h3>
                    <p class="product-price">MK${formatCurrency(product.dollar)}</p>
                    <div class="view-details">
                        <button class="btn1 add-to-cart js-add-to-cart"
                            data-product-id="${product.id}"><i
                            class="fa fa-bag-shopping"></i></button>
                        <button class="btn2"><a href="details.html?id=${product.id}">Details</a></button>
                    </div>
                </div>
            `;
        });
    }

    document.querySelector('.js-products-grid').innerHTML = productsHTML;

    // making sure the searched results does the ussual functionality=ies like add-to-cart...
    document.querySelectorAll('.js-add-to-cart').forEach((button) => {
        button.addEventListener('click', async () => {
            button.classList.add('animate__animated', 'animate__pulse');
            const productId = button.dataset.productId;
            await addToCart(productId);
            setTimeout(() => {
                button.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        });
    });
}

// the results query stuff
function renderSearchResults(query) {
    const queryText = (query || '').trim().toLowerCase();
    if (!queryText) {
        // display the active category if not all
        if (window.restoreCategorySelection) {
            window.restoreCategorySelection();
        } else {
            renderProductsByCategory('All');
        }
        return;
    }

    // filltering the seaech results by text searched tied to category and description of the product 
    const results = products.filter(p => {
        return (p.name && p.name.toLowerCase().includes(queryText)) ||
            (p.description && p.description.toLowerCase().includes(queryText)) || (p.category && p.category.toLowerCase().includes(queryText));
    });

    renderProductCards(results);
}

// hooking up input event. listeners after DOM ready
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        let debounceTimer = null;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const queryText = e.target.value;
            debounceTimer = setTimeout(() => {
                renderSearchResults(queryText);
            }, 250);
        });

        // focus when hovered and keeps it active ... i dont know some people might want the search bar there for a while ... might comment out later
        const wrapper = document.querySelector('.search-wrapper');
        if (wrapper) {
            wrapper.addEventListener('mouseenter', () => searchInput.focus());
        }

        // the icon clicks focus the input so mobile users can open the field by clicking the icon
        const icon = document.querySelector('.search-icon');
        if (icon) {
            icon.addEventListener('click', (e) => {
                e.preventDefault();
                searchInput.focus();
            });
        }
    }
});