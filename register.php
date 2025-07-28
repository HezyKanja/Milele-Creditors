<?php
session_start();
$message = '';
$registration_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $id_number = trim($_POST['id_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $id_photo_path = '';

    // Passwords must match
    if ($password !== $confirm_password) {
        $message = "❌ Passwords do not match.";
    }
    // Borrower emails cannot be @milelecreditors.com
    elseif ($role === 'borrower' && preg_match("/@milelecreditors\.com$/", $email)) {
        $message = "❌ Borrowers cannot register using @milelecreditors.com emails.";
    }
    // Staff emails must be @milelecreditors.com
    elseif ($role === 'staff' && !preg_match("/@milelecreditors\.com$/", $email)) {
        $message = "❌ Staff must register using a @milelecreditors.com email.";
    }
    // ID photo is required
    elseif (empty($_FILES['id_photo']['name'])) {
        $message = "❌ Please upload an ID photo.";
    } else {
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'milele_db');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "❌ Email already exists. Please use another one.";
        } else {
            // Handle ID photo upload
            $upload_dir = 'uploads/id_photos/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = time() . '_' . basename($_FILES['id_photo']['name']);
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['id_photo']['tmp_name'], $target_path)) {
                $id_photo_path = $target_path;

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (email, password, name, role, id_number, id_photo) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $email, $hashed_password, $name, $role, $id_number, $id_photo_path);

                if ($stmt->execute()) {
                    $registration_success = true;
                    // Redirect to login page after success
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    $message = "❌ Error saving user: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $message = "❌ Failed to upload ID photo.";
            }
        }

        $check->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
 
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        form {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 350px;
        }

        h2 {
            text-align: center;
            color: #2a2a2a;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
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
            background-color: #ff4d4d;
        }

        .popup.success {
            background-color: #28a745;
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
    <div class="popup" id="popup"><?= htmlspecialchars($message) ?></div>
<?php elseif ($registration_success): ?>
    <div class="popup success" id="popup">✅ Registration successful! You can now <a href="login.php" style="color: #fff; font-weight:bold;">login</a>.</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <h2>Register</h2>

    <label for="name">Full Name:</label>
    <input type="text" name="name" required>

    <label for="email">Email:</label>
    <input type="email" name="email" required>

    <label for="role">Role:</label>
    <select name="role" required>
        <option value="">Choose Role</option>
        <option value="staff">Staff</option>
        <option value="borrower">Borrower</option>
    </select>

    <label for="id_number">ID Number:</label>
    <input type="text" name="id_number" required>

    <label for="id_photo">Upload ID Photo:</label>
    <input type="file" name="id_photo" accept="image/*" required>

    <label for="password">Password:</label>
    <input type="password" name="password" required>

    <label for="confirm_password">Confirm Password:</label>
    <input type="password" name="confirm_password" required>

    <button type="submit">Register</button>

    <p class="login-link">Have an account? <a href="login.php">Log in</a></p>
</form>

<script>
    setTimeout(() => {
        const popup = document.getElementById('popup');
        if (popup) popup.style.display = 'none';
    }, 2500);
</script>

</body>
</html>
