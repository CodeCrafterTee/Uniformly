<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Safety Tips - UniformMarket</title>
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

    .safety-container {
      max-width: 1000px;
      margin: 0 auto;
    }

    .safety-card {
      background: white;
      border-radius: 28px;
      padding: 2.5rem;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
      border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .tip-card {
      background: #FEF9E6;
      border-left: 4px solid #000;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      border-radius: 16px;
      transition: all 0.2s;
    }

    .tip-card:hover {
      transform: translateX(5px);
      background: #FFFCF5;
    }

    .tip-icon {
      font-size: 2rem;
      margin-bottom: 1rem;
    }

    .tip-card h3 {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
    }

    .tip-card p {
      color: #4a4a4a;
      line-height: 1.6;
      margin-bottom: 0;
    }

    .warning-box {
      background: #fff5e8;
      border: 1px solid #ffd9a5;
      border-radius: 20px;
      padding: 1.5rem;
      margin: 2rem 0;
    }

    .badge-safety {
      background: black;
      color: white;
      padding: 0.3rem 0.8rem;
      border-radius: 30px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 1rem;
    }

    .do-card, .dont-card {
      padding: 1rem;
      border-radius: 16px;
      height: 100%;
    }

    .do-card {
      background: #e8f5e9;
      border-left: 4px solid #2e7d32;
    }

    .dont-card {
      background: #ffebee;
      border-left: 4px solid #c62828;
    }

    .back-to-top {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: black;
      color: white;
      width: 45px;
      height: 45px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      transition: all 0.3s;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 100;
    }

    .back-to-top:hover {
      background: #2c2c2c;
      transform: translateY(-3px);
      color: white;
    }

    @media (max-width: 768px) {
      .safety-card {
        padding: 1.5rem;
      }
      .tip-card h3 {
        font-size: 1.1rem;
      }
      .back-to-top {
        bottom: 1rem;
        right: 1rem;
        width: 40px;
        height: 40px;
      }
    }
  </style>
</head>
<body>

<div id="header-container"></div>

<main>
  <div class="container my-5 pt-4">
    <div class="safety-container">
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none">Home</a></li>
          <li class="breadcrumb-item active text-dark" aria-current="page">Safety Tips</li>
        </ol>
      </nav>

      <div class="safety-card">
        <div class="text-center mb-4">
          <i class="bi bi-shield-shaded fs-1" style="color: #000;"></i>
          <h1 class="display-6 fw-bold mt-2">Safety Tips</h1>
          <p class="lead text-muted">Your safety is our priority. Follow these guidelines for secure transactions.</p>
        </div>

        <!-- Emergency Warning -->
        <div class="warning-box">
          <i class="bi bi-exclamation-triangle-fill fs-3 me-2" style="color: #d9534f;"></i>
          <strong class="fs-5">Emergency?</strong> If you feel unsafe or witness suspicious behavior, <strong>trust your instincts</strong> and leave immediately. Report any serious concerns to local authorities.
        </div>

        <!-- Top Tips Section -->
        <div class="mb-5">
          <div class="badge-safety"><i class="bi bi-star-fill me-1"></i> GOLDEN RULES</div>
          <div class="row g-4 mt-2">
            <div class="col-md-6">
              <div class="do-card">
                <i class="bi bi-check-circle-fill fs-3" style="color: #2e7d32;"></i>
                <h4 class="fw-bold mt-2">DO's</h4>
                <ul class="mt-2 mb-0">
                  <li>Meet in public, well-lit places</li>
                  <li>Bring a friend or family member</li>
                  <li>Inspect items before paying</li>
                  <li>Use secure payment methods</li>
                  <li>Trust your instincts</li>
                  <li>Communicate through our platform</li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <div class="dont-card">
                <i class="bi bi-x-circle-fill fs-3" style="color: #c62828;"></i>
                <h4 class="fw-bold mt-2">DON'Ts</h4>
                <ul class="mt-2 mb-0">
                  <li>Don't share personal address initially</li>
                  <li>Don't wire money or use untraceable payments</li>
                  <li>Don't meet alone in isolated locations</li>
                  <li>Don't ignore red flags</li>
                  <li>Don't share sensitive financial details</li>
                  <li>Don't rush transactions</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Tips -->
        <h2 class="fw-bold mb-4">Essential Safety Guidelines</h2>

        <div class="tip-card">
          <div class="tip-icon"><i class="bi bi-chat-dots-fill"></i></div>
          <h3>1. Communicate Safely</h3>
          <p>Always use Uniformly's messaging system for all communication. This keeps a record of your conversations and protects your privacy. Be wary of users who insist on moving conversations to external apps or sharing personal contact information immediately. Report any suspicious or inappropriate messages to our support team.</p>
        </div>

        <div class="tip-card">
          <div class="tip-icon"><i class="bi bi-person-check-fill"></i></div>
          <h3>2. Verify Sellers & Buyers</h3>
          <p>Check user profiles for reviews and ratings. Long-standing members with positive feedback are generally more trustworthy. Ask questions about the uniform's condition, size, and history. Request additional photos if needed. A legitimate seller will be happy to provide more information about their listing.</p>
        </div>

        <div class="tip-card">
          <div class="tip-icon"><i class="bi bi-geo-alt-fill"></i></div>
          <h3>3. Safe Meeting Locations</h3>
          <p>For local pickups, always choose public, well-trafficked locations. Recommended meeting spots include:</p>
          <ul class="mt-2">
            <li>Police station parking lots (many have designated online exchange zones)</li>
            <li>Bank lobbies or shopping center food courts</li>
            <li>Coffee shops or libraries</li>
            <li>School parking lots during busy hours</li>
          </ul>
          <p class="mt-2 mb-0">Never invite strangers to your home or agree to meet in isolated areas. Always let someone know where you're going and when you expect to return.</p>
        </div>

        <div class="tip-card">
          <div class="tip-icon"><i class="bi bi-credit-card"></i></div>
          <h3>4. Secure Payment Methods</h3>
          <p>Use traceable payment methods that offer some protection. Recommended options:</p>
          <ul class="mt-2">
            <li><strong>Cash:</strong> Only use for in-person meetings. Count bills carefully.</li>
            <li><strong>Bank Transfer/EFT:</strong> Traceable and secure for verified users.</li>
            <li><strong>Payment Apps:</strong> Use apps like PayPal Goods & Services (offers buyer protection).</li>
          </ul>
          <p class="mt-2 mb-0"><strong>Red Flags:</strong> Avoid wire transfers, cryptocurrency, or requests for payment via gift cards. Never send payment before inspecting items unless using a protected payment service.</p>
        </div>

        <div class="tip-card">
          <div class="tip-icon"><i class="bi bi-search"></i></div>
          <h3>5. Inspect Before You Buy</h3>
          <p>Always examine uniforms carefully before completing the purchase. Check for:</p>
          <ul class="mt-2">
            <li>Stains, tears, or excessive wear</li>
            <li>Correct sizing (bring your child's measurements)</li>
            <li>School logos and labeling accuracy</li>
            <li>Zippers, buttons, and elastic condition</li>
            <li>Any odors or damage not visible in photos</li>
          </ul>
          <p class="mt-2 mb-0">If the item doesn't match the description, you have the right to walk away from the transaction.</p>
        </div>

        <div class="tip-card">
          <div class="tip-icon"><i class="bi bi-flag-fill"></i></div>
          <h3>6. Trust Your Instincts</h3>
          <p>If something feels wrong, it probably is. Warning signs include:</p>
          <ul class="mt-2">
            <li>Sellers pressuring you to pay quickly</li>
            <li>Prices that seem too good to be true</li>
            <li>Vague answers about item condition</li>
            <li>Reluctance to share additional photos</li>
            <li>Requests to meet at odd hours or secluded places</li>
          </ul>
          <p class="mt-2 mb-0">Cancel the transaction and report suspicious users to our support team immediately.</p>
        </div>

        <div class="tip-card">
          <div class="tip-icon"><i class="bi bi-envelope-paper-fill"></i></div>
          <h3>7. Shipping Safety</h3>
          <p>If shipping items instead of meeting locally:</p>
          <ul class="mt-2">
            <li>Use trackable shipping services with insurance</li>
            <li>Get proof of postage and tracking numbers</li>
            <li>Package items securely to prevent damage</li>
            <li>For high-value items, require signature confirmation</li>
            <li>Consider using payment services with escrow protection</li>
          </ul>
        </div>

        <div class="tip-card">
          <div class="tip-icon"><i class="bi bi-chat-quote-fill"></i></div>
          <h3>8. For Parents Selling Uniforms</h3>
          <p>When meeting buyers:</p>
          <ul class="mt-2">
            <li>Bring a friend or family member along</li>
            <li>Share your meeting location with a trusted contact</li>
            <li>Stick to daytime hours for meetings</li>
            <li>Keep your phone charged and accessible</li>
            <li>Consider meeting near your child's school during pickup/dropoff times</li>
          </ul>
        </div>

        <!-- Reporting Section -->
        <div class="tip-card" style="background: #000; color: white;">
          <div class="tip-icon"><i class="bi bi-megaphone-fill" style="color: white;"></i></div>
          <h3 style="color: white;">Report Suspicious Activity</h3>
          <p style="color: #e0e0e0;">Help keep our community safe. If you encounter:</p>
          <ul style="color: #e0e0e0;">
            <li>Fake listings or scam attempts</li>
            <li>Harassment or inappropriate messages</li>
            <li>Suspicious meeting requests</li>
            <li>Counterfeit items or stolen goods</li>
          </ul>
          <p style="color: #e0e0e0;">Report immediately using the "Report" button on listings or messages, or email <strong>safety@uniformly.com</strong>. We investigate all reports and take appropriate action.</p>
        </div>

        <!-- Additional Resources -->
        <div class="mt-5 pt-3">
          <h3 class="fw-bold mb-3">Additional Resources</h3>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="border rounded-4 p-3 text-center h-100">
                <i class="bi bi-file-text fs-2"></i>
                <p class="mt-2 mb-0"><a href="terms-of-use.php" class="text-dark fw-bold">Terms of Use</a></p>
                <small class="text-muted">Read our platform policies</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="border rounded-4 p-3 text-center h-100">
                <i class="bi bi-question-circle fs-2"></i>
                <p class="mt-2 mb-0"><a href="help-center.php" class="text-dark fw-bold">Help Center</a></p>
                <small class="text-muted">FAQs and support articles</small>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-5 pt-3">
          <a href="index.php" class="btn btn-black rounded-pill px-5 me-3">Back to Home</a>
          <a href="#" class="btn btn-outline-black rounded-pill px-5">Contact Support</a>
        </div>
      </div>
    </div>
  </div>
</main>

<div id="footer-container"></div>

<a href="#" class="back-to-top" id="backToTop">
  <i class="bi bi-arrow-up"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Load header and footer
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

  // Back to top button
  const backToTop = document.getElementById('backToTop');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
      backToTop.style.display = 'flex';
    } else {
      backToTop.style.display = 'none';
    }
  });
  backToTop.style.display = 'none';
</script>
</body>
</html>