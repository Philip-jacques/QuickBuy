<?php
/**
 * @file payfastRedirect.php
 *
 * @brief This page handles the redirection from PayFast after a payment attempt.
 * It processes the payment ID, fetches relevant order details, and displays
 * a summary or initiates further actions based on the payment status.
 *
 */

// Start a new session or resume the existing one. This is crucial for accessing
// session variables and maintaining user state.
session_start();

// Include the database connection file.
require_once 'db.php';

// --- Validate incoming 'payment_id' ---
/**
 * @brief Checks if the 'payment_id' is set in the GET request.
 * If missing, it terminates the script with an error message.
 */
if (!isset($_GET['payment_id'])) {
    // Log an error if the payment ID is not provided.
    error_log("PayFast Redirect: No payment ID provided in GET request.");
    die("❌ No payment ID provided for PayFast redirection.");
}

// Sanitize and store the payment ID from the GET request.
$payment_id = $_GET['payment_id'];

// --- Fetch Payment Details from Database ---
/**
 * @brief Prepares and executes a SQL query to fetch total_amount, courier_cost,
 * and order_id from the 'payments' table based on the provided payment ID.
 * Includes robust error handling for statement preparation and execution.
 */
$stmt = $conn->prepare("SELECT total_amount, courier_cost, order_id FROM payments WHERE id = ?");

// Check if the statement preparation was successful.
if ($stmt === false) {
    // Log an error if preparation failed.
    error_log("PayFast Redirect: Database query preparation failed: " . $conn->error);
    die("❌ Database query preparation failed for payment details.");
}

// Bind the 'payment_id' parameter to the prepared statement.
$stmt->bind_param("i", $payment_id);

// Execute the prepared statement.
$stmt->execute();

// Check for errors during execution.
if ($stmt->error) {
    // Log an error if execution failed.
    error_log("PayFast Redirect: Database query execution failed: " . $stmt->error);
    die("❌ Database query execution failed for payment details.");
}

// MODIFIED LINE: Added $orderId to bind_result
$stmt->bind_result($total_amount, $courier_cost, $orderId);

// Fetch the results.
$stmt->fetch();

// Close the prepared statement.
$stmt->close();

// Handle case where payment ID is not found in the database.
if (is_null($total_amount)) { // If fetch did not find a row, $total_amount will be null
    error_log("PayFast Redirect: Payment ID " . $payment_id . " not found in database.");
    die("❌ Payment details not found for the provided ID.");
}

// Close the main database connection.
$conn->close();

// Calculate the subtotal.
$subtotal = $total_amount - $courier_cost;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayFast Payment Confirmation</title>

    <style>
        /* CSS Custom Properties (Variables) */
        /* Defines a set of color variables for consistent theming and easy maintenance.
            These are categorized for clarity (Main blues, Greens & Deeper Blues, Neutrals, Accent, Danger, Page specific). */
        :root {
            /* Main blues */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues */
            --caribbean-current: #006466ff;
            --midnight-green: #065a60ff;
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff;
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a;

            /* Neutrals */
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff;
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;

            /* Accent */
            --white-pop: #FFFFFF;
            --dark-font: #333;
            --light-font: #fefefe;
	    --highlight-color: #f7ff95;

            /* Red for danger/logout */
            --danger-red: #dc3545;
            --danger-red-hover: #bd2130;

            /* Page specific colors */
            --highlight-color: #ffe066;
            --container-bg: rgba(255, 255, 255, 0.15); /* Slightly less opaque for better frost */
            --container-border: rgba(255, 255, 255, 0.3); /* More visible border */
            --text-color: var(--ghost-white);
            --heading-color: var(--white-pop);
            --strong-color: var(--light-font); /* Amounts/IDs are now light-font */
            --link-color: var(--cool-gray); /* Links are now a cooler gray */
            --link-hover-color: var(--slate-gray); /* Link hover is a slightly darker gray */
            --button-bg: var(--caribbean-current);
            --button-hover: var(--midnight-green);
            --next-steps-bg: rgba(0, 0, 0, 0.25);
            --next-steps-border: var(--sapphire); /* Next steps border is now sapphire blue */
            --next-steps-heading: var(--sapphire); /*  Next steps heading is now sapphire blue */
            --icon-color: var(--true-blue); /* variable for icon color */
        }

        /* Universal Box-Sizing */
        /* Applies the 'border-box' model globally. This makes layout calculations
            more intuitive, as padding and border are included in the element's total width and height. */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Body and HTML Base Styles */
        /* Resets default margins/paddings, sets minimum height for full viewport coverage,
            prevents horizontal scrolling, applies a dynamic background gradient,
            sets base font, text color, and centers content using flexbox. */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        body {
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Allows for larger background to animate */
            animation: bgShift 25s ease infinite; /* Animates background position for a subtle shift effect */
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            display: flex;
            align-items: center; /* Vertically centers content */
            justify-content: center; /* Horizontally centers content */
            padding: 30px; /* Overall page padding */
        }

        /* Keyframe Animation for Background */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Main Container Styling */
        /* Applies a frosted glass effect with a translucent background, strong blur,
            visible border, prominent shadow, increased padding, rounded corners,
            and a fade-in animation for a modern UI feel. */
        .container {
            background: var(--container-bg);
            backdrop-filter: blur(15px); /* Stronger blur for better frost */
            border: 1px solid var(--container-border); /* More visible border */
            padding: 35px 45px; /* Increased padding */
            border-radius: 25px; /* More rounded corners */
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); /* More prominent shadow */
            width: 100%;
            max-width: 550px; /* Wider container */
            text-align: center;
            position: relative; /* For potential pseudo-elements or absolute positioning */
            animation: fadeIn 1s ease-out; /* Fade in animation */
        }

        /* Keyframe Animation for Container Fade-in */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Heading (h2) Styling */
        /* Styles the main heading with a distinct color, larger font size,
            bolder weight, letter spacing, and a subtle text shadow. */
        h2 {
            color: var(--heading-color);
            margin-bottom: 25px;
            font-size: 2.5rem; /* Larger heading */
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3); /* Subtle text shadow */
        }

        /* Paragraph (p) Styling */
        /* Defines base styles for paragraphs including color, font size,
            line height, and lighter font weight for readability. */
        p {
            color: var(--text-color);
            font-size: 1.1rem; /* Slightly larger base font */
            margin-bottom: 15px;
            line-height: 1.7;
            font-weight: 300; /* Lighter font weight for body text */
        }

        /* Strong Tag Styling */
        /* Styles `strong` tags to stand out with a bolder font weight
            and a specific light font color. */
        strong {
            font-weight: 600;
            color: var(--light-font); /* Changed to light-font for labels */
        }

        /* Amount Display Section Styling */
        /* Styles the section displaying payment amounts with larger font,
            vertical padding, and subtle top/bottom borders for separation.
            Includes specific styling for labels (strong) and values (span). */
        .amount-display {
            font-size: 1.3rem; /* Larger for amounts */
            margin-bottom: 25px;
            padding: 10px 0;
            border-top: 1px solid rgba(255,255,255,0.1); /* Subtle separators */
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .amount-display strong {
            display: inline-block; /* Helps with spacing */
            margin-right: 5px;
            min-width: 120px; /* Align amount labels */
            text-align: right;
            color: var(--ghost-white); /* Label color */
        }
        .amount-display span {
            color: var(--strong-color); /* Changed to ghost-white for amounts */
            font-weight: 700;
        }

        /* Back to Shop Button Styling */
        /* Styles the "Continue Shopping" button with a distinct background,
            text color, padding, rounded corners, hover effects for background,
            transform (lift), and a strong shadow. */
        .back-to-shop {
            background: var(--button-bg);
            border: none;
            color: var(--white-pop);
            padding: 15px 30px;
            margin-top: 35px;
            border-radius: 12px; /* More rounded */
            cursor: pointer;
            font-size: 1.15rem;
            font-weight: 600;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.4); /* Stronger shadow */
            letter-spacing: 0.5px;
        }

        .back-to-shop:hover {
            background: var(--button-hover);
            transform: translateY(-3px); /* More pronounced lift */
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.5); /* Even stronger shadow */
        }

        /* Next Steps Section Styling */
        /* Styles the "What Happens Next?" section with a translucent background,
            backdrop blur, increased padding, a thick left border, rounded corners,
            and a shadow for visual separation. */
        .next-steps {
            background-color: var(--next-steps-bg);
            backdrop-filter: blur(10px);
            padding: 25px; /* Increased padding */
            margin-top: 35px;
            border-left: 6px solid var(--next-steps-border); /* Thicker border */
            border-radius: 18px; /* More rounded */
            text-align: left;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
            /* Added borders for a more defined "card" look */
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            border-right: 1px solid rgba(255, 255, 255, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Next Steps Heading (h3) Styling */
        /* Styles the heading within the "Next Steps" section with a specific color,
            margins, larger font, bold weight, and uses flexbox for aligning
            text with a subtle emoji icon before it. */
        .next-steps h3 {
            color: var(--heading-color);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.6rem; /* Larger heading */
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px; /* Space for icon */
        }
        .next-steps h3::before {
            content: '✨'; /* Added a subtle emoji icon */
            font-size: 1.2em;
        }

        /* Next Steps Paragraph (p) Styling */
        /* Styles paragraphs within the "Next Steps" section with a base font size,
            line height, light font color for contrast, and uses flexbox for
            aligning text with an icon at the start. */
        .next-steps p {
            font-size: 1rem; /* Base font size */
            margin-bottom: 12px;
            line-height: 1.6;
            color: var(--light-font); /* Ensure good contrast */
            display: flex; /* For icon alignment */
            align-items: flex-start; /* Align text to top of icon */
            gap: 10px; /* Space between icon and text */
        }
        .next-steps p span.icon {
            font-size: 1.2em;
            color: var(--icon-color); /* Changed to a blue for icons */
        }

        /* Horizontal Rule (hr) Styling */
        /* Styles the horizontal rule with a light, subtle border for separation. */
        hr {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.1); /* Lighter separator */
            margin: 20px 0;
        }

        /* Next Steps Link (a) Styling */
        /* Styles links within the "Next Steps" section with a highlight color,
            no underline by default, bold font, and a transition for hover effects. */
        .next-steps a {
            color: var(--highlight-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease, text-decoration 0.3s ease;
        }

        .next-steps a:hover {
            color: var(--highlight-color);
            text-decoration: underline;
        }
/* Specific styling for the Important Warning Note */
        .next-steps .warning-note {
            border-left: 6px solid var(--danger-red);
            color: var(--highlight-color); /* Now correctly defined as yellow */
            font-weight: 500;
            padding: 15px 20px; /* Combined vertical & horizontal padding */                       
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Softer, darker shadow */
            
            display: flex;
            align-items: center; /* Vertically center icon with text */
            gap: 10px; /* Space between icon and text */
            flex-wrap: wrap; /* Allows content to wrap to the next line */
            word-break: break-word; /* Ensures long words break if needed */
	    max-width: 100%;
        }

        
        .next-steps .warning-note .icon {
            color: var(--danger-red);
            font-size: 1.4em;
            flex-shrink: 0; /* Prevents the icon from shrinking if space is tight */
        }

        /* Adjustments for smaller screens */
        @media (max-width: 480px) {
            .next-steps .warning-note {
                padding-left: 15px;
                margin-left: -18px; /* Adjust for smaller padding of .next-steps */
                margin-right: -18px; /* Adjust for smaller padding of .next-steps */
                gap: 8px;
            }
        }

        /* Hide PayFast-related Forms */
        /* These forms are hidden because the current page serves as a confirmation
            page and not a payment gateway interaction page. They might be
            placeholders for future integration or debugging. */
        .payment-buttons form {
            display: none;
        }

        /* Responsive Adjustments */
        /* Media queries to adjust layout and font sizes for different screen widths
            (max-width: 768px and max-width: 480px) to ensure responsiveness.
            Adjusts padding, border-radius, font sizes, and stacking of elements. */
        @media (max-width: 768px) {
            .container {
                padding: 30px 35px;
                border-radius: 20px;
            }
            h2 {
                font-size: 2.2rem;
            }
            p {
                font-size: 1rem;
            }
            .amount-display {
                font-size: 1.15rem;
            }
            .amount-display strong {
                min-width: 90px;
            }
            .back-to-shop {
                padding: 13px 25px;
                font-size: 1.05rem;
                margin-top: 30px;
            }
            .next-steps {
                padding: 20px;
                margin-top: 30px;
                border-left-width: 5px;
                border-radius: 15px;
            }
            .next-steps h3 {
                font-size: 1.4rem;
            }
            .next-steps p {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 20px;
            }
            .container {
                padding: 25px 20px;
                border-radius: 15px;
            }
            h2 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            p {
                font-size: 0.9rem;
            }
            .amount-display {
                font-size: 1.05rem;
            }
            .amount-display strong {
                display: block; /* Stack labels and values on small screens */
                text-align: center;
                margin-right: 0;
            }
            .amount-display span {
                display: block;
                margin-bottom: 5px;
            }

            .back-to-shop {
                padding: 10px 20px;
                font-size: 0.95rem;
                margin-top: 25px;
            }
            .next-steps {
                padding: 18px;
                margin-top: 25px;
                border-radius: 12px;
            }
            .next-steps h3 {
                font-size: 1.2rem;
                flex-direction: column; /* Stack icon and text */
                align-items: center;
                text-align: center;
                gap: 5px;
            }
            .next-steps p {
                font-size: 0.85rem;
                flex-direction: column; /* Stack icon and text */
                align-items: center;
                text-align: center;
                gap: 5px;
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
    <h2>Payment Details & Next Steps</h2>

    <p class="amount-display">
        <strong>Payment ID:</strong> <span><?= htmlspecialchars($payment_id) ?></span><br>
        <?php $subtotal = $total_amount - $courier_cost; ?>
        <strong>Subtotal:</strong> <span>R<?= htmlspecialchars(number_format($subtotal, 2)) ?></span><br>
        <strong>Courier Cost:</strong> <span>R<?= htmlspecialchars(number_format($courier_cost, 2)) ?></span><br>
        <strong>Total Amount:</strong> <span>R<?= htmlspecialchars(number_format($total_amount, 2)) ?></span>
    </p>

    <div class="next-steps">
        <h3><span class="icon">✨</span> What Happens Next?</h3>
        <p><span class="icon">📄</span> **Please email your Proof of Payment (POP)** to:
            <a href="mailto:accounts@quickbuy.co.za">accounts@quickbuy.co.za</a>
        </p>
        <p>👉 <a href="uploadPOP.php?order_id=<?php echo htmlspecialchars($orderId); ?>">Click here to upload your Proof of Payment</a></p>
        <hr>
        <p><span class="icon">🚚</span> Your order will be **dispatched within 24–48 hours** after POP verification.</p>
        <p><span class="icon">📞</span> For delivery queries, contact our courier at **022 485 2258**.</p>
        <p class="warning-note"><span class="icon">⚠️</span> **Important:** Courier delivery will not proceed until your Proof of Payment has been received and confirmed.</p>
    </div>

    <div class="payment-buttons">
        <a href="BrowseItemsPage.php" class="back-to-shop">← Continue Shopping</a>
        <form action="paymentSuccess.php" method="GET" style="display: none;">
            <input type="hidden" name="payment_id" value="<?= htmlspecialchars($payment_id) ?>">
            <input type="hidden" name="method" value="payfast">
            <button type="submit">✅ Successful Payment</button>
        </form>

        <form action="paymentCancelled.php" method="POST" style="display: none;">
            <input type="hidden" name="payment_id" value="<?= htmlspecialchars($payment_id) ?>">
            <button type="submit" class="cancel">❌ Cancel</button>
        </form>
    </div>
</div>

</body>
</html>
