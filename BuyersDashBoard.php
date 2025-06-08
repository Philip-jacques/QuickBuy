<?php
/**
 * Buyer Dashboard Page
 *
 * This page serves as the main dashboard for logged-in buyers.
 * It provides links to various buyer-specific functionalities such as
 * viewing their profile, Browse items, checking purchase history, and
 * submitting website reviews.
 *
 * It includes essential security checks to ensure only authenticated buyers
 * can access this page.
 */

// Start a new session or resume the existing one. This is crucial for accessing session variables.
session_start();

// Include the database connection file.
require_once 'db.php';

// Include the session validation script.
// This script likely contains functions or logic to further validate the session,
// beyond the basic checks performed directly on this page.
require_once 'session_validate.php';

// --- Security Check: User Authentication and Role Validation ---
// Redirect to the login page if the 'user_id' session variable is not set,
// or if the 'role' session variable is not explicitly 'buyer'.
// This prevents unauthorized access and ensures only buyers can view this dashboard.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: LoginPage.php");
    exit(); // Always call exit() after a header redirect to prevent further script execution.
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /*
         * CSS Custom Properties (Variables)
         *
         * Defines a consistent color palette for the entire dashboard, making it
         * easy to manage and update the theme. Grouped by color categories for clarity.
         */
        :root {
            /* Main blues for primary elements and background gradients */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues for accents and background depth */
            --caribbean-current: #006466ff; /* Your desired darker green */
            --midnight-green: #065a60ff;     /* Your desired lighter green */
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff;
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a;

            /* Neutrals for text, borders, and subtle backgrounds */
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff; /* A slightly off-white for text on dark backgrounds */
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff; /* Used for default button states */
            --slate-gray: #7d8597ff; /* Used for button hover states */
            --cool-gray: #979dacff; /* For softer text elements */
            --charcoal: #1b3a4bff;

            /* Accent Colors */
            --white-pop: #FFFFFF; /* Pure white for high contrast text/elements */
            --dark-font: #333; /* For text on light backgrounds (not heavily used here) */
            --light-font: #fefefe; /* For text on dark backgrounds, similar to ghost-white */

            /* Red for danger/logout actions */
            --danger-red: #dc3545; /* Standard red for destructive actions */
            --danger-red-hover: #bd2130; /* Darker red on hover */
        }

        /* Universal Box-Sizing for consistent layout behavior across all elements. */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Basic HTML and Body Styling */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh; /* Ensure body takes at least full viewport height */
            overflow-x: hidden; /* Prevent horizontal scrollbar, crucial for responsive gradients */
        }

        body {
            /* Animated gradient background for a dynamic visual effect. */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Larger background size for more movement in animation */
            animation: bgShift 25s ease infinite; /* Smooth, infinite animation for background position */
            font-family: 'Poppins', sans-serif; /* Applied globally for consistent typography */
            color: var(--ghost-white); /* Default text color for the entire body */
            display: flex;
            flex-direction: column; /* Stack children vertically */
            align-items: center; /* Center content horizontally */
            justify-content: flex-start; /* Align content to the top, allowing padding to push it down */
            padding-top: 30px; /* Top padding for spacing from the top edge */
            padding-bottom: 30px; /* Bottom padding to ensure space for the logout button */
        }

        /* Keyframe animation for the background gradient shift. */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Main Container Styling */
        .container {
            max-width: 95%; /* Adjusted for better fit on smaller screens */
            width: 100%;
            padding: 20px;
            margin-top: 20px; /* Adjusted margin from the top of the viewport */
            /* Optional: Frosted glass effect for the main container. Commented out
               to allow the background gradient to show through directly, but
               can be uncommented if a contained frosted look is preferred. */
            /* background-color: rgba(27, 42, 75, 0.4); */
            /* border: 1px solid rgba(255, 255, 255, 0.05); */
            /* backdrop-filter: blur(12px); */
            /* border-radius: 15px; */
            /* box-shadow: 0 0 20px rgba(0, 0, 0, 0.3); */
            text-align: center; /* Center content within the container */
        }

        /* Main Heading Styling */
        h2 {
            font-size: 2.5rem; /* Adjusted font size for responsiveness */
            color: var(--white-pop); /* Bright white for high visibility */
            text-align: center;
            margin-bottom: 15px; /* Spacing below the heading */
            font-weight: 600; /* Semi-bold text */
        }

        /* Dashboard Introduction Text */
        .dashboard-intro {
            text-align: center;
            font-size: 1.1rem; /* Slightly larger for improved readability */
            color: var(--cool-gray); /* Lighter gray for a softer visual */
            margin-bottom: 30px; /* Spacing below the introduction text */
            line-height: 1.5; /* Improved line spacing for readability */
        }

        /* Styling for the anchor tags wrapping dashboard sections */
        .dashboard-section-link {
            text-decoration: none; /* Remove default underline from links */
            color: inherit; /* Inherit text color from the parent for consistency */
            display: block; /* Make the entire link clickable block */
            margin-bottom: 15px; /* Spacing between each dashboard section card */
        }

        /* Styling for individual dashboard section cards */
        .dashboard-section {
            background-color: rgba(255, 255, 255, 0.1); /* Frosted glass background effect */
            color: var(--ghost-white); /* Text color for the cards */
            padding: 15px 20px; /* Internal padding within the card */
            border-radius: 12px; /* Rounded corners for a softer look */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Subtle shadow for depth */
            border: 1px solid rgba(255, 255, 255, 0.1); /* Thin white border for separation */
            backdrop-filter: blur(8px); /* The blur effect for the frosted glass */
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease; /* Smooth hover transitions */
            min-height: 80px; /* Minimum height to ensure consistent card size */
            display: flex; /* Use flexbox for vertical alignment of content */
            flex-direction: column;
            align-items: center; /* Center content horizontally within the card */
            justify-content: center; /* Center content vertically within the card */
            text-align: center; /* Center text within the card */
        }

        /* Hover effects for dashboard section cards */
        .dashboard-section:hover {
            transform: translateY(-5px); /* Lift effect on hover */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); /* Stronger shadow on hover */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly less transparent on hover */
        }

        /* Styling for the main title within each dashboard section card */
        .dashboard-section h4 {
            font-size: 1.4rem; /* Adjusted font size for titles */
            margin-bottom: 5px; /* Spacing below the title */
            color: var(--white-pop); /* Bright white for titles */
            font-weight: 500; /* Medium font weight */
        }

        /* Styling for the descriptive paragraph within each dashboard section card */
        .dashboard-section p {
            display: none; /* Hidden by default, appears on hover */
            background-color: rgba(0, 0, 0, 0.2); /* Darker background for the pop-up text */
            color: var(--cool-gray); /* Lighter text color for readability */
            padding: 6px 10px; /* Internal padding */
            border-radius: 6px; /* Rounded corners */
            font-size: 0.9rem; /* Smaller font size for descriptive text */
            box-shadow: 0 1px 4px rgba(0,0,0,0.2); /* Subtle shadow */
            margin-top: 8px; /* Spacing above the descriptive text */
            line-height: 1.4; /* Improved line height */
            opacity: 0; /* Start fully transparent for fade-in effect */
            transition: opacity 0.3s ease; /* Smooth fade-in transition */
        }

        /* Shows the descriptive paragraph on hover */
        .dashboard-section:hover p {
            display: block; /* Changes display to block to make it visible */
            opacity: 1; /* Fades in the text */
        }

        /* Styling for the Logout button */
        .logout-btn {
            background-color: var(--danger-red); /* Red color from variables */
            color: var(--white-pop); /* White text */
            padding: 12px 25px; /* Padding for button size */
            border: none; /* Remove default border */
            border-radius: 10px; /* Rounded corners */
            font-size: 1rem; /* Font size */
            cursor: pointer; /* Indicate clickable element */
            margin: 30px auto 0; /* Center the button horizontally with top margin */
            display: block; /* Make it a block element to apply auto margins */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth hover transitions */
            font-weight: 500; /* Medium font weight */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Shadow for depth */
        }

        /* Hover effects for the Logout button */
        .logout-btn:hover {
            background-color: var(--danger-red-hover); /* Darker red on hover */
            transform: scale(1.02); /* Slight scale-up effect */
        }


        /* --- Media Queries for Responsive Design --- */

        /* Tablets and smaller Laptops (min-width: 768px) */
        @media (min-width: 768px) {
            body {
                padding-top: 50px;
                padding-bottom: 50px;
            }
            .container {
                max-width: 900px;
                padding: 30px;
            }
            h2 {
                font-size: 3rem;
                margin-bottom: 20px;
            }
            .dashboard-intro {
                font-size: 1.25rem;
                margin-bottom: 40px;
            }
            .dashboard-section-link {
                margin-bottom: 20px; /* Maintain spacing for stacked layout on larger tablets */
            }
            .dashboard-section {
                padding: 20px 25px;
                border-radius: 16px;
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25); /* Slightly stronger shadow */
                min-height: 100px;
            }
            .dashboard-section:hover {
                transform: translateY(-6px); /* More pronounced lift */
                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.35); /* Stronger shadow */
            }
            .dashboard-section h4 {
                font-size: 1.6rem;
                margin-bottom: 8px;
            }
            .dashboard-section p {
                padding: 8px 12px;
                border-radius: 8px;
                font-size: 1rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                margin-top: 10px;
            }
            .logout-btn {
                padding: 15px 30px;
                border-radius: 12px;
                font-size: 1.1rem;
                margin: 40px auto 0;
            }
        }

        /* Desktops / Laptops (min-width: 1024px) */
        @media (min-width: 1024px) {
            body {
                padding-top: 60px;
                padding-bottom: 60px;
            }
            .container {
                max-width: 1000px; /* Wider container for more content space */
                padding: 40px;
            }
            h2 {
                font-size: 3.5rem;
                margin-bottom: 25px;
            }
            .dashboard-intro {
                font-size: 1.35rem;
                margin-bottom: 50px;
            }
            /* New class for grid layout on larger screens */
            .dashboard-sections-grid {
                display: grid;
                /* Creates two columns with flexible width, minimum 380px each */
                grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
                gap: 25px; /* Spacing between grid items (dashboard sections) */
            }
            .dashboard-section-link {
                margin-bottom: 0; /* Remove individual margins as grid gap handles spacing */
            }
            .dashboard-section {
                padding: 25px 30px;
                border-radius: 20px;
                min-height: 120px;
            }
            .dashboard-section h4 {
                font-size: 1.8rem;
            }
            .dashboard-section p {
                font-size: 1.05rem;
            }
            .logout-btn {
                padding: 18px 35px;
                font-size: 1.2rem;
                margin: 50px auto 0;
            }
        }
    </style>
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
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p class="dashboard-intro">This is your **buyer dashboard**. Explore your options below.</p>

    <div class="dashboard-sections-grid">
        <a href="BuyersProfile.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>View Profile</h4>
                <p>Check and edit your personal information here.</p>
            </div>
        </a>

        <a href="BrowseItemsPage.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>Browse Items</h4>
                <p>View and purchase available items listed by sellers.</p>
            </div>
        </a>

        <a href="BuyerPurchases.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>Purchased Items</h4>
                <p>See a list of all the items you’ve bought so far.</p>
            </div>
        </a>

        <a href="website_review.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>Review Web Page</h4>
                <p>Share your feedback on our website to help us improve your experience!</p>
            </div>
        </a>
    </div>

    <form method="post" action="Logout.php">
        <button type="submit" class="logout-btn">Logout</button>
    </form>
</div>

</body>
</html>
