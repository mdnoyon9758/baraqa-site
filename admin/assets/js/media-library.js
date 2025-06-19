document.addEventListener('DOMContentLoaded', function () {

    const gallery = document.getElementById('media-gallery');
    const modalElement = document.getElementById('mediaDetailsModal');
    const modal = new bootstrap.Modal(modalElement);
    const modalContent = document.getElementById('media-details-content');
    const selectModeBtn = document.getElementById('select-mode-btn');
    const deleteSelectedBtn = document.getElementById('delete-selected-btn');
    const selectionCountSpan = document.getElementById('selection-count');
    const searchInput = document.getElementById('media-search-input');
    const uploadForm = document.getElementById('upload-form');
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('media-file-input');
    const browseBtn = document.getElementById('browse-btn');
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    
    let isSelectMode = false;
    let selectedItems = new Set();

    // =================================================================
    // Utility Functions
    // =================================================================
    function showToast(message, type = 'success') {
        // You would need a toast notification library for this to work.
        // Example: toastr.info(message);
        alert(message); // Simple alert as a fallback
    }

    // =================================================================
    // AJAX Handler
    // =================================================================
    async function performAction(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', CSRF_TOKEN);

        for (const key in data) {
            if (Array.isArray(data[key])) {
                data[key].forEach(value => formData.append(key + '[]', value));
            } else {
                formData.append(key, data[key]);
            }
        }

        try {
            const response = await fetch('/admin/media_action.php', {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('AJAX Error:', error);
            showToast('An error occurred during the request.', 'danger');
            return { status: 'error', message: 'Network or server error.' };
        }
    }

    // =================================================================
    // Media Card Click Logic (Modal vs. Selection)
    // =================================================================
    gallery.addEventListener('click', function(e) {
        const card = e.target.closest('.media-card');
        if (!card) return;

        const id = card.dataset.id;
        if (isSelectMode) {
            toggleSelection(card, id);
        } else {
            openDetailsModal(id);
        }
    });

    // =================================================================
    // Media Details Modal
    // =================================================================
    async function openDetailsModal(id) {
        modal.show();
        modalContent.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>'; // Loading spinner

        const result = await performAction('get_details', { id });
        if (result.status === 'success') {
            modalContent.innerHTML = result.html;
            attachModalEventListeners();
        } else {
            modalContent.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    }

    function attachModalEventListeners() {
        document.getElementById('save-media-btn').addEventListener('click', saveMediaDetails);
        document.getElementById('delete-media-btn').addEventListener('click', deleteFromModal);
    }
    
    async function saveMediaDetails(e) {
        const id = e.target.dataset.id;
        const form = document.getElementById('update-media-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        const result = await performAction('update_details', data);
        if (result.status === 'success') {
            showToast(result.message);
            modal.hide();
            // Optionally refresh the specific item's alt tag in the gallery view
            const img = gallery.querySelector(`.media-card[data-id="${id}"] img`);
            if (img) img.alt = data.alt_text;
        } else {
            showToast(result.message, 'danger');
        }
    }

    async function deleteFromModal(e) {
        const id = e.target.dataset.id;
        if (confirm('Are you sure you want to permanently delete this file? This cannot be undone.')) {
            const result = await performAction('delete_media', { ids: [id] });
            if (result.status === 'success') {
                showToast(result.message);
                modal.hide();
                gallery.querySelector(`.media-item-container[data-id="${id}"]`).remove();
            } else {
                showToast(result.message, 'danger');
            }
        }
    }

    // =================================================================
    // Selection Mode & Bulk Delete
    // =================================================================
    selectModeBtn.addEventListener('click', toggleSelectMode);

    function toggleSelectMode() {
        isSelectMode = !isSelectMode;
        document.body.classList.toggle('selection-mode', isSelectMode);
        selectModeBtn.classList.toggle('active', isSelectMode);
        
        if (!isSelectMode) {
            // Clear all selections when exiting select mode
            selectedItems.clear();
            document.querySelectorAll('.media-card.selected').forEach(card => card.classList.remove('selected'));
            updateSelectionUI();
        }
    }

    function toggleSelection(card, id) {
        if (selectedItems.has(id)) {
            selectedItems.delete(id);
            card.classList.remove('selected');
        } else {
            selectedItems.add(id);
            card.classList.add('selected');
        }
        updateSelectionUI();
    }

    function updateSelectionUI() {
        const count = selectedItems.size;
        selectionCountSpan.textContent = count;
        deleteSelectedBtn.classList.toggle('d-none', count === 0);
    }

    deleteSelectedBtn.addEventListener('click', async function() {
        const count = selectedItems.size;
        if (count === 0) return;

        if (confirm(`Are you sure you want to delete the ${count} selected files? This cannot be undone.`)) {
            const ids = Array.from(selectedItems);
            const result = await performAction('delete_media', { ids });

            if (result.status === 'success') {
                showToast(result.message);
                ids.forEach(id => {
                    gallery.querySelector(`.media-item-container[data-id="${id}"]`)?.remove();
                });
                selectedItems.clear();
                updateSelectionUI();
            } else {
                showToast(result.message, 'danger');
            }
        }
    });

    // =================================================================
    // Search Functionality
    // =================================================================
    let searchTimeout;
    searchInput.addEventListener('keyup', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(async () => {
            const query = e.target.value.trim();
            document.getElementById('search-loading').classList.remove('d-none');
            document.getElementById('pagination-nav')?.classList.add('d-none'); // Hide pagination during search
            
            const result = await performAction('search_media', { query });
            
            document.getElementById('search-loading').classList.add('d-none');
            if (result.status === 'success') {
                gallery.innerHTML = result.html;
            }
            if (query === "") {
                document.getElementById('pagination-nav')?.classList.remove('d-none'); // Show pagination if search is cleared
            }
        }, 300); // Debounce search
    });

    // =================================================================
    // AJAX Uploader
    // =================================================================
    browseBtn.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    dropZone.addEventListener('dragenter', () => dropZone.classList.add('dragover'));
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
        dropZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    function handleFiles(files) {
        if (files.length === 0) return;
        
        const formData = new FormData();
        formData.append('csrf_token', CSRF_TOKEN); // Use the global CSRF token
        for (const file of files) {
            formData.append('media_files[]', file);
        }

        progressContainer.classList.remove('d-none');
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/ajax_upload.php', true);
        
        xhr.upload.addEventListener('progress', function(e) {
            const percent = e.lengthComputable ? (e.loaded / e.total * 100) : 0;
            progressBar.style.width = percent.toFixed(2) + '%';
            progressBar.textContent = percent.toFixed(2) + '%';
        });

        xhr.onload = function() {
            progressContainer.classList.add('d-none');
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success' && response.files) {
                        showToast(response.message);
                        // Prepend new files to gallery
                        response.files.forEach(file => {
                            const newMediaItem = `
                             <div class="col-lg-2 col-md-3 col-sm-4 col-6 media-item-container" data-id="${file.id}">
                                 <div class="card h-100 shadow-sm media-card" data-id="${file.id}">
                                     <div class="media-card-img-wrapper">
                                         <img src="/${file.file_path}" class="card-img-top" loading="lazy" alt="${file.alt_text || file.file_name}">
                                         <div class="selection-overlay"><i class="bi bi-check-circle-fill"></i></div>
                                     </div>
                                 </div>
                             </div>`;
                             gallery.insertAdjacentHTML('afterbegin', newMediaItem);
                        });
                        // Remove "no media" message if it exists
                        document.getElementById('no-media-message')?.remove();

                    } else {
                        showToast(response.message || 'An error occurred during upload.', 'danger');
                    }
                } catch (e) {
                    console.error("Error parsing response:", e);
                    showToast('Invalid response from server.', 'danger');
                }
            } else {
                showToast(`Upload failed. Server responded with ${xhr.status}`, 'danger');
            }
        };

        xhr.send(formData);
    }
});