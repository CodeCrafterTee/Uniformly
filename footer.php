<footer class="mt-5 pt-5 pb-4" style="background-color: #F5EFE0; border-top: 1px solid #E2D8C6;">
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['subscribe_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 12px; background: #d4edda; border-left: 4px solid #28a745;">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['subscribe_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['subscribe_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['subscribe_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 12px; background: #f8d7da; border-left: 4px solid #dc3545;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['subscribe_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['subscribe_error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['subscribe_message'])): ?>
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert" style="border-radius: 12px; background: #d1ecf1; border-left: 4px solid #17a2b8;">
                <i class="bi bi-info-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['subscribe_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['subscribe_message']); ?>
        <?php endif; ?>
        
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <a class="navbar-brand fw-bold fs-3 d-inline-block mb-3" href="index.php" style="color: #000;">
                    <i class="bi bi-backpack"></i> Uniformly
                </a>
                <p class="small text-muted">The trusted marketplace for parents to buy and sell pre-loved school uniforms. Save money, reduce waste, and give uniforms a second life.</p>
                <div class="d-flex gap-3 mt-3">
                    <!-- Added generic social media links that open in a new tab -->
                    <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" class="text-dark fs-5"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" class="text-dark fs-5"><i class="bi bi-facebook"></i></a>
                    <a href="https://web.whatsapp.com/" target="_blank" rel="noopener noreferrer" class="text-dark fs-5"><i class="bi bi-whatsapp"></i></a>
                    <a href="mailto:info@uniformly.com" class="text-dark fs-5"><i class="bi bi-envelope"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3">Shop</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="shop.php" class="footer-link text-dark text-decoration-none">Shop by School</a></li>
                    <li class="mb-2"><a href="shop.php?category=school_bag" class="footer-link text-dark text-decoration-none">School Bags</a></li>
                    <li class="mb-2"><a href="shop.php?category=uniform_set" class="footer-link text-dark text-decoration-none">Uniform Sets</a></li>
                    <li class="mb-2"><a href="shop.php?category=blazer" class="footer-link text-dark text-decoration-none">Blazers & Jackets</a></li>
                    <li class="mb-2"><a href="shop.php?category=sports_kit" class="footer-link text-dark text-decoration-none">Sports Uniforms</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3">Sell & Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="how-to-sell.php" class="footer-link text-dark text-decoration-none">How to Sell</a></li>
                    <li class="mb-2"><a href="selling-guidelines.php" class="footer-link text-dark text-decoration-none">Selling Guidelines</a></li>
                    <li class="mb-2"><a href="help-center.php" class="footer-link text-dark text-decoration-none">Help Center</a></li>
                    <li class="mb-2"><a href="safe-transactions.php" class="footer-link text-dark text-decoration-none">Safe Transactions</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-3">Get Updates</h6>
                <p class="small text-muted">Be the first to know about new uniform listings in your area & selling tips.</p>
                <form method="POST" action="subscribe.php" class="input-group" onsubmit="return validateEmail(this);">
                    <input type="email" name="email" id="newsletter_email" class="form-control bg-white border-dark" placeholder="Your email" style="border-radius: 40px 0 0 40px;" required>
                    <button class="btn btn-black rounded-pill px-4" style="border-radius: 0 40px 40px 0;" type="submit">Subscribe</button>
                </form>
                <p class="small mt-3 text-muted">
                    <i class="bi bi-shield-check"></i> Join <span id="subscriber_count">loading...</span>+ parents saving on school costs.
                    <br>
                    <a href="unsubscribe.php" class="text-muted small">Unsubscribe</a>
                </p>
            </div>
        </div>
        <hr class="mt-5" style="border-color: #d9cfbc;">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="small text-muted mb-0">© 2026 Uniformly — Uniforms Reused, Value Renewed. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="privacy-policy.php" class="footer-link text-dark text-decoration-none small me-3">Privacy Policy</a>
                <a href="terms.php" class="footer-link text-dark text-decoration-none small me-3">Terms of Use</a>
                <a href="safety-tips.php" class="footer-link text-dark text-decoration-none small me-3">Safety Tips</a>
                <a href="cookie-policy.php" class="footer-link text-dark text-decoration-none small">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>

<!-- JavaScript for email validation and subscriber count -->
<script>
// Client-side email validation
function validateEmail(form) {
    const email = form.email.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address (e.g., name@example.com)');
        return false;
    }
    return true;
}

// Fetch and display subscriber count
function updateSubscriberCount() {
    fetch('get-subscriber-count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count) {
                document.getElementById('subscriber_count').innerText = data.count.toLocaleString();
            } else {
                document.getElementById('subscriber_count').innerText = '5,000';
            }
        })
        .catch(error => {
            console.error('Error fetching subscriber count:', error);
            document.getElementById('subscriber_count').innerText = '5,000';
        });
}

// Load subscriber count when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateSubscriberCount();
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>