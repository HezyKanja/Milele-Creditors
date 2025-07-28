<?php
session_start();

$email = $_SESSION['user_email'] ?? '';
if (!$email) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'milele_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("
    SELECT la.*, d.name AS device_name, d.model, d.serial_number, d.image_url, d.price AS device_price, pd.method, la.name AS user_name
    FROM loan_applications la
    JOIN devices d ON la.device_id = d.id
    LEFT JOIN payment_details pd ON la.id = pd.loan_application_id
    WHERE la.user_email = ?
    ORDER BY la.id DESC
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$loan = $result->fetch_assoc();
$stmt->close();

if (!$loan) {
    echo "<p>No loan application found.</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pdf'])) {
    require_once('tcpdf/tcpdf.php');

    $pdf = new TCPDF();
    $pdf->SetCreator('Milele Loans');
    $pdf->SetAuthor('Milele');
    $pdf->SetTitle('Loan Summary');
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetFont('dejavusans', '', 11);
    $pdf->AddPage();

    $html = '
    <style>
        h2 { color: #007bff; text-align: center; font-size: 20pt; margin-bottom: 20px; }
        .section { margin-bottom: 18px; }
        .section h3 {
            color: #444; font-size: 14pt; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 10px;
        }
        .section p { font-size: 12pt; margin: 5px 0; }
    </style>
    <h2>Loan Summary</h2>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');

    if (!empty($loan['image_url']) && @getimagesize($loan['image_url'])) {
        $imageWidth = 50;
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // Insert image and calculate image height
        $imageFile = $loan['image_url'];
        $imageSize = getimagesize($imageFile);
        $imageHeight = ($imageWidth / $imageSize[0]) * $imageSize[1];

        $pdf->Image($imageFile, $x, $y, $imageWidth);
        $pdf->SetY($y + $imageHeight + 10); // Add extra spacing after image
    }

    $html = '
    <div class="section">
        <h3>Device Information</h3>
        <p><strong>Device:</strong> ' . htmlspecialchars($loan['device_name']) . '</p>
        <p><strong>Model:</strong> ' . htmlspecialchars($loan['model']) . '</p>
        <p><strong>Serial Number:</strong> ' . htmlspecialchars($loan['serial_number']) . '</p>
    </div>

    <div class="section">
        <h3>User Details</h3>
        <p><strong>Name:</strong> ' . htmlspecialchars($loan['user_name']) . '</p>
        <p><strong>Email:</strong> ' . htmlspecialchars($loan['user_email']) . '</p>
        <p><strong>Phone:</strong> ' . htmlspecialchars($loan['phone']) . '</p>
        <p><strong>ID Number:</strong> ' . htmlspecialchars($loan['id_number']) . '</p>
    </div>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');

    if (!empty($loan['id_photo_path']) && @getimagesize($loan['id_photo_path'])) {
    $imageWidth = 60;
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Display the image at current position
    $pdf->Image($loan['id_photo_path'], $x, $y, $imageWidth, 0, '', '', '', false, 300, '', false, false, 1, false);

    // Move the cursor just below the image with 10pt padding
    $pdf->SetY($y + $pdf->getImageRBY() - $y + 10);
}


    $html = '
    <div class="section">
        <h3>Loan Details</h3>
        <p><strong>Loan ID:</strong> ' . (int)$loan['id'] . '</p>
        <p><strong>Price:</strong> KES ' . number_format($loan['device_price'], 2) . '</p>
        <p><strong>Initial Payment:</strong> KES ' . number_format($loan['initial_payment'], 2) . '</p>
        <p><strong>Loan Duration:</strong> ' . (int)$loan['loan_duration'] . ' months</p>
        <p><strong>Total with Interest:</strong> KES ' . number_format($loan['total_with_interest'], 2) . '</p>
        <p><strong>Remaining Balance:</strong> KES ' . number_format($loan['remaining_balance'], 2) . '</p>
    </div>
    <div class="section">
        <h3>Payment Method</h3>
        <p><strong>Method:</strong> ' . (ucfirst($loan['method']) ?: 'N/A') . '</p>
    </div>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('loan_summary.pdf', 'D');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Loan Summary</title>
    <style>
         body, html {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100%;
      overflow-x: hidden;
    padding-top: 50px;
    }
        .summary-container {
            max-width: 750px;
            margin: auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #007bff;
            text-align: center;
            margin-bottom: 25px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            color: #444;
            font-size: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .section p {
            font-size: 15px;
            margin: 6px 0;
        }
        img {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
            border-radius: 6px;
        }
        .btn-download {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            display: block;
            margin: 30px auto 0;
            transition: background-color 0.3s;
        }
        .btn-download:hover {
            background-color: #0056b3;
        }
        a.back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #007bff;
            text-decoration: none;
        }
        a.back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="summary-container">
    <h2>📄 Loan Summary</h2>

    <div class="section">
        <h3>📱 Device Information</h3>
        <?php if (!empty($loan['image_url'])): ?>
            <img src="<?= htmlspecialchars($loan['image_url']) ?>" alt="Device Image" />
        <?php endif; ?>
        <p><strong>Device:</strong> <?= htmlspecialchars($loan['device_name']) ?></p>
        <p><strong>Model:</strong> <?= htmlspecialchars($loan['model']) ?></p>
        <p><strong>Serial Number:</strong> <?= htmlspecialchars($loan['serial_number']) ?></p>
    </div>

    <div class="section">
        <h3>👤 User Details</h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($loan['user_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($loan['user_email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($loan['phone']) ?></p>
        <p><strong>ID Number:</strong> <?= htmlspecialchars($loan['id_number']) ?></p>
        <p><strong>ID Photo:</strong>
            <?php if (!empty($loan['id_photo_path'])): ?>
                <a href="<?= htmlspecialchars($loan['id_photo_path']) ?>" target="_blank">View ID Photo</a>
            <?php else: ?>
                Not uploaded
            <?php endif; ?>
        </p>
    </div>

    <div class="section">
        <h3>💳 Loan Details</h3>
        <p><strong>Loan ID:</strong> <?= (int)$loan['id'] ?></p>
        <p><strong>Price:</strong> KES <?= number_format($loan['device_price'], 2) ?></p>
        <p><strong>Initial Payment:</strong> KES <?= number_format($loan['initial_payment'], 2) ?></p>
        <p><strong>Loan Duration:</strong> <?= (int)$loan['loan_duration'] ?> months</p>
        <p><strong>Total with Interest:</strong> KES <?= number_format($loan['total_with_interest'], 2) ?></p>
        <p><strong>Remaining Balance:</strong> KES <?= number_format($loan['remaining_balance'], 2) ?></p>
    </div>

    <div class="section">
        <h3>💰 Payment Method</h3>
        <p><strong>Method:</strong> <?= ucfirst($loan['method']) ?: 'N/A' ?></p>
    </div>

    <form method="post">
    <button type="submit" name="download_pdf" class="btn-download">📥 Download PDF</button>
</form>

<button id="proceedBtn" class="btn-download" type="button">✅ Proceed</button>

<script>
    document.getElementById('proceedBtn').addEventListener('click', function () {
        const message = document.createElement('div');
        message.textContent = "✅ Thank you for applying for the loan";
        message.style.position = "fixed";
        message.style.top = "20px";
        message.style.left = "50%";
        message.style.transform = "translateX(-50%)";
        message.style.background = "#28a745";
        message.style.color = "white";
        message.style.padding = "15px 25px";
        message.style.borderRadius = "8px";
        message.style.fontSize = "16px";
        message.style.boxShadow = "0 4px 12px rgba(0, 0, 0, 0.2)";
        document.body.appendChild(message);

        setTimeout(() => {
            message.remove();
            window.location.href = "user_home.php";
        }, 2000);
    });
</script>

</div>

</body>
</html>
