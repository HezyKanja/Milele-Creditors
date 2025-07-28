<?php
session_start();
$conn = new mysqli("localhost", "root", "", "milele_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure only staff/admin users access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = 'Staff';

// Get staff name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'staff'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fetched_name);
if ($stmt->fetch()) {
    $name = $fetched_name;
}
$stmt->close();

$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_penalty'])) {
    $loan_id = (int)$_POST['loan_id'];
    $penalty = (float)$_POST['penalty_amount'];

    if ($penalty <= 0) {
        $error = "Penalty must be greater than zero.";
    } else {
        // Fetch current loan details
        $stmt = $conn->prepare("SELECT remaining_balance, loan_duration FROM loan_applications WHERE id = ? AND is_approved = 1 AND is_defaulted = 0");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $stmt->bind_result($current_balance, $loan_duration);
        $found = $stmt->fetch();
        $stmt->close();

        if ($found) {
            // Count how many installments have been paid
            $stmt = $conn->prepare("SELECT COUNT(*) FROM loan_payments WHERE loan_application_id = ?");
            $stmt->bind_param("i", $loan_id);
            $stmt->execute();
            $stmt->bind_result($paid_installments);
            $stmt->fetch();
            $stmt->close();

            $remaining_months = $loan_duration - $paid_installments;

            if ($remaining_months <= 0) {
                $error = "All installments already paid.";
            } else {
                $new_balance = $current_balance + $penalty;
                $new_installment = round($new_balance / $remaining_months, 2);

                // Update remaining_balance, fixed_monthly_installment, and penalty_applied
                $update = $conn->prepare("
                    UPDATE loan_applications 
                    SET remaining_balance = ?, 
                        fixed_monthly_installment = ?, 
                        penalty_applied = penalty_applied + ?
                    WHERE id = ?
                ");
                $update->bind_param("dddi", $new_balance, $new_installment, $penalty, $loan_id);

                if ($update->execute()) {
                    $success = "✅ Penalty of KES " . number_format($penalty, 2) .
                               " applied.<br>📌 New remaining balance: KES " . number_format($new_balance, 2) .
                               "<br>📅 New monthly installment: KES " . number_format($new_installment, 2);
                } else {
                    $error = "❌ Failed to apply penalty.";
                }

                $update->close();
            }
        } else {
            $error = "Loan not found.";
        }
    }
}

// Fetch active loans
$loans = [];
$sql = "SELECT la.id, la.user_email, d.name AS device_name, la.remaining_balance
        FROM loan_applications la
        JOIN devices d ON la.device_id = d.id
        WHERE la.is_approved = 1 AND la.is_defaulted = 0 AND la.remaining_balance > 0";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $loans[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Apply Loan Penalty - Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f8fb;
            padding: 0px;
            padding-top:50px; /* Adjusted for fixed nav */
            margin: 0;
        }

        nav { /* Simplified admin nav */
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 50px;
            position: fixed;
            width: 100%;
            top: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            z-index: 1000;
            background-color: #343a40; /* Darker admin theme */
            color: white;
        }
        .brand { font-weight: 700; font-size: 1.2rem; margin-right: 40px; }
        .menu { display: flex; gap: 12px; list-style: none; margin: 0; padding: 0; }
        .menu li a { color: white; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 4px 8px; border-radius: 4px; transition: 0.25s; }
        .menu li a.active, .menu li a:hover { background-color: rgba(255,255,255,0.25); }
        .right-section { margin-left: auto; display: flex; gap: 15px; align-items: center; }
        .right-section span { font-size: 0.9rem; }
        .right-section .btn { background: white; color: #343a40; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .page-header {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px; /* Space below header */
        }
        .sub-nav {
            max-width: 900px;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            gap: 30px;
        }
        .sub-nav a {
            text-decoration: none;
            font-weight: bold;
            color: #007bff;
        }
        .sub-nav a:hover {
            text-decoration: underline;
        }
         h2 {
            text-align: center;
            color: #e63946;
            margin-bottom: 30px;
        }
        .form-container {
            background: #fff;
            padding: 25px;
            max-width: 600px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        label, select, input {
            width: 100%;
            margin-bottom: 15px;
        }
        select, input[type="number"] {
            padding: 8px;
            font-size: 1rem;
        }
        .btn {
            background: #28a745;
            color: #fff;
            padding: 10px 14px;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn:hover {
            background: #218838;
        }
        .msg {
            margin: 20px auto;
            max-width: 600px;
            padding: 15px;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<nav>
    <div class="brand">Milele Admin</div>
    <ul class="menu">
       <li><a href="admin_all_devices.php">Devices</a></li>
            <li><a href="admin_all_loans_applied.php" class="active">Loans</a></li>
            <li><a href="admin_all_payments.php">Payments</a></li>
            <li><a href="admin_users.php">Users</a></li>
            <li><a href="admin_send_messages.php">Messages</a></li>
            
    </ul>
    <div class="right-section">
         <span>Welcome Staff, <?= htmlspecialchars($name) ?></span>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</nav>

<h2>📌 Apply Penalty to Active Loan</h2>

<div class="sub-nav">
    <a href="admin_all_loans_applied.php">All Loans</a>
    <a href="admin_approved_loans.php">Approved Loans</a>
    <a href="admin_pending_loans.php">Pending Loans</a>
    <a href="admin_cleared_loans.php" class="active">Cleared Loans</a>
    <a href="admin_default_loans.php">Default Loans</a>
    <a href="admin_apply_penalties.php" class="active">Penalized Loans </a>
</div>

<div class="sub-nav">
   <a href="admin_apply_penalties.php" class="active">Apply Loan Penalty </a>
   <a href="admin_penalized_loans.php" class="active">Penalized Loans </a>
    
</div>



<?php if ($success): ?><div class="msg success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="msg error"><?= $error ?></div><?php endif; ?>

<div class="form-container">
    <form method="post">
        <label>Select Loan:</label>
        <select name="loan_id" required>
            <option value="">-- Choose Loan --</option>
            <?php foreach ($loans as $loan): ?>
                <option value="<?= $loan['id'] ?>">
                    Loan #<?= $loan['id'] ?> - <?= htmlspecialchars($loan['user_email']) ?> (<?= htmlspecialchars($loan['device_name']) ?>) - KES <?= number_format($loan['remaining_balance'], 2) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Penalty Amount (KES):</label>
        <input type="number" name="penalty_amount" min="1" step="0.01" required>

        <button type="submit" name="apply_penalty" class="btn">✅ Apply Penalty</button>
    </form>
</div>

</body>
</html>
