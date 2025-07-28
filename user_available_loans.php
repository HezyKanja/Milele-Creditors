<?php
session_start();

// Simulate user session name and email for filtering
$name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? null;

if (!$user_email) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loans = [];
$stmt = $conn->prepare("
    SELECT la.*, la.name AS user_name, d.name AS device_name, d.model, d.serial_number, d.image_url,
        (SELECT method FROM payment_details pd WHERE pd.loan_application_id = la.id ORDER BY created_at DESC LIMIT 1) AS payment_method
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    WHERE la.is_approved = 0
      AND la.user_email = ?
      AND EXISTS (
          SELECT 1 FROM payment_details pd
          WHERE pd.loan_application_id = la.id
      )
    ORDER BY la.id DESC
");

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

while ($loan = $result->fetch_assoc()) {
    $loans[] = $loan;
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Available Loans</title>
    <style>
        body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
      padding-top: 50px;
    }

        nav {
            background-color: #007BFF;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 50px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
        }
        .brand {
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 1px;
            margin-right: 40px;
        }

        .menu {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 12px;
        }

        .menu li a {
            text-decoration: none;
            color: white;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: background-color 0.25s ease;
        }

         .menu li a.active {
            background-color: white;
            color: #007BFF;
            font-weight: 700;
        }

        .right-section {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .right-section span {
            font-size: 0.9rem;
        }

        .right-section .btn {
            background: white;
            color: #007BFF;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        /* Submenu Bar */
        .subnav {
            background-color: #f1f1f1;
            padding: 8px 20px;
            display: flex;
            justify-content: center;
            position: fixed;
            top: 50px;
            width: 100%;
            z-index: 999;
            border-bottom: 1px solid #ccc;
        }

        .subnav a {
            margin: 0 12px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            font-size: 14px;
        }

        .subnav a:hover {
            text-decoration: underline;
        }

        .subnav a.active {
            color: #0056b3;
            text-decoration: underline;
        }

        .loan-box {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 25px;
    margin: 30px auto;
    max-width: 900px;
    text-align: left; /* <== Ensures left alignment of content */
}


        h2 {
            color: #2c3e50;
            text-align: center;
        }

        img {
            max-width: 150px;
            margin-bottom: 10px;
            border-radius: 6px;
        }

        .section {
            margin-bottom: 20px;
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

<!-- Main Top Menu -->
<nav>
  <div class="brand">
    <a href="user_home.php" style="text-decoration: none; color: white;">Milele Creditors</a>
  </div>

<ul class="menu">
  <li><a href="user_home.php">Home</a></li>
  <li><a href="user_about_us.php">About Us</a></li>
  <li><a href="user_available_devices.php">Available Devices</a></li>
  <li><a href="user_loan_calculator.php">Loan Calculator</a></li>

  <?php if (isset($_SESSION['user_id'])): ?>
    <li><a href="user_available_loans.php" class="active">Loans</a></li>
    <li><a href="user_pay_for_loan.php">Pay for loan</a></li>
  <?php endif; ?>
</ul>



  <div class="right-section">
  <?php if (isset($_SESSION['user_id'])): ?>
    <span>Welcome, <?= htmlspecialchars($name) ?></span>
    <div class="icon-links">
      <a href="user_profile.php" title="Profile">👤</a>
      <a href="user_messages.php" title="Messages">💬</a>
    </div>
   <a href="#" class="btn" onclick="confirmLogout(event)">Logout</a>
  <?php else: ?>
    <a href="login.php" class="btn">Log In</a>
  <?php endif; ?>
</div>
</nav>

<!-- Submenu for Loan Pages -->
<div class="subnav">
    <a href="user_available_loans.php" class="active">Available Loans</a>
    <a href="user_approved_loans.php">Approved Loans</a>
    <a href="user_loans_cleared.php">Cleared Loans</a>
    <a href="user_defaulted_loans.php">Defaulted Loans</a>
</div>

<h2>📋 Available (Unapproved) Loans</h2>

<?php if (count($loans) === 0): ?>
    <p style="text-align:center;">No available loan applications found.</p>
<?php else: ?>
    <?php foreach ($loans as $loan): ?>
        <div class="loan-box">
            <div class="section">
                <h3>📱 Device Information</h3>
                <?php if (!empty($loan['image_url'])): ?>
                    <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image" />
                <?php endif; ?>
                <p><strong>Device:</strong> <?= htmlspecialchars($loan['device_name']) ?></p>
                <p><strong>Model:</strong> <?= htmlspecialchars($loan['model']) ?></p>
                <p><strong>Serial Number:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
            </div>

            <div class="section">
                <h3>👤 User Details</h3>
                <p><strong>Loan ID:</strong> <?= (int)$loan['id'] ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($loan['user_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($loan['user_email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($loan['phone']) ?></p>
                <p><strong>ID Number:</strong> <?= htmlspecialchars($loan['id_number']) ?></p>
            </div>

            <div class="section">
                <h3>💳 Loan Details</h3>
                <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
                <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
                <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['remaining_balance'], 2) ?></p>
                <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
                <?php if (!empty($row['offer_details'])): ?>
        <p style="color: green;"><strong>Offer:</strong> <?= htmlspecialchars($row['offer_details']) ?></p>
    <?php endif; ?>
            </div>

            <div class="section">
    <h3>📆 Payment History</h3>
    <p><strong>Payment Method:</strong> <?= htmlspecialchars($loan['payment_method'] ?? 'N/A') ?></p>
    <p><strong>Applied On:</strong> <?= htmlspecialchars($loan['created_at']) ?></p>
</div>

<div class="section" style="text-align: center;">
    <button style="
        background-color: #ffc107;
        color: black;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-weight: bold;
        cursor: not-allowed;
    " disabled>
        ⏳ Pending Approval
    </button>
</div>

        </div>
    <?php endforeach; ?>
<?php endif; ?>

<footer style="color: black; text-align: center; padding: 30px 0;">
  &copy; <?= date("Y") ?> Milele Creditors. All Rights Reserved.
</footer>


  <script>
function confirmLogout(event) {
  event.preventDefault();
  if (confirm("Do you want to log out?")) {
    window.location.href = "logout.php";
  }
}
</script>

</body>
</html>
