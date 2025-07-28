<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch actual admin name from DB and store in session
$_SESSION['admin_name'] = $_SESSION['admin_name'] ?? 'Admin';
$user_id = $_SESSION['user_id'] ?? null;
$name = 'Staff';

if ($user_id) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'staff'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($fetched_name);
    if ($stmt->fetch()) {
        $name = $fetched_name;
        $_SESSION['admin_name'] = $fetched_name;
    }
    $stmt->close();
}

$admin_name = $_SESSION['admin_name']; // used in insert

// === Safely calculate totals ===

// Total loan payments (installments only)
$totalPayments = 0;
$paymentsResult = $conn->query("SELECT SUM(amount) as total_paid FROM loan_payments");
if ($paymentsResult) {
    $row = $paymentsResult->fetch_assoc();
    $totalPayments = $row['total_paid'] ?? 0;
} else {
    echo "Error fetching payments: " . $conn->error;
}

// Total initial payments (from loan_applications)
$totalInitialPayments = 0;
$initResult = $conn->query("SELECT SUM(initial_payment) as total_initial FROM loan_applications WHERE is_approved = 1");
if ($initResult) {
    $row = $initResult->fetch_assoc();
    $totalInitialPayments = $row['total_initial'] ?? 0;
} else {
    echo "Error fetching initial payments: " . $conn->error;
}

// Final grand total: installments + initial
$finalTotalCollected = $totalPayments + $totalInitialPayments;


// Total withdrawals
$totalWithdrawals = 0;
$withdrawalsResult = $conn->query("SELECT SUM(amount) as total_withdrawn FROM account_withdrawals");
if ($withdrawalsResult) {
    $row = $withdrawalsResult->fetch_assoc();
    $totalWithdrawals = $row['total_withdrawn'] ?? 0;
} else {
    echo "Error fetching withdrawals: " . $conn->error;
}

$availableBalance = $totalPayments - $totalWithdrawals;

// === Handle withdrawal form submission ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($amount > 0 && $amount <= $availableBalance) {
        $stmt = $conn->prepare("INSERT INTO account_withdrawals (amount, reason, admin_name) VALUES (?, ?, ?)");
        $stmt->bind_param("dss", $amount, $reason, $admin_name);
        $stmt->execute();

        $_SESSION['withdraw_msg'] = "Successfully withdrew KES " . number_format($amount, 2);
        header("Location: admin_account_withdraw.php");
        exit();
    } else {
        $_SESSION['withdraw_msg'] = "Amount exceeds available balance or is invalid.";
        header("Location: admin_account_withdraw.php");
        exit();
    }
}

// === Load message if any ===
$message = '';
if (isset($_SESSION['withdraw_msg'])) {
    $msg = $_SESSION['withdraw_msg'];
    $color = (strpos($msg, 'Successfully') !== false) ? 'green' : 'red';
    $message = "<p style='color:$color;'>$msg</p>";
    unset($_SESSION['withdraw_msg']);
}

// === Load withdrawal history ===
$withdrawals = $conn->query("SELECT * FROM account_withdrawals ORDER BY withdrawal_date DESC");
?>



<!DOCTYPE html>
<html>
<head>
    <title>Admin Withdraw from Loan Payments</title>
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
        .container { background: white; padding: 20px; max-width: 800px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin-top: 8px; border-radius: 5px; border: 1px solid #ccc; }
        button { padding: 10px 20px; background: #d9534f; color: white; border: none; border-radius: 5px; margin-top: 10px; cursor: pointer; }
        button:hover { background: #c9302c; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #f9f9f9; }
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

<div class="sub-nav">
    <a href="admin_all_payments.php">Payment history</a>
    <a href="admin_total_payments.php">Payments</a>
    <a href="admin_account_withdraw.php">Withdraw money</a>
</div>
    <div class="container">
        <h2>Admin: Withdraw from Loan Payments</h2>

<p><strong>Total Collected from Users:</strong> KES <?= number_format($finalTotalCollected, 2) ?></p>

<p><strong>Total Withdrawn So Far:</strong> KES <?= number_format($totalWithdrawals, 2) ?></p>
<p><strong>Available Balance:</strong><br>
   <span style="color:green; font-weight: bold;">
       KES <?= number_format($totalPayments + $totalInitialPayments - $totalWithdrawals, 2) ?>
   </span>
</p>


        <?= $message ?>

        <form method="POST">
            <label>Amount to Withdraw (KES):</label>
            <input type="number" step="0.01" name="amount" required>

            <label>Reason:</label>
            <textarea name="reason" rows="3" placeholder="e.g., Buy new devices..." required></textarea>

            <button type="submit">Withdraw</button>
        </form>

        <h3>Past Withdrawals</h3>
        <table>
            <tr>
                <th>#</th>
                <th>Amount</th>
                <th>Reason</th>
                <th>Date</th>
                <th>Admin</th>
            </tr>
            <?php $i = 1; while($row = $withdrawals->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>KES <?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= date('d M Y, H:i', strtotime($row['withdrawal_date'])) ?></td>
                    <td><?= htmlspecialchars($row['admin_name']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
