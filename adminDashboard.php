<?php
// Start the session. This is essential for accessing and managing session variables,
// such as the logged-in user's role and username.
session_start();

// Include the database connection file. 
require_once 'db.php';

// Include the session validation file. This file is expected to contain logic
// to validate the current session, potentially checking for session expiration,
// unauthorized access attempts, or other security measures.
require_once 'session_validate.php';

// --- Access Control Check ---
// Verify if the user is logged in and if their role is 'admin'.
// If 'role' is not set in the session, or if the role is not 'admin',
// redirect the user to the LoginPage.php to prevent unauthorized access.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: LoginPage.php"); // Redirect to the login page.
    exit(); // Terminate script execution to ensure the redirect happens immediately.
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /*
        * CSS Custom Properties (Variables)
        * Defines a set of reusable color variables for consistent theming.
        * This makes it easy to update the site's color scheme from one central location.
        */
        :root {
            /* Main blues from Browse Categories */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff;

            /* Greens & Deeper Blues from Browse Categories */
            --caribbean-current: #006466ff;
            --midnight-green: #065a60ff;
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff;
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a;

            /* Neutrals from Browse Categories */
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff; /* Off-white for section backgrounds */
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff; /* Used for card borders */
            --charcoal: #1b3a4bff;

            /* Accent from Browse Categories */
            --white-pop: #FFFFFF; /* Pure white, used for dashboard background */
            --dark-font: #333; /* Dark font color for primary text */
            --light-font: #fefefe; /* Light font color, typically for buttons with dark backgrounds */

            /* Admin Dashboard Specific Colors */
            --admin-bg-start: var(--deep-space-blue); /* Darker blue for background gradient start */
            --admin-bg-end: var(--midnight-green-3);    /* Slightly lighter deep blue for background gradient end */
            --dashboard-card-bg: var(--white-pop); /* White background for the main dashboard card */
            --card-border: var(--cool-gray); /* Border color for sections */
            --header-color: var(--oxford-blue); /* Strong blue for main headers */
            --section-title-color: var(--sapphire); /* Slightly lighter blue for section titles */
            --text-color-primary: var(--dark-font); /* Primary text color */
            --text-color-secondary: var(--paynes-gray); /* Secondary text color, often for paragraphs */
            --button-primary-bg: var(--true-blue); /* Background for primary action buttons */
            --button-primary-hover: var(--sapphire); /* Hover state background for primary buttons */
            --button-danger-bg: #dc3545; /* Red for destructive actions, like logout */
            --button-danger-hover: #c82333; /* Darker red for danger button hover */
            --shadow-light: rgba(0, 0, 0, 0.08); /* Light shadow for cards */
            --shadow-medium: rgba(0, 0, 0, 0.15); /* Medium shadow for hover effects */
        }

        /*
        * Universal Box-Sizing
        * Ensures padding and border are included in an element's total width and height,
        * simplifying layout calculations and preventing unexpected overflows.
        */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /*
        * Body Styling
        * Sets up the overall page background, font, and layout using flexbox
        * to center the dashboard content vertically and horizontally.
        */
        body {
            /* Linear gradient background for a modern look, smoothly transitioning colors. */
            background: linear-gradient(135deg, var(--admin-bg-start), var(--admin-bg-end));
            background-size: 300% 300%; /* Larger background size for animation */
            animation: bgShift 20s ease infinite; /* Applies a slow, continuous background movement */
            min-height: 100vh; /* Ensures the body takes at least the full viewport height */
            font-family: 'Poppins', sans-serif; /* Applies the imported Google Font */
            display: flex; /* Enables flexbox for easy centering */
            justify-content: center; /* Centers content horizontally */
            align-items: center; /* Centers content vertically */
            padding: 20px; /* Padding around the entire content area */
            color: var(--text-color-primary); /* Sets the default text color */
            box-sizing: border-box; /* Ensures padding/border are included in element's total size */
        }

        /* Keyframe animation for the background shift */
        @keyframes bgShift {
            0% { background-position: 0% 50%; } /* Start position */
            50% { background-position: 100% 50%; } /* Mid position */
            100% { background-position: 0% 50%; } /* End position, loops back to start */
        }

        /*
        * Dashboard Container Styling
        * Styles the main container for the dashboard, giving it a card-like appearance
        * with a background, rounded corners, and a shadow. Includes a fade-in animation.
        */
        .dashboard {
            max-width: 95%; /* Maximum width on smaller screens */
            margin: 30px auto; /* Centers the dashboard horizontally with vertical margin */
            padding: 25px; /* Padding inside the dashboard container */
            background: var(--dashboard-card-bg); /* White background for the card */
            border-radius: 12px; /* Rounded corners for the card */
            box-shadow: 0 4px 15px var(--shadow-light); /* Subtle shadow for depth */
            animation: fadeIn 0.8s ease-out forwards; /* Fade-in animation on page load */
        }

        /* Keyframe animation for the dashboard fade-in effect */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); } /* Starts invisible and slightly below position */
            to { opacity: 1; transform: translateY(0); } /* Ends fully visible at its final position */
        }

        /*
        * Main Heading (h2) Styling
        * Styles the main welcome message at the top of the dashboard.
        */
        h2 {
            text-align: center; /* Centers the text */
            margin-bottom: 30px; /* Space below the heading */
            font-weight: 700; /* Bold font weight */
            font-size: 2.2rem; /* Large font size */
            color: var(--header-color); /* Deep blue color */
            position: relative; /* Needed for positioning the ::after pseudo-element */
            padding-bottom: 10px; /* Space for the accent line below */
        }

        /* Pseudo-element for the accent line under the main heading */
        h2::after {
            content: ''; /* Required for pseudo-elements */
            position: absolute; /* Positions the line relative to the h2 */
            left: 50%; /* Starts at the horizontal center */
            bottom: 0; /* Positions at the bottom of the h2 */
            transform: translateX(-50%); /* Adjusts to perfectly center the line */
            width: 70px; /* Width of the accent line */
            height: 3px; /* Thickness of the accent line */
            background-color: var(--true-blue); /* Color of the accent line */
            border-radius: 2px; /* Slightly rounded corners for the line */
        }

        /*
        * Section Styling
        * Styles individual functional sections within the dashboard.
        */
        .section {
            padding: 20px; /* Padding inside each section */
            border: 1px solid var(--card-border); /* Subtle border for definition */
            border-radius: 10px; /* Rounded corners for sections */
            margin-bottom: 20px; /* Space below each section */
            background-color: var(--ghost-white); /* Off-white background for sections */
            box-shadow: 0 2px 8px var(--shadow-light); /* Light shadow for sections */
            /* Smooth transition for transform (lift) and box-shadow on hover */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        /* Hover effect for sections: lifts the section and increases shadow */
        .section:hover {
            transform: translateY(-3px); /* Moves the section up slightly */
            box-shadow: 0 5px 15px var(--shadow-medium); /* Enlarges and darkens the shadow */
        }

        /* Section Heading (h4) Styling */
        .section h4 {
            margin-bottom: 10px; /* Space below the section title */
            font-weight: 600; /* Semi-bold font weight */
            font-size: 1.2rem; /* Font size for section titles */
            color: var(--section-title-color); /* Sapphire blue color */
        }

        /* Section Paragraph (p) Styling */
        .section p {
            font-size: 0.95rem; /* Font size for section descriptions */
            margin-bottom: 15px; /* Space below the paragraph */
            color: var(--text-color-secondary); /* Gray color for secondary text */
        }

        /*
        * Base Button Styling
        * Applies common styles to all buttons, including padding, rounded corners,
        * and transition effects for hover states.
        */
        .btn {
            font-size: 0.95rem; /* Consistent font size for buttons */
            padding: 10px 18px; /* Padding inside the button */
            border-radius: 8px; /* Rounded corners for buttons */
            /* Smooth transitions for background, transform, and shadow */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow for buttons */
            text-decoration: none; /* Removes underline from anchor tags used as buttons */
            display: inline-block; /* Allows padding and width to be applied correctly to anchors */
        }

        /* Primary Button Styling (e.g., "Manage" buttons) */
        .btn-primary {
            background-color: var(--button-primary-bg); /* True blue background */
            color: var(--light-font); /* Light text color */
            border: none; /* No border for primary buttons */
        }

        /* Hover effect for primary buttons: changes background and lifts */
        .btn-primary:hover {
            background-color: var(--button-primary-hover); /* Darker blue on hover */
            transform: translateY(-2px); /* Lifts the button slightly */
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); /* Increases shadow on hover */
        }

        /* Outline Danger Button Styling (e.g., Logout button) */
        .btn-outline-danger {
            background-color: transparent; /* Transparent background */
            color: var(--button-danger-bg); /* Red text color */
            border: 2px solid var(--button-danger-bg); /* Red border */
        }

        /* Hover effect for danger buttons: fills with color and lifts */
        .btn-outline-danger:hover {
            background-color: var(--button-danger-bg); /* Fills with red on hover */
            color: var(--white-pop); /* Changes text to white on hover */
            transform: translateY(-2px); /* Lifts the button slightly */
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); /* Increases shadow on hover */
        }

        /* Utility classes */
        .text-center {
            text-align: center; /* Centers text horizontally */
        }
        .mt-4 {
            margin-top: 30px !important; /* Sets top margin, !important ensures override */
        }

        /*
        * Media Queries for Responsiveness
        * Adjusts styles for different screen sizes to ensure optimal viewing.
        */

        /* Styles for screens larger than 768px (tablets and desktops) */
        @media (min-width: 768px) {
            body {
                padding: 40px; /* Increased padding around the body content */
            }
            .dashboard {
                max-width: 900px; /* Larger max-width for the dashboard container */
                padding: 40px; /* More internal padding for the dashboard */
                border-radius: 20px; /* More rounded corners for the dashboard */
                box-shadow: 0 10px 30px var(--shadow-medium); /* Larger shadow for desktops */
            }
            h2 {
                font-size: 2.8rem; /* Larger main title font size */
                margin-bottom: 40px; /* More space below the main title */
            }
            h2::after {
                width: 90px; /* Longer accent line under the main title */
            }
            .section {
                padding: 25px; /* More internal padding for sections */
                border-radius: 15px; /* More rounded corners for sections */
                margin-bottom: 25px; /* More space below sections */
            }
            .section h4 {
                font-size: 1.3rem; /* Larger section title font size */
            }
            .section p {
                font-size: 1rem; /* Standard paragraph font size */
            }
            .btn {
                padding: 12px 22px; /* Larger padding for buttons */
                font-size: 1rem; /* Larger button font size */
            }
            .mt-4 {
                margin-top: 40px !important; /* Adjust top margin for the logout button */
            }
        }

        /* Styles for screens smaller than or equal to 480px (mobile phones) */
        @media (max-width: 480px) {
            .dashboard {
                padding: 15px; /* Reduced padding for compact mobile view */
                border-radius: 10px; /* Slightly less rounded corners */
            }
            h2 {
                font-size: 1.8rem; /* Smaller main title font size for mobile */
                margin-bottom: 20px; /* Reduced space below the main title */
            }
            .section {
                padding: 15px; /* Reduced padding for sections */
                margin-bottom: 15px; /* Reduced space below sections */
            }
            .section h4 {
                font-size: 1.1rem; /* Smaller section title font size */
            }
            .section p {
                font-size: 0.85rem; /* Smaller paragraph font size for mobile */
            }
            .btn {
                padding: 8px 15px; /* Smaller padding for buttons */
                font-size: 0.85rem; /* Smaller button font size */
            }
        }
    </style>
</head>
<body>

<div class="dashboard">
    <h2>Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

    <div class="section">
        <h4>User Management</h4>
        <p>View, edit, or remove users.</p>
        <a href="manageUsers.php" class="btn btn-primary">Manage Users</a>
    </div>

    <div class="section">
        <h4>User Logs</h4>
        <p>View login history of users and admins.</p>
        <a href="view_user_logs.php" class="btn btn-primary">View User Logs</a>
    </div>

    <div class="section">
        <h4>Product Management</h4>
        <p>Approve or remove seller listings.</p>
        <a href="manageProducts.php" class="btn btn-primary">Manage Products</a>
    </div>

    <div class="section">
        <h4>Proof of Payments</h4>
        <p>View uploaded proof of payment files.</p>
        <a href="view_pop_uploads.php" class="btn btn-primary">View POP Uploads</a>
    </div>

    <div class="section">
        <h4>Reports</h4>
        <p>View platform-wide sales and user reports.</p>
        <a href="viewReports.php" class="btn btn-primary">View Reports</a>
    </div>

    <div class="section">
        <h4>Website Feedback</h4>
        <p>Review user ratings and comments about the website.</p>
        <a href="view_website_feedback.php" class="btn btn-primary">View Feedback</a>
    </div>

    <?php
    // This PHP block conditionally displays the 'Admin Accounts' section.
    // It will only be visible if the logged-in user has the 'superadmin' rank,
    // providing an additional layer of role-based access control within the dashboard itself.
    if (isset($_SESSION['rank']) && $_SESSION['rank'] === 'superadmin'):
    ?>
        <div class="section">
            <h4>Admin Accounts</h4>
            <p>Add or remove admin users.</p>
            <a href="manageAdmin.php" class="btn btn-primary">Manage Admins</a>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="admin_logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
</div>

</body>
</html>