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


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_device'])) {
    $id = intval($_POST['device_id']);
    $status = $_POST['status'] ?? 'Available';
    $quantityAvailable = intval($_POST['quantity_available']);
    $offer = trim($_POST['offer_details']);
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0.00;
    $deposit = isset($_POST['deposit_price']) ? floatval($_POST['deposit_price']) : 0.00;

    // Get current issued
    $q = $conn->query("SELECT is_issued FROM devices WHERE id = $id");
    $device = $q->fetch_assoc();
    $isIssued = intval($device['is_issued']);

    // Calculate new quantity
    $new_quantity = max($quantityAvailable + $isIssued, $isIssued);

    // Update all fields
    $stmt = $conn->prepare("UPDATE devices SET status = ?, quantity = ?, offer_details = ?, price = ?, deposit_price = ? WHERE id = ?");
    $stmt->bind_param("sissdi", $status, $new_quantity, $offer, $price, $deposit, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}



// Add new device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_device'])) {
    $name = $_POST['name'];
    $model = $_POST['model'];
    $status = $_POST['status'];
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $deposit = floatval($_POST['deposit_price']);
    $serial = $_POST['serial_number'];
    $image = "";

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['image_file']['tmp_name'];
        $fileName = basename($_FILES['image_file']['name']);
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $targetFile = $targetDir . time() . "_" . $fileName;
        move_uploaded_file($fileTmp, $targetFile);
        $image = $targetFile;
    }

   $offer = $_POST['offer_details'] ?? null;
$stmt = $conn->prepare("INSERT INTO devices (name, model, status, quantity, is_issued, price, deposit_price, serial_number, image_url, offer_details) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssiddsss", $name, $model, $status, $quantity, $price, $deposit, $serial, $image, $offer);

    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle device deletion
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    // Optionally delete the image file from the server
    $imgQuery = $conn->query("SELECT image_url FROM devices WHERE id = $deleteId");
    if ($imgQuery && $img = $imgQuery->fetch_assoc()) {
        if (file_exists($img['image_url'])) {
            unlink($img['image_url']);
        }
    }

    // Delete device from database
    $conn->query("DELETE FROM devices WHERE id = $deleteId");

    // Redirect with success message
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=deleted");
    exit();
}


// Toggle availability
if (isset($_GET['toggle'])) {
    $toggleId = intval($_GET['toggle']);
    $getStatus = $conn->query("SELECT status FROM devices WHERE id = $toggleId");
    if ($getStatus && $getStatus->num_rows > 0) {
        $current = $getStatus->fetch_assoc()['status'];
        $newStatus = strtolower($current) === 'available' ? 'Not Available' : 'Available';
        $conn->query("UPDATE devices SET status = '$newStatus' WHERE id = $toggleId");
    }
   header("Location: " . $_SERVER['PHP_SELF'] . "?success=added");
   header("Location: " . $_SERVER['PHP_SELF'] . "?success=toggled");



    exit();
}

// Fetch devices
$result = $conn->query("SELECT *, (quantity - is_issued) AS quantity_available FROM devices");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Devices</title>
    
    <style>
       body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
            margin: 0;
            padding-top:100px; /* Adjusted for fixed nav */
        }
        h1, h2 { text-align: center; }
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


        .device-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .device-card {
            background: white;
            width: 300px;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .device-card img {
            max-width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }
        .device-card p {
            font-size: 14px;
            margin: 6px 0;
        }
        .toggle-btn {
            margin-top: 10px;
            background: #ffc107;
            color: #000;
            border: none;
            padding: 8px 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        form.add-device-form {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        form.add-device-form input,
        form.add-device-form select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        form.add-device-form button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        form.add-device-form button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<nav>
    <div class="brand">Milele Admin</div>
    <ul class="menu">
       <li><a href="admin_all_devices.php" class="active">Devices</a></li>
            <li><a href="admin_all_loans_applied.php">Loans</a></li>
            <li><a href="admin_all_payments.php">Payments</a></li>
            <li><a href="admin_users.php">Users</a></li>
            <li><a href="admin_send_messages.php">Messages</a></li>
            
    </ul>
    <div class="right-section">
        <span>Welcome Staff, <?= htmlspecialchars($name) ?></span>

        <a href="logout.php" class="btn">Logout</a>
    </div>
</nav>

<h1>Admin - Manage Devices</h1>

   <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
    <div style="max-width:600px;margin:10px auto;background:#d4edda;padding:15px;border:1px solid #c3e6cb;color:#155724;border-radius:6px;">
        ✅ Device added successfully!
    </div>
<?php endif; ?>

<h2>Add New Device</h2>
<form method="POST" class="add-device-form" enctype="multipart/form-data">
    <input type="hidden" name="add_device" value="1">
    <input type="text" name="name" placeholder="Device Name" required>
    <input type="text" name="model" placeholder="Model" required>
    <select name="status" required>
        <option value="Available">Available</option>
        <option value="Not Available">Not Available</option>
    </select>
    <input type="number" name="quantity" placeholder="Quantity" required>
    <input type="number" step="0.01" name="price" placeholder="Price (KES)" required>
    <input type="number" step="0.01" name="deposit_price" placeholder="Deposit (KES)" required>
    <input type="text" name="serial_number" placeholder="Serial Number" required>
    <input type="text" name="offer_details" placeholder="Offer Details (e.g., 10% Off, Free Item)" >
    <input type="file" name="image_file" accept="image/*" required>
    <button type="submit">Add Device</button>


</form>

<h2>Device List</h2>
<div class="device-container">
<?php while ($row = $result->fetch_assoc()): ?>
    <div class="device-card">
        <form method="POST">
            <input type="hidden" name="update_device" value="1">
            <input type="hidden" name="device_id" value="<?= $row['id'] ?>">

            <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">

            <p><strong>Name:</strong> <input type="text" value="<?= htmlspecialchars($row['name']) ?>" readonly></p>
            <p><strong>Model:</strong> <input type="text" value="<?= htmlspecialchars($row['model']) ?>" readonly></p>

          <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>


            <p><strong>Issued:</strong> <input type="number" value="<?= $row['is_issued'] ?>" readonly></p>
            <p><strong>Quantity Available:</strong> <input type="number" name="quantity_available" value="<?= $row['quantity_available'] ?>" required></p>

            <p><strong>Price:</strong> <input type="number" name="price" step="0.01" value="<?= $row['price'] ?>" required></p>
<p><strong>Deposit:</strong> <input type="number" name="deposit_price" step="0.01" value="<?= $row['deposit_price'] ?>" required></p>

           
    
<p><strong>Offer:</strong> <input type="text" name="offer_details" value="<?= htmlspecialchars($row['offer_details']) ?>"></p>


            <p><strong>Serial:</strong> <input type="text" value="<?= htmlspecialchars($row['serial_number']) ?>" readonly></p>

           <button type="submit" class="toggle-btn" style="background: #28a745; color: white;">Save Changes</button>

        </form>

        <form method="get" style="margin-top: 10px;">
            <input type="hidden" name="toggle" value="<?= $row['id'] ?>">
            <button type="submit" class="toggle-btn">
                Make <?= strtolower($row['status']) === 'available' ? 'Not Available' : 'Available' ?>
            </button>
        </form>

        <form method="get" onsubmit="return confirm('Are you sure you want to delete this device?');">
    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
    <button type="submit" class="toggle-btn" style="background:#dc3545; color:white;">
        Delete
    </button>
</form>

    </div>
<?php endwhile; ?>
</div>

<?php if (isset($_GET['success'])): ?>
<script>
    window.onload = function() {
        let message = "";
        switch ("<?= $_GET['success'] ?>") {
            case "added": message = "✅ Device added successfully!"; break;
            case "toggled": message = "🔁 Device status updated!"; break;
        }
        if (message) alert(message);
        window.history.replaceState({}, document.title, window.location.pathname);
    };
</script>
<?php endif; ?>



</body>
</html>
