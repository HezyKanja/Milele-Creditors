<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "milele_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch all devices
$devices = [];
$sql = "SELECT id, name, price, deposit_price FROM devices ORDER BY name ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
}
$conn->close();
$name = $_SESSION['user_name'] ?? 'Borrower';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Calculator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
    }

        /* Nav Bar Styles */
        nav {
            background-color: #007BFF;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 50px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
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

        .menu li {
            cursor: pointer;
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

        .menu li a:hover {
            background-color: rgba(255, 255, 255, 0.25);
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

        /* Container Styles */
        .container {
            background: #fff;
            max-width: 600px;
            margin: 60px auto 20px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #007BFF;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        select, input[type="number"], input[readonly] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .btn {
        background-color: white;
        color: #007BFF;
        border: none;
        padding: 6px 14px;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.25s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn:hover {
        background-color: #e2e6ea;
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

<!-- Navigation Menu -->
<nav>
  <div class="brand">
    <a href="user_home.php" style="text-decoration: none; color: white;">Milele Creditors</a>
  </div>

  <ul class="menu">
  <li><a href="user_home.php">Home</a></li>
  <li><a href="user_about_us.php">About Us</a></li>
  <li><a href="user_available_devices.php">Available Devices</a></li>
  <li><a href="user_loan_calculator.php" class="active">Loan Calculator</a></li>

  <?php if (isset($_SESSION['user_id'])): ?>
    <li><a href="user_available_loans.php">Loans</a></li>
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

<!-- Loan Calculator Form -->
<div class="container">
    <h2>Loan Calculator</h2>

    <label for="device_select">Select Device</label>
    <select id="device_select" onchange="updatePrice()">
        <option value="" data-price="0" data-deposit="0">-- Select a device --</option>
        <?php foreach ($devices as $device): ?>
            <option value="<?= $device['id'] ?>" 
                    data-price="<?= $device['price'] ?>" 
                    data-deposit="<?= $device['deposit_price'] ?>">
                <?= htmlspecialchars($device['name']) ?> (KES <?= number_format($device['price'], 2) ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label>Device Price (KES)</label>
    <input type="text" id="device_price" readonly value="0.00" />

    <label>Initial Payment (KES)</label>
    <input type="number" id="initial_payment" readonly value="0" min="0" />

    <label>Loan Duration</label>
    <select id="loan_duration" onchange="calculateLoan()">
        <option value="">-- Choose Duration --</option>
        <option value="6">6 months (5%)</option>
        <option value="12">12 months (12%)</option>
        <option value="18">18 months (20%)</option>
    </select>

    <label>Total with Interest (KES)</label>
    <input type="text" id="total_with_interest" readonly value="0.00" />

    <label>Remaining Balance (KES)</label>
    <input type="text" id="remaining_balance" readonly value="0.00" />

</div>

<footer style="color: black; text-align: center; padding: 30px 0;">
  &copy; <?= date("Y") ?> Milele Creditors. All Rights Reserved.
</footer>


<script>
function updatePrice() {
    const select = document.getElementById('device_select');
    const selectedOption = select.options[select.selectedIndex];
    const price = parseFloat(selectedOption.dataset.price) || 0;
    const deposit = parseFloat(selectedOption.dataset.deposit) || 0;

    document.getElementById('device_price').value = price.toFixed(2);
    document.getElementById('initial_payment').value = deposit.toFixed(2);
    calculateLoan();
}

function calculateLoan() {
    const price = parseFloat(document.getElementById('device_price').value) || 0;
    const deposit = parseFloat(document.getElementById('initial_payment').value) || 0;
    const duration = parseInt(document.getElementById('loan_duration').value) || 0;

    if (deposit > price) {
        alert("Initial payment cannot exceed the device price.");
        document.getElementById('initial_payment').value = 0;
        return;
    }

    let interestRate = 0;
    if (duration === 6) interestRate = 0.05;
    else if (duration === 12) interestRate = 0.12;
    else if (duration === 18) interestRate = 0.20;

    const total = price + (price * interestRate);
    const balance = total - deposit;

    document.getElementById('total_with_interest').value = total.toFixed(2);
    document.getElementById('remaining_balance').value = balance.toFixed(2);
}


function confirmLogout(event) {
  event.preventDefault();
  if (confirm("Do you want to log out?")) {
    window.location.href = "logout.php";
  }
}

</script>

</body>
</html>
