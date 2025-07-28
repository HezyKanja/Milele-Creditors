<?php
session_start();
require_once 'db_connection.php';

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
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search input
$search = $_GET['search'] ?? '';

$sql = "SELECT la.*, d.name AS device_name, d.model, d.serial_number, d.image_url
        FROM loan_applications la
        JOIN devices d ON la.device_id = d.id
        WHERE la.remaining_balance <= 0";
        

if (!empty($search)) {
    $sql .= " AND (la.user_email LIKE ? OR la.id = ?)";
    $stmt = $conn->prepare($sql);
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param("si", $likeSearch, $search);
} else {
    $stmt = $conn->prepare($sql);
}

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
    <title>Admin - Cleared Loans</title>
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
        h2 {
            text-align: center;
            color: #2c3e50;
        }
        .search-box {
            text-align: center;
            margin-bottom: 20px;
        }
        .search-box input[type="text"] {
            width: 300px;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        .search-box button {
            padding: 10px 16px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            margin-left: 10px;
        }
        .loan-box {
            max-width: 950px;
            background: #fff;
            margin: 20px auto;
            padding: 20px;
            border-left: 6px solid #27ae60;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }
        .loan-box img {
            max-width: 140px;
            float: right;
            margin-left: 20px;
            border-radius: 6px;
        }
        .loan-box h3 {
            margin: 0;
            color: #34495e;
        }
        p {
            color: #555;
            margin: 6px 0;
        }
        .status {
            color: #27ae60;
            font-weight: bold;
        }
        .no-loans {
            text-align: center;
            color: #888;
            font-size: 1.1rem;
            margin-top: 40px;
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

<h2>✅ All Cleared Loans by Users</h2>

<div class="sub-nav">
    <a href="admin_all_loans_applied.php">All Loans</a>
    <a href="admin_approved_loans.php">Approved Loans</a>
    <a href="admin_pending_loans.php">Pending Loans</a>
    <a href="admin_cleared_loans.php" class="active">Cleared Loans</a>
    <a href="admin_default_loans.php">Default Loans</a>
    <a href="admin_apply_penalties.php" class="active">Penalized Loans </a>
</div>



<div class="search-box">
    <form method="GET">
        <input type="text" name="search" placeholder="Search by Email or Loan ID" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>
</div>

<?php if (empty($cleared_loans)): ?>
    <p class="no-loans">No cleared loans found for your search.</p>
<?php else: ?>
    <?php foreach ($cleared_loans as $loan): ?>
        <?php
        $loan_id = $loan['id'];
        $completed_on = 'N/A';
        $date_result = $conn->query("SELECT MAX(due_date) as completed_on FROM repayment_schedule WHERE loan_id = $loan_id AND status = 'Paid'");
        if ($date_result && $date_row = $date_result->fetch_assoc()) {
            $completed_on = $date_row['completed_on'] ?? 'N/A';
        }
        ?>
        <div class="loan-box">
            <?php if (!empty($loan['image_url'])): ?>
                <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image">
            <?php endif; ?>
            <h3><?= htmlspecialchars($loan['device_name']) ?> - <?= htmlspecialchars($loan['model']) ?></h3>
            <p><strong>User Email:</strong> <?= htmlspecialchars($loan['user_email']) ?></p>
            <p><strong>Loan ID:</strong> <?= $loan['id'] ?></p>
            <p><strong>Serial Number:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
             <p style="color:blue"><strong>Total Paid:</strong> KES <?= number_format($loan['total_paid'], 2) ?></p>
            <p><strong>Total With Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
            <p style="color: red;"><strong>Penalty Applied:</strong> KES <?= number_format($loan['penalty_applied'], 2) ?></p>
            <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
            <p><strong>Status:</strong> <span class="status">Fully Paid</span></p>
            <p><strong>Completed On:</strong> <?= htmlspecialchars($loan['updated_at'] ?? 'N/A') ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
