<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = 'Staff';

// Get staff name from DB
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'staff'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fetched_name);
if ($stmt->fetch()) {
    $name = $fetched_name;
}
$stmt->close();

// Handle deletion
if (isset($_GET['delete'])) {
    $idToDelete = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $deleteStmt->bind_param("i", $idToDelete);
    $deleteStmt->execute();
    header("Location: admin_users.php");
    exit();
}

// Fetch all users
// Fetch only users with role 'borrower'
$result = $conn->query("SELECT id, name, email, role, password FROM users WHERE role = 'borrower'");

?>

<!DOCTYPE html>
<html>
<head>
    <title>User List - Admin View</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
            margin: 0;
            padding-top: 100px; /* Adjusted for fixed nav */
        }
        nav { /* Simplified admin nav */
            display: flex;
            align-items: center;
            padding: 0 20px;
            height: 50px;
            position: fixed;
            width: 100%;
            top: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            z-index: 1000;
            background-color: #343a40; /* Darker admin theme */
            color: white;
        }
        .brand { font-weight: 700; font-size: 1.2rem; margin-right: 40px; }
        .menu { display: flex; gap: 12px; list-style: none; margin: 0; padding: 0; }
        .menu li a { color: white; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 4px 8px; border-radius: 4px; transition: 0.25s; }
        .menu li a.active, .menu li a:hover { background-color: rgba(255,255,255,0.25); }
        .right-section { margin-left: auto; display: flex; gap: 15px; align-items: center; }
        .right-section span { font-size: 0.9rem; }
        .right-section .btn { background: white; color: #343a40; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .page-header {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px; /* Space below header */
        }
        .sub-nav {
            max-width: 900px;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            gap: 30px;
        }
        .sub-nav a {
            text-decoration: none;
            font-weight: bold;
            color: #007bff;
        }
        .sub-nav a:hover {
            text-decoration: underline;
        }
        

        .container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(500px, 1fr)); /* increased min width */
    gap: 20px;
}

.user-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    position: relative;
    word-break: break-all; /* ensures long hashed passwords break to new lines */
}

        .user-card h3 {
            margin-top: 0;
        }

        .user-card p {
            margin: 8px 0;
            color: #444;
            font-size: 14px;
        }

        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        .delete-btn:hover {
            background-color: #b52a38;
        }
    </style>
</head>
<body>

<nav>
    <div class="brand">Milele Admin</div>
    <ul class="menu">
       <li><a href="admin_all_devices.php">Devices</a></li>
            <li><a href="admin_all_loans_applied.php">Loans</a></li>
            <li><a href="admin_all_payments.php">Payments</a></li>
            <li><a href="admin_users.php" class="active">Users</a></li>
            <li><a href="admin_send_messages.php">Messages</a></li>
            
    </ul>
    <div class="right-section">
         <span>Welcome Staff, <?= htmlspecialchars($name) ?></span>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</nav>

<h2>All Registered Users</h2>

<div class="container">
    <?php while ($row = $result->fetch_assoc()): ?>
        <a href="admin_user_loans.php?user_id=<?= $row['id'] ?>" style="text-decoration:none; color:inherit;">
    <div class="user-card">
        <button class="delete-btn" onclick="event.stopPropagation(); confirmDelete(<?= $row['id'] ?>)">Delete</button>
        <h3>User Info</h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($row['role']) ?></p>
        <p><strong>Hashed Password:</strong><br><?= htmlspecialchars($row['password']) ?></p>
    </div>
</a>

    <?php endwhile; ?>
</div>

<script>
    function confirmDelete(id) {
        if (confirm("Are you sure you want to delete this user?")) {
            window.location.href = 'admin_users.php?delete=' + id;
        }
    }
</script>

</body>
</html>
