<?php
session_start();

// Get the user's name from the session
$name = $_SESSION['user_name'] ?? 'Borrower';

// Display system messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'no_loans') {
        echo "<p style='color: green; font-weight: bold;'>You currently have no disbursed loans. Feel free to browse and request available devices.</p>";
    } elseif ($_GET['msg'] === 'limit_reached') {
        echo "<p style='color: red; font-weight: bold;'>You have reached your maximum number of active loans based on your clearance rating. Clear existing loans to increase your limit.</p>";
    } elseif ($_GET['msg'] === 'limit_reached_no_rating') {
        echo "<p style='color: red; font-weight: bold;'>Since you have not cleared any loan yet, you are only allowed to take one loan. Clear it first to increase your clearance rating.</p>";
    }
}


// Database connection
$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$searchTerm = $_GET['search'] ?? '';
if (!empty($searchTerm)) {
    $stmt = $conn->prepare("SELECT * FROM devices WHERE name LIKE ?");
    $searchWildcard = "%" . $searchTerm . "%";
    $stmt->bind_param("s", $searchWildcard);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM devices";
    $result = $conn->query($sql);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Available Devices</title>
 <link rel="stylesheet" href="style.css"/>

<style>
      body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
    padding-top: 10px;
    }
       nav {
    background-color: #007BFF;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between; /* ensure right-section stays on the right cleanly */
    padding: 0 10px; /* reduced to prevent overflow on smaller screens */
    height: 50px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 10000; /* ⬅️ Set higher than #welcome-screen (which is 9999) */
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

        /* === Animation styles === */
        #welcome-screen {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to right, #2c3e50, #007BFF);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            z-index: 9999;
            animation: fadeOut 2s ease-in-out 2s forwards;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        #welcome-screen h1 {
            font-size: 3em;
            animation: slideIn 1.5s ease-in-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #main-content {
            opacity: 0;
            animation: fadeInMain 1s ease-in-out 4s forwards;
        }

        @keyframes fadeInMain {
            to { opacity: 1; }
        }

        /* === Provided Styling === */
        
        h1 {
            text-align: center;
            color: white;
            margin-top: 80px;
            font-weight: 700;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-top: 80px;
            font-weight: 700;
        }
        p {
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 20px;
    color: white;
}

        .device-container {
    display: flex;
    flex-direction: row; /* Already set in media queries */
    flex-wrap: wrap;
    justify-content: center;
    align-items: stretch; /* 🔑 Makes child cards stretch to same height */
    gap: 20px;
    margin-top: 20px;
    padding-bottom: 30px;
}

.device-card {
    display: flex;              /* 🔑 Allow flex column inside */
    flex-direction: column;     /* Stack content */
    justify-content: space-between; /* Stretch button area to bottom */
    background: white;
    width: 100%;
    max-width: 400px;
    min-height: 500px;          /* Optional: forces a minimum height */
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.2s ease;
    position: relative;
}

.device-details {
    flex-grow: 1;               /* 🔑 Take all available space */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 20px;
}


        .device-card:hover {
            transform: scale(1.01);
        }

        .device-details {
            padding: 20px;
        }

        .device-details h3 {
            margin: 0 0 10px;
            font-size: 22px;
            color: #007BFF;
        }

        .device-details p {
            margin: 8px 0;
            font-size: 16px;
            color: #444;
        }

        .button-group {
            margin-top: 15px;
        }

        .cart-button {
            display: inline-block;
            width: 100%;
            padding: 10px;
            font-size: 14px;
            background-color: #ff9800;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .cart-button:hover {
            background-color: #e67e22;
        }

        @media (min-width: 768px) {
            .device-container {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
            .device-card {
                width: 45%;
            }
        }

        @media (min-width: 1024px) {
            .device-card {
                width: 30%;
            }
        }
        .icon-links a {
  font-size: 1.5rem;         
  color: white;
  text-decoration: none;
  transition: transform 0.2s ease, color 0.3s ease;
}

.icon-links a:hover {
  color: #ffdd57;             
  transform: scale(1.2);      /* Slight zoom on hover */
}

#checkout-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
}

.checkout-message {
    background: white;
    color: #007BFF;
    font-size: 1.5rem;
    padding: 20px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    font-weight: bold;
}
    </style>
</head>
<body>

<?php if (!isset($_GET['search'])): ?>
<!-- Welcome Screen -->
<div id="welcome-screen">
    <h1>Milele Creditors</h1>
    <p>Fetching available devices for you...</p>
</div>
<?php endif; ?>


<nav>
  <div class="brand">
    <a href="user_home.php" style="text-decoration: none; color: white;">Milele Creditors</a>
  </div>

<ul class="menu">
  <li><a href="user_home.php">Home</a></li>
  <li><a href="user_about_us.php">About Us</a></li>
  <li><a href="user_available_devices.php" class="active">Available Devices</a></li>
  <li><a href="user_loan_calculator.php">Loan Calculator</a></li>

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

<h2>Available Devices</h2>

<form method="get" style="text-align:center; margin-top: 20px;">
    <input type="text" name="search" placeholder="Search by device name..." value="<?= htmlspecialchars($searchTerm ?? '') ?>" style="padding: 8px; width: 250px; border: 1px solid #ccc; border-radius: 5px;">
    <button type="submit" style="padding: 8px 15px; background-color: #007BFF; color: white; border: none; border-radius: 5px; font-weight: bold;">Search</button>
</form>


<div class="device-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="device-card <?= strtolower($row['status']) !== 'available' ? 'unavailable' : '' ?>">
                <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                <div class="device-details">
    <h3><?= htmlspecialchars($row['name']) ?></h3>
    <p><strong>Model:</strong> <?= htmlspecialchars($row['model']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>
    <p><strong>Quantity Available:</strong> <?= htmlspecialchars($row['quantity_available']) ?> in stock</p>
    <p><strong>Price:</strong> KES <?= htmlspecialchars(number_format($row['price'], 2)) ?></p>
    <p><strong>Deposit:</strong> KES <?= htmlspecialchars(number_format($row['deposit_price'], 2)) ?></p>
    <p><strong>Serial No:</strong> <?= htmlspecialchars($row['serial_number']) ?></p>
    
        <?php if (!empty($row['offer_details'])): ?>
        <p style="color: green;"><strong>Offer:</strong> <?= htmlspecialchars($row['offer_details']) ?></p>
    <?php endif; ?>



                    <?php if (strtolower($row['status']) === 'available'): ?>
                        <div class="button-group">
                            <form method="post" onsubmit="return showCheckoutMessage(this);" action="loan.php">
    <input type="hidden" name="id" value="<?= $row['id'] ?>">
    <button type="submit" class="cart-button">Buy on Loan</button>
</form>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">No devices found.</p>
    <?php endif; ?>
</div>

<?php
// Prepare JS alert messages based on the `msg` parameter
$alertScript = '';
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    if ($msg === 'no_loans') {
        $alertScript = "alert('You currently have no disbursed loans. Feel free to browse and request available devices.');";
    } elseif ($msg === 'limit_reached') {
        $alertScript = "alert('You have reached your maximum number of active loans based on your clearance rating. Clear existing loans to increase your limit.');";
    } elseif ($msg === 'limit_reached_no_rating') {
        $alertScript = "alert('Since you have not cleared any loan yet, you are only allowed to take one loan. Please clear it first to increase your clearance rating.');";
    }
}
?>

<!-- JS Alert Handler -->
<?php if (!empty($alertScript)): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        <?= $alertScript ?>
    });
    
</script>
<?php endif; ?>

  
<footer style="color: black; text-align: center; padding: 20px 0;">
  &copy; <?= date("Y") ?> Milele Creditors. All Rights Reserved.
</footer>

  <script>
function confirmLogout(event) {
  event.preventDefault();
  if (confirm("Do you want to log out?")) {
    window.location.href = "logout.php";
  }
}

function showCheckoutMessage(form) {
    // Create overlay
    const overlay = document.createElement('div');
    overlay.id = 'checkout-overlay';
    overlay.innerHTML = `
        <div class="checkout-message">Proceeding to loan checkout...Please wait</div>
    `;
    document.body.appendChild(overlay);

    // Delay form submission by 1.5 seconds
    setTimeout(() => {
        form.submit();
    }, 1500);

    return false; // Prevent immediate submission
}
</script>

</body>
</html>
