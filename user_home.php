<?php
session_start();

// Get the user's name from the session
$name = $_SESSION['user_name'] ?? 'Borrower';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Milele Creditors - Home</title>


  <style>

    body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
    padding-top: 20px;
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

    .hero-section {
      position: relative;
      height: 100vh;
      background: linear-gradient(to right, #2c3e50, #007BFF);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
      text-align: center;
      animation: fadeIn 2s ease-in-out;
    }
    h2 {
    text-align: center;
    color: white;
}
p {
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 20px;
    color: white;
}

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .hero-section h1 {
      font-size: 3rem;
      margin: 0;
      font-weight: 700;
    }

    .hero-section p {
      font-size: 1.2rem;
      margin: 20px 0;
      max-width: 600px;
    }

    .cta-buttons {
      margin-top: 20px;
      display: flex;
      gap: 15px;
    }

    .cta-buttons a {
      background-color: #007BFF;
      color: white;
      padding: 12px 24px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }

    .cta-buttons a:hover {
      background-color: #0056b3;
    }

    @media (max-width: 768px) {
      .hero-section h1 {
        font-size: 2rem;
      }

      .hero-section p {
        font-size: 1rem;
      }

      .cta-buttons {
        flex-direction: column;
        align-items: center;
      }
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
<nav>
  <div class="brand">
    <a href="user_home.php" style="text-decoration: none; color: white;">Milele Creditors</a>
  </div>

  <ul class="menu">
  <li><a href="user_home.php" class="active">Home</a></li>
  <li><a href="user_about_us.php">About Us</a></li>
  <li><a href="user_available_devices.php">Available Devices</a></li>
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


  <section class="hero-section">
    <h1>Welcome to Milele Creditors</h1>
    <h2>Flexible Loans. Trusted Devices. Empowering You.</h2>
    <p>Access affordable loans for quality devices with Milele Creditors.</p>
    <div class="cta-buttons">
      <a href="user_about_us.php">About Us</a>
      <a href="user_available_devices.php">Browse Devices</a>
      <a href="user_available_devices.php">Apply for a Loan</a>
    </div>
  </section>

  
<footer style="color: black; text-align: center; padding: 0px 0;">
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
