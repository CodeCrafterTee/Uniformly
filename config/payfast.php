<?php
// config/payfast.php

// PayFast Sandbox Settings
define('PF_MERCHANT_ID', '10000100');
define('PF_MERCHANT_KEY', '46f0cd694581a');
define('PF_PASSPHRASE', ''); // No passphrase for this sandbox account

// PayFast URLs
define('PF_ACTION_URL', 'https://sandbox.payfast.co.za/eng/process');
define('PF_RETURN_URL', 'http://uniformly.unaux.com/payment-success.php'); // Update with your domain
define('PF_CANCEL_URL', 'http://uniformly.unaux.com/cart.php'); // Update with your domain
define('PF_NOTIFY_URL', 'http://uniformly.unaux.com/payment-notify.php'); // Update with your domain

// For production (uncomment when going live)
// define('PF_MERCHANT_ID', 'your_live_merchant_id');
// define('PF_MERCHANT_KEY', 'your_live_merchant_key');
// define('PF_PASSPHRASE', 'your_live_passphrase');
// define('PF_ACTION_URL', 'https://www.payfast.co.za/eng/process');