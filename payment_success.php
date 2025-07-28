<?php
session_start();
$userEmail = $_SESSION['user_email'] ?? '';
if (!$userEmail) {
    die("Please login.");
}

$loan_id = $_GET['loan_id'] ?? null;
$paid = $_GET['paid'] ?? null;

if (!$loan_id || !$paid) {
    die("Invalid access.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 50px; text-align: center; }
        .box { background: white; padding: 30px; margin: 0 auto; width: 400px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        a.btn { text-decoration:none; display:inline-block; margin-top: 20px; padding: 10px 15px; background:#007bff; color:white; border-radius: 5px;}
        a.btn:hover { background:#0056b3; }
    </style>
</head>
<body>
    <div class="box">
        <h2>✅ Payment Successful</h2>
        <p>You have successfully paid <strong>KES <?= htmlspecialchars(number_format($paid, 2)) ?></strong> towards Loan ID <strong><?= htmlspecialchars($loan_id) ?></strong>.</p>
        <a href="loans_list.php" class="btn">← Back to My Loans</a>
    </div>
</body>
</html>
