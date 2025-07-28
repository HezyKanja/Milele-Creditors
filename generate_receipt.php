<?php
require_once('tcpdf/tcpdf.php'); // Adjust this path if needed

if (!isset($_GET['loan_id'])) {
    die("Loan ID not provided.");
}

$loanId = (int)$_GET['loan_id'];

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("
    SELECT la.*, la.name AS user_name, d.name AS device_name, d.model, d.serial_number
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    WHERE la.id = ? AND la.is_approved = 1
");
$stmt->bind_param("i", $loanId);
$stmt->execute();
$result = $stmt->get_result();
$loan = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$loan) {
    die("Approved loan not found.");
}

// Create PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Milele Creditors');
$pdf->SetTitle('Loan Approval Form');
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();

// HTML content with APPROVED sign
$html = '
<h2 style="text-align:center;">Milele Creditors - Loan Approval</h2>
<div style="text-align:center; font-size:18px; color:green; font-weight:bold; margin-bottom:10px;">
    APPROVED
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
<p><strong>Date Applied:</strong> ' . $loan['created_at'] . '</p>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('loan_approved_' . $loanId . '.pdf', 'I');
