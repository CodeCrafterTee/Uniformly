<?php
session_start();
require_once 'config/database.php';

// Get cart count for logged in users
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart_count_sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $cart_count_stmt = $conn->prepare($cart_count_sql);
    $cart_count_stmt->bind_param("i", $_SESSION['user_id']);
    $cart_count_stmt->execute();
    $cart_count_result = $cart_count_stmt->get_result();
    $cart_count = $cart_count_result->fetch_assoc()['total'] ?? 0;
    $cart_count_stmt->close();
}

// Get all active listings
$listings_sql = "SELECT l.*, s.school_name, 
                 u.first_name as seller_first_name, u.last_name as seller_last_name,
                 (SELECT image_url FROM listing_images WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_image
                 FROM listings l
                 JOIN schools s ON l.school_id = s.school_id
                 JOIN users u ON l.seller_id = u.user_id
                 WHERE l.status = 'active'
                 ORDER BY l.created_at DESC
                 LIMIT 20";
$listings_result = $conn->query($listings_sql);

$listings = [];
while ($row = $listings_result->fetch_assoc()) {
    $listings[] = $row;
}

// Get schools for filter
$schools_sql = "SELECT school_id, school_name FROM schools WHERE is_active = 1 ORDER BY school_name";
$schools_result = $conn->query($schools_sql);
$schools = [];
while ($row = $schools_result->fetch_assoc()) {
    $schools[] = $row;
}

// Get categories for filter
$categories = [
    'uniform_set' => 'Uniform Set',
    'blazer' => 'Blazer',
    'shirt' => 'Shirt',
    'pants' => 'Pants/Skirt',
    'school_bag' => 'School Bag',
    'sports_kit' => 'Sports Kit',
    'accessory' => 'Accessory'
];

// Handle search/filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$school_filter = isset($_GET['school']) ? intval($_GET['school']) : 0;
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build filter query
$where_conditions = ["l.status = 'active'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(l.title LIKE ? OR s.school_name LIKE ? OR l.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($school_filter > 0) {
    $where_conditions[] = "l.school_id = ?";
    $params[] = $school_filter;
    $types .= "i";
}

if (!empty($category_filter)) {
    $where_conditions[] = "l.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get filtered listings
$filtered_sql = "SELECT l.*, s.school_name, 
                 u.first_name as seller_first_name, u.last_name as seller_last_name,
                 (SELECT image_url FROM listing_images WHERE listing_id = l.listing_id AND is_primary = 1 LIMIT 1) as primary_image
                 FROM listings l
                 JOIN schools s ON l.school_id = s.school_id
                 JOIN users u ON l.seller_id = u.user_id
                 WHERE $where_clause
                 ORDER BY l.created_at DESC
                 LIMIT 50";

if (!empty($params)) {
    $filtered_stmt = $conn->prepare($filtered_sql);
    $filtered_stmt->bind_param($types, ...$params);
    $filtered_stmt->execute();
    $filtered_result = $filtered_stmt->get_result();
    $filtered_listings = [];
    while ($row = $filtered_result->fetch_assoc()) {
        $filtered_listings[] = $row;
    }
    $filtered_stmt->close();
} else {
    $filtered_result = $conn->query($filtered_sql);
    $filtered_listings = [];
    while ($row = $filtered_result->fetch_assoc()) {
        $filtered_listings[] = $row;
    }
}

// Get user info if logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;
$user_name = $is_logged_in ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : '';

// Get unread messages count for logged in users
$unread_count = 0;
if ($is_logged_in) {
    $unread_sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $unread_stmt = $conn->prepare($unread_sql);
    $unread_stmt->bind_param("i", $user_id);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->get_result();
    $unread_count = $unread_result->fetch_assoc()['count'] ?? 0;
    $unread_stmt->close();
}

// Check for success message
$success_message = '';
if (isset($_GET['success'])) {
    $success_message = urldecode($_GET['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Shop - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.5rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; transition: all 0.2s; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .product-card { background: white; border-radius: 20px; overflow: hidden; transition: all 0.2s; border: 1px solid rgba(0, 0, 0, 0.05); height: 100%; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1); }
        .product-image { width: 100%; height: 200px; object-fit: cover; }
        .price { font-size: 1.25rem; font-weight: 700; color: #000; }
        .filter-card { background: white; border-radius: 20px; padding: 1rem; margin-bottom: 1rem; border: 1px solid rgba(0, 0, 0, 0.05); }
        .filter-card.active { border-color: #000; background: #FEF9E6; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        @media (max-width: 768px) { .product-image { height: 180px; } }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <div class="container my-5">
        <!-- Hero Section -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <h1 class="fw-bold display-5">Shop Pre-Loved Uniforms</h1>
                <p class="lead text-muted">Find quality uniforms from schools across Gauteng. Save up to 70% off retail prices!</p>
            </div>
            <div class="col-lg-4">
                <form method="GET" action="shop.php" class="input-group">
                    <input type="text" name="search" class="form-control rounded-pill" placeholder="Search by school, size..." style="border-radius: 40px 0 0 40px;" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-black rounded-pill" style="border-radius: 0 40px 40px 0;"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert-message alert-success">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-card">
                    <h6 class="fw-bold mb-3"><i class="bi bi-funnel"></i> Filters</h6>
                    
                    <label class="form-label fw-semibold small">School</label>
                    <select class="form-select form-select-sm mb-3" onchange="window.location.href=this.value">
                        <option value="shop.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>">All Schools</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="shop.php?school=<?php echo $school['school_id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                <?php echo ($school_filter == $school['school_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($school['school_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label class="form-label fw-semibold small mt-2">Category</label>
                    <select class="form-select form-select-sm" onchange="window.location.href=this.value">
                        <option value="shop.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?><?php echo $school_filter ? '&school=' . $school_filter : ''; ?>">All Categories</option>
                        <?php foreach ($categories as $key => $cat): ?>
                            <option value="shop.php?category=<?php echo $key; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $school_filter ? '&school=' . $school_filter : ''; ?>" 
                                <?php echo ($category_filter == $key) ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if ($search || $school_filter || $category_filter): ?>
                        <hr>
                        <a href="shop.php" class="btn btn-sm btn-outline-black w-100 rounded-pill">Clear Filters</a>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Links -->
                <div class="filter-card mt-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-link"></i> Quick Links</h6>
                    <a href="shop-by-school.php" class="d-block mb-2 text-dark text-decoration-none small">
                        <i class="bi bi-building"></i> Shop by School
                    </a>
                    <?php if ($is_logged_in): ?>
                        <a href="account-saved.php" class="d-block mb-2 text-dark text-decoration-none small">
                            <i class="bi bi-heart"></i> Saved Items
                        </a>
                        <a href="account-listings.php" class="d-block text-dark text-decoration-none small">
                            <i class="bi bi-plus-circle"></i> Sell Your Uniforms
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="d-block text-dark text-decoration-none small">
                            <i class="bi bi-person-plus"></i> Create Account to Sell
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">Showing <?php echo count($filtered_listings); ?> items</p>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-black rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Sort by: Newest
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Newest First</a></li>
                            <li><a class="dropdown-item" href="#">Price: Low to High</a></li>
                            <li><a class="dropdown-item" href="#">Price: High to Low</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4">
                    <?php if (empty($filtered_listings)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-box-seam fs-1 text-muted"></i>
                            <h5 class="mt-3">No listings found</h5>
                            <p class="text-muted">Try adjusting your filters or check back later for new uniforms!</p>
                            <a href="shop.php" class="btn btn-outline-black rounded-pill mt-2">Clear Filters</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($filtered_listings as $item): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="product-card">
                                    <img src="<?php echo htmlspecialchars($item['primary_image'] ?? 'https://placehold.co/400x300/F5EFE0/2c2c2c?text=Uniform'); ?>" class="product-image" alt="Uniform">
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                        <p class="small text-muted mb-1">
                                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($item['school_name']); ?>
                                        </p>
                                        <p class="small mb-2">
                                            <i class="bi bi-tag"></i> Size: <?php echo htmlspecialchars($item['size']); ?> | <?php echo ucfirst($item['item_condition']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="price">R<?php echo number_format($item['price'], 2); ?></span>
                                            <?php if ($is_logged_in): ?>
                                                <div class="btn-group">
                                                    <a href="add-to-cart.php?listing_id=<?php echo $item['listing_id']; ?>&redirect=shop" class="btn btn-sm btn-black rounded-pill">
                                                        <i class="bi bi-cart-plus"></i> Add to Cart
                                                    </a>
                                                    <a href="add-to-saved.php?listing_id=<?php echo $item['listing_id']; ?>" class="btn btn-sm btn-outline-black rounded-pill ms-1" title="Save for later">
                                                        <i class="bi bi-heart"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <a href="login.php?redirect=shop" class="btn btn-sm btn-outline-black rounded-pill">Login to Buy</a>
                                            <?php endif; ?>
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

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>