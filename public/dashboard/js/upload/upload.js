import { uploadContainer } from '../upload/upload-data';
import { categories } from '../../../scripts/categories-data.js';
import { validateSession, getCurrentUser } from '../session-manager.js';

let uploadContainerHTML = '';

uploadContainer.forEach((container) => {
    uploadContainerHTML += `
        <div class="header">
            <button type="button" class="back-btn"><a href="./index.html">←</a></button>
            <h2>${container.title}</h2>
        </div>
        
        <form id="product-form" enctype="multipart/form-data">
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
                    Add Main Photo
                    <input type="file" id="main_image" name="main_image" accept="image/*" style="display: none;" required>
                </label>
                <div class="file-name" id="main-image-name">No files chosen</div>
                ${container.previewContainers}
                
                <label class="custom-file-upload">
                    Add Other Photos
                    <input type="file" id="other_images" name="images[]" accept="image/*" style="display: none;" multiple>
                </label>
                <div class="file-name" id="other-images-name">No files chosen</div>
            </div>
            <div class="policy-note">
                All products go through a quick standard review when published.
            </div>
            <button type="submit" class="submit-btn">${container.PublishButton}</button>
        </form>
        <div id="toast" class="toast"></div>
    `;
});

document.querySelector('.container').innerHTML = uploadContainerHTML;

// initialize category select
const categorySelect = document.querySelector('#category');
categories.forEach((category) => {
    const option = document.createElement('option');
    option.textContent = category.name;
    categorySelect.appendChild(option);
});

// file upload preview handlers
document.getElementById('main_image').addEventListener('change', function(e) {
    const fileName = e.target.files.length > 0 ? e.target.files[0].name : 'No files chosen';
    document.getElementById('main-image-name').textContent = fileName;
});

document.getElementById('other_images').addEventListener('change', function(e) {
    const fileCount = e.target.files.length;
    const message = fileCount > 0 ? `${fileCount} file${fileCount > 1 ? 's' : ''} selected` : 'No files chosen';
    document.getElementById('other-images-name').textContent = message;
});

// toast notification function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}

async function uploadProduct(formData) {
    // check user session first
    const user = JSON.parse(sessionStorage.getItem('user'));
    if (!user || !user.id) {
        window.location.href = '/nsikacart/auth/login.html';
        return;
    }

    try {
        const response = await fetch('/nsikacart/api/products/upload.php', {
            method: 'POST',
            body: formData,
            credentials: 'include', 
        });

        if (response.status === 401) {
            // set some session expired or invalid
            sessionStorage.removeItem('user');
            window.location.href = '/nsikacart/auth/login.html';
            return;
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Upload failed');
        }

        return data;

    } catch (error) {
        console.error('Upload error:', error);
        throw error;
    }
}

async function handleFormSubmit(event) {
    event.preventDefault();
    
    // Validate session before proceeding
    const isValid = await validateSession();
    if (!isValid) {
        return; // Stop if session isn't valid
    }
    
    const user = getCurrentUser();
    
    const submitButton = event.target.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Uploading...';
    
    try {
        const formData = new FormData(event.target);
        const result = await uploadProduct(formData);
        
        if (result.success) {
            showToast('Product uploaded successfully', 'success');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 2000);
        } else {
            showToast(result.message || 'Upload failed', 'error');
        }
    } catch (error) {
        showToast(error.message || 'Upload failed', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Publish';
    }
}

document.getElementById('product-form').addEventListener('submit', handleFormSubmit);

// Add to your upload.js
document.addEventListener('DOMContentLoaded', () => {
    // Log session state on page load
    const user = sessionStorage.getItem('user');
    console.log('Session storage user on load:', user ? JSON.parse(user) : null);
    
    // Check PHP session too
    fetch('/nsikacart/api/auth/session-debug.php')
        .then(res => res.json())
        .then(data => {
            console.log('PHP session debug data:', data);
        })
        .catch(err => console.error('Error fetching session debug:', err));
});