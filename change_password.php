<?php
session_start();
require 'db_connection.php';

$message = '';
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if ($stmt->num_rows === 1 && password_verify($current_password, $hashed_password)) {
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->bind_param("ss", $new_hashed, $email);
            if ($update->execute()) {
                $message = "✅ Password updated successfully!";
                $redirect = true;
            } else {
                $message = "Error updating password.";
            }
        } else {
            $message = "Email or current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #eef2f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background-color: white;
            padding: 60px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
        }
        .field-wrapper {
            position: relative;
        }
        input {
            width: 100%;
            padding: 10px 40px 10px 10px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 14px;
            color: #007BFF;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056cc;
        }
        .popup {
            background-color: #ff4d4d;
            color: white;
            padding: 12px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .success {
            background-color: #28a745;
        }
    </style>
</head>
<body>

<form method="post">
    <h2>Change Password</h2>

    <?php if (!empty($message)): ?>
        <div class="popup <?= strpos($message, '✅') !== false ? 'success' : '' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php if ($redirect): ?>
            <script>
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1000);
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <input type="email" name="email" placeholder="Your Email" required>

    <div class="field-wrapper">
        <input type="password" name="current_password" id="current_password" placeholder="Current Password" required>
        <span class="toggle-password" onclick="togglePassword('current_password', this)">Show</span>
    </div>

    <div class="field-wrapper">
        <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
        <span class="toggle-password" onclick="togglePassword('new_password', this)">Show</span>
    </div>

    <div class="field-wrapper">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
        <span class="toggle-password" onclick="togglePassword('confirm_password', this)">Show</span>
    </div>

    <button type="submit">Change Password</button>
</form>

<script>
function togglePassword(id, element) {
    const field = document.getElementById(id);
    if (field.type === 'password') {
        field.type = 'text';
        element.innerText = 'Hide';
    } else {
        field.type = 'password';
        element.innerText = 'Show';
    }
}
</script>

</body>
</html>
