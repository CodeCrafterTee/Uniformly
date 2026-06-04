<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>UniformMarket - Buy & Sell Pre-Loved School Uniforms</title>
  <!-- Bootstrap 5 CSS + Icons + custom font -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #FEF9E6;
      color: #1E1E1E;
      scroll-behavior: smooth;
    }

    .bg-cream {
      background-color: #FEF9E6;
    }

    .bg-offwhite {
      background-color: #FFFCF5;
    }

    .btn-black {
      background-color: #000000;
      border: 1px solid #000000;
      color: white;
      font-weight: 500;
      padding: 0.6rem 1.8rem;
      border-radius: 40px;
      transition: all 0.25s ease;
      letter-spacing: 0.3px;
    }

    .btn-black:hover {
      background-color: #2C2C2C;
      border-color: #2C2C2C;
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
    }

    .btn-outline-black {
      background-color: transparent;
      border: 1.5px solid #000000;
      color: #000000;
      font-weight: 500;
      padding: 0.5rem 1.5rem;
      border-radius: 40px;
      transition: all 0.2s;
    }

    .btn-outline-black:hover {
      background-color: #000000;
      color: white;
    }

    .category-card {
      background: white;
      border-radius: 28px;
      padding: 1.8rem 1rem;
      text-align: center;
      transition: all 0.25s ease;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.02);
      border: 1px solid rgba(0, 0, 0, 0.04);
      cursor: pointer;
    }

    .category-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 30px -12px rgba(0, 0, 0, 0.08);
      border-color: #e9e0ce;
    }

    .category-icon {
      font-size: 2.6rem;
      margin-bottom: 1rem;
      display: inline-block;
    }

    .carousel-item img {
      object-fit: cover;
      height: 500px;
      width: 100%;
      border-radius: 32px;
    }

    .carousel-custom-container {
      borderRadius: 32px;
      overflow: hidden;
      box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.1);
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
      background-color: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      padding: 1.2rem;
      background-size: 60%;
    }

    .carousel-indicators [data-bs-target] {
      background-color: #1e1e1e;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin: 0 6px;
    }

    .hero-section {
      padding: 4rem 0 2rem 0;
    }

    .section-title {
      font-weight: 700;
      letter-spacing: -0.3px;
      border-left: 5px solid black;
      padding-left: 1rem;
    }

    @media (max-width: 768px) {
      .carousel-item img {
        height: 320px;
      }
      .hero-section {
        padding: 2rem 0 1rem;
      }
      .btn-black {
        padding: 0.5rem 1.4rem;
      }
    }

    .listing-badge {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: black;
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }
  </style>
</head>
<body>

<!-- Include Header -->
<div id="header-container"></div>

<main>
  <!-- Hero section - Uniform Marketplace -->
  <div class="hero-section">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <span class="badge bg-dark text-white mb-3 px-3 py-2 rounded-pill">👕 Save Money, Reduce Waste</span>
          <h1 class="display-4 fw-bold" style="color: #1f1f1f;">Buy & Sell Pre-Loved School Uniforms</h1>
          <p class="lead mt-3" style="color: #3a3a3a;">The trusted marketplace for parents. Find quality uniforms from top schools, or sell ones your kids have outgrown. Save up to 70% off retail prices.</p>
          <div class="d-flex flex-wrap gap-3 mt-4">
            <a href="shop.php" class="btn btn-black btn-lg rounded-pill px-5">Start Shopping <i class="bi bi-arrow-right-short"></i></a>
            <?php if ($is_logged_in): ?>
              <a href="account-listings.php" class="btn btn-outline-black btn-lg rounded-pill px-5">Sell Your Uniforms</a>
            <?php else: ?>
              <a href="login.php?redirect=sell" class="btn btn-outline-black btn-lg rounded-pill px-5">Sell Your Uniforms</a>
            <?php endif; ?>
          </div>
          <div class="mt-5 d-flex gap-4">
            <div><small class="text-muted fw-semibold"><i class="bi bi-check-lg text-black"></i> 5,000+ listings</small></div>
            <div><small class="text-muted fw-semibold"><i class="bi bi-check-lg text-black"></i> 200+ schools</small></div>
            <div><small class="text-muted fw-semibold"><i class="bi bi-check-lg text-black"></i> Verified sellers</small></div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="bg-offwhite rounded-4 p-3 shadow-sm text-center">
            <img src="images/hero-uniforms.jpg" class="img-fluid rounded-4" alt="school uniforms" style="max-height: 360px; width: 100%; object-fit: cover;" onerror="this.src='images/placeholder.jpg'">
            <p class="mt-2 fst-italic text-muted">👔 Find uniforms from top schools in your area</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- NEW IN!! section with CAROUSEL - Latest Uniform Listings -->
  <div class="container my-5 pt-4">
    <div class="d-flex justify-content-between align-items-end flex-wrap mb-4">
      <div>
        <span class="text-uppercase fw-semibold text-muted" style="letter-spacing: 1px;">Fresh listings</span>
        <h2 class="display-6 fw-bold" style="color: #000;">NEW IN!!</h2>
      </div>
      <a href="shop.php" class="btn btn-black rounded-pill">View All <i class="bi bi-grid-3x3-gap-fill"></i></a>
    </div>

    <!-- Bootstrap Carousel: Latest uniform listings -->
    <div id="newInCarousel" class="carousel slide carousel-fade carousel-custom-container" data-bs-ride="carousel" data-bs-interval="4000">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#newInCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#newInCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#newInCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        <button type="button" data-bs-target="#newInCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
      </div>
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="images/complete-uniform-sets.jpg" class="d-block w-100" alt="Complete School Uniform Sets" onerror="this.src='images/placeholder.jpg'">
          <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-40 rounded-3 p-3" style="backdrop-filter: blur(2px);">
            <h5 class="text-white fw-bold">Complete Uniform Sets</h5>
            <p class="text-white">Blazer, shirt, tie & pants - all from one school</p>
            <a href="shop.php?category=uniform_set" class="btn btn-sm btn-black rounded-pill">Shop Sets</a>
          </div>
        </div>
        <div class="carousel-item">
          <img src="images/school-bags-backpacks.jpg" class="d-block w-100" alt="School Bags and Backpacks" onerror="this.src='images/placeholder.jpg'">
          <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-40 rounded-3 p-3">
            <h5 class="text-white fw-bold">School Bags & Backpacks</h5>
            <p class="text-white">Durable, like-new condition - great deals</p>
            <a href="shop.php?category=school_bag" class="btn btn-sm btn-black rounded-pill">Shop Bags</a>
          </div>
        </div>
        <div class="carousel-item">
          <img src="images/sports-uniforms-pe-kits.jpg" class="d-block w-100" alt="Sports Uniforms and PE Kits" onerror="this.src='images/placeholder.jpg'">
          <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-40 rounded-3 p-3">
            <h5 class="text-white fw-bold">Sports & PE Kits</h5>
            <p class="text-white">Track suits, polo shirts, and more</p>
            <a href="shop.php?category=sports_kit" class="btn btn-sm btn-black rounded-pill">Shop Sports</a>
          </div>
        </div>
        <div class="carousel-item">
          <img src="images/blazers-school-jumpers.jpg" class="d-block w-100" alt="Blazers and School Jumpers" onerror="this.src='images/placeholder.jpg'">
          <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-40 rounded-3 p-3">
            <h5 class="text-white fw-bold">School Blazers & Jumpers</h5>
            <p class="text-white">High-quality, embossed with school logos</p>
            <a href="shop.php?category=blazer" class="btn btn-sm btn-black rounded-pill">Shop Blazers</a>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#newInCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#newInCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
    <div class="text-center mt-3">
      <span class="badge bg-white text-dark border px-3 py-2 rounded-pill"><i class="bi bi-camera-reels"></i> New listings added daily — swipe to explore</span>
    </div>
  </div>

  <!-- Explore by Category - Uniform Specific -->
  <div class="container my-5 pt-3 pb-4">
    <div class="text-center mb-5">
      <h2 class="display-6 fw-bold" style="color: #000;">Explore by Category</h2>
      <div class="mx-auto" style="width: 70px; height: 3px; background-color: black; margin-top: 12px;"></div>
      <p class="text-muted mt-3">Find exactly what you need for the new school year</p>
    </div>

    <div class="row g-4">
      <!-- Shop by School -->
      <div class="col-md-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-building"></i></div>
          <h5 class="fw-bold">Shop by School</h5>
          <p class="text-muted small">Find uniforms from specific schools in your area.</p>
          <a href="shop.php" class="btn btn-outline-black rounded-pill mt-2 w-100">Browse Schools →</a>
        </div>
      </div>
      <!-- School Bags -->
      <div class="col-md-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-backpack"></i></div>
          <h5 class="fw-bold">School Bags</h5>
          <p class="text-muted small">Backpacks, messenger bags, and sport bags.</p>
          <a href="shop.php?category=school_bag" class="btn btn-outline-black rounded-pill mt-2 w-100">Shop Bags →</a>
        </div>
      </div>
      <!-- Uniform Sets -->
      <div class="col-md-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-person-standing"></i></div>
          <h5 class="fw-bold">Uniform Sets</h5>
          <p class="text-muted small">Complete outfits - shirts, pants, skirts, ties.</p>
          <a href="shop.php?category=uniform_set" class="btn btn-outline-black rounded-pill mt-2 w-100">Shop Sets →</a>
        </div>
      </div>
      <!-- Blazers & Jackets -->
      <div class="col-md-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-journal"></i></div>
          <h5 class="fw-bold">Blazers & Jackets</h5>
          <p class="text-muted small">School blazers, fleeces, and winter wear.</p>
          <a href="shop.php?category=blazer" class="btn btn-outline-black rounded-pill mt-2 w-100">Shop Blazers →</a>
        </div>
      </div>
    </div>

    <!-- Second row of categories -->
    <div class="row g-4 mt-2">
      <div class="col-md-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-trophy"></i></div>
          <h5 class="fw-bold">Sports Uniforms</h5>
          <p class="text-muted small">PE kits, track suits, and sports jerseys.</p>
          <a href="shop.php?category=sports_kit" class="btn btn-outline-black rounded-pill mt-2 w-100">Shop Sports →</a>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-scissors"></i></div>
          <h5 class="fw-bold">Accessories</h5>
          <p class="text-muted small">Ties, belts, hats, and school scarves.</p>
          <a href="shop.php?category=accessory" class="btn btn-outline-black rounded-pill mt-2 w-100">Shop Accessories →</a>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-calculator"></i></div>
          <h5 class="fw-bold">School Supplies</h5>
          <p class="text-muted small">Calculators, and sports equipment.</p>
          <a href="shop.php?category=accessory" class="btn btn-outline-black rounded-pill mt-2 w-100">Shop Supplies →</a>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="category-card">
          <div class="category-icon"><i class="bi bi-cart-plus"></i></div>
          <h5 class="fw-bold">Bulk Bundles</h5>
          <p class="text-muted small">Multiple items from same school, great value.</p>
          <a href="shop.php" class="btn btn-outline-black rounded-pill mt-2 w-100">View Bundles →</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Featured banner - How it works for sellers -->
  <div class="container my-5">
    <div class="row g-0 bg-white rounded-4 overflow-hidden shadow-sm">
      <div class="col-md-6 p-4 p-lg-5 d-flex flex-column justify-content-center order-md-1 order-2">
        <h3 class="fw-bold">Sell Your Outgrown Uniforms</h3>
        <p class="mt-2">Kids grow fast, uniforms don't have to go to waste. List your pre-loved school uniforms for free, set your price, and connect with parents in your community. Earn money back while helping other families save.</p>
        <div class="mt-3">
          <?php if ($is_logged_in): ?>
            <a href="account-listings.php" class="btn btn-black rounded-pill me-2">Start Selling <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="login.php?redirect=sell" class="btn btn-black rounded-pill me-2">Start Selling <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
          <a href="how-to-sell.php" class="btn btn-outline-black rounded-pill">How It Works</a>
        </div>
        <div class="mt-4">
          <small class="text-muted"><i class="bi bi-shield-check text-black"></i> Safe & secure transactions | Verified community</small>
        </div>
      </div>
      <div class="col-md-6 order-md-2 order-1 p-0">
        <img src="images/sell-school-uniforms.jpg" class="img-fluid w-100 h-100 object-fit-cover" style="object-fit: cover; height: 100%; min-height: 240px;" alt="Sell school uniforms" onerror="this.src='images/placeholder.jpg'">
      </div>
    </div>
  </div>

</main>

<!-- Include Footer -->
<div id="footer-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Load header and footer from PHP files
  fetch('header.php')
    .then(response => response.text())
    .then(data => {
      document.getElementById('header-container').innerHTML = data;
    })
    .catch(error => console.error('Error loading header:', error));

  fetch('footer.php')
    .then(response => response.text())
    .then(data => {
      document.getElementById('footer-container').innerHTML = data;
    })
    .catch(error => console.error('Error loading footer:', error));

  // Initialize carousel after header loads
  setTimeout(() => {
    const myCarousel = document.querySelector('#newInCarousel');
    if (myCarousel) {
      const carousel = new bootstrap.Carousel(myCarousel, {
        interval: 4000,
        wrap: true,
        ride: 'carousel',
        pause: 'hover'
      });
    }
  }, 500);
</script>
</body>
</html>