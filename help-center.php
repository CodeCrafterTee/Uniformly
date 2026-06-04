<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Help Center - UniformMarket</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
    .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.8rem; border-radius: 40px; transition: all 0.25s ease; }
    .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
    .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
    .faq-card { background: white; border-radius: 28px; padding: 1.5rem; margin-bottom: 1rem; cursor: pointer; transition: all 0.2s; border: 1px solid rgba(0, 0, 0, 0.05); }
    .faq-card:hover { transform: translateX(4px); border-color: rgba(0, 0, 0, 0.15); }
    .faq-question { font-weight: 700; font-size: 1.1rem; display: flex; justify-content: space-between; align-items: center; }
    .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; margin-top: 0; }
    .faq-card.open .faq-answer { max-height: 500px; margin-top: 1rem; }
    .faq-card.open .faq-icon { transform: rotate(45deg); }
    .category-tab { cursor: pointer; transition: all 0.2s; }
    .category-tab.active { background: #000; color: white; border-color: #000; }
    .search-box { border: 1.5px solid #e0d8cc; border-radius: 60px; padding: 0.75rem 1rem 0.75rem 2.5rem; width: 100%; }
    .search-wrapper { position: relative; }
    .search-wrapper i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9b8e7a; }
    .back-to-top { position: fixed; bottom: 2rem; right: 2rem; background: black; color: white; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; z-index: 100; }
    @media (max-width: 768px) { .faq-card { padding: 1rem; } }
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
            <li class="breadcrumb-item active text-dark" aria-current="page">Help Center</li>
          </ol>
        </nav>

        <div class="text-center mb-5">
          <i class="bi bi-question-circle fs-1" style="color: #000;"></i>
          <h1 class="display-5 fw-bold mt-2">Help Center</h1>
          <p class="lead text-muted">Find answers to commonly asked questions</p>
        </div>

        <!-- Search -->
        <div class="search-wrapper mb-4">
          <i class="bi bi-search"></i>
          <input type="text" class="search-box" id="searchFaq" placeholder="Search for help...">
        </div>

        <!-- Categories -->
        <div class="d-flex flex-wrap gap-2 mb-4">
          <button class="btn btn-outline-black rounded-pill category-tab active" data-category="all">All</button>
          <button class="btn btn-outline-black rounded-pill category-tab" data-category="account">Account</button>
          <button class="btn btn-outline-black rounded-pill category-tab" data-category="selling">Selling</button>
          <button class="btn btn-outline-black rounded-pill category-tab" data-category="buying">Buying</button>
          <button class="btn btn-outline-black rounded-pill category-tab" data-category="safety">Safety</button>
          <button class="btn btn-outline-black rounded-pill category-tab" data-category="payment">Payment</button>
        </div>

        <!-- FAQ Items -->
        <div id="faqContainer"></div>

        <!-- Contact Support -->
        <div class="bg-white rounded-4 p-4 mt-4 text-center">
          <i class="bi bi-envelope-paper fs-2"></i>
          <h5 class="fw-bold mt-2">Still have questions?</h5>
          <p class="text-muted">Our support team is here to help</p>
          <a href="#" class="btn btn-black rounded-pill">Contact Support</a>
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

  const faqs = [
    { q: "How do I create an account?", a: "Click 'Join Free' in the top right corner, fill in your details, verify your email, and you're ready to start buying and selling!", category: "account" },
    { q: "Is it free to list items?", a: "Yes! Listing items on UniformMarket is completely free. We want to help parents save money and reduce waste without any upfront costs.", category: "selling" },
    { q: "How do I price my uniforms?", a: "Price based on condition: Like New: 50-70% retail, Excellent: 40-50%, Good: 30-40%, Fair: 20-30%. Research similar listings for guidance.", category: "selling" },
    { q: "What payment methods should I use?", a: "For safety, we recommend cash for in-person meetups or secure payment apps like PayPal Goods & Services that offer buyer protection.", category: "payment" },
    { q: "How do I stay safe when meeting buyers?", a: "Always meet in public, well-lit places like police stations, banks, or coffee shops. Bring a friend and trust your instincts.", category: "safety" },
    { q: "What if an item doesn't match the description?", a: "Contact the seller first to resolve. If unresolved, report the issue to our support team. Always inspect items before paying.", category: "buying" },
    { q: "How do I edit or delete my listing?", a: "Go to 'My Listings' in your account dashboard, find the listing, and click 'Edit' or 'Delete'.", category: "selling" },
    { q: "Can I sell items from any school?", a: "Yes! We accept uniforms from all schools. Just specify the school name in your listing so buyers can find it easily.", category: "selling" },
    { q: "How do I know if an item will fit?", a: "Ask the seller for measurements! We encourage sellers to provide exact measurements in their listings.", category: "buying" },
    { q: "What if I forgot my password?", a: "Click 'Forgot Password' on the login page and follow the instructions to reset your password via email.", category: "account" },
    { q: "Can I sell uniforms that have school logos?", a: "Yes! Items with school logos are welcome. Just be honest about the condition and include clear photos.", category: "selling" },
    { q: "How do I leave feedback for a seller?", a: "After a transaction, visit the listing or go to 'My Purchases' and click 'Leave Feedback'.", category: "buying" }
  ];

  function renderFaqs(filterCategory = 'all', searchTerm = '') {
    let filtered = faqs;
    if (filterCategory !== 'all') filtered = filtered.filter(f => f.category === filterCategory);
    if (searchTerm) filtered = filtered.filter(f => f.q.toLowerCase().includes(searchTerm) || f.a.toLowerCase().includes(searchTerm));
    
    const container = document.getElementById('faqContainer');
    container.innerHTML = filtered.map((faq, index) => `
      <div class="faq-card" data-category="${faq.category}" onclick="toggleFaq(this)">
        <div class="faq-question">
          <span>${faq.q}</span>
          <i class="bi bi-plus-lg faq-icon"></i>
        </div>
        <div class="faq-answer">
          <p class="text-muted mb-0">${faq.a}</p>
        </div>
      </div>
    `).join('');
  }

  window.toggleFaq = function(element) {
    element.classList.toggle('open');
  };

  // Category filtering
  document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      const category = this.dataset.category;
      const searchTerm = document.getElementById('searchFaq').value.toLowerCase();
      renderFaqs(category, searchTerm);
    });
  });

  // Search functionality
  document.getElementById('searchFaq').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const activeCategory = document.querySelector('.category-tab.active').dataset.category;
    renderFaqs(activeCategory, searchTerm);
  });

  renderFaqs();

  const backToTop=document.getElementById('backToTop');
  window.addEventListener('scroll',()=>{backToTop.style.display=window.scrollY>300?'flex':'none';});
  backToTop.style.display='none';
</script>
</body>
</html>