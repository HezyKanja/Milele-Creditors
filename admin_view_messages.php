<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = 'Staff';

// Get staff name from DB
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'staff'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fetched_name);
if ($stmt->fetch()) {
    $name = $fetched_name;
}
$stmt->close();
require 'db_connection.php';

// Fetch all sent messages from the messages table (admin-sent = all messages)
$messages = $conn->query("SELECT m.*, u.name AS recipient_name, u.email AS recipient_email 
                         FROM messages m 
                         JOIN users u ON m.recipient_id = u.id
                         ORDER BY m.sent_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Sent Messages</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f8fc;
            margin: 0;
            padding-top: 100px;
        }
        nav {
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 50px;
            position: fixed;
            width: 100%;
            top: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            z-index: 1000;
            background-color: #343a40;
            color: white;
        }
        .brand { font-weight: 700; font-size: 1.2rem; margin-right: 40px; }
        .menu { display: flex; gap: 12px; list-style: none; margin: 0; padding: 0; }
        .menu li a { color: white; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 4px 8px; border-radius: 4px; transition: 0.25s; }
        .menu li a.active, .menu li a:hover { background-color: rgba(255,255,255,0.25); }
        .right-section { margin-left: auto; display: flex; gap: 15px; align-items: center; }
        .right-section span { font-size: 0.9rem; }
        .right-section .btn { background: white; color: #343a40; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .message-box {
            border-left: 5px solid #007bff;
            padding: 15px 20px;
            margin-bottom: 20px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .message-box p {
            margin: 5px 0;
        }
        .message-box .meta {
            color: #555;
            font-size: 14px;
        }
        .message-box .payment-request {
            color: #c0392b;
            font-weight: bold;
        }
        .sub-nav {
            max-width: 900px;
            margin: 20px auto 0 auto;
            display: flex;
            justify-content: center;
            gap: 30px;
        }
        .sub-nav a {
            text-decoration: none;
            font-weight: bold;
            color: #007bff;
        }
        .sub-nav a:hover {
            text-decoration: underline;
        }
        .sub-nav a.active {
            color: #000;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<nav>
    <div class="brand">Milele Admin</div>
    <ul class="menu">
        <li><a href="admin_all_devices.php">Devices</a></li>
        <li><a href="admin_all_loans_applied.php">Loans</a></li>
        <li><a href="admin_all_payments.php">Payments</a></li>
        <li><a href="admin_users.php">Users</a></li>
        <li><a href="admin_send_messages.php" class="active">Messages</a></li>
        
    </ul>
    <div class="right-section">
        <span>Welcome Staff, <?= htmlspecialchars($name) ?></span>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</nav>

<h2 style="text-align:center;">📋 Sent Messages</h2>

<div class="sub-nav">
    <a href="admin_send_messages.php">Send Message</a>
    <a href="admin_user_messages.php">Inbox</a>
    <a href="admin_view_messages.php" class="active">Sent Messages</a>
</div>

<div class="container">
    <h2>📤 Messages Sent to Borrowers</h2>

    <?php if ($messages->num_rows === 0): ?>
        <p>No messages have been sent yet.</p>
    <?php else: ?>
        <?php while ($msg = $messages->fetch_assoc()): ?>
            <div class="message-box">
                <p><strong>To:</strong> <?= htmlspecialchars($msg['recipient_name']) ?> (<?= htmlspecialchars($msg['recipient_email']) ?>)</p>
                <p><strong>Message:</strong><br><?= nl2br(htmlspecialchars($msg['message_text'])) ?></p>
                <?php if ($msg['is_payment_request']): ?>
                    <p class="payment-request">💰 Payment Request</p>
                <?php endif; ?>
                <p class="meta">Sent at: <?= $msg['sent_at'] ?></p>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

</body>
</html>
