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

// Fetch admin name
$user_id = $_SESSION['user_id'];
$name = 'Staff';
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'staff'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fetched_name);
if ($stmt->fetch()) {
    $name = $fetched_name;
}
$stmt->close();

// Initialize stats
$total_amount = 0;
$initial_payment_total = 0;
$total_payments = 0;
$total_mpesa = 0;
$total_card = 0;
$total_cash = 0;
$last_payment_date = "N/A";

// 1. Total from loan_payments
$result = $conn->query("SELECT SUM(amount) AS total, COUNT(*) AS count FROM loan_payments");
if ($result && $row = $result->fetch_assoc()) {
    $total_amount = $row['total'] ?? 0;
    $total_payments = $row['count'] ?? 0;
}

// 2. Add initial payments from approved loans
$init_result = $conn->query("SELECT SUM(initial_payment) AS initial_total FROM loan_applications WHERE is_approved = 1");
if ($init_result && $row = $init_result->fetch_assoc()) {
    $initial_payment_total = $row['initial_total'] ?? 0;
    $total_amount += $initial_payment_total;
}

// 3. Totals by method
$res2 = $conn->query("SELECT payment_method, SUM(amount) AS method_total FROM loan_payments GROUP BY payment_method");
if ($res2 && $res2->num_rows > 0) {
    while ($row = $res2->fetch_assoc()) {
        switch ($row['payment_method']) {
            case 'MPesa': $total_mpesa = $row['method_total']; break;
            case 'Credit Card': $total_card = $row['method_total']; break;
            case 'Cash': $total_cash = $row['method_total']; break;
        }
    }
}

// 4. Latest payment date
$res3 = $conn->query("SELECT MAX(payment_date) AS latest_date FROM loan_payments");
if ($res3 && $row = $res3->fetch_assoc()) {
    $last_payment_date = $row['latest_date'] ? date('F j, Y', strtotime($row['latest_date'])) : "N/A";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Total Payments</title>
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
        .stats-grid {
            max-width: 900px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .card {
            background: #f1f1f1;
            border-left: 6px solid #007BFF;
            padding: 20px;
            border-radius: 6px;
        }
        .card h3 {
            margin: 0;
            font-size: 1rem;
            color: #555;
        }
        .card .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007BFF;
            margin-top: 10px;
        }
        .card.green { border-color: #28a745; }
        .card.green .value { color: #28a745; }
        .card.orange { border-color: #fd7e14; }
        .card.orange .value { color: #fd7e14; }
        .card.gray { border-color: #6c757d; }
        .card.gray .value { color: #6c757d; }
        .card.brown { border-color: #795548; }
        .card.brown .value { color: #795548; }
        .card.blue { border-color: #007bff; }
        .card.blue .value { color: #007bff; }
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
    <a href="admin_all_payments.php">Payment History</a>
    <a href="admin_total_payments.php">Payments</a>
    <a href="admin_account_withdraw.php">Withdraw Money</a>
</div>

<div class="stats-grid">
    <a href="view_payments_by_method.php?payment_method=All" style="text-decoration:none;">
        <div class="card green">
            <h3>Total Amount Collected</h3>
            <div class="value">KES <?= number_format($total_amount, 2) ?></div>
        </div>
    </a>
    <a href="view_payments_by_method.php?payment_method=All" style="text-decoration:none;">
        <div class="card orange">
            <h3>Total Number of Payments</h3>
            <div class="value"><?= $total_payments ?></div>
        </div>
    </a>
    <a href="view_payments_by_method.php?payment_method=Initial" style="text-decoration:none;">
    <div class="card blue">
        <h3>Total Initial Payments</h3>
        <div class="value">KES <?= number_format($initial_payment_total, 2) ?></div>
    </div>
</a>

    <a href="view_payments_by_method.php?payment_method=MPesa" style="text-decoration:none;">
        <div class="card gray">
            <h3>Total via MPesa</h3>
            <div class="value">KES <?= number_format($total_mpesa, 2) ?></div>
        </div>
    </a>
    <a href="view_payments_by_method.php?payment_method=Credit Card" style="text-decoration:none;">
        <div class="card gray">
            <h3>Total via Credit Card</h3>
            <div class="value">KES <?= number_format($total_card, 2) ?></div>
        </div>
    </a>
    <a href="view_payments_by_method.php?payment_method=Cash" style="text-decoration:none;">
        <div class="card brown">
            <h3>Total via Cash</h3>
            <div class="value">KES <?= number_format($total_cash, 2) ?></div>
        </div>
    </a>
</div>

</body>
</html>
