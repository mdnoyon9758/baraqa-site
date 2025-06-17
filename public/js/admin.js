/**
 * Main JavaScript file for the Admin Panel.
 * Handles functionalities like sidebar toggle and confirmation modals.
 */

document.addEventListener("DOMContentLoaded", function() {

    // --- 1. Sidebar Toggle Functionality ---
    const menuToggle = document.getElementById("menu-toggle");
    const wrapper = document.getElementById("admin-wrapper");
    
    if (menuToggle && wrapper) {
        menuToggle.addEventListener("click", function(event) {
            event.preventDefault();
            wrapper.classList.toggle("toggled");
        });
    }

    // --- 2. Enable Bootstrap Tooltips ---
    // This initializes any tooltips used in the admin panel (e.g., on action buttons).
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // --- 3. Generic Delete Confirmation Modal Handler ---
    // This script works with any delete modal that has the ID 'deleteConfirmationModal'.
    const deleteModal = document.getElementById('deleteConfirmationModal');
    if (deleteModal) {
        // Find the hidden input field inside the modal that will hold the ID of the item to be deleted.
        const deleteIdInput = document.getElementById('deleteId');
        
        // Listen for the modal to be shown
        deleteModal.addEventListener('show.bs.modal', function(event) {
            // Get the button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract the 'data-id' attribute from the button
            const idToDelete = button.getAttribute('data-id');
            
            // Set the value of the hidden input field to the extracted ID
            if (deleteIdInput) {
                deleteIdInput.value = idToDelete;
            }

            // Optional: You can also change modal text dynamically if needed.
            // const modalBody = deleteModal.querySelector('.modal-body');
            // if (modalBody) {
            //     modalBody.textContent = `Are you sure you want to delete item with ID: ${idToDelete}?`;
            // }
        });
    }
    
    // Note: The Edit Modal logic is specific to each page (e.g., categories.php, manage_brands.php)
    // and is therefore kept within those pages' <script> tags for clarity.

});