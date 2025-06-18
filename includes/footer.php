</main> <!-- Closing the .main-content tag from header.php -->

<footer class="site-footer pt-5 pb-4">
    <div class="container text-center text-md-start">
        <div class="row">
            <!-- About Section -->
            <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold"><?php echo e($SITE_SETTINGS['site_name'] ?? 'AI Affiliate Hub'); ?></h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto footer-hr"/>
                <p>
                    Discover the best deals and trending products, all curated by our advanced AI algorithms just for you. Your smart shopping journey starts here.
                </p>
                <div class="mt-4 social-icons">
                    <a href="#" class="me-3" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="me-3" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="me-3" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Categories Section -->
            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold">Categories</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto footer-hr"/>
                <?php if(!empty($all_parent_categories)): ?>
                    <?php foreach($all_parent_categories as $footer_cat): ?>
                        <!-- CHANGED: Removed /bs/ from path -->
                        <p><a href="/category/<?php echo e($footer_cat['slug']); ?>" class="text-white-50"><?php echo e($footer_cat['name']); ?></a></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No categories found.</p>
                <?php endif; ?>
            </div>
            
            <!-- Useful Links Section -->
            <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4">
                <h6 class="text-uppercase fw-bold">Useful links</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto footer-hr"/>
                <?php if(!empty($nav_pages)): ?>
                    <?php foreach ($nav_pages as $page): ?>
                         <!-- CHANGED: Removed /bs/ from path -->
                         <p><a href="/page/<?php echo e($page['slug']); ?>" class="text-white-50"><?php echo e($page['title']); ?></a></p>
                    <?php endforeach; ?>
                <?php endif; ?>
                 <!-- CHANGED: Removed /bs/ from path -->
                 <p><a href="/wishlist" class="text-white-50">Wishlist</a></p>
            </div>

            <!-- Contact Section -->
            <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
                <h6 class="text-uppercase fw-bold" id="contact">Contact</h6>
                <hr class="mb-4 mt-0 d-inline-block mx-auto footer-hr"/>
                <p><i class="fas fa-home me-3"></i> <?php echo e($SITE_SETTINGS['contact_address'] ?? 'New York, NY 10012, US'); ?></p>
                <p><i class="fas fa-envelope me-3"></i> <?php echo e($SITE_SETTINGS['contact_email'] ?? 'info@example.com'); ?></p>
                <p><i class="fas fa-phone me-3"></i> <?php echo e($SITE_SETTINGS['contact_phone'] ?? '+ 01 234 567 88'); ?></p>
            </div>
        </div>
    </div>
    <div class="text-center p-3 mt-4 footer-bottom">
        © <?php echo date('Y'); ?> Copyright:
        <!-- CHANGED: Removed /bs/ from path -->
        <a class="text-white" href="/"><?php echo e($SITE_SETTINGS['site_name']); ?></a>
    </div>
</footer>

<!-- JavaScript Bundles -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<!-- CHANGED: Removed /bs/ from path -->
<script src="/public/js/main.js"></script>
<script>AOS.init({duration: 800, once: true});</script>

</body>
</html>