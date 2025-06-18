</main> <!-- Closing the .main-content tag from header.php -->

<!-- =======================
Back to Top Section
======================== -->
<a href="#" id="back-to-top" class="back-to-top-btn">Back to top</a>

<!-- =======================
Main Footer Section
======================== -->
<footer class="site-footer-amazon pt-5 pb-4">
    <div class="container text-center text-md-start">
        <div class="row gy-4">

            <!-- Column 1: Get to Know Us -->
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">Get to Know Us</h6>
                <ul class="list-unstyled footer-links">
                    <?php if (!empty($nav_pages)): ?>
                        <?php foreach ($nav_pages as $page): ?>
                            <li><a href="/page/<?php echo e($page['slug']); ?>"><?php echo e($page['title']); ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li><a href="/admin/login.php">Admin Login</a></li>
                </ul>
            </div>

            <!-- Column 2: Shop with Us -->
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">Shop with Us</h6>
                <ul class="list-unstyled footer-links">
                     <?php if(!empty($all_parent_categories)): ?>
                        <?php foreach(array_slice($all_parent_categories, 0, 5) as $footer_cat): // Show first 5 parent categories ?>
                            <li><a href="/category/<?php echo e($footer_cat['slug']); ?>"><?php echo e($footer_cat['name']); ?></a></li>
                        <?php endforeach; ?>
                     <?php endif; ?>
                </ul>
            </div>

            <!-- Column 3: Let Us Help You -->
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">Let Us Help You</h6>
                 <ul class="list-unstyled footer-links">
                    <li><a href="/wishlist">Your Wishlist</a></li>
                    <li><a href="#contact">Contact Us</a></li>
                    <li><a href="/page/faq">FAQ</a></li>
                </ul>
            </div>

            <!-- Column 4: Contact Info & Social -->
            <div class="col-lg-3 col-md-6">
                 <h6 class="footer-heading">Connect with Us</h6>
                 <p class="footer-contact-info"><i class="fas fa-envelope me-2"></i> <?php echo e($SITE_SETTINGS['contact_email'] ?? 'info@example.com'); ?></p>
                 <p class="footer-contact-info"><i class="fas fa-phone me-2"></i> <?php echo e($SITE_SETTINGS['contact_phone'] ?? '+ 01 234 567 88'); ?></p>
                 <div class="mt-3 social-icons-footer">
                    <a href="#" class="social-icon" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon" title="Instagram"><i class="fab fa-instagram"></i></a>
                 </div>
            </div>
        </div>

        <hr class="footer-divider my-4">

        <div class="text-center">
            <a class="navbar-brand" href="/"><?php echo e($SITE_SETTINGS['site_name']); ?></a>
        </div>
    </div>
</footer>

<!-- =======================
Sub-Footer Section
======================== -->
<div class="sub-footer py-3">
    <div class="container text-center">
        <small class="text-white-50">© <?php echo date('Y'); ?> <?php echo e($SITE_SETTINGS['site_name']); ?>. All Rights Reserved. An Amazon-inspired affiliate marketing project.</small>
    </div>
</div>


<!-- JavaScript Bundles -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Slick Carousel JS (if needed by the page) -->
<?php if (isset($extra_js) && is_array($extra_js)): ?>
    <?php foreach ($extra_js as $js_url): ?>
        <script src="<?php echo e($js_url); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Main Custom JS -->
<script src="/public/js/main.js"></script>

<!-- Inline script for initializations -->
<script>
    AOS.init({duration: 800, once: true});

    // Initialize Slick Carousel if the element exists
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $.fn.slick && document.querySelector('.brand-carousel')) {
            $('.brand-carousel').slick({
                slidesToShow: 6,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 2500,
                arrows: false,
                dots: false,
                pauseOnHover: true,
                responsive: [
                    { breakpoint: 992, settings: { slidesToShow: 4 } },
                    { breakpoint: 768, settings: { slidesToShow: 3 } },
                    { breakpoint: 576, settings: { slidesToShow: 2 } }
                ]
            });
        }

        // Back to top button logic
        const backToTopBtn = document.getElementById('back-to-top');
        if(backToTopBtn) {
            backToTopBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    });
</script>

</body>
</html>