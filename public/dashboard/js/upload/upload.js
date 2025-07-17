import { uploadContainer } from '../upload/upload-data.js';
import { categories } from '../../../scripts/categories-data.js';
import { validateSession, getCurrentUser } from '../session-manager.js';

let uploadContainerHTML = '';
let isEditMode = false;
let editProductId = null;
let currentProduct = null;

// Check if we're in edit mode
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('edit')) {
    isEditMode = true;
    editProductId = urlParams.get('edit');
}

uploadContainer.forEach((container) => {
    const title = isEditMode ? container.editTitle : container.title;
    const buttonText = isEditMode ? container.UpdateButton : container.PublishButton;
    
    uploadContainerHTML += `
        <div class="header">
            <button type="button" class="back-btn"><a href="./index.html">←</a></button>
            <h2>${title}</h2>
        </div>
        
        <form id="product-form" enctype="multipart/form-data">
            ${isEditMode ? `<input type="hidden" name="product_id" value="${editProductId}">` : ''}
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select a category</option>
                </select>
            </div>
            <div class="form-group">
                <label for="name">What are you selling?</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="price">Price (MK)</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="custom-file-upload">
                    ${isEditMode ? 'Change Main Photo (optional)' : 'Add Main Photo'}
                    <input type="file" id="main_image" name="main_image" accept="image/*" style="display: none;" ${isEditMode ? '' : 'required'}>
                </label>
                <div class="file-name" id="main-image-name">No files chosen</div>
                ${container.previewContainers}
                
                <label class="custom-file-upload">
                    ${isEditMode ? 'Add More Photos (optional)' : 'Add Other Photos'}
                    <input type="file" id="other_images" name="images[]" accept="image/*" style="display: none;" multiple>
                </label>
                <div class="file-name" id="other-images-name">No files chosen</div>
            </div>
            <div class="policy-note">
                All products go through a quick standard review when published.
            </div>
            <button type="submit" class="submit-btn">${buttonText}</button>
        </form>
        <div id="toast" class="toast"></div>
    `;
});

document.querySelector('.container').innerHTML = uploadContainerHTML;

// Initialize category select
const categorySelect = document.querySelector('#category');
categories.forEach((category) => {
    const option = document.createElement('option');
    option.value = category.name;
    option.textContent = category.name;
    categorySelect.appendChild(option);
});

// Load product data if in edit mode
if (isEditMode && editProductId) {
    loadProductForEdit(editProductId);
}

async function loadProductForEdit(productId) {
    try {
        const response = await fetch(`/nsikacart/api/products/get-product.php?id=${productId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        currentProduct = data.product;
        populateForm(currentProduct);
        
    } catch (error) {
        console.error('Error loading product:', error);
        showToast('Error loading product: ' + error.message, 'error');
        setTimeout(() => {
            window.location.href = './index.html';
        }, 2000);
    }
}

function populateForm(product) {
    // Populate form fields
    document.getElementById('name').value = product.name || '';
    document.getElementById('price').value = product.price || '';
    document.getElementById('location').value = product.location || '';
    document.getElementById('description').value = product.description || '';
    document.getElementById('category').value = product.category || '';
    document.getElementById('status').value = product.status || 'active';
    
    // Show current main image
    if (product.main_image) {
        const mainPreview = document.getElementById('main_preview');
        mainPreview.src = `uploads/${product.main_image}`;
        mainPreview.style.display = 'block';
        document.getElementById('main-image-name').textContent = `Current: ${product.main_image}`;
    }
    
    // Show other images
    if (product.images && product.images.length > 0) {
        const otherPreviews = document.getElementById('other_previews');
        otherPreviews.innerHTML = '';
        
        product.images.forEach(imageName => {
            if (imageName !== product.main_image) { // Don't show main image again
                const img = document.createElement('img');
                img.src = `uploads/${imageName}`;
                img.classList.add('preview-image');
                img.setAttribute('title', imageName);
                otherPreviews.appendChild(img);
            }
        });
        
        if (otherPreviews.children.length > 0) {
            otherPreviews.style.display = 'flex';
            document.getElementById('other-images-name').textContent = `${otherPreviews.children.length} current image(s)`;
        }
    }
}

// File upload preview handlers (existing code remains the same)
document.getElementById('main_image').addEventListener('change', function(e) {
    const fileName = e.target.files.length > 0 ? e.target.files[0].name : 'No files chosen';
    document.getElementById('main-image-name').textContent = fileName;
});

document.getElementById('other_images').addEventListener('change', function(e) {
    const fileCount = e.target.files.length;
    const message = fileCount > 0 ? `${fileCount} file${fileCount > 1 ? 's' : ''} selected` : 'No files chosen';
    document.getElementById('other-images-name').textContent = message;
});

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}

async function uploadProduct(formData) {
    const user = JSON.parse(sessionStorage.getItem('user'));
    if (!user || !user.id) {
        window.location.href = '/nsikacart/auth/login.html';
        return;
    }

    try {
        // Use different endpoint for edit vs create
        const endpoint = isEditMode ? '/nsikacart/api/products/update.php' : '/nsikacart/api/products/upload.php';
        
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'include', 
        });

        if (response.status === 401) {
            sessionStorage.removeItem('user');
            window.location.href = '/nsikacart/auth/login.html';
            return;
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Operation failed');
        }

        return data;

    } catch (error) {
        console.error('Operation error:', error);
        throw error;
    }
}

async function handleFormSubmit(event) {
    event.preventDefault();
    
    // Validate session before proceeding
    const isValid = await validateSession();
    if (!isValid) {
        return;
    }
    
    const user = getCurrentUser();
    
    const submitButton = event.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = isEditMode ? 'Updating...' : 'Uploading...';
    
    try {
        const formData = new FormData(event.target);
        const result = await uploadProduct(formData);
        
        if (result.success) {
            const successMessage = isEditMode ? 'Product updated successfully' : 'Product uploaded successfully';
            showToast(successMessage, 'success');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 2000);
        } else {
            showToast(result.message || 'Operation failed', 'error');
        }
    } catch (error) {
        showToast(error.message || 'Operation failed', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

document.getElementById('product-form').addEventListener('submit', handleFormSubmit);

// Debug logging (existing code)
document.addEventListener('DOMContentLoaded', () => {
    const user = sessionStorage.getItem('user');
    console.log('Session storage user on load:', user ? JSON.parse(user) : null);
    
    fetch('/nsikacart/api/auth/session-debug.php')
        .then(res => res.json())
        .then(data => {
            console.log('PHP session debug data:', data);
        })
        .catch(err => console.error('Error fetching session debug:', err));
});

document.addEventListener('DOMContentLoaded', () => {
    // Load product data if in edit mode
    if (isEditMode && editProductId) {
        loadProductForEdit(editProductId);
    }
});