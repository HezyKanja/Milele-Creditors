<?php
session_start();
require 'db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? '';
$email = $_SESSION['user_email'] ?? '';
$role = $_SESSION['user_role'] ?? '';

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$id_number = '';
$id_photo = '';
$stmt = $conn->prepare("SELECT id_number, id_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id_number, $id_photo);
$stmt->fetch();
$stmt->close();

// Handle Profile Update (name, ID number, ID photo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    $new_name = trim($_POST['edit_name']);
    $new_id_number = trim($_POST['id_number']);
    $upload_dir = "uploads/";
    $new_photo_path = $id_photo;

    // Handle photo upload if new file is provided
    if (!empty($_FILES['id_photo']['name'])) {
        $file_name = basename($_FILES["id_photo"]["name"]);
        $target_path = $upload_dir . time() . "_" . $file_name;

        if (move_uploaded_file($_FILES["id_photo"]["tmp_name"], $target_path)) {
            $new_photo_path = $target_path;
        } else {
            echo "<script>alert('Failed to upload ID photo.');</script>";
        }
    }

    // Update user details (name, ID number, photo)
    $update = $conn->prepare("UPDATE users SET name = ?, id_number = ?, id_photo = ? WHERE email = ?");
    $update->bind_param("ssss", $new_name, $new_id_number, $new_photo_path, $email);

    if ($update->execute()) {
        // Update session data to reflect new profile information
        $_SESSION['user_name'] = $new_name;
        $name = $new_name;
        $id_number = $new_id_number;
        $id_photo = $new_photo_path;
        echo "<script>alert('Profile updated successfully.'); window.location.href='user_profile.php';</script>";
    } else {
        echo "<script>alert('Failed to update profile.');</script>";
    }
    $update->close();
}

// Handle Password Change (current password, new password, confirm password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    // Ensure new passwords match
    if ($new !== $confirm) {
        echo "<script>alert('New passwords do not match.');</script>";
    } else {
        // Fetch current password from DB and verify
        $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($hashed);
        $stmt->fetch();
        $stmt->close();

        // Verify if the current password is correct
        if (!password_verify($current, $hashed)) {
            echo "<script>alert('Incorrect current password.');</script>";
        } else {
            // Hash the new password and update it in the DB
            $new_hashed = password_hash($new, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->bind_param("ss", $new_hashed, $email);

            if ($update->execute()) {
                echo "<script>alert('Password updated successfully.'); window.location.href='user_profile.php';</script>";
            } else {
                echo "<script>alert('Failed to update password.');</script>";
            }
            $update->close();
        }
    }
}

// Fetch loan rating based on email (user_email)
$rating = 0;
$ratingStmt = $conn->prepare("SELECT COUNT(*) AS cleared_count FROM loan_applications WHERE user_email = ? AND is_cleared = 1");
$ratingStmt->bind_param("s", $email);
$ratingStmt->execute();
$ratingResult = $ratingStmt->get_result();
if ($ratingResult && $row = $ratingResult->fetch_assoc()) {
    $cleared = (int)$row['cleared_count'];
    $rating = min($cleared, 10);  // Cap rating to 10
}
$ratingStmt->close();

// Application summary stats based on email
$loanStats = [
    'total_applied' => 0,
    'total_amount' => 0,
    'approved_count' => 0,
    'pending_count' => 0
];

$statsStmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_applied,
        SUM(total_with_interest) AS total_amount,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) AS pending_count
    FROM loan_applications
    WHERE user_email = ?
");
$statsStmt->bind_param("s", $email);
$statsStmt->execute();
$result = $statsStmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $loanStats = array_map(fn($val) => $val ?? 0, $row);
}

$statsStmt->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Dashboard</title>

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

        .page-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
    padding: 40px 20px;
}
.box {
    width: 360px;
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}



        .box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 360px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .info {
            font-size: 15px;
            margin-bottom: 10px;
        }

        .info strong {
            width: 130px;
            display: inline-block;
        }

        img {
            max-width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .logout-btn {
            display: block;
            margin-top: 20px;
            background-color: #dc3545;
            color: white;
            padding: 10px;
            border: none;
            width: 100%;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .no-loan {
            text-align: center;
            color: #777;
            font-style: italic;
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
  <li><a href="user_home.php">Home</a></li>
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


<div class="page-container">

    <!-- User Profile Box -->
    <div class="box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">👤 Your Profile</h2>
            <button onclick="toggleEditForm()" style="padding: 6px 10px; border: none; background-color: #17a2b8; color: white; border-radius: 5px; cursor: pointer;">Edit</button>
        </div>

        <div class="info"><strong>Name:</strong> <?= htmlspecialchars($name) ?></div>
        <div class="info"><strong>Email:</strong> <?= htmlspecialchars($email) ?></div>
        <div class="info"><strong>Role:</strong> <?= htmlspecialchars($role) ?></div>
        <div class="info"><strong>ID Number:</strong> <?= htmlspecialchars($id_number) ?></div>
        <div class="info"><strong>ID Photo:</strong><br><img src="<?= $id_photo ?>" alt="ID Photo" width="120"></div>
        <div class="info"><strong>Loan Clearance Rating:</strong> <?= $rating ?> / 10 ⭐</div>

        <!-- Edit Form -->

        <form id="editForm" method="POST" enctype="multipart/form-data" style="display: none; margin-top: 20px;">

<form method="POST" enctype="multipart/form-data">
    <!-- Profile Update Section -->
    <input type="hidden" name="update_details" value="1">
    <fieldset style="margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;">
        <legend><strong>Update Profile</strong></legend>

        <label>Name:</label><br>
        <input type="text" name="edit_name" value="<?= htmlspecialchars($name) ?>" required><br>

        <label>Email:</label><br>
        <input type="email" value="<?= htmlspecialchars($email) ?>" readonly><br>

        <label>ID Number:</label><br>
        <input type="text" name="id_number" value="<?= htmlspecialchars($id_number) ?>" readonly><br>

        <label>ID Photo:</label><br>
        <img src="<?= htmlspecialchars($id_photo) ?>" alt="ID Photo" width="120"><br>
      

        <button type="submit" name="update_details">💾 Save Changes</button>
    </fieldset>
</form>

<form method="POST" enctype="multipart/form-data">
    <!-- Password Change Section -->
    <input type="hidden" name="change_password" value="1">
    <fieldset style="padding: 10px; border: 1px solid #ccc;">
        <legend><strong>Change Password</strong></legend>

        <label>Current Password:</label><br>
        <input type="password" name="current_password" required><br>

        <label>New Password:</label><br>
        <input type="password" name="new_password" required><br>

        <label>Confirm New Password:</label>
        <input type="password" name="confirm_password" required><br><br>

        <button type="submit" name="update_password">🔒 Update Password</button>
    </fieldset>
</form>




       

        <?php if (!empty($error)) echo "<div style='color: red;'>$error</div>"; ?>
        <?php if (!empty($success)) echo "<div style='color: green;'>$success</div>"; ?>
    </div>



    <!-- Loan Statistics -->
    <div class="box">
        <h2>📊 Loan Application Stats</h2>
        <div class="info"><strong>Total Applied:</strong> <?= $loanStats['total_applied'] ?></div>
        <div class="info"><strong>Total Amount:</strong> KES <?= number_format($loanStats['total_amount']) ?></div>
        <div class="info"><strong>Approved:</strong> <?= $loanStats['approved_count'] ?></div>
        <div class="info"><strong>Pending:</strong> <?= $loanStats['pending_count'] ?></div>
    </div>

</div>

<footer style="color: black; text-align: center; padding: 30px 0;">
  &copy; <?= date("Y") ?> Milele Creditors. All Rights Reserved.
</footer>


<script>
function toggleEditForm() {
    const form = document.getElementById('editForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>


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
