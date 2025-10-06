import { uploadContainer } from '../upload/upload-data.js';
import { categories } from '../../../scripts/categories-data.js';
import { validateSession, getCurrentUser } from '../session-manager.js';

let uploadContainerHTML = '';
let isEditMode = false;
let editProductId = null;
let currentProduct = null;
let selectedImages = []; // Array to store selected image files
let maxImages = 10;

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
        
        <form id="product-form" enctype="multipart/form-data" novalidate>
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
                <label>Product Images</label>
                <div class="image-upload-container">
                    <label class="image-upload-button" id="image_upload_button">
                        <div class="upload-icon">📷</div>
                        <span>Choose Images</span>
                        <input type="file" id="product_images" name="images[]" accept="image/*" style="display: none;" multiple>
                    </label>
                    ${container.previewContainers}
                </div>
                <div class="image-validation-error" id="image_validation_error" style="display: none;">
                    <span class="error-text">Please select at least one image for your product</span>
                </div>
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
        const response = await fetch(`./api/products/get-product.php?id=${productId}`);
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
    
    // Handle existing images
    if (product.images && product.images.length > 0) {
        const imageGrid = document.getElementById('image_grid');
        imageGrid.innerHTML = '';
        
        product.images.forEach((imageName, index) => {
            const imageContainer = createImagePreview(`uploads/${imageName}`, imageName, true, index);
            imageGrid.appendChild(imageContainer);
        });
        
        updateUploadButton();
    }
}

// Create image preview container
function createImagePreview(src, fileName, isExisting = false, index = null) {
    const imageContainer = document.createElement('div');
    imageContainer.className = 'image-preview-container';
    imageContainer.dataset.isExisting = isExisting;
    if (isExisting) imageContainer.dataset.fileName = fileName;
    
    imageContainer.innerHTML = `
        <div class="image-preview">
            <img src="${src}" alt="${fileName}" />
            <button type="button" class="remove-image-btn" onclick="removeImage(this)">×</button>
            ${index === 0 ? '<div class="main-image-badge">Main</div>' : ''}
        </div>
        <div class="image-name">${fileName}</div>
    `;
    
    return imageContainer;
}

// Remove image function
window.removeImage = function(button) {
    const container = button.closest('.image-preview-container');
    const isExisting = container.dataset.isExisting === 'true';
    const index = Array.from(container.parentNode.children).indexOf(container);
    
    if (isExisting) {
        // Mark existing image for deletion
        container.style.display = 'none';
        container.dataset.markedForDeletion = 'true';
    } else {
        // Remove from selectedImages array
        selectedImages.splice(index - getExistingImagesCount(), 1);
        container.remove();
    }
    
    updateImageIndices();
    updateUploadButton();
    validateImages();
};

// Get count of existing images not marked for deletion
function getExistingImagesCount() {
    const imageGrid = document.getElementById('image_grid');
    return Array.from(imageGrid.children).filter(child => 
        child.dataset.isExisting === 'true' && 
        child.dataset.markedForDeletion !== 'true'
    ).length;
}

// Update main image badges
function updateImageIndices() {
    const imageGrid = document.getElementById('image_grid');
    const visibleImages = Array.from(imageGrid.children).filter(child => 
        child.style.display !== 'none'
    );
    
    visibleImages.forEach((container, index) => {
        const badge = container.querySelector('.main-image-badge');
        if (badge) badge.remove();
        
        if (index === 0) {
            const preview = container.querySelector('.image-preview');
            const mainBadge = document.createElement('div');
            mainBadge.className = 'main-image-badge';
            mainBadge.textContent = 'Main';
            preview.appendChild(mainBadge);
        }
    });
}

// Update upload button state
function updateUploadButton() {
    const uploadButton = document.getElementById('image_upload_button');
    const totalImages = getExistingImagesCount() + selectedImages.length;
    
    if (totalImages >= maxImages) {
        uploadButton.classList.add('disabled');
        uploadButton.querySelector('span').textContent = `Maximum ${maxImages} images`;
    } else {
        uploadButton.classList.remove('disabled');
        uploadButton.querySelector('span').textContent = `Choose Images (${totalImages}/${maxImages})`;
    }
}

// Validate images
function validateImages() {
    const totalImages = getExistingImagesCount() + selectedImages.length;
    const errorDiv = document.getElementById('image_validation_error');
    
    if (!isEditMode && totalImages === 0) {
        errorDiv.style.display = 'block';
        return false;
    } else {
        errorDiv.style.display = 'none';
        return true;
    }
}

// Handle file input change
document.addEventListener('change', function(e) {
    if (e.target.id === 'product_images') {
        const files = Array.from(e.target.files);
        const currentTotal = getExistingImagesCount() + selectedImages.length;
        const availableSlots = maxImages - currentTotal;
        
        if (files.length > availableSlots) {
            showToast(`You can only add ${availableSlots} more images`, 'error');
            files.splice(availableSlots);
        }
        
        const imageGrid = document.getElementById('image_grid');
        
        files.forEach(file => {
            if (!file.type.startsWith('image/')) {
                showToast(`${file.name} is not an image file`, 'error');
                return;
            }
            
            selectedImages.push(file);
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageContainer = createImagePreview(e.target.result, file.name, false);
                imageGrid.appendChild(imageContainer);
                updateImageIndices();
                updateUploadButton();
                validateImages();
            };
            reader.readAsDataURL(file);
        });
        
        // Clear the input so the same files can be selected again if needed
        e.target.value = '';
    }
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
        window.location.href = './auth/login.html';
        return;
    }

    try {
        // Use different endpoint for edit vs create
        const endpoint = isEditMode ? './api/products/update.php' : './api/products/upload.php';
        
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'include', 
        });

        if (response.status === 401) {
            sessionStorage.removeItem('user');
            window.location.href = './auth/login.html';
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

// Custom form validation
function validateForm() {
    let isValid = true;
    const form = document.getElementById('product-form');
    
    // Clear previous validation styles
    form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    
    // Check required fields
    const requiredFields = ['category', 'name', 'price', 'location', 'description'];
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        }
    });
    
    // Validate images
    if (!validateImages()) {
        isValid = false;
    }
    
    return isValid;
}

async function handleFormSubmit(event) {
    event.preventDefault();
    
    // Custom validation
    if (!validateForm()) {
        showToast('Please fill in all required fields and add at least one image', 'error');
        return;
    }
    
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
        
        // Remove the default file input data
        formData.delete('images[]');
        
        // Add selected images to form data
        selectedImages.forEach((file, index) => {
            if (index === 0) {
                formData.append('main_image', file);
            }
            formData.append('images[]', file);
        });
        
        // In edit mode, handle existing images
        if (isEditMode) {
            const imagesToDelete = [];
            const imageGrid = document.getElementById('image_grid');
            Array.from(imageGrid.children).forEach(container => {
                if (container.dataset.isExisting === 'true' && 
                    container.dataset.markedForDeletion === 'true') {
                    imagesToDelete.push(container.dataset.fileName);
                }
            });
            
            if (imagesToDelete.length > 0) {
                formData.append('delete_images', JSON.stringify(imagesToDelete));
            }
        }
        
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
    
    fetch('./api/auth/session-debugg.php')
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
    
    // Initialize upload button state
    updateUploadButton();
});