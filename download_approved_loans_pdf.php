<?php
session_start();
require_once('tcpdf/tcpdf.php');

// Check if the user is logged in
$user_email = $_SESSION['user_email'] ?? null;
if (!$user_email) {
    header("Location: login.php");
    exit();
}

// Get the loan ID from the query string
$loan_id = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : 0;
if ($loan_id <= 0) {
    die('Invalid Loan ID');
}

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the specific approved loan details
$stmt = $conn->prepare("
    SELECT la.*, la.name AS user_name, d.name AS device_name, d.model, d.serial_number,
        (SELECT method FROM payment_details pd WHERE pd.loan_application_id = la.id ORDER BY created_at DESC LIMIT 1) AS payment_method
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    WHERE la.is_approved = 1
      AND la.id = ?
      AND la.user_email = ?
");
$stmt->bind_param("is", $loan_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();

$loan = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$loan) {
    die('Loan not found or you do not have permission to access it.');
}

// Initialize TCPDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Milele Creditors');
$pdf->SetTitle('Loan Approval Details');
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->SetFont('helvetica', '', 11);

// Add a page
$pdf->AddPage();

// HTML content for the loan details
$html = '
<h2 style="text-align:center;">Milele Creditors - Loan Approval</h2>
<div style="text-align:center; font-size:18px; color:green; font-weight:bold; margin-bottom:10px;">
    APPROVED LOAN
</div>
<hr><br>

<h3>Device Information</h3>
<p><strong>Device:</strong> ' . htmlspecialchars($loan['device_name']) . '</p>
<p><strong>Model:</strong> ' . htmlspecialchars($loan['model']) . '</p>
<p><strong>Serial Number:</strong> ' . htmlspecialchars($loan['serial_number']) . '</p>

<h3>User Details</h3>
<p><strong>Loan ID:</strong> ' . $loan['id'] . '</p>
<p><strong>Name:</strong> ' . htmlspecialchars($loan['user_name']) . '</p>
<p><strong>Email:</strong> ' . htmlspecialchars($loan['user_email']) . '</p>
<p><strong>Phone:</strong> ' . htmlspecialchars($loan['phone']) . '</p>
<p><strong>ID Number:</strong> ' . htmlspecialchars($loan['id_number']) . '</p>

<h3>Loan Details</h3>
<p><strong>Loan ID:</strong> ' . $loan['id'] . '</p>
<p><strong>Initial Payment:</strong> KES ' . number_format($loan['initial_payment'], 2) . '</p>
<p><strong>Total with Interest:</strong> KES ' . number_format($loan['total_with_interest'], 2) . '</p>
<p><strong>Loan Duration:</strong> ' . $loan['loan_duration'] . ' months</p>
<p><strong>Remaining Balance:</strong> KES ' . number_format($loan['remaining_balance'], 2) . '</p>
<p><strong>Penalty Applied:</strong> KES ' . number_format($loan['penalty_applied'], 2) . '</p>
<p><strong>Payment Method:</strong> ' . htmlspecialchars($loan['payment_method'] ?? 'N/A') . '</p>
<p><strong>Date Applied:</strong> ' . htmlspecialchars($loan['created_at']) . '</p>
';

// Write HTML content to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF
$pdf->Output('approved_loan_' . $loan['id'] . '.pdf', 'D');
exit;
?>
