<?php
// We can use the same function from header.php to get the footer menu items
// This assumes the function is available (e.g., loaded in a common functions file)
if (function_exists('get_menu_by_location')) {
    $footer_menu_items = get_menu_by_location('footer');
} else {
    $footer_menu_items = []; // Fallback to an empty array if the function doesn't exist
}
?>
</main> <!-- Closing the .main-content tag from header.php -->

<!-- Back to Top Button -->
<a href="#" id="back-to-top" class="back-to-top-btn shadow-lg" title="Back to Top"><i class="bi bi-arrow-up"></i></a>

<!-- Main Footer -->
<footer class="site-footer bg-dark text-white pt-5 pb-4">
    <div class="container text-center text-md-start">
        <div class="row gy-4">

            <!-- Column 1: About the Site -->
            <div class="col-lg-4 col-md-12 mb-4 mb-lg-0">
                <h5 class="text-uppercase fw-bold"><?php echo e(get_setting('site_name', 'AI Affiliate')); ?></h5>
                <p class="text-white-50">
                    <?php echo e(get_setting('site_description', 'Your one-stop destination for the best affiliate products, curated with advanced AI technology to bring you top deals and trending items.')); ?>
                </p>
                <div class="mt-3 social-icons-footer">
                    <a href="<?php echo e(get_setting('social_facebook', '#')); ?>" class="social-icon" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="<?php echo e(get_setting('social_twitter', '#')); ?>" class="social-icon" title="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="<?php echo e(get_setting('social_instagram', '#')); ?>" class="social-icon" title="Instagram"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <!-- Column 2: Footer Menu (Dynamically Generated) -->
            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase footer-heading">Useful Links</h6>
                <ul class="list-unstyled footer-links">
                    <?php if (!empty($footer_menu_items)): ?>
                        <?php foreach ($footer_menu_items as $item): ?>
                            <li><a href="<?php echo e($item['url']); ?>"><?php echo e($item['title']); ?></a></li>
                        <?php endforeach; ?>
                    <?php else: // Fallback if no menu is assigned ?>
                        <li><a href="/page/about-us">About Us</a></li>
                        <li><a href="/page/contact">Contact</a></li>
                        <li><a href="/page/faq">FAQ</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Column 3: Main Categories -->
            <div class="col-lg-3 col-md-6">
                <h6 class="text-uppercase footer-heading">Top Categories</h6>
                <ul class="list-unstyled footer-links">
                     <?php 
                        $footer_categories = $pdo->query("SELECT name, slug FROM categories WHERE status='published' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                        foreach($footer_categories as $footer_cat):
                     ?>
                        <li><a href="/category/<?php echo e($footer_cat['slug']); ?>"><?php echo e($footer_cat['name']); ?></a></li>
                     <?php endforeach; ?>
                </ul>
            </div>

            <!-- Column 4: Contact Info -->
            <div class="col-lg-3 col-md-6">
                 <h6 class="text-uppercase footer-heading">Contact</h6>
                 <p class="footer-contact-info"><i class="bi bi-envelope-fill me-2"></i> <?php echo e(get_setting('contact_email', 'info@example.com')); ?></p>
                 <p class="footer-contact-info"><i class="bi bi-telephone-fill me-2"></i> <?php echo e(get_setting('contact_phone', '+01 234 567 88')); ?></p>
                 <p class="footer-contact-info"><i class="bi bi-geo-alt-fill me-2"></i> <?php echo e(get_setting('contact_address', '123 Tech Street, Silicon Valley')); ?></p>
            </div>
        </div>
    </div>
</footer>

<!-- Sub-Footer -->
<div class="sub-footer py-3 bg-black text-center">
    <small class="text-white-50">© <?php echo date('Y'); ?> <?php echo e(get_setting('site_name')); ?>. All Rights Reserved.</small>
</div>

<!-- =======================
 JAVASCRIPTS & STYLES
======================== -->

<!-- Page-specific styles passed from templates (e.g., home.php) -->
<?php if (isset($page_styles) && !empty($page_styles)): ?>
<style>
    <?php echo $page_styles; ?>
</style>
<?php endif; ?>

<!-- Core JavaScript Bundles -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Extra JS libraries passed from templates (e.g., Slick Carousel) -->
<?php if (isset($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js_url): ?>
        <script src="<?php echo e($js_url); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Main Custom JS -->
<script src="/public/js/main.js"></script>

<!-- Inline script for page-specific initializations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Back to top button logic
    const backToTopBtn = document.getElementById('back-to-top');
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });
        backToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Page-specific inline script from template (e.g., for initializing carousels)
    <?php if (isset($page_inline_script) && !empty($page_inline_script)): ?>
        <?php echo $page_inline_script; ?>
    <?php endif; ?>
});
</script>

</body>
</html>