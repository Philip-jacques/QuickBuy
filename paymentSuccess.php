<?php
session_start();
$payment_id = $_GET['payment_id'] ?? null;
$method = $_GET['method'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #fdfbfb, #ebedee);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        h2 {
            color: #27ae60;
            margin-bottom: 15px;
            font-size: 2rem;
        }

        p {
            color: #555;
            font-size: 1rem;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        strong {
            font-weight: bold;
            color: #333;
        }

        .dashboard-link {
            display: inline-block;
            margin-top: 25px;
            text-decoration: none;
            background: #1abc9c;
            color: #fff;
            padding: 12px 25px;
            border-radius: 10px;
            transition: background 0.3s ease;
            font-size: 1rem;
        }

        .dashboard-link:hover {
            background: #16a085;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .container {
                padding: 20px;
                border-radius: 15px;
            }

            h2 {
                font-size: 1.75rem;
                margin-bottom: 10px;
            }

            p {
                font-size: 0.9rem;
                margin-bottom: 12px;
            }

            .dashboard-link {
                padding: 10px 20px;
                font-size: 0.9rem;
                margin-top: 20px;
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>✅ Payment Successful</h2>
    <p>Thank you! Your payment has been received.</p>
    <?php if (!empty($payment_id)): ?>
        <p><strong>Payment ID:</strong> <?= htmlspecialchars($payment_id) ?></p>
    <?php endif; ?>
    <p><strong>Payment Method:</strong> <?= htmlspecialchars(ucwords($method)) ?></p>

    <a href="BuyersDashBoard.php" class="dashboard-link">Back to Dashboard</a>
</div>

</body>
</html>