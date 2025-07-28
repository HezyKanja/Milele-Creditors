<?php
session_start();
$message = '';
$login_success = false;
$redirect_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $inputName = trim($_POST['name']);

    $conn = new mysqli('localhost', 'root', '', 'milele_db');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $dbName, $hashed_password, $role);
        $stmt->fetch();

        if (strcasecmp($dbName, $inputName) === 0 && password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $dbName;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;
            $login_success = true;

            $redirect_url = ($role === 'staff')
                ? 'admin_all_devices.php'
                : ($_SESSION['redirect_after_login'] ?? 'user_home.php');
        } else {
            $message = "Name or password does not match our records.";
        }
    } else {
        $message = "No account found with that email.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            
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
            display: block;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 9999;
            font-size: 14px;
        }
        .popup.success {
            background-color: #28a745;
        }
        .popup.error {
            background-color: #ff4d4d;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #555;
        }
        .login-link a {
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php if (!empty($message)): ?>
    <div class="popup error" id="popup"><?= htmlspecialchars($message) ?></div>
<?php elseif (isset($_GET['registered'])): ?>
    <div class="popup success" id="popup">✅ Registration successful! Please log in.</div>
<?php elseif ($login_success): ?>
    <div class="popup success" id="popup">✅ Login successful! Redirecting...</div>
<?php endif; ?>

<form method="post">
    <h2>Login</h2>
    <input type="text" name="name" placeholder="Full Name" required />
    <input type="email" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <button type="submit">Login</button>

    <p class="login-link">
        <a href="change_password.php">Forgot Password?</a><br>
        Don't have an account? <a href="register.php">Register</a>
    </p>
</form>

<script>
    const popup = document.getElementById('popup');

    setTimeout(() => {
        if (popup) popup.style.display = 'none';
    }, 2000);

    <?php if ($login_success): ?>
        setTimeout(() => {
            window.location.href = <?= json_encode($redirect_url); ?>;
        }, 2000);
    <?php endif; ?>
</script>

</body>
</html>
