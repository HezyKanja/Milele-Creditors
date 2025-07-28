<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_email']) || !isset($_SESSION['user_name'])) {
    // Not logged in → redirect to login
    header("Location: login.php");
    exit();
}

$name = $_SESSION['user_name'] ?? '';
$email = $_SESSION['user_email'] ?? '';

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Get the user's rating
$rating = 0;
$ratingQuery = $conn->prepare("
    SELECT COUNT(*) AS cleared 
    FROM loan_applications 
    WHERE name = ? AND is_cleared = 1
");
$ratingQuery->bind_param("s", $name);
$ratingQuery->execute();
$ratingResult = $ratingQuery->get_result();
if ($ratingResult && $row = $ratingResult->fetch_assoc()) {
    $rating = min((int)$row['cleared'], 10); // Cap at 10
}
$ratingQuery->close();

// Step 2: Count active loans (not yet cleared)
$activeLoans = 0;
$activeQuery = $conn->prepare("
    SELECT COUNT(*) AS active_loans 
    FROM loan_applications 
    WHERE name = ? AND is_cleared = 0
");
$activeQuery->bind_param("s", $name);
$activeQuery->execute();
$activeResult = $activeQuery->get_result();
if ($activeResult && $row = $activeResult->fetch_assoc()) {
    $activeLoans = (int)$row['active_loans'];
}
$activeQuery->close();

// Step 3: Check if the user exceeds their limit
$allowedLoans = $rating + 1;
if ($activeLoans >= $allowedLoans) {
    if ($rating === 0) {
        header("Location: user_available_devices.php?msg=limit_reached_no_rating");
    } else {
        header("Location: user_available_devices.php?msg=limit_reached");
    }
    exit();
}


define('ENCRYPTION_KEY', 'your-secret-16key'); // 16 bytes

function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

$device = null;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['device_id'])) {
    $device_id = intval($_POST['device_id']);
    $phone = $_POST['phone'];
    $id_number = $_POST['id_number'];
    $initial_payment = floatval($_POST['initial_payment']);
    $loan_duration = intval($_POST['loan_duration']);
    $payment_method = $_POST['payment_method'];
    $device_price = floatval($_POST['device_price']);

    switch ($loan_duration) {
        case 6:  $interest_rate = 0.05; break;
        case 12: $interest_rate = 0.12; break;
        case 18: $interest_rate = 0.20; break;
        default: $interest_rate = 0.0;
    }

    $total_with_interest = $device_price + ($device_price * $interest_rate);
    $remaining_balance = $total_with_interest - $initial_payment;

    $id_photo_path = '';
    if (isset($_FILES['id_photo']) && $_FILES['id_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = basename($_FILES['id_photo']['name']);
        $targetFilePath = $uploadDir . uniqid() . '_' . $filename;
        if (move_uploaded_file($_FILES['id_photo']['tmp_name'], $targetFilePath)) {
            $id_photo_path = $targetFilePath;
        }
    }

   $stmt = $conn->prepare("INSERT INTO loan_applications 
    (device_id, name, user_email, phone, id_number, initial_payment, loan_duration, payment_method, total_with_interest, remaining_balance, id_photo_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) die("Prepare failed: " . $conn->error);

$stmt->bind_param("issssdisdss",
    $device_id, $name, $email, $phone, $id_number, $initial_payment, $loan_duration,
    $payment_method, $total_with_interest, $remaining_balance, $id_photo_path
);


    if ($stmt->execute()) {
        $loan_id = $stmt->insert_id;
        $payment_data = '';

        if ($payment_method === 'credit_card') {
            $card_number = $_POST['card_number'] ?? '';
            $payment_data = encryptData($card_number, ENCRYPTION_KEY);
        } elseif ($payment_method === 'mpesa') {
            $mpesa_phone = $_POST['mpesa_phone'] ?? '';
            $payment_data = encryptData($mpesa_phone, ENCRYPTION_KEY);
        }

        if ($payment_data) {
            $stmt2 = $conn->prepare("INSERT INTO payment_details (loan_application_id, encrypted_data, method, user_email) VALUES (?, ?, ?, ?)");
            if ($stmt2) {
                $stmt2->bind_param("isss", $loan_id, $payment_data, $payment_method, $email);
                $stmt2->execute();
                $stmt2->close();
            }
        }

        // After calculating remaining_balance...
$fixed_monthly_installment = $loan_duration > 0 ? round($remaining_balance / $loan_duration, 2) : 0;

// Updated query to include fixed_monthly_installment
$stmt = $conn->prepare("INSERT INTO loan_applications 
    (device_id, name, user_email, phone, id_number, initial_payment, loan_duration, payment_method, total_with_interest, remaining_balance, fixed_monthly_installment, id_photo_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) die("Prepare failed: " . $conn->error);

$stmt->bind_param("issssdisddss",
    $device_id, $name, $email, $phone, $id_number, $initial_payment, $loan_duration,
    $payment_method, $total_with_interest, $remaining_balance, $fixed_monthly_installment, $id_photo_path
);


        echo "<script>alert('Loan application submitted successfully.');</script>";
        echo "<script>window.location.href='user_loan_summary.php';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=loan_summary.php'></noscript>";
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("SELECT * FROM devices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $device = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Loan Checkout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
         body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
    padding-top: 50px;
    }
        .form-container {
            background: white; max-width: 600px; margin: 0 auto; padding: 30px;
            border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #007bff; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input[type="text"], input[type="number"], input[type="email"], select {
            width: 100%; padding: 10px; margin-top: 8px; border: 1px solid #ccc; border-radius: 6px;
        }
        .hidden { display: none; }
        button {
            margin-top: 25px; width: 100%; padding: 12px; background-color: #ff9800;
            color: white; font-size: 16px; border: none; border-radius: 8px; cursor: pointer;
        }
        .device-info {
            margin-top: 20px; padding: 10px; background: #fafafa; border-left: 4px solid #007bff;
        }
        .device-info p { margin: 5px 0; }
    </style>
</head>
<body>

<a href="user_available_devices.php">&larr; Back to Devices</a>

<div class="form-container">
    <h2>Loan Application</h2>

    <div class="device-info">
        <?php if (!empty($device['image_url'])): ?>
            <img src="<?= htmlspecialchars($device['image_url']) ?>" alt="Device Image" style="max-width:100%;">
        <?php endif; ?>
        <p><strong>Device:</strong> <?= htmlspecialchars($device['name'] ?? '') ?></p>
        <p><strong>Model:</strong> <?= htmlspecialchars($device['model'] ?? '') ?></p>
        <p><strong>Serial Number:</strong> <?= htmlspecialchars($device['serial_number'] ?? '') ?></p>
        <p><strong>Price:</strong> KES <?= number_format($device['price'] ?? 0, 2) ?></p>
        <p><strong>Deposit:</strong> KES <?= number_format($device['deposit_price'] ?? 0, 2) ?></p>
    </div>

    <form id="loanForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="device_id" value="<?= $device['id'] ?? '' ?>">

        <label>Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" readonly>

        <label>Email</label>
        <input type="email" value="<?= htmlspecialchars($email) ?>" readonly>
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

        <label>Phone Number</label>
        <input type="text" name="phone" required>

        <label>ID Number</label>
        <input type="text" name="id_number" required>

        <label>Upload ID Photo</label>
        <input type="file" name="id_photo" accept="image/*" required>

        <label>Device Price (KES)</label>
        <input type="text" id="device_price" value="<?= number_format($device['price'] ?? 0, 2) ?>" readonly>
        <input type="hidden" name="device_price" value="<?= $device['price'] ?? 0 ?>">

        <label>Initial Payment (KES)</label>
        <input type="number" name="initial_payment" required min="0" value="<?= htmlspecialchars($device['deposit_price'] ?? 0) ?>" readonly>


        <label>Loan Duration</label>
        <select name="loan_duration" id="loan_duration" required>
            <option value="">Choose duration</option>
            <option value="6">6 Months</option>
            <option value="12">12 Months</option>
            <option value="18">18 Months</option>
        </select>

        <label>Total Payable with Interest (KES)</label>
        <input type="text" id="total_with_interest" readonly>

        <label>Remaining Balance After Deposit (KES)</label>
        <input type="text" id="remaining_balance" readonly>

        <label>Payment Method</label>
        <select name="payment_method" id="payment_method" required onchange="togglePaymentFields()">
            <option value="">Select Payment Method</option>
            <option value="credit_card">Credit Card</option>
            <option value="mpesa">MPesa</option>
        </select>

        <div id="credit_card_fields" class="hidden">
    <label>Card Number</label>
    <input type="text" name="card_number" id="card_number" maxlength="19" placeholder="xxxx xxxx xxxx xxxx">

    <div class="cc-details">
        <div>
            <label>Expiry Date (MM/YY)</label>
            <input type="text" name="card_expiry" id="card_expiry" maxlength="5" placeholder="MM/YY">
        </div>
        <div>
            <label>CVV</label>
            <input type="text" name="card_cvv" id="card_cvv" maxlength="4" placeholder="123">
        </div>
    </div>
</div>


        <div id="mpesa_fields" class="hidden">
            <label>MPesa Phone</label>
            <input type="text" name="mpesa_phone" id="mpesa_phone" maxlength="10">
        </div>

        <button type="submit">Submit Loan Request</button>
    </form>
</div>

<script>
function togglePaymentFields() {
    const method = document.getElementById('payment_method').value;
    const cc = document.getElementById('credit_card_fields');
    const mp = document.getElementById('mpesa_fields');
    
    // Hide all first and remove required attributes
    cc.classList.add('hidden');
    mp.classList.add('hidden');
    document.getElementById('card_number').required = false;
    document.getElementById('card_expiry').required = false; // New
    document.getElementById('card_cvv').required = false;    // New
    document.getElementById('mpesa_phone').required = false;

    // Show selected and set required attributes
    if (method === 'credit_card') {
        cc.classList.remove('hidden');
        document.getElementById('card_number').required = true;
        document.getElementById('card_expiry').required = true; // New
        document.getElementById('card_cvv').required = true;    // New
    } else if (method === 'mpesa') {
        mp.classList.remove('hidden');
        document.getElementById('mpesa_phone').required = true;
    }
}

// Format card number with spaces as user types
document.getElementById('card_number').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.substring(0, 16);
    value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
    e.target.value = value;
});

// NEW: Format expiry date with a slash as user types
document.getElementById('card_expiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// UPDATED: Add validation for new fields on form submit
document.getElementById('loanForm').addEventListener('submit', function (e) {
    const method = document.getElementById('payment_method').value;
    if (method === 'credit_card') {
        const card = document.getElementById('card_number').value.trim();
        if (!/^\d{4} \d{4} \d{4} \d{4}$/.test(card)) {
            e.preventDefault();
            alert('Please enter a valid 16-digit card number.');
            return;
        }

        const expiry = document.getElementById('card_expiry').value.trim();
        const expiryRegex = /^(0[1-9]|1[0-2])\/?([0-9]{2})$/;
        if (!expiryRegex.test(expiry)) {
            e.preventDefault();
            alert('Please enter a valid expiry date in MM/YY format.');
            return;
        }

        const cvv = document.getElementById('card_cvv').value.trim();
        if (!/^\d{3,4}$/.test(cvv)) {
            e.preventDefault();
            alert('Please enter a valid 3 or 4-digit CVV.');
            e.preventDefault();
            return;
        }
    }
});

// This calculation function does not need changes
function calculateInterest() {
    const price = parseFloat(document.querySelector('input[name="device_price"]').value.replace(/,/g, '')) || 0;
    const deposit = parseFloat(document.querySelector('input[name="initial_payment"]').value) || 0;
    const duration = parseInt(document.getElementById('loan_duration').value);

    let interestRate = 0;
    if (duration === 6) interestRate = 0.12;
    else if (duration === 12) interestRate = 0.24;
    else if (duration === 18) interestRate = 0.36;

    const total = price + (price * interestRate);
    const balance = total - deposit;

    document.getElementById('total_with_interest').value = total > 0 ? total.toLocaleString('en-KE', { minimumFractionDigits: 2 }) : '';
    document.getElementById('remaining_balance').value = balance > 0 ? balance.toLocaleString('en-KE', { minimumFractionDigits: 2 }) : '';
}

document.getElementById('loan_duration').addEventListener('change', calculateInterest);
document.querySelector('input[name="initial_payment"]').addEventListener('input', calculateInterest);
</script>

</body>
</html>
