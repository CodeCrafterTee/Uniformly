<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=messages');
    exit();
}

// Get user details from session
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$email = $_SESSION['user_email'];
$user_name = $first_name . ' ' . $last_name;
$user_avatar = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get all conversations for this user
$conversations_sql = "SELECT 
    CASE 
        WHEN m.sender_id = ? THEN m.receiver_id
        ELSE m.sender_id
    END as other_user_id,
    u.first_name as other_first_name,
    u.last_name as other_last_name,
    u.email as other_email,
    MAX(m.message_id) as last_message_id,
    MAX(m.created_at) as last_message_time,
    (SELECT message_text FROM messages m2 
     WHERE (m2.sender_id = m.sender_id AND m2.receiver_id = m.receiver_id) 
        OR (m2.sender_id = m.receiver_id AND m2.receiver_id = m.sender_id)
     ORDER BY m2.created_at DESC LIMIT 1) as last_message,
    (SELECT COUNT(*) FROM messages m3 
     WHERE m3.sender_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END 
     AND m3.receiver_id = ? AND m3.is_read = 0) as unread_count,
    l.listing_id,
    l.title as listing_title
FROM messages m
JOIN users u ON (CASE 
    WHEN m.sender_id = ? THEN m.receiver_id
    ELSE m.sender_id
END) = u.user_id
LEFT JOIN listings l ON m.listing_id = l.listing_id
WHERE m.sender_id = ? OR m.receiver_id = ?
GROUP BY other_user_id
ORDER BY last_message_time DESC";

$conversations_stmt = $conn->prepare($conversations_sql);
$conversations_stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$conversations_stmt->execute();
$conversations_result = $conversations_stmt->get_result();

$conversations = [];
while ($row = $conversations_result->fetch_assoc()) {
    $conversations[] = $row;
}
$conversations_stmt->close();

// Get selected conversation
$selected_user_id = isset($_GET['user']) ? intval($_GET['user']) : 0;
$selected_listing_id = isset($_GET['listing']) ? intval($_GET['listing']) : 0;

if ($selected_user_id > 0) {
    // Mark messages as read
    $mark_read_sql = "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
    $mark_read_stmt = $conn->prepare($mark_read_sql);
    $mark_read_stmt->bind_param("ii", $selected_user_id, $user_id);
    $mark_read_stmt->execute();
    $mark_read_stmt->close();
    
    // Get messages for this conversation
    $messages_sql = "SELECT m.*, u.first_name, u.last_name 
                     FROM messages m
                     JOIN users u ON m.sender_id = u.user_id
                     WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                        OR (m.sender_id = ? AND m.receiver_id = ?)
                     ORDER BY m.created_at ASC";
    $messages_stmt = $conn->prepare($messages_sql);
    $messages_stmt->bind_param("iiii", $user_id, $selected_user_id, $selected_user_id, $user_id);
    $messages_stmt->execute();
    $messages_result = $messages_stmt->get_result();
    
    $messages = [];
    while ($row = $messages_result->fetch_assoc()) {
        $messages[] = $row;
    }
    $messages_stmt->close();
    
    // Get other user details
    $other_user_sql = "SELECT first_name, last_name, email FROM users WHERE user_id = ?";
    $other_user_stmt = $conn->prepare($other_user_sql);
    $other_user_stmt->bind_param("i", $selected_user_id);
    $other_user_stmt->execute();
    $other_user_result = $other_user_stmt->get_result();
    $other_user = $other_user_result->fetch_assoc();
    $other_user_stmt->close();
    
    $other_user_name = $other_user['first_name'] . ' ' . $other_user['last_name'];
    $other_user_email = $other_user['email'];
    
    // Get listing info if applicable
    $listing_info = null;
    if ($selected_listing_id > 0) {
        $listing_sql = "SELECT l.title, l.listing_id, s.school_name 
                        FROM listings l
                        JOIN schools s ON l.school_id = s.school_id
                        WHERE l.listing_id = ?";
        $listing_stmt = $conn->prepare($listing_sql);
        $listing_stmt->bind_param("i", $selected_listing_id);
        $listing_stmt->execute();
        $listing_result = $listing_stmt->get_result();
        $listing_info = $listing_result->fetch_assoc();
        $listing_stmt->close();
    }
}

// Send new message
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $message_text = trim($_POST['message_text'] ?? '');
    
    if (empty($message_text)) {
        $error_message = "Please enter a message.";
    } elseif ($receiver_id <= 0) {
        $error_message = "Invalid recipient.";
    } else {
        $insert_sql = "INSERT INTO messages (sender_id, receiver_id, listing_id, message_text, is_read, created_at) 
                       VALUES (?, ?, ?, ?, 0, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiis", $user_id, $receiver_id, $listing_id, $message_text);
        
        if ($insert_stmt->execute()) {
            $success_message = "Message sent successfully!";
            
            // Create notification for receiver
            $notif_sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                          VALUES (?, 'message', 'New Message from " . addslashes($first_name . ' ' . $last_name) . "', 
                          '" . addslashes(substr($message_text, 0, 100)) . "', 
                          '/account-messages.php?user=" . $user_id . "')";
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param("i", $receiver_id);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            // Refresh page
            header("Location: account-messages.php?user=" . $receiver_id . "&sent=1");
            exit();
        } else {
            $error_message = "Failed to send message. Please try again.";
        }
        $insert_stmt->close();
    }
}

// Check for success messages
if (isset($_GET['sent'])) {
    $success_message = "Message sent successfully!";
}

// Get unread messages count
$unread_sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['count'] ?? 0;
$unread_stmt->close();

// Get member since date
$member_since_sql = "SELECT created_at FROM users WHERE user_id = ?";
$member_stmt = $conn->prepare($member_since_sql);
$member_stmt->bind_param("i", $user_id);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member_data = $member_result->fetch_assoc();
$member_since = date('F Y', strtotime($member_data['created_at'] ?? 'now'));
$member_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Messages - UniformMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #FEF9E6; color: #1E1E1E; }
        .btn-black { background-color: #000000; border: 1px solid #000000; color: white; font-weight: 500; padding: 0.6rem 1.5rem; border-radius: 40px; transition: all 0.25s ease; }
        .btn-black:hover { background-color: #2C2C2C; transform: translateY(-2px); }
        .btn-outline-black { background-color: transparent; border: 1.5px solid #000000; color: #000000; font-weight: 500; padding: 0.5rem 1.5rem; border-radius: 40px; }
        .btn-outline-black:hover { background-color: #000000; color: white; }
        .dashboard-sidebar { background: white; border-radius: 24px; padding: 1.5rem; position: sticky; top: 2rem; }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #4a4a4a; text-decoration: none; border-radius: 16px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: #FEF9E6; color: #000; }
        .sidebar-nav a.active { background: #000; color: white; }
        .messages-container { background: white; border-radius: 24px; overflow: hidden; height: calc(100vh - 250px); min-height: 550px; display: flex; }
        .conversations-list { width: 320px; border-right: 1px solid #e0d8cc; overflow-y: auto; background: white; }
        .conversation-item { padding: 1rem; border-bottom: 1px solid #f0e8dc; cursor: pointer; transition: all 0.2s; }
        .conversation-item:hover { background: #FEF9E6; }
        .conversation-item.active { background: #FEF9E6; border-left: 3px solid #000; }
        .conversation-avatar { width: 48px; height: 48px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.2rem; }
        .chat-area { flex: 1; display: flex; flex-direction: column; }
        .chat-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e0d8cc; background: white; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 1.5rem; background: #FEF9E6; }
        .message { margin-bottom: 1rem; display: flex; }
        .message.sent { justify-content: flex-end; }
        .message.received { justify-content: flex-start; }
        .message-bubble { max-width: 70%; padding: 0.75rem 1rem; border-radius: 20px; }
        .message.sent .message-bubble { background: #000; color: white; border-radius: 20px 20px 0 20px; }
        .message.received .message-bubble { background: white; color: #1e1e1e; border: 1px solid #e0d8cc; border-radius: 20px 20px 20px 0; }
        .message-time { font-size: 0.7rem; opacity: 0.7; margin-top: 0.25rem; display: block; }
        .chat-input { padding: 1rem; border-top: 1px solid #e0d8cc; background: white; display: flex; gap: 0.5rem; }
        .user-avatar-large { width: 80px; height: 80px; background: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2rem; margin: 0 auto 1rem; }
        .alert-message { padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1e4620; }
        .alert-error { background: #ffebee; border-left: 4px solid #c62828; color: #b71c1c; }
        .unread-badge { background: #dc3545; color: white; border-radius: 50%; padding: 0.1rem 0.4rem; font-size: 0.7rem; margin-left: 0.5rem; }
        @media (max-width: 768px) { 
            .dashboard-sidebar { position: relative; top: 0; margin-bottom: 1.5rem; }
            .messages-container { flex-direction: column; height: auto; }
            .conversations-list { width: 100%; max-height: 300px; }
            .message-bubble { max-width: 85%; }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    <div class="text-center mb-4">
                        <div class="user-avatar-large mx-auto mb-3"><?php echo $user_avatar; ?></div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user_name); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($email); ?></p>
                        <span class="badge bg-dark rounded-pill">Member since <?php echo $member_since; ?></span>
                    </div>
                    <ul class="sidebar-nav">
                         <li><a href="index.php"><i class="bi bi-house"></i> Home</a></li>
                        <li><a href="account-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li><a href="account-profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                        <li><a href="account-listings.php"><i class="bi bi-grid-3x3-gap-fill"></i> My Listings</a></li>
                        <li><a href="account-orders.php"><i class="bi bi-bag-check"></i> My Purchases</a></li>
                        <li><a href="account-saved.php"><i class="bi bi-heart"></i> Saved Items</a></li>
                        <li><a href="account-messages.php" class="active"><i class="bi bi-chat-dots"></i> Messages <?php if ($unread_count > 0): ?><span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
                        <li><a href="account-settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="my-2"></li>
                        <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="messages-container">
                    <!-- Conversations List -->
                    <div class="conversations-list">
                        <div class="p-3 border-bottom bg-white">
                            <h6 class="fw-bold mb-0">Messages</h6>
                            <small class="text-muted"><?php echo count($conversations); ?> conversations</small>
                        </div>
                        <?php if (empty($conversations)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-chat-dots fs-1 text-muted"></i>
                                <p class="mt-2 text-muted">No messages yet</p>
                                <small class="text-muted">Start a conversation by contacting a seller</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv): ?>
                                <div class="conversation-item <?php echo ($selected_user_id == $conv['other_user_id']) ? 'active' : ''; ?>" 
                                     onclick="window.location.href='account-messages.php?user=<?php echo $conv['other_user_id']; ?><?php echo $conv['listing_id'] ? '&listing=' . $conv['listing_id'] : ''; ?>'">
                                    <div class="d-flex gap-3">
                                        <div class="conversation-avatar flex-shrink-0">
                                            <?php echo strtoupper(substr($conv['other_first_name'], 0, 1) . substr($conv['other_last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong><?php echo htmlspecialchars($conv['other_first_name'] . ' ' . $conv['other_last_name']); ?></strong>
                                                <small class="text-muted"><?php echo date('M d', strtotime($conv['last_message_time'])); ?></small>
                                            </div>
                                            <div class="small text-muted text-truncate">
                                                <?php echo htmlspecialchars(substr($conv['last_message'], 0, 50)); ?>
                                            </div>
                                            <?php if ($conv['listing_title']): ?>
                                                <small class="text-muted"><i class="bi bi-tag"></i> <?php echo htmlspecialchars($conv['listing_title']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Area -->
                    <div class="chat-area">
                        <?php if ($selected_user_id > 0 && isset($messages)): ?>
                            <div class="chat-header">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="conversation-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                                        <?php echo strtoupper(substr($other_user_name, 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($other_user_name); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($other_user_email); ?></small>
                                    </div>
                                    <?php if ($listing_info): ?>
                                        <div class="ms-auto">
                                            <a href="buy-now.php?listing_id=<?php echo $listing_info['listing_id']; ?>" class="btn btn-sm btn-outline-black rounded-pill">
                                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($listing_info['title']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="chat-messages" id="chatMessages">
                                <?php if (empty($messages)): ?>
                                    <div class="text-center text-muted py-5">
                                        <i class="bi bi-chat-dots fs-1"></i>
                                        <p class="mt-2">No messages yet. Start the conversation!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                            <div class="message-bubble">
                                                <strong><?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?></strong><br>
                                                <?php echo nl2br(htmlspecialchars($msg['message_text'])); ?>
                                                <span class="message-time"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="account-messages.php?user=<?php echo $selected_user_id; ?><?php echo $selected_listing_id ? '&listing=' . $selected_listing_id : ''; ?>" class="chat-input">
                                <input type="hidden" name="receiver_id" value="<?php echo $selected_user_id; ?>">
                                <input type="hidden" name="listing_id" value="<?php echo $selected_listing_id; ?>">
                                <input type="hidden" name="send_message" value="1">
                                <input type="text" name="message_text" class="form-control rounded-pill" placeholder="Type your message here..." required>
                                <button type="submit" class="btn btn-black rounded-pill px-4">Send</button>
                            </form>

                        <?php elseif (count($conversations) > 0 && $selected_user_id == 0): ?>
                            <div class="d-flex align-items-center justify-content-center flex-grow-1">
                                <div class="text-center">
                                    <i class="bi bi-chat-dots fs-1 text-muted"></i>
                                    <p class="mt-3 text-muted">Select a conversation to start messaging</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center flex-grow-1">
                                <div class="text-center">
                                    <i class="bi bi-envelope-open fs-1 text-muted"></i>
                                    <h6 class="mt-3">No messages yet</h6>
                                    <p class="text-muted">When you contact a seller, your conversations will appear here</p>
                                    <a href="shop.php" class="btn btn-outline-black rounded-pill mt-2">
                                        <i class="bi bi-shop"></i> Browse Uniforms
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert-message alert-success mt-3">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert-message alert-error mt-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-scroll to bottom of messages
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Auto-refresh messages every 30 seconds (optional)
    function refreshMessages() {
        const currentUrl = window.location.href;
        if (currentUrl.includes('user=')) {
            window.location.reload();
        }
    }
    // Uncomment to enable auto-refresh
    // setInterval(refreshMessages, 30000);
</script>
</body>
</html>