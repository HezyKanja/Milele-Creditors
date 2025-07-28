<?php 
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? '';
$email = $_SESSION['user_email'] ?? '';
$role = $_SESSION['user_role'] ?? '';

// Database connection (if not included in db_connection.php already)
$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = "";
$error = "";

// ----------------------
// ✅ Handle payment form
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_installment'])) {
    $loan_id = (int)$_POST['loan_id'];
    $payment_method = $_POST['payment_method'] ?? '';
    $mpesa_number = $_POST['mpesa_number'] ?? null;
    $credit_card_number = $_POST['credit_card_number'] ?? null;

    // Validate payment method and required fields
    if (!in_array($payment_method, ['Cash', 'MPesa', 'Credit Card'])) {
        $error = "Invalid payment method.";
    } elseif ($payment_method === 'MPesa' && empty($mpesa_number)) {
        $error = "MPesa number required.";
    } elseif ($payment_method === 'Credit Card' && empty($credit_card_number)) {
        $error = "Credit card number required.";
    } else {
        // Check if loan exists and is active
        $stmt = $conn->prepare("SELECT total_with_interest, loan_duration, remaining_balance, fixed_monthly_installment 
                                FROM loan_applications 
                                WHERE id = ? AND user_email = ? AND is_approved = 1 AND is_defaulted = 0 AND remaining_balance > 0");
        $stmt->bind_param("is", $loan_id, $email);
        $stmt->execute();
        $stmt->bind_result($total_with_interest, $loan_duration, $remaining_balance, $monthly_installment);
        $found = $stmt->fetch();
        $stmt->close();

        if ($found) {
            // ✅ Check last payment date to limit one per month
            $stmt = $conn->prepare("SELECT MAX(payment_date) FROM loan_payments WHERE loan_application_id = ?");
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            $stmt->bind_result($last_payment_date);
            $stmt->fetch();
            $stmt->close();

            $can_pay = true;
            if ($last_payment_date) {
                $last_date = new DateTime($last_payment_date);
                $now = new DateTime();
                $interval = $last_date->diff($now);

                // Disallow payment if last one was less than 1 month ago
                if ($interval->m < 1 && $interval->y == 0 && $now->format('Y-m') == $last_date->format('Y-m')) {
                    $can_pay = false;
                }
            }

            if (!$can_pay) {
                $error = "⏳ You can only pay once a month. Next payment will be done next month.";
            } else {
                // ✅ Count installments made so far
                $stmt = $conn->prepare("SELECT COUNT(*) FROM loan_payments WHERE loan_application_id = ?");
                $stmt->bind_param("i", $loan_id);
                $stmt->execute();
                $stmt->bind_result($paid_count);
                $stmt->fetch();
                $stmt->close();

                $next_installment = $paid_count + 1;
                $installment_amount = ($remaining_balance < $monthly_installment) ? $remaining_balance : $monthly_installment;
                $balance_after = $remaining_balance - $installment_amount;

                // ✅ Insert the payment
                $stmt = $conn->prepare("INSERT INTO loan_payments 
                    (loan_application_id, installment_number, amount, balance_after_payment, payment_method, mpesa_number, credit_card_number, payment_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiddsss", $loan_id, $next_installment, $installment_amount, $balance_after, $payment_method, $mpesa_number, $credit_card_number);

                if ($stmt->execute()) {
                    $stmt->close();

                    // ✅ Update remaining balance
                    $update1 = $conn->prepare("UPDATE loan_applications SET remaining_balance = ? WHERE id = ?");
                    $update1->bind_param("di", $balance_after, $loan_id);
                    $update1->execute();
                    $update1->close();

                    // ✅ Update total_paid field
                    $update2 = $conn->prepare("UPDATE loan_applications SET total_paid = total_paid + ? WHERE id = ?");
                    $update2->bind_param("di", $installment_amount, $loan_id);
                    $update2->execute();
                    $update2->close();

                    // ✅ Redirect if loan fully cleared
                    if ($balance_after <= 0.01) {
                        header("Location: user_cleared_loans.php?loan_id=$loan_id");
                        exit();
                    }

                    $success = "✅ Installment #$next_installment of KES " . number_format($installment_amount, 2) . " paid successfully.";
                } else {
                    $error = "❌ Failed to save payment. Please try again.";
                }
            }
        } else {
            $error = "❌ Loan not found or already fully paid.";
        }
    }
}


// ✅ Fetch active loans + info

$loans = [];

$sql = "SELECT la.*, d.name AS device_name, d.image_url 
        FROM loan_applications la
        JOIN devices d ON la.device_id = d.id
        WHERE la.user_email = ? AND la.is_approved = 1 AND la.is_defaulted = 0 AND la.remaining_balance > 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $loan_id = $row['id'];

    // Count how many installments already made
    $pay_stmt = $conn->prepare("SELECT COUNT(*) FROM loan_payments WHERE loan_application_id = ?");
    $pay_stmt->bind_param("i", $loan_id);
    $pay_stmt->execute();
    $pay_stmt->bind_result($payments_done);
    $pay_stmt->fetch();
    $pay_stmt->close();

    $row['months_left'] = $row['loan_duration'] - $payments_done;

    // Get total paid so far
    $total_paid_stmt = $conn->prepare("SELECT IFNULL(SUM(amount), 0) FROM loan_payments WHERE loan_application_id = ?");
    $total_paid_stmt->bind_param("i", $loan_id);
    $total_paid_stmt->execute();
    $total_paid_stmt->bind_result($total_paid);
    $total_paid_stmt->fetch();
    $total_paid_stmt->close();
    $row['total_paid'] = $total_paid;

    // Last payment date for due tracking
    $due_stmt = $conn->prepare("SELECT MAX(payment_date) FROM loan_payments WHERE loan_application_id = ?");
    $due_stmt->bind_param("i", $loan_id);
    $due_stmt->execute();
    $due_stmt->bind_result($last_payment_date);
    $due_stmt->fetch();
    $due_stmt->close();

    $row['next_due_date'] = $last_payment_date ? date("Y-m-d", strtotime($last_payment_date . " +1 month")) : "Not yet paid";

    $loans[] = $row;
}
?>




<!DOCTYPE html>
<html>
<head>
    <title>Pay for Loan</title>

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
         h2,h4  { color: #2c3e50; text-align: center; }
        .loan {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin: 20px auto;
    max-width: 700px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    border: 1px solid #e0e0e0;
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
.success, .error {
    position: fixed;
    top: 70px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 20px;
    z-index: 9999;
    border-radius: 6px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: opacity 0.5s ease;
    max-width: 90%;
    text-align: center;
}

.success {
    background-color: #d4edda;
    color: #155724;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
}

.fade-out {
    opacity: 0;
}
.payment-form {
    margin-top: 15px;
}

.payment-form label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
}

.payment-form select,
.payment-form input[type="text"] {
    width: 100%;
    padding: 8px 10px;
    margin-top: 5px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 0.95rem;
}

.payment-extra {
    margin-bottom: 12px;
}

.pay-btn {
    background: #28a745;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.pay-btn:hover {
    background: #218838;
    transform: scale(1.03);
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
<h2 class="page-header">Pay for loan</h2>

<?php if ($success): ?><div class="success" id="alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="error" id="alert-error"><?= $error ?></div><?php endif; ?>


<?php if (empty($loans)): ?>
    <h4>No active loans available for payment.</h4>
<?php else: ?>
    <?php foreach ($loans as $loan): ?>
        <?php
        $loan_id = $loan['id'];
        $installment = round($loan['total_to_pay'] / $loan['loan_duration'], 2);
        ?>
       <div class="loan">
    <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image" style="max-width:150px; border-radius:8px;">
    <h3>Loan ID: <?= $loan['id'] ?></h3>
   <?php
$amount_paid = $loan['total_with_interest'] - $loan['remaining_balance'];
$remaining_balance_display = ($amount_paid <= 0) ? $loan['total_with_interest'] : $loan['remaining_balance'];
?>
<p><strong>Device:</strong> <?= htmlspecialchars($loan['name']) ?></p>
<p style="color: blue;"><strong>Total to pay:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
<p><strong>Amount Paid:</strong> KES <?= number_format($amount_paid, 2) ?></p>
<p style="color: red;"><strong>Penalty Applied:</strong> KES <?= number_format($loan['penalty_applied'], 2) ?></p>
<p><strong>Remaining Balance:</strong> KES <?= number_format($remaining_balance_display, 2) ?></p>
<p><strong>Monthly Installment:</strong> KES <?= number_format($loan['fixed_monthly_installment'], 2) ?></p>
<p><strong>Months Left to Pay:</strong> <?= $loan['months_left'] ?></p>
<p><strong>Next Payment Due Date:</strong> <?= date("F j, Y", strtotime($loan['next_due_date'])) ?></p>





            <form method="post" class="payment-form">
    <input type="hidden" name="loan_id" value="<?= $loan_id ?>">

    <label for="payment_method_<?= $loan_id ?>">Payment Method:</label>
    <select name="payment_method" id="payment_method_<?= $loan_id ?>" onchange="toggleFields(<?= $loan_id ?>)">
        <option value="">--Select--</option>
        <option value="Cash">Cash</option>
        <option value="MPesa">MPesa</option>
        <option value="Credit Card">Credit Card</option>
    </select>

    <div id="mpesa_<?= $loan_id ?>" class="payment-extra" style="display:none;">
        <label>MPesa Number:</label>
        <input type="text" name="mpesa_number" pattern="\d{10,15}">
    </div>

    <div id="card_<?= $loan_id ?>" class="payment-extra" style="display:none;">
        <div id="credit_card_fields" class="hidden">
    <label>Card Number</label>
    <input type="text" name="card_number" id="card_number" maxlength="19" placeholder="xxxx xxxx xxxx xxxx">

    <div class="cc-details">
        <div>
            <label>Expiry Date (MM/YY)</label>
            <input type="text" name="card_expiry" id="card_expiry" maxlength="5" placeholder="MM/YY">
        </div>
        <div>
            <label>CVV</label>
            <input type="text" name="card_cvv" id="card_cvv" maxlength="4" placeholder="123">
        </div>
    </div>
</div>
    </div>

    <button type="submit" name="pay_installment" class="pay-btn">💸 Pay Installment</button>
</form>

        </div>
    <?php endforeach; ?>
<?php endif; ?>

<footer style="color: black; text-align: center; padding: 30px 0;">
  &copy; <?= date("Y") ?> Milele Creditors. All Rights Reserved.
</footer>

<script>
function toggleFields(id) {
    const method = document.getElementById("payment_method_" + id).value;
    document.getElementById("mpesa_" + id).style.display = (method === 'MPesa') ? 'block' : 'none';
    document.getElementById("card_" + id).style.display = (method === 'Credit Card') ? 'block' : 'none';
}


window.addEventListener('DOMContentLoaded', function () {
    const successBox = document.getElementById('alert-success');
    const errorBox = document.getElementById('alert-error');

    [successBox, errorBox].forEach(box => {
        if (box) {
            setTimeout(() => {
                box.classList.add('fade-out');
            }, 1500);
        }
    });
});



    function confirmLogout(event) {
  event.preventDefault();
  if (confirm("Do you want to log out?")) {
    window.location.href = "logout.php";
  }
}

</script>

</body>
</html>
