<?php
session_start();
require_once 'db_connection.php';

$name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? null;

if (!$user_email) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "milele_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if required columns exist
$columnsCheck = $conn->query("SHOW COLUMNS FROM loan_applications LIKE 'is_defaulted'");
if ($columnsCheck->num_rows === 0) {
    die("Error: 'is_defaulted' column is missing in loan_applications table.");
}

// Prepare query
$sql = "
    SELECT 
        la.id AS loan_id, la.total_with_interest, la.remaining_balance, la.status,
        d.name AS device_name, d.model, d.serial_number, d.image_url
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    WHERE la.user_email = ? AND la.is_defaulted = 1 AND la.is_approved = 1
    ORDER BY la.id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error); // Improved error message
}

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

$loans = [];
while ($row = $result->fetch_assoc()) {
    $loans[] = $row;
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html>
<head>
    <title>My Defaulted Loans</title>
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
        h2 {
            text-align: center;
            color: #2c3e50;
        }
        .loan-box {
            background: white;
            margin: 20px auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .device-img {
            width: 100%;
            max-width: 250px;
            border-radius: 8px;
        }
        .no-loans {
            text-align: center;
            color: #777;
            margin-top: 40px;
        }
        .logout-btn {
            background: white;
            color: #2c3e50;
            padding: 5px 10px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
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

<!-- Main Top Menu -->
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

<!-- Submenu for Loan Pages -->
<div class="subnav">
    <a href="user_available_loans.php">Available Loans</a>
    <a href="user_approved_loans.php">Approved Loans</a>
    <a href="user_loans_cleared.php">Cleared Loans</a>
    <a href="user_defaulted_loans.php" class="active">Defaulted Loans</a>
</div>

<h2>❌ My Defaulted Loans</h2>

<?php if (count($loans) === 0): ?>
    <p class="no-loans">You have no defaulted loans.</p>
<?php else: ?>
    <?php foreach ($loans as $loan): ?>
        <div class="loan-box">
            <?php if (!empty($loan['image_url'])): ?>
                <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image" class="device-img" />
            <?php endif; ?>

            <p><strong>Loan ID:</strong> <?= $loan['loan_id'] ?></p>
            <p><strong>Device:</strong> <?= htmlspecialchars($loan['device_name']) ?> - <?= htmlspecialchars($loan['model']) ?></p>
            <p><strong>Serial Number:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
            <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
            <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['remaining_balance'], 2) ?></p>
                        <p style="color: red;"><strong>Penalty Applied:</strong> KES <?= number_format($loan['penalty_applied'], 2) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($loan['status']) ?></p>
            <p style="color: red;"><strong>⚠ This loan has been marked as defaulted.</strong></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<footer style="color: black; text-align: center; padding: 30px 0;">
  &copy; <?= date("Y") ?> Milele Creditors. All Rights Reserved.
</footer>

</body>
</html>
