<?php
session_start();
require 'db_connection.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? 'User';
$admin_id = 1;
$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($subject) || empty($message)) {
        $error = "Please fill in both subject and message.";
    } else {
        $stmt = $conn->prepare("INSERT INTO user_messages (sender_id, recipient_id, subject, message_text, is_read, is_payment_request, sent_at) VALUES (?, ?, ?, ?, 0, 0, NOW())");
        $stmt->bind_param("iiss", $user_id, $admin_id, $subject, $message);

        if ($stmt->execute()) {
            $success = "Message sent to admin successfully!";
        } else {
            $error = "Failed to send message: " . $conn->error;
        }

        $stmt->close();
    }
}

// Fetch sent messages by this user
$sent_query = $conn->prepare("SELECT subject, message_text, is_read, sent_at FROM user_messages WHERE sender_id = ? AND recipient_id = ? ORDER BY sent_at DESC");
$sent_query->bind_param("ii", $user_id, $admin_id);
$sent_query->execute();
$sent_result = $sent_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Message to Admin</title>

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
        .container { max-width: 700px; margin: auto; background: white; padding: 20px; border-radius: 10px; }
        h2 { color: #333; }
        .message-box {
            border: 1px solid #ccc;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
        }
        .read { background-color: #eee; }
        .unread { background-color: #def; }
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
        .subnav a:hover { text-decoration: underline; }
        .subnav a.active { color: #0056b3; text-decoration: underline; }
        .form-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
        input[type="text"], textarea {
            width: 100%; padding: 10px; margin: 10px 0 20px;
            border-radius: 5px; border: 1px solid #ccc;
        }
        input[type="submit"] {
            background-color: #0284c7; color: white;
            border: none; padding: 10px 20px;
            border-radius: 6px; cursor: pointer;
        }
        input[type="submit"]:hover { background-color: #0369a1; }
        .message { margin-bottom: 15px; padding: 10px; border-radius: 5px; }
        .success { background-color: #d1fae5; color: #065f46; }
        .error { background-color: #fee2e2; color: #991b1b; }
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

<!-- Top Nav -->
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

<!-- Subnav -->
<div class="subnav">
   <a href="user_send_messages.php" class="active">Send Message</a>
    <a href="user_messages.php">Inbox</a>
    <a href="user_sent_messages.php">Sent Messsages</a>
</div><br><br><br><br><br>

<!-- Message Form -->
<div class="form-container">
    <h2>Send Message to Admin</h2>

    <?php if ($success): ?>
        <div class="message success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label>Subject:</label>
        <input type="text" name="subject" required>

        <label>Message:</label>
        <textarea name="message" rows="6" required></textarea>

        <input type="submit" value="Send Message">
    </form>
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
