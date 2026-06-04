<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and has checkout data
if (!isset($_SESSION['user_id']) || !isset($_SESSION['checkout_data'])) {
    header('Location: cart.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$checkout = $_SESSION['checkout_data'];

// Process final order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $delivery_photo = '';
    $delivery_comment = trim($_POST['delivery_comment'] ?? '');
    $rating = intval($_POST['rating'] ?? 5);
    
    $errors = [];
    
    // Handle delivery photo upload
    if (isset($_FILES['delivery_photo']) && $_FILES['delivery_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/delivery_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($_FILES['delivery_photo']['type'], $allowed)) {
            $errors[] = "Delivery photo must be JPG or PNG.";
        } elseif ($_FILES['delivery_photo']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Photo size must be less than 5MB.";
        } else {
            $filename = 'delivery_' . $user_id . '_' . time() . '.' . pathinfo($_FILES['delivery_photo']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['delivery_photo']['tmp_name'], $upload_dir . $filename)) {
                $delivery_photo = $upload_dir . $filename;
            } else {
                $errors[] = "Failed to upload delivery photo.";
            }
        }
    } else {
        $errors[] = "Please upload a photo of the delivered items.";
    }
    
    if (empty($errors)) {
        // Create order record (simplified - you can expand this)
        // For now, we'll store order in session and redirect to success
        $_SESSION['order_completed'] = [
            'order_id' => 'ORD_' . $user_id . '_' . time(),
            'total' => $checkout['total_amount'],
            'date' => date('Y-m-d H:i:s'),
            'delivery_photo' => $delivery_photo,
            'delivery_comment' => $delivery_comment,
            'rating' => $rating
        ];
        
        // Clear cart
        $clear_sql = "DELETE FROM cart WHERE user_id = ?";
        $clear_stmt = $conn->prepare($clear_sql);
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        // Clear checkout session
        unset($_SESSION['checkout_data']);
        
        header('Location: order-success.php');
        exit();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Calculate delivery fee display
$delivery_fee_display = ($checkout['delivery_method'] === 'delivery') ? 'R' . number_format($checkout['delivery_fee'], 2) : 'Free Pickup';
if ($checkout['delivery_method'] === 'delivery' && $checkout['subtotal'] >= 500) {
    $delivery_fee_display = 'Free Delivery';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Review Order - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.75rem 1.8rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .review-card { background: white; border-radius: 24px; padding: 2rem; margin-bottom: 1.5rem; border: 1px solid rgba(0, 0, 0, 0.05); }
        .form-control, .form-select { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; }
        .form-control:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); outline: none; }
        .photo-preview { width: 200px; height: 150px; object-fit: cover; border-radius: 16px; margin-top: 1rem; }
        .rating-star { font-size: 2rem; cursor: pointer; color: #ddd; transition: all 0.2s; }
        .rating-star.selected, .rating-star:hover { color: #ffc107; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        @media (max-width: 768px) { .review-card { padding: 1rem; } }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="cart.php" class="text-dark text-decoration-none">Cart</a></li>
                        <li class="breadcrumb-item"><a href="checkout.php" class="text-dark text-decoration-none">Checkout</a></li>
                        <li class="breadcrumb-item active text-dark" aria-current="page">Review & Confirm</li>
                    </ol>
                </nav>

                <?php if (isset($error_message)): ?>
                    <div class="alert-message alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="review-order.php" enctype="multipart/form-data">
                    <!-- Order Summary -->
                    <div class="review-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-receipt"></i> Order Summary</h5>
                        <?php foreach ($checkout['cart_items'] as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo htmlspecialchars($item['title']); ?> (x<?php echo $item['quantity']; ?>)</span>
                                <span>R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Subtotal</span>
                            <span>R<?php echo number_format($checkout['subtotal'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Delivery</span>
                            <span><?php echo $delivery_fee_display; ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span>
                            <span>R<?php echo number_format($checkout['total_amount'], 2); ?></span>
                        </div>
                    </div>

                    <!-- Delivery Information -->
                    <div class="review-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-truck"></i> Delivery Information</h5>
                        <?php if ($checkout['delivery_method'] === 'pickup'): ?>
                            <p><strong>Pickup Location:</strong> UniformMarket Hub, Sandton, Johannesburg</p>
                            <p><strong>Pickup Hours:</strong> Mon-Fri 9am-5pm, Sat 9am-1pm</p>
                            <p><strong>Contact:</strong> +27 12 345 6789</p>
                        <?php else: ?>
                            <p><strong>Delivery Address:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($checkout['delivery_address'])); ?><br>
                            <?php echo htmlspecialchars($checkout['delivery_suburb'] . ', ' . $checkout['delivery_city']); ?><br>
                            <?php echo htmlspecialchars($checkout['delivery_postal']); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Delivery Review (Photo & Comment) -->
                    <div class="review-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-camera"></i> Delivery Confirmation</h5>
                        <p class="text-muted small mb-3">After you receive your items, please upload a photo and leave a review.</p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload Delivery Photo *</label>
                            <input type="file" class="form-control" name="delivery_photo" accept="image/jpeg,image/png,image/jpg" required onchange="previewPhoto(this)">
                            <small class="text-muted">Take a photo of the delivered uniforms and upload here</small>
                            <img id="photoPreview" class="photo-preview" style="display: none;">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Delivery Rating *</label>
                            <div class="d-flex gap-2 mb-2">
                                <i class="bi bi-star-fill rating-star" data-rating="1"></i>
                                <i class="bi bi-star-fill rating-star" data-rating="2"></i>
                                <i class="bi bi-star-fill rating-star" data-rating="3"></i>
                                <i class="bi bi-star-fill rating-star" data-rating="4"></i>
                                <i class="bi bi-star-fill rating-star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="ratingValue" value="5">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Delivery Comments</label>
                            <textarea class="form-control" name="delivery_comment" rows="3" placeholder="How was your delivery experience? Was the item as described?"></textarea>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit_review" class="btn btn-black btn-lg rounded-pill px-5">
                            <i class="bi bi-check-circle"></i> Complete Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Photo preview
    function previewPhoto(input) {
        const preview = document.getElementById('photoPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Star rating
    const stars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('ratingValue');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            ratingInput.value = rating;
            
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('selected');
                    s.style.color = '#ffc107';
                } else {
                    s.classList.remove('selected');
                    s.style.color = '#ddd';
                }
            });
        });
    });
</script>
</body>
</html>