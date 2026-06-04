<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection for cart count and user data
// Since header.php is in root folder, config is in ./config/
require_once __DIR__ . '/config/database.php';

// Check if user is logged in (using PHP session, not localStorage)
$is_logged_in = isset($_SESSION['user_id']);
$user_name = '';
$user_avatar = '';
$cart_count = 0;
$unread_count = 0;

if ($is_logged_in) {
    $user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $user_avatar = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
    
    // Get cart count from database (check if cart table exists first)
    $cart_count = 0;
    $table_check = $conn->query("SHOW TABLES LIKE 'cart'");
    if ($table_check && $table_check->num_rows > 0) {
        $cart_count_sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
        $cart_count_stmt = $conn->prepare($cart_count_sql);
        if ($cart_count_stmt) {
            $cart_count_stmt->bind_param("i", $_SESSION['user_id']);
            $cart_count_stmt->execute();
            $cart_count_result = $cart_count_stmt->get_result();
            $cart_count = $cart_count_result->fetch_assoc()['total'] ?? 0;
            $cart_count_stmt->close();
        }
    }
    
    // Get unread messages count (check if messages table exists)
    $table_check = $conn->query("SHOW TABLES LIKE 'messages'");
    if ($table_check && $table_check->num_rows > 0) {
        $unread_sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
        $unread_stmt = $conn->prepare($unread_sql);
        if ($unread_stmt) {
            $unread_stmt->bind_param("i", $_SESSION['user_id']);
            $unread_stmt->execute();
            $unread_result = $unread_stmt->get_result();
            $unread_count = $unread_result->fetch_assoc()['count'] ?? 0;
            $unread_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  
  <!-- Favicon using logo.png from same folder -->
  <link rel="icon" type="image/png" sizes="16x16" href="logo.png">
  <link rel="icon" type="image/png" sizes="32x32" href="logo.png">
  <link rel="icon" type="image/png" sizes="96x96" href="logo.png">
  <link rel="apple-touch-icon" href="logo.png">
  
  <!-- Tan Meringue Font - Google Fonts alternative (similar style) -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <style>
    /* Header-specific styles that complement the main site */
    .navbar-brand {
      transition: transform 0.2s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .navbar-brand:hover {
      transform: scale(1.02);
    }
    
    .logo-img {
      height: 40px;
      width: auto;
      display: block;
    }
    
    /* Tan Meringue style font for website name */
    .brand-name {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      font-style: italic;
      letter-spacing: -0.5px;
      background: linear-gradient(135deg, #D2A679 0%, #C4956A 25%, #B8865A 50%, #C4956A 75%, #D2A679 100%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    @media (max-width: 768px) {
      .logo-img {
        height: 32px;
      }
      .brand-name {
        font-size: 1.25rem;
      }
    }
    
    .dropdown-menu {
      border-radius: 16px;
      border: 1px solid rgba(0, 0, 0, 0.08);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      padding: 0.5rem;
      margin-top: 0.5rem;
    }
    
    .dropdown-item {
      border-radius: 12px;
      padding: 0.6rem 1rem;
      transition: all 0.2s;
      font-size: 0.9rem;
    }
    
    .dropdown-item:hover {
      background-color: #FEF9E6;
      transform: translateX(4px);
    }
    
    .dropdown-item i {
      width: 1.5rem;
      color: #4a4a4a;
    }
    
    .user-avatar {
      width: 32px;
      height: 32px;
      background: #000;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 0.9rem;
      margin-right: 0.5rem;
    }
    
    .cart-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #dc3545;
      color: white;
      border-radius: 50%;
      padding: 0.2rem 0.5rem;
      font-size: 0.7rem;
      font-weight: 600;
      min-width: 18px;
      text-align: center;
    }
    
    .btn-cart {
      position: relative;
    }
    
    @media (max-width: 768px) {
      .dropdown-menu {
        position: absolute !important;
        transform: translate3d(0px, 40px, 0px) !important;
      }
    }
  </style>
</head>
<body>

<!-- ============================================= -->
<!-- HEADER - Uniform Marketplace with Auth        -->
<!-- ============================================= -->
<header id="main-header">
  <div class="container">
    <nav class="navbar navbar-expand-lg py-3">
      <div class="container-fluid px-0">
        <!-- Logo / Brand with Image and Tan Meringue text -->
        <a class="navbar-brand fw-bold" href="index.php">
          <img src="logo.png" alt="Uniformly Logo" class="logo-img" onerror="this.onerror=null; this.parentElement.querySelector('.brand-name').style.display='inline'">
          <span class="brand-name">UNIFORMLY</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-lg-3">
            <li class="nav-item">
              <a class="nav-link fw-semibold" href="index.php" style="color: #000;">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="shop.php" style="color: #2c2c2c;">Shop by School</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="shop.php?category=school_bag" style="color: #2c2c2c;">School Bags</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="shop.php?category=uniform_set" style="color: #2c2c2c;">Uniform Sets</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="account-listings.php" style="color: #2c2c2c;">Sell Your Uniform</a>
            </li>
          </ul>
          
          <!-- Dynamic Navigation Section - Shows different content based on login status -->
          <?php if ($is_logged_in): ?>
            <!-- Logged In User Navigation -->
            <!-- Cart Button -->
            <a href="cart.php" class="btn btn-outline-black rounded-pill ms-lg-2 me-2 position-relative btn-cart">
              <i class="bi bi-cart"></i> Cart
              <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?php echo $cart_count; ?></span>
              <?php endif; ?>
            </a>
            
            <!-- User Menu -->
            <div class="d-flex gap-2">
              <div class="dropdown">
                <button class="btn btn-black rounded-pill px-3 py-1 dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <div class="user-avatar"><?php echo $user_avatar; ?></div>
                  <span><?php echo htmlspecialchars($user_name); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="account-dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                  <li><a class="dropdown-item" href="account-profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                  <li><a class="dropdown-item" href="account-listings.php"><i class="bi bi-grid-3x3-gap-fill me-2"></i>My Listings</a></li>
                  <li><a class="dropdown-item" href="account-orders.php"><i class="bi bi-bag-check me-2"></i>My Purchases</a></li>
                  <li><a class="dropdown-item" href="account-saved.php"><i class="bi bi-heart me-2"></i>Saved Items</a></li>
                  <li><a class="dropdown-item" href="account-messages.php"><i class="bi bi-chat-dots me-2"></i>Messages 
                    <?php if ($unread_count > 0): ?>
                      <span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                  </a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="account-settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                  <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
              </div>
            </div>
          <?php else: ?>
            <!-- Guest User Navigation (Not Logged In) -->
            <!-- Cart Button (still visible for guests to browse cart) -->
            <a href="cart.php" class="btn btn-outline-black rounded-pill ms-lg-2 me-2 position-relative btn-cart">
              <i class="bi bi-cart"></i> Cart
            </a>
            
            <!-- Auth Buttons -->
            <div class="d-flex gap-2">
              <a href="login.php" class="btn btn-outline-black rounded-pill px-4 py-1">Sign In</a>
              <a href="register.php" class="btn btn-black rounded-pill px-4 py-1">Join Free</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </nav>
  </div>
</header>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>