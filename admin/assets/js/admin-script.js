document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar Toggle Functionality ---
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const adminLayout = document.querySelector('.admin-layout');

    // IMPORTANT: Check if the elements actually exist on the page before adding listeners
    if (sidebarToggle && adminLayout) {
        // Function to handle the toggle action
        function handleToggle() {
            adminLayout.classList.toggle('sidebar-toggled');
            // Save the state to localStorage
            const isToggled = adminLayout.classList.contains('sidebar-toggled');
            localStorage.setItem('sidebar_toggled', isToggled);
        }

        // Add the click event listener
        sidebarToggle.addEventListener('click', handleToggle);

        // Check the saved state on page load
        if (localStorage.getItem('sidebar_toggled') === 'true') {
            adminLayout.classList.add('sidebar-toggled');
        }
    }

    // --- You can add other global admin scripts below ---
    // For example, the generic delete modal handler from your old footer

    const deleteModal = document.getElementById('deleteConfirmationModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                const id = button.getAttribute('data-id');
                const deleteIdInput = deleteModal.querySelector('#deleteId');
                if (deleteIdInput) {
                    deleteIdInput.value = id;
                }
            }
        });
    }
});