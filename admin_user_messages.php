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



$user_id = $_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? 'Admin';
$admin_id = 1;

// Handle mark as read
if (isset($_GET['mark_read'])) {
    $msg_id = (int)$_GET['mark_read'];
    $conn->query("UPDATE user_messages SET is_read = 1 WHERE id = $msg_id AND recipient_id = $admin_id");
    header("Location: admin_user_messages.php");
    exit();
}

// Fetch messages sent to admin
$sent_query = $conn->prepare("SELECT um.id, um.subject, um.message_text, um.is_read, um.sent_at, u.name AS sender_name, u.email AS sender_email FROM user_messages um JOIN users u ON um.sender_id = u.id WHERE um.recipient_id = ? ORDER BY um.sent_at DESC");
$sent_query->bind_param("i", $admin_id);
$sent_query->execute();
$sent_result = $sent_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Messages to Admin</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
            margin: 0;
            padding-top: 100px; /* Adjusted for fixed nav */
        }
        nav { /* Simplified admin nav */
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 50px;
            position: fixed;
            width: 100%;
            top: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            z-index: 1000;
            background-color: #343a40; /* Darker admin theme */
            color: white;
        }
        .brand { font-weight: 700; font-size: 1.2rem; margin-right: 40px; }
        .menu { display: flex; gap: 12px; list-style: none; margin: 0; padding: 0; }
        .menu li a { color: white; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 4px 8px; border-radius: 4px; transition: 0.25s; }
        .menu li a.active, .menu li a:hover { background-color: rgba(255,255,255,0.25); }
        .right-section { margin-left: auto; display: flex; gap: 15px; align-items: center; }
        .right-section span { font-size: 0.9rem; }
        .right-section .btn { background: white; color: #343a40; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .page-header {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px; /* Space below header */
        }
        .sub-nav {
            max-width: 900px;
            margin: 20px auto;
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

        .container {
            max-width: 900px;
            margin: 60px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
        .message-box {
            border: 1px solid #ccc;
            padding: 15px;
            border-left: 5px solid #0284c7;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .read { background-color: #f1f5f9; }
        .unread { background-color: #e0f2fe; }
        .subject { font-weight: bold; }
        .status { margin-top: 10px; font-style: italic; }
        .mark-btn {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .mark-btn:hover {
            background-color: #218838;
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
    <a href="admin_send_messages.php">Inbox</a>
    <a href="admin_view_messages.php" class="active">Sent Messages</a>
</div>

<div class="container">
    <h2>📥 Messages from Users</h2>
    <?php if ($sent_result->num_rows === 0): ?>
        <p>No messages received from users yet.</p>
    <?php else: ?>
        <?php while ($msg = $sent_result->fetch_assoc()): ?>
            <div class="message-box <?= $msg['is_read'] ? 'read' : 'unread' ?>">
                <p><strong>From:</strong> <?= htmlspecialchars($msg['sender_name']) ?> (<?= htmlspecialchars($msg['sender_email']) ?>)</p>
                <p class="subject">📨 <?= htmlspecialchars($msg['subject']) ?></p>
                <p><strong>Date:</strong> <?= $msg['sent_at'] ?></p>
                <p><?= nl2br(htmlspecialchars($msg['message_text'])) ?></p>
                <p class="status">
                    <strong>Status:</strong> <?= $msg['is_read'] ? '✔️ Read' : '⏳ Not Yet Read' ?>
                    <?php if (!$msg['is_read']): ?>
                        <br><a class="mark-btn" href="?mark_read=<?= $msg['id'] ?>">Mark as Read</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

</body>
</html>
