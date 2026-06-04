<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>How to Sell - UniformMarket</title>
  <!-- Bootstrap 5 CSS + Icons + custom font -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
    .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.8rem; border-radius: 40px; transition: all 0.25s ease; }
    .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
    .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
    .btn-outline-black:hover { background-color: #000000; color: white; }
    .step-card { background: white; border-radius: 28px; padding: 2rem; margin-bottom: 1.5rem; transition: all 0.2s; border: 1px solid rgba(0, 0, 0, 0.05); }
    .step-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08); }
    .step-number { width: 50px; height: 50px; background: #000; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; }
    .tip-box { background: #FEF9E6; border-left: 4px solid #000; padding: 1rem 1.5rem; border-radius: 16px; margin: 1rem 0; }
    .back-to-top { position: fixed; bottom: 2rem; right: 2rem; background: black; color: white; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; z-index: 100; }
    .back-to-top:hover { background: #2c2c2c; transform: translateY(-3px); color: white; }
    @media (max-width: 768px) { .step-card { padding: 1.5rem; } .back-to-top { bottom: 1rem; right: 1rem; width: 40px; height: 40px; } }
  </style>
</head>
<body>

<div id="header-container"></div>

<main>
  <div class="container my-5 pt-4">
    <div class="row">
      <div class="col-lg-8 mx-auto">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="#" class="text-dark text-decoration-none">Sell & Support</a></li>
            <li class="breadcrumb-item active text-dark" aria-current="page">How to Sell</li>
          </ol>
        </nav>

        <div class="text-center mb-5">
          <i class="bi bi-cash-stack fs-1" style="color: #000;"></i>
          <h1 class="display-5 fw-bold mt-2">How to Sell</h1>
          <p class="lead text-muted">Turn outgrown uniforms into cash in 4 simple steps</p>
        </div>

        <!-- Step 1 -->
        <div class="step-card">
          <div class="step-number">1</div>
          <h3 class="fw-bold mb-3">Create Your Free Account</h3>
          <p>Sign up for a UniformMarket account - it's completely free! Provide your basic information and verify your email address to get started. As a registered seller, you'll be able to list items, manage your inventory, and connect with buyers in your community.</p>
          <div class="tip-box">
            <i class="bi bi-star-fill me-2"></i> <strong>Pro Tip:</strong> Complete your profile with a photo and bio to build trust with potential buyers. Sellers with complete profiles sell 3x faster!
          </div>
          <a href="register.php" class="btn btn-black rounded-pill mt-2">Create Free Account →</a>
        </div>

        <!-- Step 2 -->
        <div class="step-card">
          <div class="step-number">2</div>
          <h3 class="fw-bold mb-3">Prepare Your Uniforms</h3>
          <p>Before listing, make sure your uniforms are ready for their next home:</p>
          <ul class="mb-3">
            <li><strong>Clean thoroughly:</strong> Wash or dry clean all items</li>
            <li><strong>Inspect condition:</strong> Check for stains, tears, or missing buttons</li>
            <li><strong>Take great photos:</strong> Use natural lighting and show all angles</li>
            <li><strong>Measure accurately:</strong> Provide exact sizing information</li>
          </ul>
          <div class="tip-box">
            <i class="bi bi-camera me-2"></i> <strong>Photo Tips:</strong> Take photos of the front, back, tags, and any imperfections. Well-lit photos sell 70% faster!
          </div>
        </div>

        <!-- Step 3 -->
        <div class="step-card">
          <div class="step-number">3</div>
          <h3 class="fw-bold mb-3">Create Your Listing</h3>
          <p>Click "Sell Your Uniform" and fill out the listing form with:</p>
          <ul>
            <li><strong>School Name:</strong> Be specific about which school the uniform is from</li>
            <li><strong>Item Type:</strong> Blazer, shirt, pants, skirt, bag, etc.</li>
            <li><strong>Size:</strong> Include both labeled size and measurements</li>
            <li><strong>Condition:</strong> Be honest about wear and tear</li>
            <li><strong>Price:</strong> Set a fair price (typically 30-50% of retail)</li>
            <li><strong>Description:</strong> Include details about the uniform's history</li>
          </ul>
          <div class="tip-box">
            <i class="bi bi-tag me-2"></i> <strong>Pricing Guide:</strong> Like New: 50-70% retail, Excellent: 40-50% retail, Good: 30-40% retail, Fair: 20-30% retail
          </div>
        </div>

        <!-- Step 4 -->
        <div class="step-card">
          <div class="step-number">4</div>
          <h3 class="fw-bold mb-3">Connect & Complete the Sale</h3>
          <p>When a buyer is interested:</p>
          <ul>
            <li>Communicate through our messaging system</li>
            <li>Answer questions promptly about condition and sizing</li>
            <li>Arrange a safe meeting location (public places like police stations, banks, or coffee shops)</li>
            <li>Accept payment (cash or secure payment apps recommended)</li>
            <li>Mark the item as sold after completion</li>
          </ul>
          <div class="tip-box">
            <i class="bi bi-shield-check me-2"></i> <strong>Safety First:</strong> Always meet in public, well-lit areas. Bring a friend if possible. Never share unnecessary personal information.
          </div>
        </div>

        <!-- Success Stories -->
        <div class="bg-white rounded-4 p-4 mt-4">
          <h4 class="fw-bold mb-3">Seller Success Stories</h4>
          <div class="row g-3">
            <div class="col-md-4">
              <div class="text-center p-3 bg-cream rounded-3">
                <i class="bi bi-emoji-smile fs-2"></i>
                <p class="mt-2 mb-0 fw-semibold">Sarah M.</p>
                <small class="text-muted">"Sold 5 uniforms in 2 weeks!"</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center p-3 bg-cream rounded-3">
                <i class="bi bi-emoji-smile fs-2"></i>
                <p class="mt-2 mb-0 fw-semibold">Michael T.</p>
                <small class="text-muted">"Made $200 from outgrown blazers"</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center p-3 bg-cream rounded-3">
                <i class="bi bi-emoji-smile fs-2"></i>
                <p class="mt-2 mb-0 fw-semibold">Jennifer R.</p>
                <small class="text-muted">"Quick and easy process!"</small>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-5">
          <a href="register.php" class="btn btn-black btn-lg rounded-pill px-5">Start Selling Today</a>
          <a href="selling-guidelines.php" class="btn btn-outline-black btn-lg rounded-pill px-5 ms-3">View Guidelines →</a>
        </div>
      </div>
    </div>
  </div>
</main>

<div id="footer-container"></div>
<a href="#" class="back-to-top" id="backToTop"><i class="bi bi-arrow-up"></i></a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
  fetch('header.php').then(r=>r.text()).then(d=>document.getElementById('header-container').innerHTML=d);
  fetch('footer.php').then(r=>r.text()).then(d=>document.getElementById('footer-container').innerHTML=d);
  const backToTop=document.getElementById('backToTop');
  window.addEventListener('scroll',()=>{backToTop.style.display=window.scrollY>300?'flex':'none';});
  backToTop.style.display='none';
</script>
</body>
</html>