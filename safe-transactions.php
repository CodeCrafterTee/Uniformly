<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Safe Transactions - UniformMarket</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
    .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.8rem; border-radius: 40px; transition: all 0.25s ease; }
    .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
    .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
    .safety-card { background: white; border-radius: 28px; padding: 2rem; margin-bottom: 1.5rem; border: 1px solid rgba(0, 0, 0, 0.05); }
    .meeting-location { background: #FEF9E6; border-radius: 20px; padding: 1rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 1rem; }
    .red-flag { background: #ffebee; border-left: 4px solid #c62828; padding: 1rem 1.5rem; border-radius: 16px; margin: 1rem 0; }
    .green-flag { background: #e8f5e9; border-left: 4px solid #2e7d32; padding: 1rem 1.5rem; border-radius: 16px; margin: 1rem 0; }
    .back-to-top { position: fixed; bottom: 2rem; right: 2rem; background: black; color: white; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; z-index: 100; }
    @media (max-width: 768px) { .safety-card { padding: 1.5rem; } .meeting-location { flex-direction: column; text-align: center; } }
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
            <li class="breadcrumb-item active text-dark" aria-current="page">Safe Transactions</li>
          </ol>
        </nav>

        <div class="text-center mb-5">
          <i class="bi bi-shield-check fs-1" style="color: #000;"></i>
          <h1 class="display-5 fw-bold mt-2">Safe Transactions</h1>
          <p class="lead text-muted">Your safety is our priority. Follow these guidelines for secure buying and selling.</p>
        </div>

        <!-- Golden Rules -->
        <div class="safety-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-star-fill me-2"></i> Golden Rules</h3>
          <div class="row">
            <div class="col-md-6">
              <div class="green-flag">
                <i class="bi bi-check-circle-fill me-2"></i> <strong>DO:</strong>
                <ul class="mt-2 mb-0">
                  <li>Meet in public, well-lit places</li>
                  <li>Bring a friend or family member</li>
                  <li>Inspect items before paying</li>
                  <li>Use secure payment methods</li>
                  <li>Trust your instincts</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="red-flag">
                <i class="bi bi-x-circle-fill me-2"></i> <strong>DON'T:</strong>
                <ul class="mt-2 mb-0">
                  <li>Share personal address initially</li>
                  <li>Wire money or use untraceable payments</li>
                  <li>Meet alone in isolated locations</li>
                  <li>Ignore red flags</li>
                  <li>Rush transactions</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Safe Meeting Locations -->
        <div class="safety-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2"></i> Recommended Meeting Locations</h3>
          <div class="meeting-location">
            <i class="bi bi-building fs-3"></i>
            <div><strong>Police Station Lobbies</strong><br>Many police stations have designated online exchange zones with 24/7 surveillance</div>
          </div>
          <div class="meeting-location">
            <i class="bi bi-bank fs-3"></i>
            <div><strong>Bank Lobbies</strong><br>Well-lit, secure, and monitored by cameras</div>
          </div>
          <div class="meeting-location">
            <i class="bi bi-cup-straw fs-3"></i>
            <div><strong>Coffee Shops / Cafés</strong><br>Public, busy locations with plenty of witnesses</div>
          </div>
          <div class="meeting-location">
            <i class="bi bi-cart fs-3"></i>
            <div><strong>Shopping Center Food Courts</strong><br>High traffic areas during business hours</div>
          </div>
          <div class="meeting-location">
            <i class="bi bi-backpack fs-3"></i>
            <div><strong>School Parking Lots</strong><br>During pickup/dropoff times when many parents are present</div>
          </div>
        </div>

        <!-- Red Flags to Watch For -->
        <div class="safety-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-flag-fill me-2"></i> Red Flags to Watch For</h3>
          <div class="red-flag">
            <strong>⚠️ Suspicious Behavior:</strong>
            <ul class="mt-2 mb-0">
              <li>Prices that seem too good to be true</li>
              <li>Sellers pressuring you to pay quickly</li>
              <li>Vague answers about item condition</li>
              <li>Reluctance to share additional photos</li>
              <li>Requests to meet at odd hours or secluded places</li>
              <li>Buyers offering to pay more than asking price</li>
              <li>Requests for payment via gift cards or wire transfer</li>
            </ul>
          </div>
          <div class="mt-3">
            <strong>If you encounter any red flags:</strong> Cancel the transaction and report the user to our support team immediately.
          </div>
        </div>

        <!-- Payment Safety -->
        <div class="safety-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-cash-stack me-2"></i> Secure Payment Methods</h3>
          <div class="green-flag">
            <i class="bi bi-check-circle-fill me-2"></i> <strong>Recommended:</strong>
            <ul class="mt-2 mb-0">
              <li><strong>Cash:</strong> Only for in-person meetings. Count carefully and meet in safe locations.</li>
              <li><strong>PayPal Goods & Services:</strong> Offers buyer protection and is traceable.</li>
              <li><strong>Bank Transfer / EFT:</strong> Traceable and secure for verified users.</li>
              <li><strong>Venmo / CashApp:</strong> Only with trusted users; use with caution.</li>
            </ul>
          </div>
          <div class="red-flag mt-3">
            <i class="bi bi-x-circle-fill me-2"></i> <strong>Avoid These:</strong>
            <ul class="mt-2 mb-0">
              <li>Wire transfers (Western Union, MoneyGram)</li>
              <li>Gift cards (iTunes, Google Play, etc.)</li>
              <li>Cryptocurrency payments</li>
              <li>Checks from unknown individuals</li>
            </ul>
          </div>
        </div>

        <!-- For Sellers -->
        <div class="safety-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-person-workspace me-2"></i> Tips for Sellers</h3>
          <ul>
            <li><strong>Verify buyer:</strong> Check their profile and any reviews before meeting</li>
            <li><strong>Communicate through platform:</strong> Keep all conversations on UniformMarket</li>
            <li><strong>Bring a friend:</strong> Never meet a buyer alone, especially for high-value items</li>
            <li><strong>Count payment:</strong> Count cash carefully before handing over items</li>
            <li><strong>Provide receipt:</strong> A simple receipt protects both parties</li>
            <li><strong>Mark as sold:</strong> Update your listing immediately after sale</li>
          </ul>
        </div>

        <!-- For Buyers -->
        <div class="safety-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-person-check me-2"></i> Tips for Buyers</h3>
          <ul>
            <li><strong>Inspect before paying:</strong> Check condition thoroughly, especially sizing and stains</li>
            <li><strong>Ask questions:</strong> Request additional photos if needed</li>
            <li><strong>Trust your gut:</strong> If something feels off, walk away</li>
            <li><strong>Bring exact change:</strong> Avoid carrying large amounts of cash</li>
            <li><strong>Test items:</strong> For electronics or bags, test functionality before paying</li>
            <li><strong>Leave feedback:</strong> Help other buyers by sharing your experience</li>
          </ul>
        </div>

        <!-- For Shipping Transactions -->
        <div class="safety-card">
          <h3 class="fw-bold mb-3"><i class="bi bi-box-seam me-2"></i> Shipping Safety Tips</h3>
          <ul>
            <li><strong>Use tracked shipping:</strong> Always use services with tracking numbers</li>
            <li><strong>Insurance:</strong> Consider insurance for high-value items</li>
            <li><strong>Package securely:</strong> Protect items from damage during transit</li>
            <li><strong>Take photos:</strong> Document packaging process for disputes</li>
            <li><strong>Signature confirmation:</strong> Require signature for valuable items</li>
            <li><strong>Use protected payments:</strong> PayPal Goods & Services offers buyer protection</li>
          </ul>
        </div>

        <!-- Report Issues -->
        <div class="safety-card bg-dark text-white" style="background: #000; color: white;">
          <h3 class="fw-bold mb-3"><i class="bi bi-megaphone-fill me-2"></i> Report Suspicious Activity</h3>
          <p>If you encounter any suspicious behavior, scams, or safety concerns, report immediately:</p>
          <ul>
            <li><strong>Email:</strong> safety@uniformly.com</li>
            <li><strong>In-app:</strong> Click "Report" on any listing or message</li>
            <li><strong>Emergency:</strong> Contact local authorities immediately</li>
          </ul>
          <p class="mb-0 mt-2">We investigate all reports and take appropriate action to protect our community.</p>
        </div>

        <div class="text-center mt-4">
          <a href="safety-tips.php" class="btn btn-outline-black rounded-pill px-4 me-2">More Safety Tips</a>
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