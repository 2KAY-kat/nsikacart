// main image preview handler
document.getElementById('main_image')?.addEventListener('change', function(e) {
    const file = this.files[0];
    const preview = document.getElementById('main_preview');
    const nameDisplay = document.getElementById('main-image-name');

    if (file) {
        // validate file type
        if (!file.type.startsWith('image/')) {
            showToast('Please select an image file', 'error');
            return;
        }

        // create preview ui
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        // update filename display
        nameDisplay.textContent = file.name;
    } else {
        preview.style.display = 'none';
        nameDisplay.textContent = 'No file chosen';
    }
});

// other images preview handler
document.getElementById('other_images')?.addEventListener('change', function(e) {
    const files = Array.from(this.files);
    const previewContainer = document.getElementById('other_previews');
    const nameDisplay = document.getElementById('other-images-name');

    // clear existing previews
    previewContainer.innerHTML = '';

    if (files.length) {
        // update file count display
        nameDisplay.textContent = `${files.length} file${files.length > 1 ? 's' : ''} selected`;

        // create previews
        files.forEach(file => {
            if (!file.type.startsWith('image/')) {
                showToast(`${file.name} is not an image file`, 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('preview-image');
                img.setAttribute('title', file.name);
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        });

        previewContainer.style.display = 'flex';
    } else {
        nameDisplay.textContent = 'No files chosen';
        previewContainer.style.display = 'none';
    }
});

// helper function for showing toast messages
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}