<?php
session_start();

$conn = new mysqli("localhost", "root", "", "milele_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure staff is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loan_id'])) {
    $loan_id = (int)$_POST['loan_id'];

    // 1. Get the device_id from the loan
    $stmt = $conn->prepare("SELECT device_id FROM loan_applications WHERE id = ? AND is_defaulted = 0 AND is_cleared = 0 AND is_approved = 1");
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $stmt->bind_result($device_id);
    if ($stmt->fetch()) {
        $stmt->close();

        // 2. Mark loan as defaulted
        $update = $conn->prepare("UPDATE loan_applications SET is_defaulted = 1 WHERE id = ?");
        $update->bind_param("i", $loan_id);
        $update->execute();
        $update->close();

        // 3. Return device to shop (increment device quantity)
        $updateDevice = $conn->prepare("UPDATE devices SET quantity = quantity + 1 WHERE id = ?");
        $updateDevice->bind_param("i", $device_id);
        $updateDevice->execute();
        $updateDevice->close();

        $_SESSION['success'] = "Loan #$loan_id marked as defaulted, device returned to inventory.";
    } else {
        $_SESSION['error'] = "Loan not found or already defaulted.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

$conn->close();

// Redirect back
header("Location: admin_default_loans.php");
exit();
