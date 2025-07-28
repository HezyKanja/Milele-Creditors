<?php
session_start();
require_once 'db_connection.php';

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
// Get staff name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'staff'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fetched_name);
if ($stmt->fetch()) {
    $name = $fetched_name;
}
$stmt->close();

// Get loan IDs
$loanIdsResult = $conn->query("SELECT la.id AS loan_id, la.name AS user_name FROM loan_applications la ORDER BY la.id DESC");
$loanIds = [];
if ($loanIdsResult) {
    while ($row = $loanIdsResult->fetch_assoc()) {
        $loanIds[] = $row;
    }
}

// Fetch payments if loan_id is provided
$payments = [];
$search_loan_id = $_GET['loan_id'] ?? '';

if (!empty($search_loan_id)) {
    $sql = "
        SELECT
            lp.id AS payment_id,
            lp.amount,
            lp.payment_method,
            lp.payment_date,
            lp.mpesa_number,
            lp.credit_card_number,
            lp.installment_number,
            la.id AS loan_id,
            la.name AS user_name,
            la.user_email,
            la.phone AS user_phone,
            la.loan_duration AS total_duration,
            la.remaining_balance,
            la.fixed_monthly_installment,
            la.updated_at,
            d.name AS device_name,
            d.model AS device_model,
            d.image_url AS device_image
        FROM loan_payments lp
        JOIN loan_applications la ON lp.loan_application_id = la.id
        JOIN devices d ON la.device_id = d.id
        WHERE la.id = ?
        ORDER BY lp.payment_date DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare failed: " . $conn->error);
    }

    $loan_id_int = (int)$search_loan_id;
    $stmt->bind_param("i", $loan_id_int);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($payment = $result->fetch_assoc()) {
        $payments[] = $payment;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin - Paid Loan Installments</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
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
            background-color: #343a40;
            color: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .brand { font-weight: 700; font-size: 1.2rem; margin-right: 40px; }
        .menu { display: flex; gap: 12px; list-style: none; margin: 0; padding: 0; }
        .menu li a {
            color: white; text-decoration: none; font-weight: 600;
            font-size: 0.85rem; padding: 4px 8px; border-radius: 4px;
        }
        .menu li a.active, .menu li a:hover {
            background-color: rgba(255,255,255,0.25);
        }
        .right-section {
            margin-left: auto; display: flex; gap: 15px; align-items: center;
        }
        .right-section .btn {
            background: white; color: #343a40;
            padding: 5px 10px; text-decoration: none;
            border-radius: 4px; font-weight: bold;
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
        .page-header {
            text-align: center; color: #2c3e50; margin-bottom: 0px;
        }
        .loan-box {
            background: #fff; border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 25px; margin: 20px auto; max-width: 900px;
        }
        .loan-box img {
            max-width: 100px; margin-right: 20px; border-radius: 6px; float: left;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-top: 20px;
            font-weight: 700;
        }
        h3 {
            color: #2c3e50; margin-top: 0;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 8px;
        }
        .info-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;
        }
        .info-grid p, .payment-details p {
            margin: 6px 0; font-size: 0.95rem;
        }
        .payment-details {
            background-color: #f9f9f9;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-top: 15px;
            border-radius: 4px;
        }
        .paid-amount {
            font-size: 1.2rem; font-weight: bold; color: #28a745;
        }
        table {
            width: 100%; border-collapse: collapse;
        }
        table th, table td {
            padding: 10px; border-bottom: 1px solid #ddd; text-align: left;
        }
        table th {
            background-color: #f1f1f1;
        }
        .search-form {
            max-width: 900px;
            margin: 0 auto 20px auto;
            text-align: center;
        }
        .search-form input[type="number"] {
            padding: 8px;
            width: 200px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .search-form button {
            padding: 8px 12px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            margin-left: 5px;
        }
    </style>
</head>
<body>

<nav>
    <div class="brand">Milele Admin</div>
    <ul class="menu">
        <li><a href="admin_all_devices.php">Devices</a></li>
        <li><a href="admin_all_loans_applied.php">Loans</a></li>
        <li><a href="admin_all_payments.php" class="active">Payments</a></li>
        <li><a href="admin_users.php">Users</a></li>
        <li><a href="admin_send_messages.php">Messages</a></li>
    </ul>
    <div class="right-section">
        <span>Welcome Staff, <?= htmlspecialchars($name) ?></span>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</nav>

<h2 class="page-header">Loan Payment History</h2>

<div class="sub-nav">
   <a href="admin_all_payments.php">Payment history</a>
    <a href="admin_total_payments.php">Payments</a>
    <a href="admin_account_withdraw.php">Withdraw money</a>
</div>



<!-- 🔍 Loan ID Search Form -->
<div class="search-form">
    <form method="get" action="">
        <input type="number" name="loan_id" placeholder="Enter Loan ID" value="<?= htmlspecialchars($search_loan_id) ?>" />
        <button type="submit">🔍 Search</button>
    </form>
</div>

<!-- 📋 Loan ID Table -->
<?php if (!empty($loanIds)): ?>
    <div style="max-width: 900px; margin: 0 auto 20px auto; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <h4 style="margin-top: 0; color: #007BFF;">📄 All Loan IDs</h4>
        <table>
            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>User</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loanIds as $loan): ?>
                    <tr>
                        <td><?= htmlspecialchars($loan['loan_id']) ?></td>
                        <td><?= htmlspecialchars($loan['user_name']) ?></td>
                        <td>
                            <a href="?loan_id=<?= $loan['loan_id'] ?>" style="color: #007BFF; text-decoration: underline;">View Payments</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- 📂 Payment Results -->
<?php if (!empty($search_loan_id)): ?>
    <?php if (empty($payments)): ?>
        <p style="text-align:center; margin-top: 50px;">No loan payments found for Loan ID <?= htmlspecialchars($search_loan_id) ?>.</p>
    <?php else: ?>
        <?php foreach ($payments as $payment): ?>
            <?php
    $remaining_months = $payment['total_duration'] - $payment['installment_number'];
    $total_to_pay = $payment['fixed_monthly_installment'] * $payment['total_duration']; // Use this instead of total_with_interest
    $balance_after_payment = $total_to_pay - ($payment['installment_number'] * $payment['fixed_monthly_installment']);
?>

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
                        <h4>👤 User & Device</h4>
                        <p><strong>User:</strong> <?= htmlspecialchars($payment['user_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($payment['user_email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($payment['user_phone']) ?></p>
                        <p><strong>Device:</strong> <?= htmlspecialchars($payment['device_name']) ?> (<?= htmlspecialchars($payment['device_model']) ?>)</p>
                    </div>
                    <div>
                        <h4>📊 Loan Status</h4>
                        <p><strong>Installment Paid:</strong> <?= $payment['installment_number'] ?> of <?= $payment['total_duration'] ?></p>
                        <p><strong>Months Left:</strong> <?= max(0, $remaining_months) ?></p>
                        <p><strong>Balance After this Payment:</strong> KES <?= number_format($balance_after_payment, 2) ?></p>
                    </div>
                </div>

                <div class="payment-details">
                    <h4>✅ Payment Confirmation</h4>
                    <p><strong>Amount Paid:</strong> <span class="paid-amount">KES <?= number_format($payment['amount'], 2) ?></span></p>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($payment['payment_method']) ?></p>
                    <?php if ($payment['payment_method'] === 'MPesa'): ?>
                        <p><strong>MPesa Number:</strong> <?= htmlspecialchars($payment['mpesa_number']) ?></p>
                    <?php elseif ($payment['payment_method'] === 'Credit Card'): ?>
                        <p><strong>Card Number:</strong> <?= htmlspecialchars(substr($payment['credit_card_number'], 0, 4) . str_repeat('*', 8) . substr($payment['credit_card_number'], -4)) ?></p>
                    <?php endif; ?>
                    <p><strong>Payment Date:</strong> <?= htmlspecialchars(date('F j, Y, g:i a', strtotime($payment['updated_at']))) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
