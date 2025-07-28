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

// Handle approval POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id'])) {
    $loanId = (int)$_POST['loan_id'];

    // Approve the loan
    $stmtUpdate = $conn->prepare("UPDATE loan_applications SET is_approved = 1 WHERE id = ?");
    $stmtUpdate->bind_param("i", $loanId);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // Fetch user info
    $stmtUser = $conn->prepare("SELECT user_email, name FROM loan_applications WHERE id = ?");
    $stmtUser->bind_param("i", $loanId);
    $stmtUser->execute();
    $stmtUser->bind_result($user_email, $user_name);
    if ($stmtUser->fetch()) {
        $stmtUser->close();

        // Get user ID
        $stmtUID = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmtUID->bind_param("s", $user_email);
        $stmtUID->execute();
        $stmtUID->bind_result($recipient_id);
        if ($stmtUID->fetch()) {
            $stmtUID->close();

            // Send message to user
            $subject = "Loan Approved";
            $message_text = "Dear $user_name,\n\nYour loan application (ID #$loanId) has been approved. Please visit the shop to collect your device.\n\nThank you for choosing Milele Creditors.";
            $sent_at = date('Y-m-d H:i:s');

            $stmtMsg = $conn->prepare("INSERT INTO user_messages (sender_id, recipient_id, subject, message_text, sent_at, is_read) VALUES (?, ?, ?, ?, ?, 0)");
            $stmtMsg->bind_param("iisss", $user_id, $recipient_id, $subject, $message_text, $sent_at);
            $stmtMsg->execute();
            $stmtMsg->close();
        }
    }

    header("Location: admin_approved_loans.php?msg=approved");
    exit;
}

// Fetch all loans with device info and latest payment method
$loans = [];
$searchTerm = $_GET['search'] ?? '';
$searchSql = "";
$params = [];

if (!empty($searchTerm)) {
    $searchSql = "WHERE la.name LIKE ? OR la.user_email LIKE ? OR la.id = ?";
    $params = ["%$searchTerm%", "%$searchTerm%", $searchTerm];
}

$sql = "
    SELECT la.*, la.name AS user_name, d.name AS device_name, d.model, d.serial_number, d.image_url,
        pd.method AS payment_method
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    JOIN (
        SELECT loan_application_id, method
        FROM payment_details
        WHERE (loan_application_id, created_at) IN (
            SELECT loan_application_id, MAX(created_at)
            FROM payment_details
            GROUP BY loan_application_id
        )
    ) pd ON pd.loan_application_id = la.id
    $searchSql
    ORDER BY la.id DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param("ssi", ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($loan = $result->fetch_assoc()) {
    $loans[] = $loan;
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin - Loan Approvals</title>
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
        
        .loan-box {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        h2, h3 {
            color: #2c3e50;
        }
        img {
            max-width: 150px;
            margin-bottom: 10px;
            border-radius: 6px;
        }
        
        .btn-approve {
            background-color: #007bff;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-approve:hover {
            background-color: #0056b3;
        }
        .approved-label {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
            margin-top: 12px;
        }
        .section {
            margin-bottom: 20px;
        }
        .message {
            max-width: 900px;
            margin: 0 auto 20px auto;
            padding: 10px 15px;
            background: #dff0d8;
            color: #3c763d;
            border-radius: 6px;
            font-weight: bold;
        }
        .nav-links {
            max-width: 900px;
            margin: 0 auto 25px auto;
            display: flex;
            justify-content: space-between;
        }
        .nav-links a {
            text-decoration: none;
            color: #343a40;
            font-weight: bold;
            font-size: 16px;
        }
        .nav-links a:hover {
            text-decoration: underline;
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

<h2 style="text-align:center;">🔒 Admin Loan Approval Panel</h2>

<div class="sub-nav">
    <a href="admin_all_loans_applied.php">All Loans</a>
    <a href="admin_approved_loans.php">Approved Loans</a>
    <a href="admin_pending_loans.php">Pending Loans</a>
    <a href="admin_cleared_loans.php" class="active">Cleared Loans</a>
    <a href="admin_default_loans.php">Default Loans</a>
    <a href="admin_apply_penalties.php" class="active">Penalized Loans </a>
</div>

<form method="get" style="text-align:center; margin-bottom: 20px;">
    <input type="text" name="search" placeholder="Search by name, email, or loan ID"
           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
           style="padding: 8px; width: 300px; border-radius: 5px; border: 1px solid #ccc;" />
    <button type="submit" style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Search</button>
</form>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
    <div class="message">✅ Loan approved successfully.</div>
<?php endif; ?>

<?php if (count($loans) === 0): ?>
    <p style="text-align:center;">No loan applications found.</p>

    


<?php else: ?>
    <?php foreach ($loans as $loan): ?>
        <div class="loan-box">
            <div class="section">
                <h3>📱 Device Information</h3>
                <?php if (!empty($loan['image_url'])): ?>
                    <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image" />
                <?php endif; ?>
                <p><strong>Device:</strong> <?= htmlspecialchars($loan['device_name']) ?></p>
                <p><strong>Model:</strong> <?= htmlspecialchars($loan['model']) ?></p>
                <p><strong>Serial Number:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
            </div>

            <div class="section">
                <h3>👤 User Details</h3>
                <p><strong>Loan ID:</strong> <?= (int)$loan['id'] ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($loan['user_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($loan['user_email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($loan['phone']) ?></p>
                <p><strong>ID Number:</strong> <?= htmlspecialchars($loan['id_number']) ?></p>

                <!-- ID PHOTO DISPLAYED HERE -->
                <?php if (!empty($loan['id_photo_path'])): ?>
                    <p><strong>ID Photo:</strong></p>
                    <a href="<?= htmlspecialchars($loan['id_photo_path']) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($loan['id_photo_path']) ?>" alt="ID Photo" class="id-photo" />
                    </a>
                <?php else: ?>
                    <p><strong>ID Photo:</strong> Not provided.</p>
                <?php endif; ?>
                <!-- END OF ID PHOTO DISPLAY -->

            </div>

            <div class="section">
                <h3>💳 Loan Details</h3>
                <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
                <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
                <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['remaining_balance'], 2) ?></p>
                <p style="color: red;"><strong>Penalty Applied:</strong> KES <?= number_format($loan['penalty_applied'], 2) ?></p>
                <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
            </div>

            <div class="section">
                <h3>📆 Payment History</h3>
                <p><strong>Payment Method:</strong> <?= htmlspecialchars($loan['payment_method'] ?? 'N/A') ?></p>
                <p><strong>Applied On:</strong> <?= htmlspecialchars($loan['created_at']) ?></p>
            </div>

            <?php if ($loan['is_approved'] == 0): ?>
                <form method="post" action="admin_all_loans_applied.php">
                    <input type="hidden" name="loan_id" value="<?= (int)$loan['id'] ?>">
                    <button type="submit" class="btn-approve">Approve Loan</button>
                </form>
            <?php else: ?>
                <p class="approved-label">Approved ✅</p>
            <?php endif; ?>

            
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
