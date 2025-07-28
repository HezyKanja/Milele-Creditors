<?php
// Process Credit Card payment
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Extract POST data
    $device_id = intval($_POST['device_id']);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $device_price = floatval($_POST['device_price']);
    $initial_payment = floatval($_POST['initial_payment']);
    $loan_duration = intval($_POST['loan_duration']);
    $card_number = $_POST['card_number'];  // You should add proper card validation
    $expiry_date = $_POST['expiry_date'];
    $cvv = $_POST['cvv'];

    // Add your credit card payment processing logic here (e.g., using a payment gateway API)
    echo "Processing Credit Card Payment for device ID: $device_id...<br>";
    // For demonstration purposes, you could simulate processing.
    // Redirect or display a success message after processing.
}
?>
