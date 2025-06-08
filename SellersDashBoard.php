<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Seller Dashboard Page
 *
 * This page serves as the main dashboard for users with a 'seller' role.
 * It provides navigation links to various seller-specific functionalities
 * such as managing profile, adding items, viewing listed items, and tracking purchases.
 *
 * It ensures that only authenticated seller users can access this page.
 */

// Start the session to access session variables.
session_start();

// Include the database connection file.
require_once 'db.php';

// Include the session validation script. This script is responsible for checking
// the validity of the current user session (e.g., if the user is logged in, session expiry).
require_once 'session_validate.php';

// Check if the 'user_id' is set in the session and if the 'role' is 'seller'.
// This is a critical access control mechanism to ensure only authorized sellers
// can view this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    // If the user is not logged in or their role is not 'seller',
    // redirect them to the login page to prevent unauthorized access.
    header("Location: LoginPage.php");
    exit(); // Terminate script execution after redirection.
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - QuickBuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap">
    <style>
/* === REVISED CSS === */

/* Original Root Variables (Keep these as they are) */
:root {
    /* Main blues for the color palette */
    --true-blue: #0466c8ff;
    --sapphire: #0353a4ff;
    --yale-blue: #023e7dff;
    --oxford-blue: #002855ff;
    --oxford-blue-2: #001845ff;
    --oxford-blue-3: #001233ff;

    /* Greens & Deeper Blues for accents and background variations */
    --caribbean-current: #006466ff;
    --midnight-green: #065a60ff;
    --midnight-green-2: #0b525bff;
    --midnight-green-3: #144552ff;
    --prussian-blue: #212f45ff;
    --deep-space-blue: #0d1b2a;

    /* Neutrals for text and background elements */
    --gunmetal: #30343fff;
    --ghost-white: #fafaffff;
    --delft-blue: #273469ff;
    --space-cadet: #1e2749ff;
    --paynes-gray: #5c677dff;
    --slate-gray: #7d8597ff;
    --cool-gray: #979dacff;
    --charcoal: #1b3a4bff;

    /* Accent color, pure white */
    --white-pop: #FFFFFF;
}

/* Base styles for the HTML and Body elements */
html, body {
    margin: 0; /* Remove default margin */
    padding: 0; /* Remove default padding */
    height: 100%; /* Ensure full height for background */
    box-sizing: border-box; /* Include padding and border in element's total width and height */
    overflow-x: hidden; /* Prevent horizontal scrollbar, common for responsive designs */
}

body {
    /* Apply a dynamic gradient background for visual appeal */
    background: linear-gradient(135deg,
        var(--deep-space-blue),
        var(--midnight-green-3),
        var(--prussian-blue),
        var(--oxford-blue),
        var(--true-blue)
    );
    background-size: 300% 300%; /* Allows for larger background for animation */
    animation: bgShift 25s ease infinite; /* Apply background shift animation */
    font-family: 'Poppins', sans-serif; /* Set primary font to Poppins */
    color: var(--ghost-white); /* Default text color */
    display: flex; /* Use flexbox for layout */
    justify-content: center; /* Center content horizontally */
    align-items: flex-start; /* Align content to the start vertically */
    flex-direction: column; /* Arrange items in a column */
    padding: 20px; /* Default padding around the content */
    min-height: 100vh; /* Ensure body takes at least full viewport height */
}

/* Keyframe animation for the background gradient to create a subtle, shifting effect */
@keyframes bgShift {
    0% { background-position: 0% 50%; } /* Start position */
    50% { background-position: 100% 50%; } /* Mid position */
    100% { background-position: 0% 50%; } /* End position, loops back */
}

/* Styling for the main content container */
.container {
    max-width: 90%; /* Max width for smaller desktops/laptops, expands on larger screens */
    width: 100%; /* Ensure it takes full width up to max-width */
    padding: 20px; /* Padding inside the container */
    box-sizing: border-box; /* Include padding in element's total width */
    display: grid; /* Use CSS Grid for layout of dashboard sections */
    grid-template-columns: 1fr; /* Default to a single column layout */
    gap: 20px; /* Space between grid items */
    justify-content: center; /* Center grid items horizontally */
    align-items: stretch; /* Stretch grid items vertically */
}

/* Styling for the main heading on the dashboard */
h2 {
    font-size: 2.5em; /* Large font size for prominence */
    color: var(--white-pop); /* Bright white color */
    text-align: center; /* Center the text */
    margin-bottom: 30px; /* Space below the heading */
    letter-spacing: 1px; /* Slightly increased letter spacing */
    text-shadow: 0 0 15px rgba(255, 255, 255, 0.2); /* Soft white glow effect */
    grid-column: 1 / -1; /* Span across all columns in the grid */
    word-break: break-word; /* Allow long words to break and wrap */
    padding: 0 10px; /* Horizontal padding */
    white-space: normal; /* Allow text to wrap naturally */
}

/* Styling for the introductory paragraph on the dashboard */
.dashboard-intro {
    text-align: center; /* Center the text */
    font-size: 1.15em; /* Readable font size */
    color: var(--cool-gray); /* Muted gray color */
    margin-bottom: 40px; /* Space below the paragraph */
    line-height: 1.6; /* Improved line spacing for readability */
    grid-column: 1 / -1; /* Span across all columns in the grid */
    padding: 0 10px; /* Horizontal padding */
    white-space: normal; /* Allow text to wrap naturally */
}

/* Ensures the entire section acts as a clickable link */
.dashboard-section-link {
    text-decoration: none; /* Remove underline from links */
    color: inherit; /* Inherit text color from parent */
    display: contents; /* Makes the anchor tag not affect the layout, allowing its child div to be styled directly */
}

/* Styling for individual dashboard sections (e.g., User Profile, Add Item) */
.dashboard-section {
    background-color: rgba(27, 42, 75, 0.4); /* Semi-transparent background with a dark blue tone */
    color: var(--ghost-white); /* Text color for section content */
    padding: 25px; /* Padding inside the section */
    border-radius: 20px; /* Rounded corners */
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.4); /* Soft shadow for depth */
    border: 1px solid rgba(255, 255, 255, 0.08); /* Subtle white border */
    backdrop-filter: blur(10px); /* Apply a blur effect to content behind the section */
    transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease; /* Smooth transitions for hover effects */
    display: flex; /* Use flexbox for internal content alignment */
    flex-direction: column; /* Stack content vertically */
    justify-content: space-between; /* Distribute space between items (title and paragraph) */
    height: auto; /* Auto height, adjusts to content */
    min-height: 150px; /* Minimum height for consistent card size */
    box-sizing: border-box; /* Include padding in total dimensions */
    text-align: center; /* Center align text within the section */
}

/* Hover effects for dashboard sections */
.dashboard-section:hover {
    transform: translateY(-5px); /* Lift the card slightly on hover */
    box-shadow: 0 0 50px rgba(0, 0, 0, 0.6); /* Enlarge shadow on hover */
    background-color: rgba(27, 42, 75, 0.6); /* Darken background on hover */
}

/* Styling for the heading within each dashboard section */
.dashboard-section h4 {
    font-size: 1.8em; /* Large font size for section titles */
    color: var(--white-pop); /* Bright white color */
    margin-bottom: 10px; /* Space below the title */
    transition: color 0.3s ease; /* Smooth color transition on hover */
    word-break: break-word; /* Allow long words to break */
    line-height: 1.2; /* Line height for titles */
    white-space: normal; /* Allow text to wrap naturally */
}

/* Color change for section heading on hover */
.dashboard-section:hover h4 {
    color: var(--caribbean-current); /* Change color to Caribbean green on hover */
}

/* Styling for the description paragraph within each dashboard section */
.dashboard-section p {
    background-color: rgba(0, 0, 0, 0.2); /* Semi-transparent dark background for the text box */
    color: var(--cool-gray); /* Muted gray text color */
    padding: 12px 15px; /* Padding inside the text box */
    border-radius: 10px; /* Rounded corners for the text box */
    font-size: 0.9em; /* Smaller font size for description */
    box-shadow: 0 2px 8px rgba(0,0,0,0.2); /* Soft shadow for the text box */
    margin-top: auto; /* Pushes the paragraph to the bottom if content is short */
    line-height: 1.5; /* Improved line spacing for readability */
    word-break: break-word; /* Allow long words to break */
    white-space: normal; /* Allow text to wrap naturally */
}

/* Styling for the Logout button */
.logout-btn {
    background-color: var(--midnight-green); /* Greenish-blue background */
    color: var(--ghost-white); /* White text color */
    padding: 16px 40px; /* Generous padding for a prominent button */
    border: none; /* No border */
    border-radius: 12px; /* Rounded corners */
    font-size: 1.1em; /* Readable font size */
    font-weight: bold; /* Bold text */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); /* Shadow for depth */
    cursor: pointer; /* Indicate clickable element */
    text-decoration: none; /* Remove underline (if it were an anchor) */
    transition: background-color 0.3s ease, transform 0.2s; /* Smooth transitions for hover effects */
    display: block; /* Make it a block-level element */
    width: 100%; /* Full width up to max-width */
    max-width: 450px; /* Max width for the button */
    margin: 40px auto 20px; /* Center horizontally with margin */
    grid-column: 1 / -1; /* Span across all grid columns */
}

/* Hover effect for the Logout button */
.logout-btn:hover {
    background-color: var(--caribbean-current); /* Change background color on hover */
    transform: scale(1.02); /* Slightly enlarge button on hover */
}

/* --- Responsive Adjustments --- */

/* Media query for Laptops and Desktops (New breakpoint for 3 columns) */
/* Targets screens from 992px to 1439px wide. */
@media (min-width: 992px) and (max-width: 1439px) {
    .container {
        grid-template-columns: repeat(3, 1fr); /* Layout into 3 equal columns */
        max-width: 95%; /* Allow container to take up more screen width */
        gap: 20px; /* Maintain gap between grid items */
        padding: 30px; /* Increased padding */
    }
    h2 {
        font-size: 2.8em; /* Adjust heading font size */
    }
    .dashboard-intro {
        font-size: 1.1em; /* Adjust intro text font size */
    }
    .dashboard-section {
        min-height: 160px; /* Adjust minimum height of dashboard cards */
        padding: 22px; /* Slightly reduced padding to help fit 3 columns */
    }
    .dashboard-section h4 {
        font-size: 1.5em; /* Slightly smaller title font size to prevent overflow */
    }
    .dashboard-section p {
        font-size: 0.85em; /* Slightly smaller description font size */
        padding: 10px 12px; /* Adjusted padding for description box */
    }
    .logout-btn {
        max-width: 400px; /* Adjust max width of logout button */
        padding: 14px 30px; /* Adjusted padding for logout button */
        font-size: 1em; /* Adjusted font size for logout button */
    }
}


/* Media query for Larger Desktops (screens 1440px and up) */
@media (min-width: 1440px) {
    .container {
        grid-template-columns: repeat(3, 1fr); /* Maintain 3 columns */
        max-width: 1500px; /* Allow content to spread out much wider */
        gap: 30px; /* Larger gap for larger screens */
        padding: 40px; /* More padding around content */
    }
    .dashboard-section {
        min-height: 180px; /* Increase card height */
        padding: 30px; /* More generous padding */
    }
    h2 {
        font-size: 3.2em; /* Larger heading font size */
    }
    .dashboard-intro {
        font-size: 1.25em; /* Larger intro text font size */
    }
    .dashboard-section h4 {
        font-size: 2em; /* Larger section title font size */
    }
    .dashboard-section p {
        font-size: 1em; /* Standard description font size */
    }
    .logout-btn {
        max-width: 500px; /* Wider logout button */
        padding: 18px 45px; /* More padding for logout button */
        font-size: 1.2em; /* Larger font size for logout button */
    }
}


/* Media query for Tablets (portrait) and larger phones */
/* Targets screens from 481px to 767px wide. */
@media (min-width: 481px) and (max-width: 767px) {
    .container {
        grid-template-columns: 1fr; /* Revert to single column layout */
        max-width: 500px; /* Constrain container width */
        padding: 20px; /* Standard padding */
        gap: 15px; /* Reduced gap between items */
    }
    h2 {
        font-size: 2em; /* Smaller heading font size */
        margin-bottom: 20px; /* Reduced margin */
        padding: 0 5px; /* Reduced horizontal padding */
    }
    .dashboard-intro {
        font-size: 1em; /* Smaller intro text font size */
        margin-bottom: 25px; /* Reduced margin */
        padding: 0 5px; /* Reduced horizontal padding */
    }
    .dashboard-section {
        padding: 18px; /* Reduced section padding */
        border-radius: 16px; /* Slightly less rounded corners */
        min-height: 110px; /* Smaller minimum height */
    }
    .dashboard-section h4 {
        font-size: 1.5em; /* Adjusted section title font size */
    }
    .dashboard-section p {
        font-size: 0.88em; /* Adjusted description font size */
        padding: 9px 12px; /* Adjusted padding for description box */
    }
    .logout-btn {
        padding: 13px 28px; /* Reduced padding for logout button */
        font-size: 0.95em; /* Reduced font size for logout button */
        margin: 28px auto 18px; /* Adjusted margins */
    }
}

/* Media query for Smartphones in Landscape Mode */
/* Targets screens from 481px to 820px wide in landscape orientation, optimizing for vertical space. */
@media (min-width: 481px) and (max-width: 820px) and (orientation: landscape) {
    body {
        padding: 0px; /* Remove all padding to maximize content area */
        align-items: flex-start; /* Align content to the top */
    }
    .container {
        grid-template-columns: 1fr; /* Single column layout */
        max-width: 98%; /* Nearly full width */
        padding: 0px; /* Remove container padding */
        gap: 5px; /* Very small gap between items */
    }
    h2 {
        font-size: 1.1em; /* Significantly smaller heading */
        margin-bottom: 3px; /* Minimal margin */
        line-height: 1; /* Tight line height */
        padding: 0 2px; /* Minimal horizontal padding */
    }
    .dashboard-intro {
        font-size: 0.7em; /* Very small intro text */
        margin-bottom: 5px; /* Small margin */
        padding: 0 2px; /* Minimal horizontal padding */
    }
    .dashboard-section {
        padding: 8px; /* Reduced section padding */
        min-height: 60px; /* Smallest minimum height */
        border-radius: 10px; /* Smaller border radius */
    }
    .dashboard-section h4 {
        font-size: 0.9em; /* Small section title */
        margin-bottom: 2px; /* Minimal margin */
    }
    .dashboard-section p {
        font-size: 0.6em; /* Very small description text */
        padding: 4px 6px; /* Minimal padding for description box */
    }
    .logout-btn {
        padding: 8px 15px; /* Reduced padding for logout button */
        font-size: 0.75em; /* Smaller font size for logout button */
        margin: 10px auto 5px; /* Reduced margins */
    }
}


/* Media query for Smartphones (most common sizes) - Portrait Only */
/* Targets screens from 376px to 480px wide in portrait orientation. */
@media (min-width: 376px) and (max-width: 480px) and (orientation: portrait) {
    body {
        padding: 8px; /* Reduced body padding */
    }
    .container {
        padding: 4px; /* Reduced container padding */
        max-width: 99%; /* Nearly full width */
        gap: 8px; /* Small gap between items */
    }
    h2 {
        font-size: 1.4em; /* Smaller heading */
        margin-bottom: 10px; /* Reduced margin */
        line-height: 1.1; /* Tight line height */
        padding: 0 2px; /* Minimal horizontal padding */
    }
    .dashboard-intro {
        font-size: 0.8em; /* Smaller intro text */
        margin-bottom: 12px; /* Reduced margin */
        padding: 0 2px; /* Minimal horizontal padding */
    }
    .dashboard-section {
        padding: 10px; /* Reduced section padding */
        border-radius: 14px; /* Slightly less rounded corners */
        min-height: 80px; /* Smaller minimum height */
    }
    .dashboard-section h4 {
        font-size: 1em; /* Small section title */
    }
    .dashboard-section p {
        font-size: 0.68em; /* Smaller description text */
        padding: 5px 7px; /* Reduced padding for description box */
    }
    .logout-btn {
        padding: 9px 18px; /* Reduced padding for logout button */
        font-size: 0.8em; /* Smaller font size for logout button */
        margin: 18px auto 8px; /* Reduced margins */
    }
}

/* Media query for Smaller Smartphones (e.g., iPhone SE/5) - Portrait Only */
/* Targets screens from 321px to 375px wide in portrait orientation. */
@media (min-width: 321px) and (max-width: 375px) and (orientation: portrait) {
    body {
        padding: 5px; /* Even smaller body padding */
    }
    .container {
        padding: 1px; /* Minimal container padding */
        gap: 6px; /* Very small gap */
    }
    h2 {
        font-size: 1.1em; /* Very small heading */
        margin-bottom: 8px; /* Minimal margin */
        line-height: 1; /* Tight line height */
        padding: 0 1px; /* Almost no horizontal padding */
    }
    .dashboard-intro {
        font-size: 0.75em; /* Very small intro text */
        margin-bottom: 10px; /* Minimal margin */
        padding: 0 1px; /* Almost no horizontal padding */
    }
    .dashboard-section {
        padding: 8px; /* Reduced section padding */
        border-radius: 10px; /* Smaller border radius */
        min-height: 65px; /* Smallest minimum height */
    }
    .dashboard-section h4 {
        font-size: 0.9em; /* Smallest section title */
    }
    .dashboard-section p {
        font-size: 0.6em; /* Extremely small description text */
        padding: 4px 6px; /* Minimal padding for description box */
    }
    .logout-btn {
        padding: 7px 15px; /* Very small padding for logout button */
        font-size: 0.75em; /* Very small font size for logout button */
        margin: 12px auto 6px; /* Minimal margins */
    }
}


/* Media query for Smartwatches and very tiny viewports */
/* Targets screens up to 320px wide (general for portrait/landscape). */
@media (max-width: 320px) {
    body {
        padding: 0px; /* No padding on body */
        align-items: flex-start; /* Align to top */
    }
    .container {
        padding: 0px; /* No padding on container */
        gap: 2px; /* Minimal gap */
        max-width: 100%; /* Full width */
    }
    h2 {
        font-size: 0.9em; /* Tiny heading */
        margin-bottom: 2px; /* Minimal margin */
        line-height: 1; /* Very tight line height */
        padding: 0 0px; /* No horizontal padding */
    }
    .dashboard-intro {
        font-size: 0.55em; /* Extremely tiny intro text */
        margin-bottom: 3px; /* Minimal margin */
        padding: 0 0px; /* No horizontal padding */
    }
    .dashboard-section {
        padding: 3px; /* Minimal section padding */
        border-radius: 4px; /* Minimal border radius */
        min-height: 40px; /* Very small minimum height */
    }
    .dashboard-section h4 {
        font-size: 0.75em; /* Very small section title */
        margin-bottom: 1px; /* Minimal margin */
        line-height: 1.1; /* Tight line height */
    }
    .dashboard-section p {
        font-size: 0.5em; /* Barely readable description text */
        padding: 1px 3px; /* Minimal padding for description box */
    }
    .logout-btn {
        padding: 3px 6px; /* Extremely small padding for logout button */
        font-size: 0.6em; /* Extremely small font size for logout button */
        margin: 5px auto 3px; /* Minimal margins */
        max-width: 120px; /* Very narrow button */
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
        <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
        <p class="dashboard-intro">This is your <strong>seller dashboard</strong>. Use the navigation panel to access different features.</p>

        <a href="SellersProfile.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>User Profile</h4>
                <p>Here you can view your User Profile and edit your personal information.</p>
            </div>
        </a>

        <a href="AdditemPage.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>Add Item</h4>
                <p>Use this section to add a new product to your QuickBuy store.</p>
            </div>
        </a>

        <a href="viewCurrentItems.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>View Your Items For Sale</h4>
                <p>Your listed products will show here.</p>
            </div>
        </a>

        <a href="viewPurchasedItems.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>View Your Purchased Items</h4>
                <p>Here you can track which of your items have been bought.</p>
            </div>
        </a>

        <a href="website_review.php" class="dashboard-section-link">
            <div class="dashboard-section">
                <h4>Review Web Page</h4>
                <p>Share your feedback on our website to help us improve your experience!</p>
            </div>
        </a>

        <form method="post" action="Logout.php">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</script>

</body>
</html>
