<?php
/**
 * @brief This page allows users (logged in or guests) to submit feedback about the website.
 * It captures an overall rating, purpose of visit, comments on what they liked/improved,
 * and other general comments. The feedback is stored in a database.
 */

// Start a new session or resume the existing session.
// This is essential for accessing user session data.
session_start();

// --- User Authentication and Session Management ---

// Initialize variables for logged-in user details.
$loggedInUserId = null; // Stores the user's ID if logged in.
$username = 'Guest'; // Default username for unauthenticated users.

// Check if a user is logged in by verifying the 'user_id' in the session.
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id']; // Assign the logged-in user's ID.
    // Assign the username from the session, defaulting to 'User' if not found.
    $username = $_SESSION['username'] ?? 'User';
}

// --- Form Submission Handling and Database Interaction ---

// Initialize flags and error messages for feedback submission.
$feedbackSubmitted = false; // Flag to indicate if feedback was successfully submitted.
$submissionError = '';      // Stores any error messages during submission.

// Check if the form has been submitted using the POST method.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input from the form.
    // (int) casts to integer; htmlspecialchars() converts special characters to HTML entities;
    // trim() removes whitespace from the beginning and end of a string.
    $overallRating = isset($_POST['overall_rating']) ? (int)$_POST['overall_rating'] : 0;
    $visitPurpose = isset($_POST['visit_purpose']) ? htmlspecialchars(trim($_POST['visit_purpose'])) : '';
    $likedComments = isset($_POST['liked_comments']) ? htmlspecialchars(trim($_POST['liked_comments'])) : '';
    $improvedComments = isset($_POST['improved_comments']) ? htmlspecialchars(trim($_POST['improved_comments'])) : '';
    $otherComments = isset($_POST['other_comments']) ? htmlspecialchars(trim($_POST['other_comments'])) : '';

    // Get additional information about the user and page.
    // Constructs the full URL of the current page.
    $currentPageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    // Retrieves browser user agent information, defaulting to 'Unknown'.
    $browserInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    // Retrieves the user's IP address.
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    // Basic validation for the overall rating.
    if ($overallRating < 1 || $overallRating > 5) {
        $submissionError = "Please select a valid star rating (1-5).";
    } else {
        // Include the database connection file.
        // It's assumed that 'db.php' is intended to provide database credentials or a connection.
        // However, the provided code explicitly defines PDO connection details here.
        include 'db.php';

        // Database connection parameters (these should ideally be loaded from a secure config or db.php).
        $servername = "sql102.infinityfree.com";
        $dbusername = "if0_39013745";
        $dbpassword = "fsnMAST1Gm37";
        $dbname = "if0_39013745_quickbuy_db";

        try {
            // Establish a new PDO database connection.
            $conn_pdo = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
            // Set PDO error mode to exception for better error handling.
            $conn_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prepare the SQL INSERT statement using named placeholders for security.
            $stmt = $conn_pdo->prepare("INSERT INTO website_feedback (user_id, username, overall_rating, visit_purpose, liked_comments, improved_comments, other_comments, feedback_date, page_url, browser_info, ip_address, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, TRUE)");

            // Execute the prepared statement with an array of values.
            // The order of values must match the order of placeholders in the prepared statement.
            $stmt->execute([
                $loggedInUserId,
                $username,
                $overallRating,
                $visitPurpose,
                $likedComments,
                $improvedComments,
                $otherComments,
                $currentPageUrl,
                $browserInfo,
                $ipAddress
            ]);

            $feedbackSubmitted = true; // Set flag to true to display the thank you message.
        } catch (PDOException $e) {
            // Catch and handle PDO exceptions (database errors).
            $submissionError = "Database error: " . $e->getMessage();
            // Log the error for debugging purposes (e.g., to an error log file).
            error_log("Website feedback DB error: " . $e->getMessage());
        } finally {
            // Ensure the PDO connection is closed regardless of success or failure.
            $conn_pdo = null;
            // If `db.php` establishes a `mysqli` connection, close it here if it's still open.
            if (isset($conn) && $conn instanceof mysqli) {
                $conn->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Feedback - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS Custom Properties (Variables) for consistent theming --- */
        :root {
            /* Main blues */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues */
            --caribbean-current: #006466ff; /* Your desired darker green */
            --midnight-green: #065a60ff;     /* Your desired lighter green */
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
        }

        /* --- Universal Box-Sizing for consistent layout behavior --- */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* --- Base Body Styles --- */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        body {
            /* Gradient background with animation for a dynamic feel */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Larger background to enable animation */
            animation: bgShift 25s ease infinite; /* Smooth background shift animation */
            font-family: 'Poppins', sans-serif; /* Apply Poppins font */
            color: var(--ghost-white); /* Default body text color */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* Center content vertically too */
            padding: 15px; /* Base padding for mobile */
        }

        /* Keyframe animation for background gradient */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* --- Container for Main Content (Feedback Form) --- */
        .container {
            max-width: 700px; /* Adjusted for feedback form */
            width: 100%;
            background-color: rgba(27, 42, 75, 0.4); /* Frosted glass effect */
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px); /* Frosted glass effect */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3); /* Stronger shadow */
            text-align: center;
            color: var(--ghost-white); /* Ensure text within container is visible */
        }

        /* --- Heading Styles --- */
        h2 {
            font-size: 2.2rem;
            color: var(--white-pop); /* Consistent title color */
            margin-bottom: 25px;
        }

        /* --- Paragraph Styles --- */
        p {
            color: var(--cool-gray); /* Lighter gray for general text */
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* --- Star Rating Section --- */
        .star-rating {
            font-size: 3em;
            cursor: pointer;
            margin: 20px 0;
            display: flex; /* Use flex for star alignment */
            justify-content: center; /* Center the stars */
            gap: 5px; /* Space between stars */
        }
        .star {
            color: var(--slate-gray); /* Light gray for unselected stars */
            transition: color 0.2s ease, transform 0.1s; /* Smooth transitions */
            font-size: 1em; /* Ensures base size is from parent */
        }
        .star.selected {
            color: gold; /* Gold for selected stars */
        }
        .star:hover {
            transform: scale(1.1); /* Slight grow on hover */
        }

        /* --- Form Group Styles (Labels and Inputs) --- */
        .form-group {
            margin-bottom: 20px;
            text-align: left; /* Align form labels to left */
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600; /* Bolder label */
            color: var(--white-pop); /* White for labels */
        }
        .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 15px; /* Consistent padding */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Soft border */
            background-color: rgba(255, 255, 255, 0.08); /* Transparent background */
            border-radius: 10px; /* More rounded corners */
            font-size: 1rem;
            box-sizing: border-box; /* Include padding in width */
            color: var(--ghost-white); /* Text color inside input */
            transition: border-color 0.3s ease, background-color 0.3s ease; /* Smooth transitions */
        }
        .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--true-blue); /* Highlight on focus */
            background-color: rgba(255, 255, 255, 0.15);
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical; /* Allow vertical resizing */
        }

        /* Custom styling for select dropdown arrow */
        .form-group select {
            appearance: none; /* Remove default select styling */
            /* Custom dropdown arrow using SVG data URI */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23fafaff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center; /* Position the arrow */
            background-size: 1em; /* Size of the arrow */
            padding-right: 40px; /* Extra padding for the arrow */
        }
        .form-group select option {
            background-color: var(--prussian-blue); /* Darker background for options */
            color: var(--white-pop);
        }

        /* --- Submit Button Styles --- */
        .btn-submit {
            background-color: var(--midnight-green); /* Your desired green */
            color: var(--white-pop);
            padding: 15px 30px; /* More padding */
            border: none;
            border-radius: 12px; /* More rounded */
            font-size: 1.15rem; /* Slightly larger */
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transitions */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Add shadow */
            width: 100%; /* Full width */
            max-width: 300px; /* Max width for button */
            margin: 20px auto 0 auto; /* Center button */
            display: block; /* Ensure it's a block for auto margins */
        }
        .btn-submit:hover {
            background-color: var(--caribbean-current); /* Darker green on hover */
            transform: scale(1.01);
        }

        /* --- Thank You Message Styles --- */
        .thank-you-message {
            color: var(--white-pop);
            font-size: 1.6rem; /* Slightly larger */
            font-weight: bold;
            margin-top: 30px;
            /* PHP embedded to control display based on $feedbackSubmitted flag */
            display: <?php echo $feedbackSubmitted ? 'block' : 'none'; ?>;
        }

        /* --- Back to Dashboard Button Styles (after submission) --- */
        .back-to-dashboard-btn {
            background-color: var(--midnight-green); /* Green color */
            color: var(--white-pop);
            padding: 12px 25px;
            border: none;
            border-radius: 10px; /* More rounded */
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 25px; /* More margin */
            /* PHP embedded to control display based on $feedbackSubmitted flag */
            display: <?php echo $feedbackSubmitted ? 'inline-block' : 'none'; ?>;
            text-decoration: none; /* Remove underline for anchor tag */
        }
        .back-to-dashboard-btn:hover {
            background-color: var(--caribbean-current); /* Darker green on hover */
            transform: scale(1.02);
        }

        /* --- Error Message Styles --- */
        .error-message {
            color: #ff6b6b; /* A more subtle red error */
            margin-top: 15px;
            margin-bottom: 20px;
            font-weight: bold;
            background-color: rgba(255, 107, 107, 0.1); /* Light red background */
            border: 1px solid #ff6b6b;
            padding: 10px;
            border-radius: 8px;
        }

        /* --- Responsive adjustments (Media Queries) --- */

        /* Tablets and smaller Laptops */
        @media (min-width: 768px) {
            body {
                padding: 30px;
            }
            .container {
                padding: 40px;
                border-radius: 20px;
            }
            h2 {
                font-size: 2.8rem;
                margin-bottom: 30px;
            }
            .star-rating {
                font-size: 4em;
                gap: 8px;
            }
            .form-group textarea, .form-group select {
                padding: 15px 20px;
                font-size: 1.05rem;
            }
            .form-group select {
                background-position: right 20px center; /* Adjust arrow position for wider select */
                padding-right: 50px; /* Adjust padding for wider select */
            }
            .btn-submit {
                padding: 18px 35px;
                font-size: 1.25rem;
                max-width: 350px;
            }
            .back-to-dashboard-btn {
                padding: 15px 30px;
                font-size: 1.1rem;
            }
        }

        /* Desktops / Laptops */
        @media (min-width: 1024px) {
            body {
                padding: 50px;
            }
            .container {
                padding: 50px;
            }
            h2 {
                font-size: 3.2rem;
            }
            .star-rating {
                font-size: 4.5em;
                gap: 10px;
            }
            .form-group textarea, .form-group select {
                padding: 18px 25px;
                font-size: 1.1rem;
            }
            .form-group select {
                background-position: right 25px center; /* Fine-tune arrow for large screens */
                padding-right: 60px; /* Fine-tune padding for large screens */
            }
            .btn-submit {
                font-size: 1.3rem;
                max-width: 400px;
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
        <?php if ($feedbackSubmitted): ?>
            <p class="thank-you-message">Thank you for your valuable feedback! We appreciate you taking the time to help us improve.</p>
            <a class="back-to-dashboard-btn" href="SellersDashBoard.php">Back to Dashboard</a>
        <?php else: ?>
            <h2>Rate Your Experience</h2>
            <p>We'd love to hear your thoughts on our website. Your feedback helps us improve!</p>

            <?php if (!empty($submissionError)): ?>
                <p class="error-message"><?php echo $submissionError; ?></p>
            <?php endif; ?>

            <form method="POST" action="website_review.php">
                <div class="form-group">
                    <label>Overall Website Rating:</label>
                    <div class="star-rating" id="starRating">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                    </div>
                    <p style="color: var(--ghost-white);">Selected Rating: <span id="selectedRating">0</span> out of 5 stars</p>
                    <input type="hidden" name="overall_rating" id="overallRatingInput" value="0">
                </div>

                <div class="form-group">
                    <label for="visitPurpose">What best describes your visit today?</label>
                    <select id="visitPurpose" name="visit_purpose">
                        <option value="">-- Select --</option>
                        <option value="Browse">Browse / Research</option>
                        <option value="purchase">Making a Purchase</option>
                        <option value="support">Seeking Customer Support</option>
                        <option value="account">Managing Account</option>
                        <option value="selling">Selling Items</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="likedComments">What did you like about our website?</label>
                    <textarea id="likedComments" name="liked_comments" rows="4" placeholder="e.g., Easy navigation, clear product descriptions..."></textarea>
                </div>

                <div class="form-group">
                    <label for="improvedComments">What could we improve?</label>
                    <textarea id="improvedComments" name="improved_comments" rows="4" placeholder="e.g., Faster loading, better search, more payment options..."></textarea>
                </div>

                <div class="form-group">
                    <label for="otherComments">Any other comments?</label>
                    <textarea id="otherComments" name="other_comments" rows="4" placeholder=""></textarea>
                </div>

                <button type="submit" class="btn-submit">Submit Feedback</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // --- JavaScript for Star Rating Interactivity ---

        let currentRating = 0; // Stores the currently selected star rating.
        const stars = document.querySelectorAll('.star'); // Selects all star elements.
        const selectedRatingSpan = document.getElementById('selectedRating'); // Span to display selected rating.
        const overallRatingInput = document.getElementById('overallRatingInput'); // Hidden input to submit rating.

        // Add event listeners to each star.
        stars.forEach(star => {
            // When a star is clicked:
            star.addEventListener('click', () => {
                currentRating = parseInt(star.dataset.rating); // Get rating from data attribute.
                selectedRatingSpan.textContent = currentRating; // Update displayed rating.
                overallRatingInput.value = currentRating; // Update hidden input for form submission.
                updateStars(); // Update star appearance based on selected rating.
            });
            // When mouse hovers over a star:
            star.addEventListener('mouseover', () => {
                highlightStars(parseInt(star.dataset.rating)); // Highlight stars up to the hovered one.
            });
            // When mouse leaves the star area:
            star.addEventListener('mouseout', () => {
                updateStars(); // Revert to displaying the selected rating.
            });
        });

        /**
         * @brief Updates the visual state of the stars based on the `currentRating`.
         * Adds 'selected' class to stars up to `currentRating`, removes it otherwise.
         */
        function updateStars() {
            stars.forEach(star => {
                if (parseInt(star.dataset.rating) <= currentRating) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }

        /**
         * @brief Highlights stars on hover.
         * Changes the color of stars up to the given `rating` to gold, and others to gray.
         * @param {number} rating The rating level to highlight up to.
         */
        function highlightStars(rating) {
            stars.forEach(star => {
                if (parseInt(star.dataset.rating) <= rating) {
                    star.style.color = 'gold'; // Highlight on hover
                } else {
                    star.style.color = 'var(--slate-gray)'; // Revert to unselected color
                }
            });
        }

        // Initialize stars on page load.
        // This ensures stars are correctly displayed if the page reloads (e.g., due to a submission error).
        updateStars();
    </script>
</body>
</html>
