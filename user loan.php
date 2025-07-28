<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

$userEmail = $_SESSION['user_email'];

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("
    SELECT 
        la.*, 
        d.name AS device_name, d.model, d.serial_number, d.image_url,
        (SELECT method FROM payment_details WHERE loan_application_id = la.id ORDER BY created_at DESC LIMIT 1) AS payment_method
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    WHERE la.user_email = ?
    ORDER BY la.created_at DESC
");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();

$loans = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>My Loans</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
            padding: 20px;
            max-width: 900px;
            margin: auto;
        }
        .loan-box {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        h2, h3 {
            color: #2c3e50;
        }
        img {
            max-width: 150px;
            margin-bottom: 10px;
            border-radius: 6px;
        }
        .status-approved {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
            margin-top: 12px;
        }
        .status-pending {
            color: #c0392b;
            font-weight: bold;
            font-size: 18px;
            margin-top: 12px;
        }
        .section {
            margin-bottom: 20px;
        }
        .no-loans {
            text-align: center;
            font-size: 18px;
            color: #555;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">My Loan Applications</h2>

<?php if (count($loans) === 0): ?>
    <p class="no-loans">You have no loan applications.</p>
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
                <h3>👤 Your Details</h3>
                <p><strong>Loan ID:</strong> <?= (int)$loan['id'] ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($loan['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($loan['user_email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($loan['phone']) ?></p>
                <p><strong>ID Number:</strong> <?= htmlspecialchars($loan['id_number']) ?></p>
            </div>

            <div class="section">
                <h3>💳 Loan Details</h3>
                <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
                <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
                <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
                <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['remaining_balance'], 2) ?></p>
            </div>

            <div class="section">
                <h3>📆 Payment Info</h3>
                <p><strong>Method:</strong> <?= htmlspecialchars($loan['payment_method'] ?? 'N/A') ?></p>
                <p><strong>Applied:</strong> <?= htmlspecialchars($loan['created_at']) ?></p>
            </div>

            <?php if ((int)$loan['is_approved'] === 1): ?>
                <p class="status-approved">Approved ✅</p>
            <?php else: ?>
                <p class="status-pending">Pending Approval ⏳</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
