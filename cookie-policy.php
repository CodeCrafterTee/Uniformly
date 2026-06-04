<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Cookie Policy - UniformMarket</title>
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

    .policy-container {
      max-width: 900px;
      margin: 0 auto;
    }

    .policy-card {
      background: white;
      border-radius: 28px;
      padding: 2.5rem;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
      border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .policy-section {
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid #f0e8dc;
    }

    .policy-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .policy-section h3 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: #000;
    }

    .policy-section h4 {
      font-size: 1.2rem;
      font-weight: 600;
      margin-top: 1.2rem;
      margin-bottom: 0.8rem;
      color: #2c2c2c;
    }

    .policy-section p {
      line-height: 1.6;
      color: #4a4a4a;
      margin-bottom: 1rem;
    }

    .policy-section ul, .policy-section ol {
      margin-bottom: 1rem;
      padding-left: 1.5rem;
    }

    .policy-section li {
      margin-bottom: 0.5rem;
      line-height: 1.6;
      color: #4a4a4a;
    }

    .last-updated {
      background: #f5efe0;
      padding: 1rem 1.5rem;
      border-radius: 16px;
      margin-bottom: 2rem;
      font-size: 0.9rem;
    }

    .cookie-table {
      width: 100%;
      border-collapse: collapse;
      margin: 1.5rem 0;
      font-size: 0.9rem;
    }

    .cookie-table th, .cookie-table td {
      border: 1px solid #e0d8cc;
      padding: 0.75rem;
      text-align: left;
      vertical-align: top;
    }

    .cookie-table th {
      background: #f5efe0;
      font-weight: 600;
    }

    .cookie-category {
      background: #FEF9E6;
      border-radius: 12px;
      padding: 1rem;
      margin: 1rem 0;
    }

    .cookie-category h5 {
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .preference-controls {
      background: #f5efe0;
      border-radius: 20px;
      padding: 1.5rem;
      margin: 1.5rem 0;
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
      .policy-card {
        padding: 1.5rem;
      }
      .policy-section h3 {
        font-size: 1.3rem;
      }
      .back-to-top {
        bottom: 1rem;
        right: 1rem;
        width: 40px;
        height: 40px;
      }
      .cookie-table {
        font-size: 0.75rem;
      }
      .cookie-table th, .cookie-table td {
        padding: 0.5rem;
      }
    }
  </style>
</head>
<body>

<div id="header-container"></div>

<main>
  <div class="container my-5 pt-4">
    <div class="policy-container">
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none">Home</a></li>
          <li class="breadcrumb-item active text-dark" aria-current="page">Cookie Policy</li>
        </ol>
      </nav>

      <div class="policy-card">
        <div class="text-center mb-4">
          <i class="bi bi-cookie fs-1" style="color: #000;"></i>
          <h1 class="display-6 fw-bold mt-2">Cookie Policy</h1>
          <p class="text-muted">How we use cookies to improve your experience</p>
        </div>

        <div class="last-updated">
          <i class="bi bi-calendar-check me-2"></i> Last Updated: March 31, 2026
          <br><small class="text-muted">This policy explains our use of cookies and similar technologies</small>
        </div>

        <div class="policy-section">
          <h3>1. What Are Cookies?</h3>
          <p>Cookies are small text files that are placed on your device (computer, smartphone, tablet) when you visit websites. They help websites remember information about your visit, preferences, and actions over time. Cookies are widely used to make websites work more efficiently and provide a better user experience.</p>
          <p>We use cookies and similar technologies such as local storage, pixels, and tracking scripts to enhance your experience on UniformMarket. This Cookie Policy explains what cookies we use, why we use them, and how you can control them.</p>
        </div>

        <div class="policy-section">
          <h3>2. Types of Cookies We Use</h3>
          
          <div class="cookie-category">
            <h5><i class="bi bi-check-circle-fill me-2" style="color: #2e7d32;"></i> Strictly Necessary Cookies</h5>
            <p>These cookies are essential for the platform to function properly. They enable core functionality such as security, account login, and transaction processing. You cannot opt out of these cookies as the platform would not work properly without them.</p>
            <ul>
              <li><strong>Session Cookies:</strong> Keep you logged in while browsing</li>
              <li><strong>Security Cookies:</strong> Help detect fraud and protect your account</li>
              <li><strong>Load Balancing Cookies:</strong> Ensure platform stability</li>
            </ul>
          </div>

          <div class="cookie-category">
            <h5><i class="bi bi-sliders2 me-2"></i> Functional Cookies</h5>
            <p>These cookies enhance functionality and personalization. They remember your preferences and choices to improve your experience.</p>
            <ul>
              <li><strong>Preference Cookies:</strong> Remember your language and region settings</li>
              <li><strong>UI Customization:</strong> Save your layout preferences</li>
              <li><strong>Recently Viewed:</strong> Track items you've viewed to help you find them later</li>
            </ul>
          </div>

          <div class="cookie-category">
            <h5><i class="bi bi-graph-up me-2"></i> Analytics & Performance Cookies</h5>
            <p>These cookies help us understand how users interact with our platform, allowing us to improve functionality and performance.</p>
            <ul>
              <li><strong>Google Analytics:</strong> Track page visits, time on site, and user flow</li>
              <li><strong>Usage Patterns:</strong> Identify popular features and areas for improvement</li>
              <li><strong>Error Tracking:</strong> Detect and fix technical issues</li>
            </ul>
          </div>

          <div class="cookie-category">
            <h5><i class="bi bi-megaphone me-2"></i> Marketing & Advertising Cookies</h5>
            <p>These cookies help us deliver relevant content and measure the effectiveness of our communications. We respect your privacy and only use these with your consent.</p>
            <ul>
              <li><strong>Retargeting:</strong> Show relevant listings you might be interested in</li>
              <li><strong>Campaign Tracking:</strong> Measure the success of our outreach</li>
              <li><strong>Social Media Integration:</strong> Allow sharing of content on social platforms</li>
            </ul>
          </div>
        </div>

        <div class="policy-section">
          <h3>3. Detailed Cookie List</h3>
          <p>The following table lists the specific cookies we use on UniformMarket:</p>
          
          <table class="cookie-table">
            <thead>
              <tr>
                <th>Cookie Name</th>
                <th>Purpose</th>
                <th>Duration</th>
                <th>Type</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>uniform_session</code></td>
                <td>Maintains your login session</td>
                <td>Session</td>
                <td>Strictly Necessary</td>
              </tr>
              <tr>
                <td><code>csrf_token</code></td>
                <td>Prevents cross-site request forgery attacks</td>
                <td>Session</td>
                <td>Strictly Necessary</td>
              </tr>
              <tr>
                <td><code>user_preferences</code></td>
                <td>Stores your language and notification preferences</td>
                <td>1 year</td>
                <td>Functional</td>
              </tr>
              <tr>
                <td><code>recently_viewed</code></td>
                <td>Tracks uniforms you've recently viewed</td>
                <td>30 days</td>
                <td>Functional</td>
              </tr>
              <tr>
                <td><code>_ga</code></td>
                <td>Google Analytics - distinguishes unique users</td>
                <td>2 years</td>
                <td>Analytics</td>
              </tr>
              <tr>
                <td><code>_gid</code></td>
                <td>Google Analytics - tracks user sessions</td>
                <td>24 hours</td>
                <td>Analytics</td>
              </tr>
              <tr>
                <td><code>_fbp</code></td>
                <td>Facebook Pixel - tracks ad effectiveness</td>
                <td>90 days</td>
                <td>Marketing</td>
              </tr>
              <tr>
                <td><code>cookie_consent</code></td>
                <td>Records your cookie preference choices</td>
                <td>1 year</td>
                <td>Strictly Necessary</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="policy-section">
          <h3>4. How to Control Cookies</h3>
          <p>You have the right to accept or reject cookies. Here's how you can manage your cookie preferences:</p>
          
          <div class="preference-controls">
            <h5><i class="bi bi-gear-fill me-2"></i> Manage Your Preferences</h5>
            <p>You can manage your cookie preferences at any time by:</p>
            <ul class="mb-0">
              <li><strong>Browser Settings:</strong> Most browsers allow you to block or delete cookies through their settings menu. Here are links to popular browser instructions:</li>
              <ul class="mt-2 mb-2">
                <li><a href="#" class="text-dark">Chrome</a> | <a href="#" class="text-dark">Firefox</a> | <a href="#" class="text-dark">Safari</a> | <a href="#" class="text-dark">Edge</a></li>
              </ul>
              <li><strong>Cookie Banner:</strong> When you first visit our platform, you can choose which non-essential cookies to accept.</li>
              <li><strong>Account Settings:</strong> Registered users can adjust marketing preferences in their account settings.</li>
            </ul>
          </div>
          
          <div class="highlight-box" style="background: #FEF9E6; border-left-color: #d9534f;">
            <i class="bi bi-exclamation-triangle-fill me-2" style="color: #d9534f;"></i>
            <strong>Important:</strong> Blocking certain cookies may impact your experience. Strictly necessary cookies cannot be disabled as they are required for the platform to function properly. Disabling functional cookies may prevent certain features from working as expected.
          </div>
        </div>

        <div class="policy-section">
          <h3>5. Third-Party Cookies</h3>
          <p>We use services provided by trusted third parties that may set cookies on your device. These third parties include:</p>
          <ul>
            <li><strong>Google Analytics:</strong> Helps us understand how visitors use our platform. <a href="https://policies.google.com/technologies/cookies" class="text-dark" target="_blank">Learn more about Google's cookie practices →</a></li>
            <li><strong>Facebook Pixel:</strong> Helps us measure the effectiveness of our advertising. <a href="https://www.facebook.com/policies/cookies/" class="text-dark" target="_blank">Learn more about Facebook's cookie practices →</a></li>
            <li><strong>Cloudflare:</strong> Provides security and performance optimization services.</li>
          </ul>
          <p>We do not control these third-party cookies, and we encourage you to review their respective privacy and cookie policies for more information.</p>
        </div>

        <div class="policy-section">
          <h3>6. Similar Technologies</h3>
          <p>In addition to cookies, we use other technologies to collect and store information:</p>
          <ul>
            <li><strong>Local Storage:</strong> Used to store larger amounts of data locally in your browser for features like saved searches and draft listings.</li>
            <li><strong>Pixels:</strong> Small transparent images that help us understand how you interact with our emails and platform.</li>
            <li><strong>Web Beacons:</strong> Used in emails to confirm delivery and engagement.</li>
          </ul>
        </div>

        <div class="policy-section">
          <h3>7. Changes to This Policy</h3>
          <p>We may update this Cookie Policy from time to time to reflect changes in technology, legal requirements, or our practices. We will notify you of significant changes by:</p>
          <ul>
            <li>Posting the updated policy on our platform</li>
            <li>Displaying a notice on our website</li>
            <li>Sending an email to registered users (for material changes)</li>
          </ul>
          <p>The "Last Updated" date at the top of this page indicates when the policy was last revised. Your continued use of UniformMarket after changes become effective constitutes acceptance of the updated policy.</p>
        </div>

        <div class="policy-section">
          <h3>8. Contact Us</h3>
          <p>If you have questions about our use of cookies or this Cookie Policy, please contact us:</p>
          <ul class="list-unstyled">
            <li><i class="bi bi-envelope me-2"></i> <strong>Privacy Team:</strong> privacy@uniformmarket.com</li>
            <li><i class="bi bi-telephone me-2"></i> <strong>Phone:</strong> +1 (555) 123-4567</li>
            <li><i class="bi bi-question-circle me-2"></i> <strong>Subject:</strong> Cookie Policy Inquiry</li>
          </ul>
          <p class="mt-3">We typically respond to inquiries within 5-7 business days.</p>
        </div>

        <div class="text-center mt-5 pt-3">
          <a href="index.php" class="btn btn-black rounded-pill px-5 me-3">Back Home</a>
          <a href="privacy-policy.php" class="btn btn-outline-black rounded-pill px-5">View Privacy Policy</a>
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