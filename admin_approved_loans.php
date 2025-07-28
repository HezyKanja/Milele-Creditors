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
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch only approved loans
$loans = [];

$searchTerm = $_GET['search'] ?? '';
$searchSql = '';
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $searchSql = "AND (la.name LIKE ? OR la.user_email LIKE ? OR la.id = ?)";
    $searchTermLike = "%" . $searchTerm . "%";
    $params = [$searchTermLike, $searchTermLike, $searchTerm];
    $types = "ssi";
}

$query = "
    SELECT la.*, la.name AS user_name, d.name AS device_name, d.model, d.serial_number, d.image_url,
           (
               SELECT method 
               FROM payment_details 
               WHERE loan_application_id = la.id 
               ORDER BY created_at DESC 
               LIMIT 1
           ) AS payment_method
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    WHERE la.is_approved = 1
      AND EXISTS (
        SELECT 1 FROM payment_details 
        WHERE loan_application_id = la.id
      )
    $searchSql
    ORDER BY la.id DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
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
    <title>Approved Loans - Admin</title>
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
        .approved-label {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
            margin-top: 12px;
        }
        .section {
            margin-bottom: 20px;
        }
        
        .id-photo {
            max-width: 350px;
            height: auto;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .btn-download {
            background-color: #28a745;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            display: inline-block;
            text-decoration: none;
        }
        .btn-download:hover {
            background-color: #218838;
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

<h2 style="text-align:center;">✅ Approved Loans</h2>

<div class="sub-nav">
    <a href="admin_all_loans_applied.php">All Loans</a>
    <a href="admin_approved_loans.php">Approved Loans</a>
    <a href="admin_pending_loans.php">Pending Loans</a>
    <a href="admin_cleared_loans.php" class="active">Cleared Loans</a>
    <a href="admin_default_loans.php">Default Loans</a>
    <a href="admin_apply_penalties.php" class="active">Penalized Loans </a>
</div>

<div class="search-form" style="text-align: center; margin-bottom: 20px;">
    <form method="get">
        <input type="text" name="search" placeholder="Search by name, email or loan ID" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="padding: 8px; width: 250px; border-radius: 6px; border: 1px solid #ccc;" />
        <button type="submit" style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 6px;">Search</button>
    </form>
</div>

<?php if (count($loans) === 0): ?>
    <p style="text-align:center;">No approved loan applications found.</p>
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
                <?php if (!empty($loan['id_photo_path'])): ?>
                    <p><strong>ID Photo:</strong></p>
                    <a href="<?= htmlspecialchars($loan['id_photo_path']) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($loan['id_photo_path']) ?>" alt="ID Photo" class="id-photo" />
                    </a>
                <?php else: ?>
                    <p><strong>ID Photo:</strong> Not provided.</p>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3>💳 Loan Details</h3>
                <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
                <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
                <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
                <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['total_to_pay'], 2) ?></p>
                <p style="color: red;"><strong>Penalty Applied:</strong> KES <?= number_format($loan['penalty_applied'], 2) ?></p>
            </div>

            <div class="section">
                <h3>📆 Payment Info</h3>
                <p><strong>Method:</strong> <?= htmlspecialchars($loan['payment_method'] ?? 'N/A') ?></p>
                <p><strong>Applied:</strong> <?= htmlspecialchars($loan['created_at']) ?></p>
            </div>

            <p class="approved-label">Approved ✅</p>

            <a href="generate_receipt.php?loan_id=<?= (int)$loan['id'] ?>" target="_blank" class="btn-download">Download as PDF</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
