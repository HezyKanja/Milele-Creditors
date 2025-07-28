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

// Fetch only users with the 'borrower' role
$users = $conn->query("SELECT id, name FROM users WHERE role = 'borrower'");

// Handle General Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_general'])) {
    $recipient_id = $_POST['recipient_id'];
    $message_text = $conn->real_escape_string($_POST['message_text']);
    $is_payment_request = isset($_POST['is_payment_request']) ? 1 : 0;
    $now = date('Y-m-d H:i:s');

    if ($recipient_id === 'all') {
        $allUsers = $conn->query("SELECT id FROM users WHERE role = 'borrower'");
        while ($user = $allUsers->fetch_assoc()) {
            $uid = $user['id'];
            $conn->query("INSERT INTO messages (recipient_id, message_text, is_payment_request, is_read, sent_at) 
                          VALUES ($uid, '$message_text', $is_payment_request, 0, '$now')");
        }
        $success = "✅ Message sent to all borrowers.";
    } else {
        $recipient_id = (int)$recipient_id;
        $conn->query("INSERT INTO messages (recipient_id, message_text, is_payment_request, is_read, sent_at) 
                      VALUES ($recipient_id, '$message_text', $is_payment_request, 0, '$now')");
        $success = "✅ Message sent to selected borrower.";
    }
}

// Handle Reminder Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminder'])) {
    $reminder_user_id = (int)$_POST['reminder_user_id'];
    $now = date('Y-m-d H:i:s');
    $reminder_text = "🚨 Your loan repayment deadline has been reached. Please make your payment as soon as possible.";

    $conn->query("INSERT INTO messages (recipient_id, message_text, is_payment_request, is_read, sent_at)
                  VALUES ($reminder_user_id, '$reminder_text', 1, 0, '$now')");

    $reminder_success = "📬 Reminder sent to selected borrower.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Message to Borrowers</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
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
            max-width: 700px;
            margin: auto;
            background: #fff;
            padding: 30px 40px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 12px;
            margin-bottom: 40px;
        }

        h2 {
            text-align: center;
            color: #222;
            margin-bottom: 30px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        select, textarea, input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }

        textarea {
            resize: vertical;
        }

        .checkbox {
            margin-top: 15px;
        }

        .checkbox input {
            margin-right: 8px;
        }

        button {
            margin-top: 25px;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        .success {
            margin-top: 20px;
            background: #e0fbe0;
            border: 1px solid #8bd98b;
            padding: 12px;
            border-radius: 8px;
            color: #2f722f;
            font-weight: bold;
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
<h2 style="text-align:center;">📋 Send Message</h2>


<div class="sub-nav">
    <a href="admin_send_messages.php">Send Message</a>
    <a href="admin_user_messages.php">Inbox</a>
    <a href="admin_view_messages.php" class="active">Sent Messages</a>
</div>

    <div class="container">
        <h2>📨 Send Message</h2>

        <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>

        <form method="POST">
            <input type="hidden" name="send_general" value="1">
            <label for="recipient_id">Recipient:</label>
            <select name="recipient_id" id="recipient_id" required>
                <option value="all">-- All Borrowers --</option>
                <?php
                $users->data_seek(0); // Reset pointer
                while ($user = $users->fetch_assoc()): ?>
                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                <?php endwhile; ?>
            </select>

            <label for="message_text">Message:</label>
            <textarea name="message_text" id="message_text" rows="5" required></textarea>

            <div class="checkbox">
                <label>
                    <input type="checkbox" name="is_payment_request" value="1">
                    Request Payment (user will be redirected to payment page)
                </label>
            </div>

            <button type="submit">📤 Send Message</button>
        </form>
    </div>

    <div class="container">
        <h2>⏰ Send Payment Reminder</h2>

        <?php if (isset($reminder_success)) echo "<div class='success'>$reminder_success</div>"; ?>

        <form method="POST">
            <input type="hidden" name="send_reminder" value="1">
            <label for="reminder_user_id">Select Borrower:</label>
            <select name="reminder_user_id" id="reminder_user_id" required>
                <?php
                $users->data_seek(0); // Reset pointer again
                while ($user = $users->fetch_assoc()): ?>
                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit">📣 Send Reminder to Pay</button>
        </form>
    </div>

</body>
</html>
