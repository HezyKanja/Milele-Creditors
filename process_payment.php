<?php
session_start();
require_once('db_connection.php');

$userEmail = $_SESSION['user_email'] ?? '';
if (!$userEmail) {
    die("User email not set. Please login.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_POST['loan_id'], $_POST['payment_amount'])) {
    die("Missing required data.");
}

$loan_id = (int)$_POST['loan_id'];
$payment_amount = floatval($_POST['payment_amount']);

if ($payment_amount <= 0) {
    die("Invalid payment amount.");
}

$conn = new mysqli("localhost", "root", "", "milele_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check loan details
$stmtCheck = $conn->prepare("SELECT remaining_balance, is_cleared, is_disbursed, months_paid, loan_duration FROM loan_applications WHERE id = ? AND user_email = ?");
if (!$stmtCheck) {
    die("Prepare failed: " . $conn->error);
}
$stmtCheck->bind_param("is", $loan_id, $userEmail);
$stmtCheck->execute();
$stmtCheck->bind_result($remaining_balance, $is_cleared, $is_disbursed, $months_paid, $loan_duration);

if (!$stmtCheck->fetch()) {
    die("Loan not found.");
}
$stmtCheck->close();

if (!$is_disbursed) {
    die("Loan is not disbursed yet.");
}
if ($is_cleared) {
    die("Loan is already cleared.");
}
if ($payment_amount > $remaining_balance) {
    die("Payment exceeds remaining balance of KES " . number_format($remaining_balance, 2));
}

// Before processing payment, confirm loan is not defaulted
$stmt = $conn->prepare("SELECT is_defaulted FROM loan_applications WHERE id = ?");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$stmt->bind_result($is_defaulted);
$stmt->fetch();
$stmt->close();

if ($is_defaulted) {
    die("❌ This loan is defaulted. Payments are not allowed.");
}


// Calculate new balance and months paid
$new_balance = $remaining_balance - $payment_amount;
$is_cleared_new = $new_balance <= 0 ? 1 : 0;

$months_left = $loan_duration - $months_paid;
if ($months_left < 1) $months_left = 1;
$installment = $remaining_balance / $months_left;

$months_paid_new = ($payment_amount >= $installment) ? $months_paid + 1 : $months_paid;

// Update loan
$stmtUpdate = $conn->prepare("UPDATE loan_applications SET remaining_balance = ?, is_cleared = ?, months_paid = ? WHERE id = ?");
if (!$stmtUpdate) {
    die("Prepare failed: " . $conn->error);
}
$stmtUpdate->bind_param("diii", $new_balance, $is_cleared_new, $months_paid_new, $loan_id);

$success = $stmtUpdate->execute();
$stmtUpdate->close();
$conn->close();

if (!$success) {
    die("Payment failed. Please try again.");
}

// Redirect back to loans list or show success message
header("Location: payment_success.php?loan_id=$loan_id&paid=" . urlencode($payment_amount));
exit;
