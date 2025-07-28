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



// Fetch all defaulted loans with user and device info
$sql = "
    SELECT 
        la.id AS loan_id, la.total_with_interest, la.remaining_balance, la.status,
        u.name AS user_name, u.email AS user_email,
        d.name AS device_name, d.model, d.serial_number, d.image_url
    FROM loan_applications la
    JOIN users u ON la.user_email = u.email
    JOIN devices d ON la.device_id = d.id
    WHERE la.is_defaulted = 1 AND la.is_approved = 1
    ORDER BY la.id DESC
";

$result = $conn->query($sql);
$loans = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $loans[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Defaulted Loans</title>
    <style>
       body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f8fb;
            padding: 0px;
            padding-top:30px; /* Adjusted for fixed nav */
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
</nav><br>
<h2 style="text-align:center;">📋 Pending Loan Applications</h2>

<div class="sub-nav">
    <a href="admin_all_loans_applied.php">All Loans</a>
    <a href="admin_approved_loans.php">Approved Loans</a>
    <a href="admin_pending_loans.php"  class="active">Pending Loans</a>
    <a href="admin_cleared_loans.php">Cleared Loans</a>
    <a href="admin_default_loans.php">Default Loans</a>
</div>

<div class="sub-nav">
    <a href="admin_default_loans.php">Default Loan</a>
    <a href="admin_view_defaulted_loans.php">View Defaulted Loans</a>
   
</div>

<h2>📛 Defaulted Loans</h2>

<?php if (count($loans) === 0): ?>
    <p class="no-loans">No defaulted loans found.</p>
<?php else: ?>
    <?php foreach ($loans as $loan): ?>
        <div class="loan-box">
            <?php if (!empty($loan['image_url'])): ?>
                <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image" class="device-img" />
            <?php endif; ?>

            <p><strong>Loan ID:</strong> <?= $loan['loan_id'] ?></p>
            <p><strong>User:</strong> <?= htmlspecialchars($loan['user_name']) ?> (<?= htmlspecialchars($loan['user_email']) ?>)</p>
            <p><strong>Device:</strong> <?= htmlspecialchars($loan['device_name']) ?> - <?= htmlspecialchars($loan['model']) ?></p>
            <p><strong>Serial Number:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
            <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
            <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['remaining_balance'], 2) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($loan['status']) ?></p>
            <p style="color: red;"><strong>⚠ Marked as Defaulted</strong></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
