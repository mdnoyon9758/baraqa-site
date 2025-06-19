document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('media-file-input');
    const browseBtn = document.getElementById('browse-btn');
    const uploadForm = document.getElementById('upload-form');
    const progressBar = document.querySelector('.progress-bar');
    const progressContainer = document.querySelector('.progress');
    const mediaGallery = document.getElementById('media-gallery');
    const detailsModal = new bootstrap.Modal(document.getElementById('mediaDetailsModal'));
    const detailsContent = document.getElementById('media-details-content');

    if (dropZone) {
        browseBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => handleFiles(fileInput.files));
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        dropZone.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files), false);
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function handleFiles(files) {
        if (files.length === 0) return;
        const formData = new FormData(uploadForm);
        for (const file of files) {
            formData.append('media_files[]', file);
        }

        progressContainer.classList.remove('d-none');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', uploadForm.action, true);
        
        xhr.upload.addEventListener('progress', function(e) {
            const percent = e.lengthComputable ? (e.loaded / e.total) * 100 : 0;
            progressBar.style.width = percent.toFixed(2) + '%';
            progressBar.textContent = percent.toFixed(2) + '%';
        });

        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    response.files.forEach(file => {
                        const noMediaMsg = document.getElementById('no-media-message');
                        if(noMediaMsg) noMediaMsg.remove();
                        
                        const galleryItem = `
                            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                                <div class="card h-100 shadow-sm media-card" data-id="${file.id}">
                                    <div class="media-card-img-wrapper">
                                        <img src="${file.path}" class="card-img-top" loading="lazy" alt="${file.name}">
                                    </div>
                                </div>
                            </div>`;
                        mediaGallery.insertAdjacentHTML('afterbegin', galleryItem);
                    });
                    setTimeout(() => progressContainer.classList.add('d-none'), 1000);
                } else {
                    alert('Error: ' + response.message);
                }
            } else {
                alert('Upload failed. Server responded with status ' + xhr.status);
            }
        };

        xhr.send(formData);
    }
    
    // Event delegation for dynamically added elements
    mediaGallery.addEventListener('click', function(e) {
        const card = e.target.closest('.media-card');
        if (card) {
            const mediaId = card.dataset.id;
            
            detailsContent.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            detailsModal.show();
            
            const formData = new FormData();
            formData.append('id', mediaId);
            formData.append('action', 'get_details');
            formData.append('csrf_token', uploadForm.querySelector('[name=csrf_token]').value);
            
            fetch('/admin/media_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    const data = res.data;
                    detailsContent.innerHTML = `
                        <div class="row">
                            <div class="col-md-5 text-center">
                                <img src="${data.file_path}" class="img-fluid rounded mb-3">
                            </div>
                            <div class="col-md-7">
                                <form id="media-details-form">
                                    <input type="hidden" name="id" value="${data.id}">
                                    <input type="hidden" name="action" value="update_details">
                                    <input type="hidden" name="csrf_token" value="${uploadForm.querySelector('[name=csrf_token]').value}">
                                    <div class="mb-3">
                                        <label class="form-label">Alt Text</label>
                                        <input type="text" class="form-control" name="alt_text" value="${data.alt_text || ''}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">File URL</label>
                                        <input type="text" class="form-control" value="${window.location.origin}${data.file_path}" readonly>
                                    </div>
                                </form>
                                <hr>
                                <strong>File Name:</strong> ${data.file_name}<br>
                                <strong>File Type:</strong> ${data.file_type}<br>
                                <strong>File Size:</strong> ${data.file_size}<br>
                                <strong>Uploaded:</strong> ${data.uploaded_at}<br>
                                <hr>
                                <button type="button" class="btn btn-primary btn-sm" id="save-media-details">Save Changes</button>
                                <button type="button" class="btn btn-danger btn-sm" id="delete-media-from-modal">Delete Permanently</button>
                            </div>
                        </div>
                    `;
                } else {
                    detailsContent.innerHTML = `<p class="text-danger">${res.message}</p>`;
                }
            });
        }
    });

    // Save and Delete from within the modal
    detailsContent.addEventListener('click', function(e) {
        const form = document.getElementById('media-details-form');
        if (!form) return;
        
        const mediaId = form.querySelector('[name=id]').value;
        const csrf = form.querySelector('[name=csrf_token]').value;
        
        if (e.target.id === 'save-media-details') {
            const altText = form.querySelector('[name=alt_text]').value;
            const formData = new FormData();
            formData.append('id', mediaId);
            formData.append('action', 'update_details');
            formData.append('csrf_token', csrf);
            formData.append('alt_text', altText);
            
            fetch('/admin/media_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') detailsModal.hide();
                else alert('Error: ' + res.message);
            });
        }
        
        if (e.target.id === 'delete-media-from-modal') {
            if (confirm('Are you sure you want to permanently delete this file?')) {
                const formData = new FormData();
                formData.append('id', mediaId);
                formData.append('action', 'delete');
                formData.append('csrf_token', csrf);
                
                fetch('/admin/media_action.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        detailsModal.hide();
                        document.querySelector(`.media-card[data-id='${mediaId}']`).closest('.col-lg-2').remove();
                    } else {
                        alert('Error: ' . res.message);
                    }
                });
            }
        }
    });
});
