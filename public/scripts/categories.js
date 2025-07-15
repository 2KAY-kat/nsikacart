import { categories } from "./categories-data.js";

let categoriesHTML = '';

categories.forEach((category, idx) => {
    const iconClass = category.categoryIcon || 'fa-tag';
    categoriesHTML += `
        <div class="category-card${idx === 0 ? ' active' : ''} ${category.sta_tus || ''}" data-category="${category.name}" tabindex="0">
            <span class="category-icon"><i class="fa ${iconClass}"></i></span>
            <h3>${category.name}</h3>
        </div>
    `;
});

document.querySelector('.js-categories-grid').innerHTML = categoriesHTML;

// Category filtering logic
const categoryCards = document.querySelectorAll('.category-card');

function setActiveCategory(selectedCard) {
    categoryCards.forEach(card => card.classList.remove('active'));
    selectedCard.classList.add('active');
    // Save selected category to localStorage
    const categoryName = selectedCard.getAttribute('data-category');
    localStorage.setItem('selectedCategory', categoryName);
}

function filterProductsByCategory(categoryName) {
    // Expose this function from index.js
    if (window.renderProductsByCategory) {
        window.renderProductsByCategory(categoryName);
    }
}

// Restore selected category from localStorage (deferred until products grid is ready)
function restoreCategorySelection() {
    const savedCategory = localStorage.getItem('selectedCategory');
    let found = false;
    if (savedCategory) {
        categoryCards.forEach(card => {
            if (card.getAttribute('data-category') === savedCategory) {
                setActiveCategory(card);
                filterProductsByCategory(savedCategory);
                found = true;
            }
        });
    }
    // If not found, default to first category
    if (!found && categoryCards.length > 0) {
        setActiveCategory(categoryCards[0]);
        filterProductsByCategory(categoryCards[0].getAttribute('data-category'));
    }
}

// Expose restore function for index.js to call after renderProductsByCategory is ready
window.restoreCategorySelection = restoreCategorySelection;

categoryCards.forEach(card => {
    card.addEventListener('click', () => {
        setActiveCategory(card);
        const categoryName = card.getAttribute('data-category');
        filterProductsByCategory(categoryName);
    });
    card.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            card.click();
        }
    });
});

const category = document.getElementById('categories');
const stickyTigger = document.getElementById('sticky-trigger');

const observer = new IntersectionObserver(
    ([entry]) => {
        if (!entry.isIntersecting) {
            category.classList.add('sticky');

            category.classList.remove('unstick');
        } else {
            category.classList.remove('sticky');
            category.classList.add('unstick');
        }
    },
    { threshold: 0 }
);

observer.observe(stickyTigger);