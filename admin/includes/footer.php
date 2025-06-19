                </div> <!-- .container-fluid -->
            </main> <!-- .page-content -->

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row text-muted">
                        <div class="col-sm-6 text-center text-sm-start">
                            <p class="mb-0">
                                <strong><?php echo e(get_setting('site_name') ?? 'BARAQA'); ?></strong> © <?php echo date('Y'); ?>
                            </p>
                        </div>
                        <div class="col-sm-6 text-center text-sm-end">
                             <p class="mb-0">Version 1.0.0</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div> <!-- .main-content-wrapper -->
    </div> <!-- .admin-layout -->

    <!-- Core JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Admin JS (New Path) -->
    <script src="/admin/assets/js/admin-script.js"></script>

    <!-- Page-specific JS can be added by individual pages -->
    <?php if (isset($page_scripts)): echo $page_scripts; endif; ?>
</body>
</html>