<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? '';
$email = $_SESSION['user_email'] ?? '';
$role = $_SESSION['user_role'] ?? '';

// --- Database Connection ---
$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Get Loan ID from GET if set ---
$search_loan_id = isset($_GET['loan_id']) ? intval($_GET['loan_id']) : null;

// --- Fetch Paid Installments for Logged-in User Only ---
$payments = [];

$sql = "
    SELECT
        lp.id as payment_id,
        lp.amount,
        lp.payment_method,
        lp.payment_date,
        lp.mpesa_number,
        lp.credit_card_number,
        lp.installment_number,
        la.id as loan_id,
        la.name as user_name,
        la.user_email,
        la.phone as user_phone,
        la.loan_duration as total_duration,
        la.remaining_balance as balance_after_payment,
        d.name as device_name,
        d.model as device_model,
        d.image_url as device_image
    FROM loan_payments lp
    JOIN loan_applications la ON lp.loan_application_id = la.id
    JOIN devices d ON la.device_id = d.id
    WHERE la.user_email = ?
";

// Add loan_id filter if provided
if (!empty($search_loan_id)) {
    $sql .= " AND la.id = ?";
}

$sql .= " ORDER BY lp.payment_date DESC";

// Prepare and bind parameters
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("<strong>SQL Error:</strong> " . htmlspecialchars($conn->error));
}

if (!empty($search_loan_id)) {
    $stmt->bind_param("si", $email, $search_loan_id); // email, loan_id
} else {
    $stmt->bind_param("s", $email); // just email
}

$stmt->execute();
$result = $stmt->get_result();

// Process results
while ($payment = $result->fetch_assoc()) {
    $payment['balance_before_payment'] = $payment['balance_after_payment'] + $payment['amount'];
    $payments[] = $payment;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User - Loan Payment History</title>
    
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
        .sub-nav {
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

        .sub-nav a {
            margin: 0 12px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            font-size: 14px;
        }

        .sub-nav a:hover {
            text-decoration: underline;
        }

        .sub-nav a.active {
            color: #0056b3;
            text-decoration: underline;
        }

        .loan-box { background: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 25px; margin: 30px auto; max-width: 900px; }
        h2, h3 { color: #2c3e50; text-align: center; }
        h3 { text-align: left; margin-bottom: 10px; }
        img { max-width: 150px; margin-bottom: 10px; border-radius: 6px; }
        .section { margin-bottom: 20px; }
        .approved-label { color: #27ae60; font-weight: bold; font-size: 18px; margin-top: 12px; text-align: left; }
        .message { max-width: 900px; margin: 0 auto 20px auto; padding: 10px 15px; background: #dff0d8; color: #3c763d; border-radius: 6px; font-weight: bold; text-align: center; }
        .error-message { max-width: 900px; margin: 0 auto 20px auto; padding: 10px 15px; background: #f8d7da; color: #842029; border-radius: 6px; font-weight: bold; text-align: center; }

        p { margin: 5px 0; }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .schedule-table th, .schedule-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        .schedule-table th {
            background-color: #007BFF;
            color: white;
        }

        form.payment-form {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        form.payment-form select, form.payment-form button {
            padding: 8px 12px;
            font-size: 1rem;
            margin-right: 12px;
        }
        form.payment-form button {
            background-color: #007BFF;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form.payment-form button:hover {
            background-color: #0056b3;
        }

        .paid {
            background-color: #d4edda;
            color: #155724;
            font-weight: 700;
            border-radius: 4px;
            padding: 3px 7px;
            display: inline-block;
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
    <li><a href="user_pay_for_loan.php" class="active">Pay for loan</a></li>
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

<?php $current = basename($_SERVER['PHP_SELF']); ?>
<div class="sub-nav">
       <a href="user_pay_for_loan.php" class="<?= $current == 'user_pay_for_loan.php' ? 'active' : '' ?>">💰 Pay for Loan</a>
    <a href="user_payment_history.php" class="<?= $current == 'user_payment_history.php' ? 'active' : '' ?>">📜 Payment History</a>

</div>

<h2 class="page-header">Your Loan Payment History</h2>

<?php if (empty($payments)): ?>
    <p style="text-align:center; margin-top: 50px;">You haven't made any loan payments yet.</p>
<?php else: ?>

    <div style="max-width: 900px; margin: 20px auto; text-align: center;">
    <form method="GET" action="user_payment_history.php" style="display: inline-block;">
        <input type="number" name="loan_id" placeholder="Enter Loan ID" value="<?= htmlspecialchars($search_loan_id) ?>" style="padding: 8px; font-size: 1rem;" />
        <button type="submit" style="padding: 8px 12px; font-size: 1rem; background-color: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer;">Search</button>
        <a href="user_payment_history.php" style="padding: 8px 12px; font-size: 1rem; margin-left: 10px; background: #6c757d; color: white; border-radius: 4px; text-decoration: none;">Reset</a>
    </form>
</div>

    <?php foreach ($payments as $payment): ?>
        <?php $remaining_months = $payment['total_duration'] - $payment['installment_number']; ?>
        <div class="loan-box">
            <?php if (!empty($payment['device_image'])): ?>
                <img src="<?= htmlspecialchars($payment['device_image']) ?>" alt="Device Image" />
            <?php endif; ?>

            <h3>
                Payment ID: #<?= htmlspecialchars($payment['payment_id']) ?> |
                Loan ID: #<?= htmlspecialchars($payment['loan_id']) ?>
            </h3>

            <div class="info-grid">
                <div>
                    <h4>📱 Device Info</h4>
                    <p><strong>Device:</strong> <?= htmlspecialchars($payment['device_name']) ?> (<?= htmlspecialchars($payment['device_model']) ?>)</p>
                </div>
                <div>
                    <h4>📊 Loan Status</h4>
                    <p><strong>Installment Paid:</strong> <?= htmlspecialchars($payment['installment_number']) ?> of <?= htmlspecialchars($payment['total_duration']) ?></p>
                    <p><strong>Months Left:</strong> <?= max(0, $remaining_months) ?></p>
                 <?php
    $initial_balance = $payment['balance_after_payment'] + $payment['amount'];
?>


                </div>
            </div>

            <div class="payment-details">
                <h4>✅ Payment Confirmation</h4>
                <p><strong>Amount Paid:</strong> <span class="paid-amount">KES <?= number_format($payment['amount'], 2) ?></span></p>
                <p><strong>Payment Method:</strong> <?= htmlspecialchars($payment['payment_method']) ?></p>
                <?php if ($payment['payment_method'] === 'MPesa' && !empty($payment['mpesa_number'])): ?>
                    <p><strong>MPesa Number:</strong> <?= htmlspecialchars($payment['mpesa_number']) ?></p>
                <?php elseif ($payment['payment_method'] === 'Credit Card' && !empty($payment['credit_card_number'])): ?>
                    <p><strong>Card Number:</strong> <?= htmlspecialchars(substr($payment['credit_card_number'], 0, 4) . str_repeat('*', 8) . substr($payment['credit_card_number'], -4)) ?></p>
                <?php endif; ?>
                <p><strong>Payment Date:</strong> <?= htmlspecialchars(date('F j, Y, g:i a', strtotime($payment['payment_date']))) ?></p>
                
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>


<?php if (!empty($search_loan_id)): ?>
    <p style="text-align: center; font-weight: bold;">Showing results for Loan ID: <?= htmlspecialchars($search_loan_id) ?></p>
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
