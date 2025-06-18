/**
 * Main JavaScript file for the frontend.
 * Handles functionalities: Mobile Menu, Live Search, Product Gallery, Wishlist, and Click Tracking.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize all site functionalities
    setupMobileMenu();
    setupLiveSearch();
    setupProductGallery();
    setupWishlistButtons();
    setupClickTracker();

    // Initialize AOS (Animate On Scroll) library
    if (typeof AOS !== 'undefined') {
        AOS.init({
          duration: 800,
          once: true,
        });
    }
});


/**
 * Sets up the mobile navigation (hamburger menu) with an overlay.
 */
function setupMobileMenu() {
    const toggler = document.querySelector('.navbar-toggler');
    const collapseMenu = document.querySelector('.navbar-collapse');
    const overlay = document.querySelector('.menu-overlay');

    if (toggler && collapseMenu && overlay) {
        toggler.addEventListener('click', function() {
            document.body.style.overflow = 'hidden';
            collapseMenu.classList.add('show');
            overlay.classList.add('show');
        });
        
        const closeMenu = () => {
            document.body.style.overflow = '';
            collapseMenu.classList.remove('show');
            overlay.classList.remove('show');
        };

        overlay.addEventListener('click', closeMenu);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && collapseMenu.classList.contains('show')) closeMenu();
        });
    }
}


/**
 * Sets up the live search functionality in the header.
 */
function setupLiveSearch() {
    const searchBox = document.getElementById('live-search-box');
    const searchResults = document.getElementById('live-search-results');
    let searchTimeout;

    if (searchBox && searchResults) {
        searchBox.addEventListener('keyup', () => {
            clearTimeout(searchTimeout);
            let query = searchBox.value.trim();

            if (query.length > 1) {
                searchTimeout = setTimeout(() => {
                    // CHANGED: Removed /bs/ from fetch URL
                    fetch(`/api/live_search.php?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            searchResults.innerHTML = '';
                            if (data.status === 'success' && data.products.length > 0) {
                                // CHANGED: Removed /bs/ from product URL
                                const productLinks = data.products.map(p => 
                                    `<a href="/product/${p.slug}" class="list-group-item list-group-item-action d-flex align-items-center">
                                        <img src="${p.image_url}" alt="" width="40" height="40" class="me-3 rounded">
                                        <span class="small">${p.title}</span>
                                    </a>`
                                ).join('');
                                // CHANGED: Removed /bs/ from search URL
                                const viewAllLink = `<a href="/search?q=${encodeURIComponent(query)}" class="list-group-item list-group-item-action text-center fw-bold bg-light">View All Results</a>`;
                                searchResults.innerHTML = productLinks + viewAllLink;
                                searchResults.style.display = 'block';
                            } else {
                                searchResults.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Live search fetch error:', error);
                            searchResults.style.display = 'none';
                        });
                }, 300);
            } else {
                searchResults.style.display = 'none';
            }
        });

        document.addEventListener('click', (event) => {
            const searchContainer = document.querySelector('.search-container');
            if (searchContainer && !searchContainer.contains(event.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
}


/**
 * Sets up the image gallery on the product detail page.
 */
function setupProductGallery() {
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-strip img');
    
    if (mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                mainImage.style.opacity = '0';
                setTimeout(() => {
                    mainImage.src = this.src;
                    mainImage.style.opacity = '1';
                }, 200);
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
}


/**
 * Sets up the Add/Remove from Wishlist buttons.
 */
function setupWishlistButtons() {
    document.body.addEventListener('click', (event) => {
        const button = event.target.closest('.wishlist-btn');
        if (button) {
            event.preventDefault();
            const productId = button.dataset.productId;
            if (!productId) return;
            // CHANGED: Removed /bs/ from fetch URL
            fetch('/api/wishlist_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const wishlistCount = document.getElementById('wishlist-count');
                    if (wishlistCount) wishlistCount.textContent = data.count;
                    const icon = button.querySelector('i');
                    if(icon) {
                        button.classList.toggle('active');
                        icon.classList.toggle('far');
                        icon.classList.toggle('fas');
                    }
                } else {
                    console.error('Wishlist error:', data.message);
                }
            })
            .catch(error => console.error('Wishlist fetch error:', error));
        }
    });
}


/**
 * Sets up the click tracker for affiliate links.
 */
function setupClickTracker() {
    document.body.addEventListener('click', (event) => {
        const trackableLink = event.target.closest('.track-click');
        if (trackableLink) {
            const productId = trackableLink.dataset.productId;
            if (productId) {
                const formData = new FormData();
                formData.append('product_id', productId);
                
                // CHANGED: Removed /bs/ from URL
                const trackUrl = '/api/click_tracker.php';
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(trackUrl, formData);
                } else {
                    fetch(trackUrl, { method: 'POST', body: formData, keepalive: true })
                    .catch(error => console.error('Click tracking fetch error:', error));
                }
            }
        }
    });
}