<?php
// Note: We assume the $pdo object from functions.php is available.
// PDO connections are automatically closed when the script ends.

$site_name = get_setting('site_name') ?? 'BARAQA Affiliate Hub';
$current_year = date('Y');
?>
            </div> <!-- End of .container-fluid p-4 -->
            
            <footer class="footer mt-auto py-3 bg-light border-top">
                <div class="container-fluid text-center">
                    <span class="text-muted">Copyright © <?php echo $current_year; ?> <?php echo e($site_name); ?>. All Rights Reserved.</span>
                </div>
            </footer>

        </div> <!-- End of #page-content-wrapper -->
    </div> <!-- End of #admin-wrapper -->

    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome for Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>

    <!-- Custom Admin JS (Path Corrected) -->
    <script src="/public/js/admin.js"></script>

    <!-- START: Generic JavaScript Handler for All Delete Modals -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Find the generic delete confirmation modal in the DOM.
        const deleteModal = document.getElementById('deleteConfirmationModal');
        
        // If the modal exists on the page, set up the event listener.
        if (deleteModal) {
            // Find the hidden input field inside the modal form that will hold the ID.
            const deleteIdInput = deleteModal.querySelector('#deleteId');
            
            if(deleteIdInput) {
                // Listen for the 'show.bs.modal' event, which is triggered by Bootstrap
                // right before the modal is shown to the user.
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    // Get the button that the user clicked to trigger the modal.
                    const button = event.relatedTarget;
                    
                    // Extract the ID of the item to be deleted from the button's 'data-id' attribute.
                    const idToDelete = button.getAttribute('data-id');
                    
                    // Set the value of the hidden '#deleteId' input field to this ID.
                    // This ensures that when the modal's form is submitted, it sends the correct ID.
                    deleteIdInput.value = idToDelete;
                });
            }
        }
    });
    </script>
    <!-- END: Generic JavaScript Handler -->

</body>
</html>