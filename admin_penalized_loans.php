<?php
session_start();
$conn = new mysqli("localhost", "root", "", "milele_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure only staff/admin users access
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


$penalized_loans = [];

$sql = "SELECT 
            la.id AS loan_id, 
            la.user_email, 
            d.name AS device_name, 
            la.total_with_interest, 
            la.remaining_balance, 
            IFNULL(SUM(lp.amount), 0) AS total_paid
        FROM loan_applications la
        JOIN devices d ON la.device_id = d.id
        LEFT JOIN loan_payments lp ON la.id = lp.loan_application_id
        WHERE la.is_approved = 1
        GROUP BY la.id
        HAVING (total_paid + remaining_balance) > total_with_interest";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $row['penalty_amount'] = ($row['total_paid'] + $row['remaining_balance']) - $row['total_with_interest'];
    $penalized_loans[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Penalized Loans</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f8fb;
            padding: 0px;
            padding-top:50px; /* Adjusted for fixed nav */
            margin: 0;
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
        .loan-box {
            background: white;
            padding: 20px;
            margin: 20px auto;
            max-width: 700px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #e63946;
            margin-bottom: 30px;
        }
        .container {
            width: 95%;
            max-width: 1200px;
            margin: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            background-color: #ffffff;
        }
        th, td {
            padding: 14px 16px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #0d6efd;
            color: white;
            font-size: 15px;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .highlight {
            color: #d90429;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            font-size: 1.2rem;
            margin-top: 50px;
            color: #555;
        }
    </style>
</head>
<body>

<nav>
    <div class="brand">Milele Admin</div>
    <ul class="menu">
       <li><a href="admin_all_devices.php">Devices</a></li>
            <li><a href="admin_all_loans_applied.php" class="active">Loans</a></li>
            <li><a href="admin_all_payments.php">Payments</a></li>
            <li><a href="admin_users.php">Users</a></li>
            <li><a href="admin_send_messages.php">Messages</a></li>
            
    </ul>
    <div class="right-section">
         <span>Welcome Staff, <?= htmlspecialchars($name) ?></span>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</nav>

  <h2>🚨 Loans with Applied Penalties</h2>

<div class="sub-nav">
    <a href="admin_all_loans_applied.php">All Loans</a>
    <a href="admin_approved_loans.php">Approved Loans</a>
    <a href="admin_pending_loans.php">Pending Loans</a>
    <a href="admin_cleared_loans.php" class="active">Cleared Loans</a>
    <a href="admin_default_loans.php">Default Loans</a>
    <a href="admin_apply_penalties.php" class="active">Penalized Loans </a>
</div>

<div class="sub-nav">
   <a href="admin_apply_penalties.php" class="active">Apply Loan Penalty </a>
   <a href="admin_penalized_loans.php" class="active">Penalized Loans </a>
    
</div>

<div class="container">
  

    <?php if (count($penalized_loans) === 0): ?>
        <div class="no-data">✅ No penalized loans at the moment.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>User Email</th>
                    <th>Device</th>
                    <th>Total With Interest (KES)</th>
                    <th>Total Paid (KES)</th>
                    <th>Remaining Balance (KES)</th>
                    <th class="highlight">Penalty (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penalized_loans as $loan): ?>
                    <tr>
                        <td><?= $loan['loan_id'] ?></td>
                        <td><?= htmlspecialchars($loan['user_email']) ?></td>
                        <td><?= htmlspecialchars($loan['device_name']) ?></td>
                        <td><?= number_format($loan['total_with_interest'], 2) ?></td>
                        <td><?= number_format($loan['total_paid'], 2) ?></td>
                        <td><?= number_format($loan['remaining_balance'], 2) ?></td>
                        <td class="highlight"><?= number_format($loan['penalty_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
