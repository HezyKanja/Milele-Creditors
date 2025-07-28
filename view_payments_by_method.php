<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$method = $_GET['payment_method'] ?? 'All';
$search = trim($_GET['search'] ?? '');
$payments = [];
$initial_payments = [];

if ($method === 'Initial') {
    // Show initial payments from loan_applications
    $query = "
        SELECT la.id AS loan_id, u.name, u.email, la.initial_payment
        FROM loan_applications la
        JOIN users u ON la.user_email = u.email
        WHERE la.is_approved = 1 AND la.initial_payment > 0
        ORDER BY la.id DESC
    ";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $initial_payments[] = $row;
    }
} else {
    // Regular loan installment payments from loan_payments
    $base_sql = "
        SELECT lp.*, la.id AS loan_id, la.name AS user_name
        FROM loan_payments lp
        JOIN loan_applications la ON lp.loan_application_id = la.id
        WHERE 1
    ";

    $conditions = [];
    $params = [];
    $types = "";

    if ($method !== 'All') {
        $conditions[] = "lp.payment_method = ?";
        $params[] = $method;
        $types .= "s";
    }

    if ($search !== '') {
        $conditions[] = "(la.name LIKE ? OR la.id = ?)";
        $params[] = "%$search%";
        $params[] = (int)$search;
        $types .= "si";
    }

    if (!empty($conditions)) {
        $base_sql .= " AND " . implode(" AND ", $conditions);
    }
    $base_sql .= " ORDER BY lp.payment_date DESC";

    $stmt = $conn->prepare($base_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payments - <?= htmlspecialchars($method) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 60px;
            background: #f4f4f4;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .filter-form {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .filter-form input, .filter-form select, .filter-form button {
            padding: 10px;
            font-size: 14px;
        }
        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        .back-btn {
            display: block;
            width: max-content;
            margin: 30px auto;
            padding: 10px 20px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>

<h2>Payments - <?= htmlspecialchars($method) ?></h2>

<form class="filter-form" method="get">
    <select name="payment_method">
        <option value="All" <?= $method === 'All' ? 'selected' : '' ?>>All Methods</option>
        <option value="MPesa" <?= $method === 'MPesa' ? 'selected' : '' ?>>MPesa</option>
        <option value="Credit Card" <?= $method === 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
        <option value="Cash" <?= $method === 'Cash' ? 'selected' : '' ?>>Cash</option>
        <option value="Initial" <?= $method === 'Initial' ? 'selected' : '' ?>>Initial</option>
    </select>
    <input type="text" name="search" placeholder="Search by name or Loan ID" value="<?= htmlspecialchars($search) ?>" />
    <button type="submit">🔍 Search</button>
</form>

<?php if ($method === 'Initial'): ?>
    <?php if (empty($initial_payments)): ?>
        <p style="text-align:center; margin-top: 30px;">No initial payments found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>User Name</th>
                    <th>User Email</th>
                    <th>Initial Payment (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($initial_payments as $p): ?>
                    <tr>
                        <td>#<?= $p['loan_id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= number_format($p['initial_payment'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php else: ?>
    <?php if (empty($payments)): ?>
        <p style="text-align:center; margin-top: 30px;">No payments found for this filter.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Loan ID</th>
                    <th>User Name</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Installment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td>#<?= $pay['id'] ?></td>
                        <td>#<?= $pay['loan_id'] ?></td>
                        <td><?= htmlspecialchars($pay['user_name']) ?></td>
                        <td>KES <?= number_format($pay['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($pay['payment_method']) ?></td>
                        <td><?= htmlspecialchars($pay['installment_number']) ?></td>
                        <td><?= date('F j, Y', strtotime($pay['payment_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<a href="admin_total_payments.php" class="back-btn">← Back to Summary</a>

</body>
</html>
