<?php
session_start();

// Save role before destroying session
$role = $_SESSION['user_role'] ?? null;

// Clear and destroy session
$_SESSION = [];
session_destroy();

// Redirect based on the saved role
if ($role === 'staff') {
    header("Location: login.php");
} else {
    header("Location: user_home.php");
}
exit();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logging Out...</title>
  <!-- Redirect to homepage after 2 seconds -->
  <meta http-equiv="refresh" content="2;url=user_home.php">
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
      animation: fadeIn 1s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="message-box">
    <h2>Logging out...</h2>
    <p>You will be redirected to the home page shortly.</p>
  </div>
</body>
</html>
