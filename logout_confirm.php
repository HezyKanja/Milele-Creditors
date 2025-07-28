<!-- logout_confirm.php -->
<?php
session_start();
if (!isset($_SESSION['user_role'])) {
  // If not logged in, redirect to home
  header("Location: user_home.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logout Confirmation</title>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f0f4f8;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
    }
    .message-box {
      text-align: center;
      background-color: #ffffff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .logout-button {
      background-color: #ff4d4d;
      color: #fff;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 20px;
    }
    .logout-button:hover {
      background-color: #e60000;
    }
  </style>
</head>
<body>
  <div class="message-box">
    <h2>Are you sure you want to log out?</h2>
    <button class="logout-button" onclick="confirmLogout()">Yes, Log Out</button>
  </div>

  <script>
    function confirmLogout() {
      if (confirm("Do you really want to log out?")) {
        window.location.href = "logout.php";
      }
    }
  </script>
</body>
</html>
