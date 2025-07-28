<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$user_name = $_SESSION['user_name'] ?? 'Valued Client';

// Fetch the latest cleared loan only
$sql = "SELECT la.*, d.name AS device_name, d.model, d.serial_number, d.image_url
        FROM loan_applications la
        JOIN devices d ON la.device_id = d.id
        WHERE la.user_email = ? AND la.remaining_balance <= 0
        ORDER BY la.updated_at DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $conn->error);
}

$stmt->bind_param("s", $user_email);
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
    <title>🎉 Your Cleared Loans | Milele Creditors</title>
    <style>
        body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
    }
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .celebrate {
            max-width: 900px;
            background-color: #d1f0da;
            border-left: 8px solid #2ecc71;
            padding: 20px;
            margin: 0 auto 30px;
            font-size: 1.2rem;
            color: #2d572c;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
        }
        .loan-box {
            max-width: 900px;
            margin: 0 auto 30px;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            border-left: 6px solid #3498db;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .loan-box img {
            max-width: 150px;
            border-radius: 6px;
            float: right;
            margin-left: 20px;
        }
        .loan-box h3 {
            color: #34495e;
            margin-top: 0;
        }
        p {
            margin: 8px 0;
            color: #555;
        }
        .status-complete {
            color: #27ae60;
            font-weight: bold;
        }
        .no-loans {
            text-align: center;
            font-size: 1.2rem;
            color: #888;
        }
        .proceed-btn {
            display: inline-block;
            background-color: #2ecc71;
            color: white;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-top: 15px;
        }
        .proceed-btn:hover {
            background-color: #27ae60;
        }

        /* Modal styles */
        #thankYouModal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        #thankYouModal .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            margin: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        #thankYouModal h3 {
            color: #27ae60;
            margin-top: 0;
        }
        .icon-links a {
  font-size: 1.5rem;          /* Slightly larger */
  color: white;
  text-decoration: none;
  transition: transform 0.2s ease, color 0.3s ease;
}

.icon-links a:hover {
  color: #ffdd57;             /* Highlight color */
  transform: scale(1.2);      /* Slight zoom on hover */
}
    </style>
</head>
<body>

<h2>🎊 Congratulations <?= htmlspecialchars($user_name) ?>!</h2>

<?php if (empty($cleared_loans)): ?>
    <p class="no-loans">You have no cleared loans yet. Keep going, you're doing great! 💪</p>
<?php else: ?>
    <div class="celebrate">
        🎉 You’ve successfully cleared <?= count($cleared_loans) ?> loan<?= count($cleared_loans) > 1 ? 's' : '' ?> with us!
        Thank you for being a responsible client with Milele Creditors. Keep enjoying your device(s)! 🚀
    </div>

    <?php foreach ($cleared_loans as $loan): ?>
        <div class="loan-box">
            <?php if (!empty($loan['image_url'])): ?>
                <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image">
            <?php endif; ?>
            <h3>📱 <?= htmlspecialchars($loan['device_name']) ?> - <?= htmlspecialchars($loan['model']) ?></h3>
            <p><strong>Loan ID:</strong> <?= (int)$loan['id'] ?></p>
            <p><strong>Serial Number:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
            <p style="color:blue"><strong>Total Paid:</strong> KES <?= number_format($loan['total_paid'], 2) ?></p>
            <p><strong>Total With Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
            <p style="color: red;"><strong>Penalty Applied:</strong> KES <?= number_format($loan['penalty_applied'], 2) ?></p>
            <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
            <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
            <p><strong>Status:</strong> <span class="status-complete">✅ Fully Paid</span></p>
            <p><strong>Completed On:</strong> <?= htmlspecialchars($loan['updated_at'] ?? 'N/A') ?></p>
            <button class="proceed-btn" onclick="handleProceed()">Proceed to get other loan</button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal -->
<div id="thankYouModal">
    <div class="modal-content">
        <h3>🎉 Thank You!</h3>
        <p>You’ve cleared your loan successfully and can now proceed to get another device.</p>
    </div>
</div>

<script>
function handleProceed() {
    const modal = document.getElementById("thankYouModal");
    modal.style.display = "flex";
    setTimeout(() => {
        window.location.href = "user_available_devices.php";
    }, 2000); // Redirect after 2 seconds
}
</script>

</body>
</html>

<?php $conn->close(); ?>
