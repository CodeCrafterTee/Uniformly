<?php
// Function to send welcome email (optional - requires mail setup)
function sendWelcomeEmail($email) {
    $to = $email;
    $subject = "Welcome to Uniformly Newsletter!";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Uniformly <newsletter@uniformly.com>" . "\r\n";
    
    $message = "
    <html>
    <head>
        <title>Welcome to Uniformly</title>
    </head>
    <body>
        <h2>Welcome to Uniformly Newsletter!</h2>
        <p>Thank you for subscribing to our newsletter. You'll now receive:</p>
        <ul>
            <li>New uniform listings in your area</li>
            <li>Special discounts and promotions</li>
            <li>Selling tips and best practices</li>
            <li>School uniform updates</li>
        </ul>
        <p>To unsubscribe anytime, click <a href='https://yourdomain.com/unsubscribe.php?email=" . urlencode($email) . "&token=" . md5($email . 'YOUR_SECRET_KEY_HERE') . "'>here</a>.</p>
        <br>
        <p>Best regards,<br>The Uniformly Team</p>
    </body>
    </html>
    ";
    
    // Uncomment to actually send email
    // mail($to, $subject, $message, $headers);
}

// Function to get subscriber count
function getSubscriberCount($conn) {
    $sql = "SELECT COUNT(*) as count FROM newsletter_subscribers WHERE is_active = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

// Function to check if email is subscribed
function isSubscribed($conn, $email) {
    $sql = "SELECT is_active FROM newsletter_subscribers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['is_active'] == 1;
    }
    return false;
}
?>