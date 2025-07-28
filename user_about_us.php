<?php
session_start();
// Get the user's name from the session
$name = $_SESSION['user_name'] ?? 'Borrower';
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>About Us - Milele Creditors</title>

    <style>

       body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
    padding-top: 30px;
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

/* About Us Container */
.about-container {
    max-width: 900px;
    margin: 40px auto;
    background: white;
    padding: 40px 50px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

h1 {
    text-align: center;
    color: white;
    margin-bottom: 30px;
}

p {
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 20px;
    color: white;
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
        .white-text {
        color: white;
}

.about-container {
    max-width: 900px;
    margin: 40px auto;
    background: white;
    padding: 40px 50px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

h1 {
    text-align: center;
    color: #007BFF;
    margin-bottom: 30px;
}

p {
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 20px;
    color: #555;
}

.section-title {
    font-weight: 700;
    font-size: 1.3rem;
    margin-top: 30px;
    margin-bottom: 15px;
    color: #0056b3;
    border-bottom: 2px solid #007BFF;
    padding-bottom: 5px;
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

<!-- Welcome Screen -->
<div id="welcome-screen">
    <h1>Milele Creditors</h1>
    <p class="white-text">About Us</p>
</div>

<nav>
  <div class="brand">
    <a href="user_home.php" style="text-decoration: none; color: white;">Milele Creditors</a>
  </div>

<ul class="menu">
  <li><a href="user_home.php">Home</a></li>
  <li><a href="user_about_us.php" class="active">About Us</a></li>
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

<div class="about-container">
    <h1>About Milele Creditors</h1>

    <p>At Milele Creditors, we believe in empowering our clients through accessible and reliable financial solutions. Established with the goal of providing fair and flexible loan options, our platform connects borrowers with devices and financial products that fit their unique needs.</p>

    <div class="section-title">Our Mission</div>
    <p>Our mission is to deliver convenient credit services with transparency and integrity. We strive to create a trustworthy environment where users can confidently manage their loans and repayments, helping them achieve their goals with ease.</p>

    <div class="section-title">Our Vision</div>
    <p>We envision a community where financial obstacles are minimized through smart technology and personalized support. Milele Creditors aims to be the leading loan system that champions innovation, customer satisfaction, and responsible lending.</p>

    <div class="section-title">Why Choose Us?</div>
    <ul>
        <li>Simple and straightforward loan application process.</li>
        <li>Transparent terms with no hidden fees.</li>
        <li>Supportive customer service ready to assist you.</li>
        <li>Secure and user-friendly platform.</li>
    </ul>

    <div class="section-title">Contact Us</div>
    <p>Have questions or need assistance? Reach out to our support team at <a href="mailto:support@milelecreditors.com">support@milelecreditors.com</a> or call us at +254 700 000 000. We’re here to help!</p>
</div>

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
</script>

</body>
</html>
