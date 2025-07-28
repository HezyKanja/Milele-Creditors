<?php
session_start();
require 'db_connection.php';

// Redirect to login if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data from session
$user_id = $_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? 'User';

// Mark message as read
if (isset($_GET['mark_read'])) {
    $msg_id = (int)$_GET['mark_read'];
    $conn->query("UPDATE messages SET is_read = 1 WHERE id = $msg_id AND recipient_id = $user_id");
    header("Location: user_messages.php");
    exit();
}


// Fetch messages for the logged-in user
$query = "SELECT * FROM messages WHERE recipient_id = $user_id ORDER BY sent_at DESC";
$messages = $conn->query($query);

if (!$messages) {
    die("Error fetching messages: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <title>Your Messages</title>
    <style>
       body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
    padding-top: 50px;
    }
        nav {
            background-color: #007BFF;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 50px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
        }
        .brand {
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 1px;
            margin-right: 40px;
        }

        .menu {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 12px;
        }

        .menu li a {
            text-decoration: none;
            color: white;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: background-color 0.25s ease;
        }

         .menu li a.active {
            background-color: white;
            color: #007BFF;
            font-weight: 700;
        }

        .right-section {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .right-section span {
            font-size: 0.9rem;
        }

        .right-section .btn {
            background: white;
            color: #007BFF;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        /* Submenu Bar */
        .subnav {
            background-color: #f1f1f1;
            padding: 8px 20px;
            display: flex;
            justify-content: center;
            position: fixed;
            top: 50px;
            width: 100%;
            z-index: 999;
            border-bottom: 1px solid #ccc;
        }

        .subnav a {
            margin: 0 12px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            font-size: 14px;
        }

        .subnav a:hover {
            text-decoration: underline;
        }

        .subnav a.active {
            color: #0056b3;
            text-decoration: underline;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
        h2 {
            color: #333;
        }
        .message-box {
            border: 1px solid #ccc;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
        }
        .unread {
            background-color: #def;
        }
        .read {
            background-color: #eee;
        }
        .message-actions a {
            margin-right: 10px;
            text-decoration: none;
            color: #007BFF;
        }
        .message-actions em {
            color: green;
        }
        .subnav {
            background-color: #f1f1f1;
            padding: 8px 20px;
            display: flex;
            justify-content: center;
            position: fixed;
            top: 50px;
            width: 100%;
            z-index: 999;
            border-bottom: 1px solid #ccc;
        }

        .subnav a {
            margin: 0 12px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            font-size: 14px;
        }

        .subnav a:hover {
            text-decoration: underline;
        }

        .subnav a.active {
            color: #0056b3;
            text-decoration: underline;
        }
        .icon-links a {
  font-size: 1.5rem;          /* Slightly larger */
  color: white;
  text-decoration: none;
  transition: transform 0.2s ease, color 0.3s ease;
}

.icon-links a:hover {
  color: #ffdd57;             /* Highlight color */
  transform: scale(1.2);      /* Slight zoom on hover */
}
    </style>
</head>
<body>

<nav>
  <div class="brand">
    <a href="user_home.php" style="text-decoration: none; color: white;">Milele Creditors</a>
  </div>

<ul class="menu">
  <li><a href="user_home.php">Home</a></li>
  <li><a href="user_about_us.php">About Us</a></li>
  <li><a href="user_available_devices.php">Available Devices</a></li>
  <li><a href="user_loan_calculator.php">Loan Calculator</a></li>

  <?php if (isset($_SESSION['user_id'])): ?>
    <li><a href="user_available_loans.php">Loans</a></li>
    <li><a href="user_pay_for_loan.php">Pay for loan</a></li>
  <?php endif; ?>
</ul>



  <div class="right-section">
  <?php if (isset($_SESSION['user_id'])): ?>
    <span>Welcome, <?= htmlspecialchars($name) ?></span>
    <div class="icon-links">
      <a href="user_profile.php" title="Profile">👤</a>
      <a href="user_messages.php" title="Messages">💬</a>
    </div>
   <a href="#" class="btn" onclick="confirmLogout(event)">Logout</a>
  <?php else: ?>
    <a href="login.php" class="btn">Log In</a>
  <?php endif; ?>
</div>
</nav>

<div class="subnav">
    <a href="user_send_messages.php">Send Message</a>
    <a href="user_messages.php" class="active">Inbox</a>
    <a href="user_sent_messages.php">Sent Messages</a>
</div><br><br><br><br><br>

<div class="container">
    <h2 style="text-align:center;">Your Messages</h2>
    <?php if ($messages->num_rows === 0): ?>
        <p style="text-align:center;">You have no messages.</p>
    <?php else: ?>
        <?php while ($msg = $messages->fetch_assoc()): ?>
            <div class="message-box <?= $msg['is_read'] ? 'read' : 'unread' ?>">
                <p><strong>Message:</strong> <?= htmlspecialchars($msg['message_text']) ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($msg['sent_at']) ?></p>

                <div class="message-actions">
                    <?php if (!empty($msg['is_payment_request'])): ?>
                        <a href="user_pay_for_loan.php">➡️ Pay for Loan</a>
                    <?php endif; ?>

                    <?php if (!$msg['is_read']): ?>
                        <a href="?mark_read=<?= $msg['id'] ?>">✅ Mark as Read</a>
                    <?php else: ?>
                        <em>Marked as Read</em>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<script>
function confirmLogout(event) {
  event.preventDefault();
  if (confirm("Do you want to log out?")) {
    window.location.href = "logout.php";
  }
}
</script>

</body>
</html>
