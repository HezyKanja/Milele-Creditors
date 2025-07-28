<?php
if (!isset($_GET['user_id'])) {
    die("User ID not provided.");
}

$userId = (int)$_GET['user_id'];
$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details using user_id
$userStmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
if (!$userStmt) {
    die("Prepare failed for user query: " . $conn->error);
}
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    die("User not found.");
}

$userEmail = $user['email'];

// Fetch all loans under this user email including device image
$loanStmt = $conn->prepare("
    SELECT la.*, d.name AS device_name, d.model, d.serial_number, d.image_url
    FROM loan_applications la 
    JOIN devices d ON la.device_id = d.id 
    WHERE la.user_email = ?
    ORDER BY la.id DESC
");
if (!$loanStmt) {
    die("Prepare failed for loan query: " . $conn->error);
}
$loanStmt->bind_param("s", $userEmail);
$loanStmt->execute();
$loans = $loanStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($user['name']) ?>'s Loans</title>
    <style>
        body {
            font-family: Arial;
            background: #f9f9f9;
            padding: 30px;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .loan-box {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        .loan-box p {
            margin: 5px 0;
        }
        .loan-box img {
            max-width: 200px;
            height: auto;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .status {
            font-weight: bold;
        }
        .approved {
            color: green;
        }
        .pending {
            color: orange;
        }
        .cleared {
            color: blue;
        }
        a.back {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        a.back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<a href="admin_users.php" class="back">← Back to Users</a>
<h2>Loans for <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</h2>

<!-- Menu Bar for Loan Filters -->
<div style="text-align:center; margin-bottom: 30px;">
    <button onclick="filterLoans('all')" style="padding: 10px 20px;">All Loans</button>
    <button onclick="filterLoans('pending')" style="padding: 10px 20px;">Pending</button>
    <button onclick="filterLoans('approved')" style="padding: 10px 20px;">Approved</button>
    <button onclick="filterLoans('cleared')" style="padding: 10px 20px;">Cleared</button>
</div>

<div id="no-loans-message" style="text-align:center; color: red; margin-top: 20px; display: none;">
    No loans found in this category.
</div>


<?php if ($loans->num_rows === 0): ?>
    <p>No loans found for this user.</p>
<?php else: ?>
    <?php while ($loan = $loans->fetch_assoc()): ?>
        <?php
            $statusClass = 'pending';
            $statusText = 'Pending ⏳';

            if ((float)$loan['remaining_balance'] == 0) {
                $statusClass = 'cleared';
                $statusText = 'Cleared ✅';
            } elseif ((int)$loan['is_approved'] === 1) {
                $statusClass = 'approved';
                $statusText = 'Approved ✅';
            }
        ?>
        <div class="loan-box loan-item <?= $statusClass ?>">
            <?php if (!empty($loan['image_url'])): ?>
                <p><strong>Device Image:</strong></p>
                <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image">
            <?php endif; ?>

            <p><strong>Loan ID:</strong> <?= $loan['id'] ?></p>
            <p><strong>Device:</strong> <?= htmlspecialchars($loan['device_name']) ?> - <?= htmlspecialchars($loan['model']) ?></p>
            <p><strong>Serial No:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
            <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
            <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
            <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
            <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['remaining_balance'], 2) ?></p>
                        <p style="color: red;"><strong>Penalty Applied:</strong> KES <?= number_format($loan['penalty_applied'], 2) ?></p>
            <p><strong>Date Applied:</strong> <?= $loan['created_at'] ?></p>
            <p class="status <?= $statusClass ?>"><strong>Status:</strong> <?= $statusText ?></p>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<!-- JavaScript to Filter Loans -->
<script>
function filterLoans(type) {
    const loans = document.querySelectorAll('.loan-item');
    let hasVisible = false;

    loans.forEach(loan => {
        if (type === 'all' || loan.classList.contains(type)) {
            loan.style.display = 'block';
            hasVisible = true;
        } else {
            loan.style.display = 'none';
        }
    });

    // Show or hide the no-loans message
    const message = document.getElementById('no-loans-message');
    message.style.display = hasVisible ? 'none' : 'block';
}
</script>


</body>
</html>
