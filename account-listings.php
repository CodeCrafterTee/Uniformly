<?php
session_start();
require_once 'config/database.php';

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=listings');
    exit();
}

// Get user details from session
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$email = $_SESSION['user_email'];
$user_name = $first_name . ' ' . $last_name;
$user_avatar = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get user's listings from database
$listings_sql = "SELECT l.*, s.school_name, 
                 (SELECT image_url FROM listing_images WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_image
                 FROM listings l
                 JOIN schools s ON l.school_id = s.school_id
                 WHERE l.seller_id = ?
                 ORDER BY l.created_at DESC";
$listings_stmt = $conn->prepare($listings_sql);
$listings_stmt->bind_param("i", $user_id);
$listings_stmt->execute();
$listings_result = $listings_stmt->get_result();

$listings = [];
while ($row = $listings_result->fetch_assoc()) {
    $listings[] = $row;
}
$listings_stmt->close();

// Get all schools for dropdown
$schools_sql = "SELECT school_id, school_name FROM schools WHERE is_active = 1 ORDER BY school_name";
$schools_result = $conn->query($schools_sql);
$schools = [];
while ($row = $schools_result->fetch_assoc()) {
    $schools[] = $row;
}

// Create upload directory if it doesn't exist - Using absolute path
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/listings/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Also create web-accessible path reference
$web_upload_dir = 'uploads/listings/';

// Process add listing form
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $school_id = intval($_POST['school_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $item_condition = trim($_POST['item_condition'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $delivery_option = 'delivery'; // Always delivery via Paxi
    $delivery_fee = 69.00; // Fixed Paxi delivery fee
    
    $errors = [];
    
    if ($school_id <= 0) {
        $errors[] = "Please select a school";
    }
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($category)) {
        $errors[] = "Please select an item type";
    }
    if (empty($size)) {
        $errors[] = "Size is required";
    }
    if (empty($item_condition)) {
        $errors[] = "Please select condition";
    }
    if ($price <= 0) {
        $errors[] = "Please enter a valid price";
    }
    if ($quantity <= 0) {
        $errors[] = "Please enter a valid quantity (at least 1)";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    // Handle file uploads
    $uploaded_images = [];
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Limit to 5 images
        $total_files = count($_FILES['photos']['name']);
        if ($total_files > 5) {
            $errors[] = "Maximum 5 images allowed.";
        }
        
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['photos']['type'][$key];
                $file_size = $_FILES['photos']['size'][$key];
                $file_name = $_FILES['photos']['name'][$key];
                
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "File '$file_name' is not allowed. Only JPG, PNG, and WebP images are allowed.";
                    continue;
                }
                
                if ($file_size > $max_size) {
                    $errors[] = "File '$file_name' is too large. Maximum size is 5MB.";
                    continue;
                }
                
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_name = uniqid() . '_' . time() . '_' . $key . '.' . $extension;
                $upload_path_absolute = $upload_dir . $unique_name;
                $upload_path_web = $web_upload_dir . $unique_name;
                
                if (move_uploaded_file($tmp_name, $upload_path_absolute)) {
                    $uploaded_images[] = $upload_path_web;
                } else {
                    $errors[] = "Failed to upload file '$file_name'. Error code: " . $_FILES['photos']['error'][$key];
                }
            } elseif ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading file. Error code: " . $_FILES['photos']['error'][$key];
            }
        }
    } else {
        $errors[] = "Please upload at least one photo of the uniform.";
    }
    
    if (empty($errors)) {
        // Insert listing with quantity
        $insert_sql = "INSERT INTO listings (seller_id, school_id, title, description, category, size, item_condition, price, quantity, 
                       delivery_option, delivery_fee, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iisssssdids", $user_id, $school_id, $title, $description, $category, $size, 
                                  $item_condition, $price, $quantity, $delivery_option, $delivery_fee);
        
        if ($insert_stmt->execute()) {
            $listing_id = $insert_stmt->insert_id;
            
            // Insert images
            $is_primary = true;
            foreach ($uploaded_images as $index => $image_path) {
                $image_sql = "INSERT INTO listing_images (listing_id, image_url, is_primary, sort_order) VALUES (?, ?, ?, ?)";
                $image_stmt = $conn->prepare($image_sql);
                $image_stmt->bind_param("isii", $listing_id, $image_path, $is_primary, $index);
                if ($image_stmt->execute()) {
                    $is_primary = false;
                }
                $image_stmt->close();
            }
            
            // Create notification for seller
            $notif_sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                          VALUES (?, 'system', 'Listing Created', 'Your uniform has been listed successfully! Payment will be held until buyer confirms delivery.', 'account-listings.php')";
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param("i", $user_id);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            $success_message = "Listing added successfully!";
            
            // Refresh the page to show new listing
            header("Location: account-listings.php?success=1");
            exit();
        } else {
            $error_message = "Failed to add listing: " . $conn->error;
        }
        $insert_stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Process delete listing
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $listing_id = intval($_GET['delete']);
    
    $check_sql = "SELECT listing_id FROM listings WHERE listing_id = ? AND seller_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $listing_id, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        // Get images to delete from filesystem
        $img_sql = "SELECT image_url FROM listing_images WHERE listing_id = ?";
        $img_stmt = $conn->prepare($img_sql);
        $img_stmt->bind_param("i", $listing_id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        
        while ($img = $img_result->fetch_assoc()) {
            $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $img['image_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $img_stmt->close();
        
        // Delete the listing (cascade should delete images from DB)
        $delete_sql = "DELETE FROM listings WHERE listing_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $listing_id);
        
        if ($delete_stmt->execute()) {
            header("Location: account-listings.php?deleted=1");
            exit();
        } else {
            $error_message = "Failed to delete listing.";
        }
        $delete_stmt->close();
    }
    $check_stmt->close();
}

if (isset($_GET['success'])) {
    $success_message = "Listing added successfully!";
}
if (isset($_GET['deleted'])) {
    $success_message = "Listing deleted successfully!";
}

// Get unread messages count
$unread_sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['count'] ?? 0;
$unread_stmt->close();

// Get member since date
$member_since_sql = "SELECT created_at FROM users WHERE user_id = ?";
$member_stmt = $conn->prepare($member_since_sql);
$member_stmt->bind_param("i", $user_id);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member_data = $member_result->fetch_assoc();
$member_since = date('F Y', strtotime($member_data['created_at'] ?? 'now'));
$member_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Listings - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.5rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .dashboard-sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        .listing-card { background: white; border-radius: 20px; overflow: hidden; transition: all 0.2s; border: 1px solid rgba(0, 0, 0, 0.05); margin-bottom: 1rem; }
        .listing-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
        .listing-image { width: 100%; height: 120px; object-fit: cover; border-radius: 12px; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-active { background: #2e7d32; color: white; }
        .status-sold { background: #9e9e9e; color: white; }
        .status-pending { background: #ff9800; color: white; }
        .modal-content { border-radius: 24px; border: none; }
        .form-control, .form-select { border: 1.5px solid #e0d8cc; border-radius: 16px; padding: 0.75rem 1rem; }
        .form-control:focus, .form-select:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1); }
        .user-avatar-large { width: 80px; height: 80px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2rem; margin: 0 auto 1rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        .image-preview { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .image-preview-item { position: relative; width: 100px; height: 100px; border-radius: 12px; overflow: hidden; border: 2px solid #e0d8cc; }
        .image-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .remove-image { position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .pricing-guide { background: #fff3e0; border-left: 4px solid #ff9800; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .quantity-badge { background: #e0e0e0; color: #333; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; }
        @media (max-width: 768px) { .dashboard-sidebar { position: relative; top: 0; margin-bottom: 1.5rem; } }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    <div class="text-center mb-4">
                        <div class="user-avatar-large mx-auto mb-3"><?php echo $user_avatar; ?></div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user_name); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($email); ?></p>
                        <span class="badge bg-dark rounded-pill">Member since <?php echo $member_since; ?></span>
                    </div>
                    <ul class="sidebar-nav">
                        <li><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
                        <li><a href="account-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li><a href="account-profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                        <li><a href="account-listings.php" class="active"><i class="bi bi-grid-3x3-gap-fill"></i> My Listings</a></li>
                        <li><a href="account-orders.php"><i class="bi bi-bag-check"></i> My Purchases</a></li>
                        <li><a href="account-saved.php"><i class="bi bi-heart"></i> Saved Items</a></li>
                        <li><a href="account-messages.php"><i class="bi bi-chat-dots"></i> Messages <?php if ($unread_count > 0): ?><span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                        <li><a href="account-settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="my-2"></li>
                        <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">My Listings</h2>
                        <p class="text-muted">Manage your uniform listings</p>
                    </div>
                    <button class="btn btn-black rounded-pill" data-bs-toggle="modal" data-bs-target="#addListingModal"><i class="bi bi-plus-lg"></i> Add New Listing</button>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert-message alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert-message alert-error">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Payment Hold Notification -->
                <div class="info-box mb-4">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Important:</strong> Payment is held securely by UniformMarket until the buyer confirms they have received and are satisfied with the item. Funds are released to you only after delivery confirmation.
                </div>

                <ul class="nav nav-tabs mb-4 border-0 gap-2" id="listingTabs">
                    <li class="nav-item"><a class="nav-link active btn-outline-black rounded-pill px-4 py-2" style="border-radius: 40px;" href="#" data-status="all" onclick="filterListings('all'); return false;">All</a></li>
                    <li class="nav-item"><a class="nav-link btn-outline-black rounded-pill px-4 py-2" style="border-radius: 40px;" href="#" data-status="active" onclick="filterListings('active'); return false;">Active</a></li>
                    <li class="nav-item"><a class="nav-link btn-outline-black rounded-pill px-4 py-2" style="border-radius: 40px;" href="#" data-status="pending" onclick="filterListings('pending'); return false;">Pending</a></li>
                    <li class="nav-item"><a class="nav-link btn-outline-black rounded-pill px-4 py-2" style="border-radius: 40px;" href="#" data-status="sold" onclick="filterListings('sold'); return false;">Sold</a></li>
                </ul>

                <div id="listingsContainer">
                    <?php if (empty($listings)): ?>
                        <div class="text-center py-5 bg-white rounded-4">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="mt-3 text-muted">No listings found</p>
                            <button class="btn btn-outline-black rounded-pill" data-bs-toggle="modal" data-bs-target="#addListingModal">Create Your First Listing</button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($listings as $listing): ?>
                            <div class="listing-card p-3" data-status="<?php echo $listing['status']; ?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <img src="<?php echo htmlspecialchars($listing['primary_image'] ?? 'https://placehold.co/300x200/F5EFE0/2c2c2c?text=Uniform'); ?>" class="listing-image w-100" alt="Uniform image">
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold"><?php echo htmlspecialchars($listing['school_name']); ?> - <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($listing['category']))); ?></h6>
                                        <p class="small text-muted mb-1">
                                            Size: <?php echo htmlspecialchars($listing['size']); ?> | 
                                            Condition: <?php echo htmlspecialchars($listing['item_condition']); ?> |
                                            <span class="quantity-badge"><i class="bi bi-box"></i> Qty: <?php echo $listing['quantity'] ?? 1; ?></span>
                                        </p>
                                        <p class="small mb-2"><?php echo htmlspecialchars(substr($listing['description'], 0, 100)); ?>...</p>
                                        <span class="status-badge status-<?php echo $listing['status']; ?>"><?php echo strtoupper($listing['status']); ?></span>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <h5 class="fw-bold text-dark">R<?php echo number_format($listing['price'], 2); ?></h5>
                                        <small class="text-muted">+ R69 Paxi delivery</small><br>
                                        <small class="text-muted d-block">Listed: <?php echo date('M d, Y', strtotime($listing['created_at'])); ?></small>
                                        <div class="mt-2">
                                            <a href="edit-listing.php?id=<?php echo $listing['listing_id']; ?>" class="btn btn-sm btn-outline-black rounded-pill me-1">Edit</a>
                                            <a href="account-listings.php?delete=<?php echo $listing['listing_id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure you want to delete this listing?');">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Listing Modal -->
<div class="modal fade" id="addListingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">List Your Uniform</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Pricing Guide -->
                <div class="pricing-guide mb-3">
                    <h6 class="fw-bold mb-2"><i class="bi bi-tag"></i> Pricing Guide</h6>
                    <div class="row small">
                        <div class="col-6">Like New</div>
                        <div class="col-6">50-70% of retail price</div>
                        <div class="col-6">Excellent</div>
                        <div class="col-6">40-50% of retail price</div>
                        <div class="col-6">Good</div>
                        <div class="col-6">30-40% of retail price</div>
                        <div class="col-6">Fair</div>
                        <div class="col-6">20-30% of retail price</div>
                    </div>
                </div>
                
                <form method="POST" action="" id="addListingForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">School Name</label>
                        <select class="form-select" name="school_id" required>
                            <option value="">Select a school</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?php echo $school['school_id']; ?>"><?php echo htmlspecialchars($school['school_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title / Item Name</label>
                        <input type="text" class="form-control" name="title" placeholder="e.g., Bryanston High School Blazer - Size 34" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Item Type</label>
                            <select class="form-select" name="category" required>
                                <option value="">Select type</option>
                                <option value="uniform_set">Uniform Set</option>
                                <option value="blazer">Blazer</option>
                                <option value="shirt">Shirt</option>
                                <option value="pants">Pants/Skirt</option>
                                <option value="school_bag">School Bag</option>
                                <option value="sports_kit">Sports Kit</option>
                                <option value="accessory">Accessory</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Size</label>
                            <input type="text" class="form-control" name="size" placeholder="e.g., Small, Medium, 10, 12, 34" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Condition</label>
                            <select class="form-select" name="item_condition" required>
                                <option value="">Select condition</option>
                                <option value="like_new">Like New - Barely worn</option>
                                <option value="excellent">Excellent - Minor wear</option>
                                <option value="good">Good - Some signs of use</option>
                                <option value="fair">Fair - Visible wear but usable</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Price (R)</label>
                            <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" class="form-control" name="quantity" value="1" min="1" placeholder="Quantity" required>
                            <small class="text-muted">Number of items available</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Describe the uniform, any imperfections, etc." required></textarea>
                    </div>
                    
                    <!-- Paxi Delivery Information -->
                    <div class="info-box mb-3">
                        <h6 class="fw-bold mb-2"><i class="bi bi-box-seam"></i> Paxi Delivery - R69</h6>
                        <p class="small mb-2">We use Paxi (via PEP stores) for reliable, tracked delivery across South Africa.</p>
                        <p class="small mb-0"><strong>How to send:</strong> Once sold, drop off the item at your nearest PEP store. Paxi will deliver to the buyer's nearest PEP store. You'll receive a tracking number.</p>
                    </div>
                    
                    <!-- Drop-off Instructions -->
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle"></i> <strong>Payment Release:</strong> You will only receive payment AFTER the buyer has received the item and confirmed satisfaction. We verify delivery before releasing funds.
                    </div>
                    
                    <!-- Image Upload -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="bi bi-images"></i> Photos (Upload up to 5 images)</label>
                        <input type="file" class="form-control" name="photos[]" id="photoUpload" accept="image/jpeg,image/png,image/jpg,image/webp" multiple required>
                        <small class="text-muted">You can upload up to 5 images. First image will be the main photo. Max 5MB each.</small>
                        <div id="imagePreviewContainer" class="image-preview"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-black w-100 rounded-pill">List Item</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function filterListings(status) {
        const listings = document.querySelectorAll('.listing-card');
        const tabs = document.querySelectorAll('#listingTabs .nav-link');
        
        tabs.forEach(tab => {
            tab.classList.remove('active');
            if (tab.getAttribute('data-status') === status) {
                tab.classList.add('active');
            }
        });
        
        listings.forEach(listing => {
            if (status === 'all' || listing.getAttribute('data-status') === status) {
                listing.style.display = 'block';
            } else {
                listing.style.display = 'none';
            }
        });
    }
    
    // Image preview functionality
    const photoUpload = document.getElementById('photoUpload');
    const previewContainer = document.getElementById('imagePreviewContainer');
    
    if (photoUpload) {
        photoUpload.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            const files = Array.from(e.target.files);
            
            if (files.length > 5) {
                alert('You can only upload up to 5 images.');
                this.value = '';
                return;
            }
            
            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'image-preview-item';
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        previewDiv.appendChild(img);
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-image';
                        removeBtn.innerHTML = '×';
                        removeBtn.onclick = function() {
                            previewDiv.remove();
                            const dt = new DataTransfer();
                            const remainingFiles = Array.from(photoUpload.files).filter((f, i) => i !== index);
                            remainingFiles.forEach(f => dt.items.add(f));
                            photoUpload.files = dt.files;
                        };
                        previewDiv.appendChild(removeBtn);
                    };
                    
                    reader.readAsDataURL(file);
                    previewContainer.appendChild(previewDiv);
                }
            });
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        filterListings('all');
    });
</script>
</body>
</html>