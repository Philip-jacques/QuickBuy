<?php
session_start();
$payment_id = $_POST['payment_id'] ?? ''; // Default to empty string
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled</title>
    <style>
        :root {
            /* Basic Colors */
            --white: #ffffff;
            --light-gray: #ebedee;
            --dark-gray: #fdfbfb;
            --text-color: #555;
            --darker-text: #333;

            /* Accent Colors */
            --cancel-red: #e74c3c;
            --cancel-red-dark: #c0392b; /* Darker red for hover */

            /* Shadows */
            --shadow-light: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, var(--dark-gray), var(--light-gray));
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background: var(--white);
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px var(--shadow-light);
            width: 100%;
            max-width: 450px;
            text-align: center;
            animation: fadeIn 0.8s ease-out; /* Add fade-in animation */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: var(--cancel-red);
            margin-bottom: 15px;
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px; /* Space between icon and text */
        }

        h2::before {
            content: '❌'; /* Unicode character for cross mark */
            font-size: 1.5em; /* Larger icon */
            line-height: 1; /* Align icon better */
        }

        p {
            color: var(--text-color);
            font-size: 1rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        strong {
            font-weight: bold;
            color: var(--darker-text);
        }

        .return-link {
            display: inline-block;
            margin-top: 25px;
            text-decoration: none;
            background: var(--cancel-red);
            color: var(--white);
            padding: 12px 25px;
            border-radius: 10px;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            font-size: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .return-link:hover {
            background: var(--cancel-red-dark);
            transform: translateY(-2px); /* Slight lift effect */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
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
                margin-bottom: 15px;
            }

            .return-link {
                padding: 10px 20px;
                font-size: 0.9rem;
                margin-top: 20px;
                border-radius: 8px;
            }
        }
    </style>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-M37ZFNLZ9Q"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-M37ZFNLZ9Q');
</script>
</head>
<body>

<div class="container">
    <h2>Payment Cancelled</h2>
    <p>Your payment was not completed.</p>
    <?php if (!empty($payment_id)): ?>
        <p><strong>Payment ID:</strong> <?= htmlspecialchars($payment_id) ?></p>
    <?php endif; ?>

    <a href="viewCart.php" class="return-link">Return to Cart</a>
</div>

</body>
</html>
