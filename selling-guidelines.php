<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Selling Guidelines - UniformMarket</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
    .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.8rem; border-radius: 40px; transition: all 0.25s ease; }
    .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
    .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
    .guideline-card { background: white; border-radius: 28px; padding: 2rem; margin-bottom: 1.5rem; border: 1px solid rgba(0, 0, 0, 0.05); }
    .do-card { background: #e8f5e9; border-left: 4px solid #2e7d32; padding: 1rem 1.5rem; border-radius: 16px; margin: 1rem 0; }
    .dont-card { background: #ffebee; border-left: 4px solid #c62828; padding: 1rem 1.5rem; border-radius: 16px; margin: 1rem 0; }
    .condition-badge { display: inline-block; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-right: 0.5rem; }
    .back-to-top { position: fixed; bottom: 2rem; right: 2rem; background: black; color: white; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; z-index: 100; }
    @media (max-width: 768px) { .guideline-card { padding: 1.5rem; } }
  </style>
</head>
<body>

<div id="header-container"></div>

<main>
  <div class="container my-5 pt-4">
    <div class="row">
      <div class="col-lg-8 mx-auto">
        <nav aria-label="breadcrumb" class="mb-4">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="#" class="text-dark text-decoration-none">Sell & Support</a></li>
            <li class="breadcrumb-item active text-dark" aria-current="page">Selling Guidelines</li>
          </ol>
        </nav>

        <div class="text-center mb-5">
          <i class="bi bi-file-text-fill fs-1" style="color: #000;"></i>
          <h1 class="display-5 fw-bold mt-2">Selling Guidelines</h1>
          <p class="lead text-muted">Everything you need to know to sell successfully</p>
        </div>

        <!-- What You Can Sell -->
        <div class="guideline-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-check-circle-fill me-2" style="color: #2e7d32;"></i> What You Can Sell</h3>
          <p>UniformMarket is specifically designed for pre-loved school items. Approved items include:</p>
          <ul>
            <li>School uniforms (shirts, blazers, pants, skirts, dresses)</li>
            <li>School bags and backpacks</li>
            <li>Sports uniforms and PE kits</li>
            <li>School accessories (ties, belts, hats, scarves)</li>
            <li>School shoes (in good condition)</li>
            <li>Textbooks and school supplies</li>
          </ul>
          <div class="dont-card">
            <i class="bi bi-x-circle-fill me-2" style="color: #c62828;"></i> <strong>Not Allowed:</strong> Counterfeit items, damaged beyond use, items with stains that cannot be removed, non-school related items, or items that violate school policies.
          </div>
        </div>

        <!-- Condition Guidelines -->
        <div class="guideline-card">
          <h3 class="fw-bold mb-3">Condition Guidelines</h3>
          <p>Be honest about your item's condition. Use these standard ratings:</p>
          <div class="row g-3 mt-2">
            <div class="col-md-3">
              <div class="text-center p-3 bg-light rounded-3">
                <span class="condition-badge" style="background: #2e7d32; color: white;">Like New</span>
                <p class="small mt-2 mb-0">Barely worn, no signs of use</p>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center p-3 bg-light rounded-3">
                <span class="condition-badge" style="background: #689f38; color: white;">Excellent</span>
                <p class="small mt-2 mb-0">Minor wear, no flaws</p>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center p-3 bg-light rounded-3">
                <span class="condition-badge" style="background: #ff9800; color: white;">Good</span>
                <p class="small mt-2 mb-0">Some signs of use, fully functional</p>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center p-3 bg-light rounded-3">
                <span class="condition-badge" style="background: #9e9e9e; color: white;">Fair</span>
                <p class="small mt-2 mb-0">Visible wear, but still usable</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Photo Guidelines -->
        <div class="guideline-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-camera me-2"></i> Photo Guidelines</h3>
          <p>Great photos sell items faster. Follow these tips:</p>
          <ul>
            <li><strong>Take 3-5 photos:</strong> Front, back, tags, close-up of details, and any imperfections</li>
            <li><strong>Use natural lighting:</strong> Avoid flash and harsh shadows</li>
            <li><strong>Show measurements:</strong> Place a measuring tape next to the item</li>
            <li><strong>Clean background:</strong> Use a plain wall or neutral surface</li>
            <li><strong>Be honest:</strong> Show any flaws clearly</li>
          </ul>
          <div class="do-card">
            <i class="bi bi-check-circle-fill me-2"></i> <strong>Pro Tip:</strong> Include a photo of the item being worn (with face hidden for privacy) to show fit and style!
          </div>
        </div>

        <!-- Pricing Guide -->
        <div class="guideline-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-tag me-2"></i> Pricing Guide</h3>
          <p>Set fair prices that attract buyers. Use these guidelines:</p>
          <table class="table table-bordered mt-3">
            <thead class="bg-light">
              <tr><th>Condition</th><th>Suggested Price</th><th>Example (R50 retail)</th></tr>
            </thead>
            <tbody>
              <tr><td>Like New</td><td>50-70% of retail</td><td>R25-R35</td></tr>
              <tr><td>Excellent</td><td>40-50% of retail</td><td>R20-R25</td></tr>
              <tr><td>Good</td><td>30-40% of retail</td><td>R15-R20</td></tr>
              <tr><td>Fair</td><td>20-30% of retail</td><td>R10-R15</td></tr>
            </tbody>
          </table>
          <div class="tip-box bg-light p-3 rounded-3 mt-3">
            <i class="bi bi-graph-up me-2"></i> <strong>Bundle Discount:</strong> Offer discounts for buying multiple items from the same school to encourage larger sales!
          </div>
        </div>

        <!-- Listing Best Practices -->
        <div class="guideline-card">
          <h3 class="fw-bold mb-3">Listing Best Practices</h3>
          <div class="row">
            <div class="col-md-6">
              <div class="do-card">
                <i class="bi bi-check-circle-fill me-2"></i> <strong>DO:</strong>
                <ul class="mt-2 mb-0">
                  <li>Include school name in title</li>
                  <li>Specify exact size and measurements</li>
                  <li>Mention if item has been altered</li>
                  <li>Respond quickly to inquiries</li>
                  <li>Mark as sold immediately</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="dont-card">
                <i class="bi bi-x-circle-fill me-2"></i> <strong>DON'T:</strong>
                <ul class="mt-2 mb-0">
                  <li>Exaggerate condition</li>
                  <li>Use stock photos</li>
                  <li>List items not in your possession</li>
                  <li>Share personal contact info</li>
                  <li>Ignore buyer messages</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Transaction Tips -->
        <div class="guideline-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-cash-stack me-2"></i> Transaction Tips</h3>
          <ul>
            <li><strong>Meeting:</strong> Always meet in public, well-lit locations (police station, bank lobby, coffee shop)</li>
            <li><strong>Payment:</strong> Accept cash or secure payment apps. Never accept checks or wire transfers</li>
            <li><strong>Inspection:</strong> Allow buyers to inspect items before payment</li>
            <li><strong>Receipt:</strong> Provide a simple receipt for the transaction</li>
            <li><strong>Feedback:</strong> Leave honest feedback after the sale</li>
          </ul>
          <div class="tip-box bg-light p-3 rounded-3 mt-3">
            <i class="bi bi-shield-check me-2"></i> <strong>Safety Reminder:</strong> Read our <a href="safety-tips.php" class="text-dark fw-bold">Safety Tips</a> for detailed guidance on safe transactions!
          </div>
        </div>

        <div class="text-center mt-4">
          <a href="how-to-sell.php" class="btn btn-outline-black rounded-pill px-4 me-2">How to Sell</a>
          <a href="help-center.php" class="btn btn-outline-black rounded-pill px-4">Help Center</a>
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