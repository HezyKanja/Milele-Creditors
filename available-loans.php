<?php
session_start();

$email = $_SESSION['user_email'] ?? '';
if (!$email) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch only NOT approved loans for this user
$stmt = $conn->prepare("
    SELECT la.*, d.name AS device_name, d.model, d.serial_number, d.image_url, d.price AS device_price, pd.method, la.name AS user_name
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    LEFT JOIN payment_details pd ON la.id = pd.loan_application_id
    WHERE la.user_email = ? AND la.is_approved = 0
    ORDER BY la.id DESC
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$loans = [];
while ($row = $result->fetch_assoc()) {
    $loans[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Available Loans</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            padding: 40px;
        }
        .summary-container {
            max-width: 750px;
            margin: 30px auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #007bff;
            text-align: center;
            margin-bottom: 25px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            font-size: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .section p {
            font-size: 15px;
            margin: 6px 0;
        }
        img {
            max-width: 100%;
            border-radius: 6px;
        }
        .back-link, .approved-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            font-weight: bold;
            font-size: 16px;
            text-decoration: none;
        }
        .approved-link {
            color: #28a745;
        }
        .btn-status {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: default;
            color: white;
            margin-top: 10px;
            display: inline-block;
        }
        .btn-pending {
            background-color: #dc3545;
        }
    </style>
</head>
<body>

<h2>⏳ Available Loans (Pending Approval)</h2>

<?php if (empty($loans)): ?>
    <p style="text-align:center;">No loans pending approval.</p>
<?php else: ?>
    <?php foreach ($loans as $loan): ?>
        <div class="summary-container">
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
                <p><strong>Name:</strong> <?= htmlspecialchars($loan['user_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($loan['user_email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($loan['phone']) ?></p>
                <p><strong>ID Number:</strong> <?= htmlspecialchars($loan['id_number']) ?></p>
                <p><strong>ID Photo:</strong>
                    <?php if (!empty($loan['id_photo_path'])): ?>
                        <a href="<?= htmlspecialchars($loan['id_photo_path']) ?>" target="_blank">View</a>
                    <?php else: ?>
                        Not uploaded
                    <?php endif; ?>
                </p>
            </div>

            <div class="section">
                <h3>💳 Loan Details</h3>
                <p><strong>Loan ID:</strong> <?= (int)$loan['id'] ?></p>
                <p><strong>Price:</strong> KES <?= number_format($loan['device_price'], 2) ?></p>
                <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
                <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
                <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
                <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['remaining_balance'], 2) ?></p>
                <p><strong>Payment Method:</strong> <?= ucfirst($loan['method']) ?: 'N/A' ?></p>
            </div>

            <button class="btn-status btn-pending" disabled>⏳ Pending Approval</button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="approved_loans.php" class="approved-link">View Approved Loans &rarr;</a>

</body>
</html>
