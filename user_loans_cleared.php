<?php
session_start();
require_once 'db_connection.php';

// Simulate user session name and email for filtering
$name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? null;

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'] ?? 'Valued Client';

// Fetch all cleared loans
$sql = "SELECT la.*, d.name AS device_name, d.model, d.serial_number, d.image_url
        FROM loan_applications la
        JOIN devices d ON la.device_id = d.id
        WHERE la.user_email = ? AND la.remaining_balance <= 0";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $conn->error);
}

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

$cleared_loans = [];
while ($row = $result->fetch_assoc()) {
    $cleared_loans[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>✅ Cleared Loans | Milele Creditors</title>
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

        .menu li a:hover {
            background-color: rgba(255, 255, 255, 0.25);
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
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .celebrate {
            max-width: 900px;
            background-color: #d1f0da;
            border-left: 8px solid #2ecc71;
            padding: 20px;
            margin: 0 auto 30px;
            font-size: 1.2rem;
            color: #2d572c;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }
        .loan-box {
            max-width: 900px;
            margin: 0 auto 30px;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            border-left: 6px solid #3498db;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .loan-box img {
            max-width: 150px;
            border-radius: 6px;
            float: right;
            margin-left: 20px;
        }
        .loan-box h3 {
            color: #34495e;
            margin-top: 0;
        }
        p {
            margin: 8px 0;
            color: #555;
        }
        .status-complete {
            color: #27ae60;
            font-weight: bold;
        }
        .no-loans {
            text-align: center;
            font-size: 1.2rem;
            color: #888;
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
    <li><a href="user_available_loans.php" class="active">Loans</a></li>
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

<!-- Submenu -->
<div class="subnav">
    <a href="user_available_loans.php">Available Loans</a>
    <a href="user_approved_loans.php">Approved Loans</a>
    <a href="user_loans_cleared.php" class="active">Cleared Loans</a>
    <a href="user_defaulted_loans.php">Defaulted Loans</a>
</div>

<h2>🎊 Hello <?= htmlspecialchars($user_name) ?>, here are your Cleared Loans</h2>

<?php if (empty($cleared_loans)): ?>
    <p class="no-loans">You haven't cleared any loans yet. Keep going, you're on the right track! 💪</p>
<?php else: ?>
    <div class="celebrate">
        ✅ You have cleared <?= count($cleared_loans) ?> loan<?= count($cleared_loans) > 1 ? 's' : '' ?> with us. Thank you for your commitment!
    </div>

    <?php foreach ($cleared_loans as $loan): ?>
        <div class="loan-box">
            <?php if (!empty($loan['image_url'])): ?>
                <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image">
            <?php endif; ?>
            <h3>📱 <?= htmlspecialchars($loan['device_name']) ?> - <?= htmlspecialchars($loan['model']) ?></h3>
            <p><strong>Loan ID:</strong> <?= (int)$loan['id'] ?></p>
            <p><strong>Serial Number:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
            <p style="color:blue"><strong>Total Paid:</strong> KES <?= number_format($loan['total_paid'], 2) ?></p>
            <p><strong>Total With Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
            <p style="color: red;"><strong>Penalty Applied:</strong> KES <?= number_format($loan['penalty_applied'], 2) ?></p>
            <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
            <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
            <p><strong>Status:</strong> <span class="status-complete">✅ Fully Paid</span></p>
            <p><strong>Completed On:</strong> <?= htmlspecialchars($loan['updated_at'] ?? 'N/A') ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<footer style="color: black; text-align: center; padding: 30px 0;">
  &copy; <?= date("Y") ?> Milele Creditors. All Rights Reserved.
</footer>

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

<?php $conn->close(); ?>
