<?php
// Start the session. This is crucial for maintaining user login state across pages.
session_start();


require_once 'db.php';

// --- Access Control Check ---
// Check if the 'user_id' session variable is set and if the 'role' session variable is 'buyer'.
// If either condition is false (user not logged in or not a buyer), redirect them to the login page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: LoginPage.php"); // Redirect to the login page.
    exit(); // Terminate script execution to prevent further output.
}

// Initialize an empty array to store product categories fetched from the database.
$productsByCategory = [];

// SQL query to select distinct categories from the 'products' table.
// It ensures that categories are not NULL or empty and orders them alphabetically.
$productQuery = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";

// Execute the query using the database connection ($conn).
$productResult = $conn->query($productQuery);

// Check if the query execution was successful.
if ($productResult) {
    // Loop through each row returned by the query.
    while ($row = $productResult->fetch_assoc()) {
        // Add each distinct category to the $productsByCategory array.
        $productsByCategory[] = $row['category'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Categories - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /*
        * CSS Custom Properties (Variables)
        * Defines a set of reusable color variables for consistent theming.
        * This promotes maintainability and allows for easy color scheme changes.
        */
        :root {
            /* Main blues */
            --true-blue: #0466c8ff;
            --sapphire: #0353a4ff;
            --yale-blue: #023e7dff;
            --oxford-blue: #002855ff;
            --oxford-blue-2: #001845ff;
            --oxford-blue-3: #001233ff; /* Deepest blue for top bar */

            /* Greens & Deeper Blues */
            --caribbean-current: #006466ff; /* Darker green */
            --midnight-green: #065a60ff;     /* Lighter green (hover state for search) */
            --midnight-green-2: #0b525bff;
            --midnight-green-3: #144552ff; /* Used for search button */
            --prussian-blue: #212f45ff;
            --deep-space-blue: #0d1b2a; /* Darkest blue for background gradient start */

            /* Neutrals */
            --gunmetal: #30343fff;
            --ghost-white: #fafaffff; /* Lightest neutral, used for main text on dark background */
            --delft-blue: #273469ff; /* Used for subtle borders */
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff; /* Used for back button */
            --charcoal: #1b3a4bff;

            /* Accent */
            --white-pop: #FFFFFF; /* Pure white */
            --dark-font: #333;
            --light-font: #fefefe;

            /* Red for danger/logout/error messages */
            --danger-red: #dc3545;
            --danger-red-hover: #bd2130;
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

        /* Basic HTML and Body Reset */
        html, body {
            margin: 0; /* Remove default margin */
            padding: 0; /* Remove default padding */
            min-height: 100vh; /* Ensure body takes at least full viewport height */
            overflow-x: hidden; /* Prevent horizontal scrollbar, crucial for responsive design */
        }

        /*
        * Body Styling
        * Sets up the overall page background, font, and layout using flexbox
        * for vertical arrangement.
        */
        body {
            /* Complex linear gradient background for a vibrant, dynamic look */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Larger background size for animation */
            animation: bgShift 25s ease infinite; /* Applies a slow, continuous background movement */
            font-family: 'Poppins', sans-serif; /* Applies the imported Google Font */
            color: var(--ghost-white); /* Default text color for the page */
            display: flex; /* Enables flexbox for easy layout */
            flex-direction: column; /* Stacks items vertically */
            align-items: center; /* Centers items horizontally within the flex container */
            justify-content: flex-start; /* Aligns content to the top */
            padding-top: 30px; /* Space from the top of the viewport */
            padding-bottom: 30px; /* Space from the bottom of the viewport */
        }

        /* Keyframe animation for the background shift */
        @keyframes bgShift {
            0% { background-position: 0% 50%; } /* Start position */
            50% { background-position: 100% 50%; } /* Mid position */
            100% { background-position: 0% 50%; } /* End position, loops back to start */
        }

        /*
        * Container Styling
        * Defines the main content area's maximum width and padding.
        */
        .container {
            max-width: 95%; /* Maximum width on smaller screens */
            width: 100%; /* Take full width up to max-width */
            padding: 20px; /* Internal padding */
            margin-top: 20px; /* Space from the element above it */
            text-align: center; /* Centers inline content within the container */
        }

        /* Main Heading (h2) Styling */
        h2 {
            font-size: 2.5rem; /* Font size for the main page title */
            color: var(--white-pop); /* White color for prominence */
            text-align: center; /* Centers the text */
            margin-bottom: 15px; /* Space below the heading */
            font-weight: 600; /* Semi-bold font weight */
        }

        /*
        * Top Bar Styling
        * Styles the fixed navigation bar at the top of the page.
        */
        .top-bar {
            background-color: var(--oxford-blue-3); /* Deep blue background */
            color: var(--white-pop); /* White text color */
            padding: 15px 20px; /* Padding inside the top bar */
            display: flex; /* Enables flexbox for aligning content */
            justify-content: space-between; /* Pushes content to opposite ends */
            align-items: center; /* Vertically centers content */
            font-size: 1.5rem; /* Font size for the 'QuickBuy' text */
            font-weight: bold; /* Bold font for the title */
            width: 100%; /* Occupy full width of the viewport */
            position: fixed; /* Fixes the top bar to the top of the viewport */
            top: 0; /* Aligns to the top edge */
            left: 0; /* Aligns to the left edge */
            z-index: 1000; /* Ensures it stays on top of other content */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Shadow for depth */
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); /* Subtle bottom border */
        }

        /* Cart Link Styling within Top Bar */
        .top-bar .cart-link {
            font-size: 1rem; /* Smaller font size for the link */
            background: var(--midnight-green-3); /* Dark green background */
            color: var(--white-pop); /* White text color */
            padding: 8px 12px; /* Padding for the link button */
            border-radius: 8px; /* Rounded corners */
            text-decoration: none; /* Removes underline */
            font-weight: bold; /* Bold font */
            transition: background-color 0.3s ease; /* Smooth transition on hover */
        }

        /* Cart Link Hover Effect */
        .top-bar .cart-link:hover {
            background-color: var( --midnight-green); /* Lighter green on hover */
        }

        /*
        * Browse Header Styling
        * Contains the main "Browse Categories" title and the search elements.
        */
        .browse-header {
            display: flex; /* Enables flexbox */
            justify-content: space-between; /* Spreads items horizontally */
            align-items: center; /* Vertically aligns items */
            margin-bottom: 30px; /* Space below the header */
            flex-direction: column; /* Stacks items vertically on small screens */
            width: 100%; /* Take full width */
            padding-top: 70px; /* Provides space so content isn't hidden by the fixed top bar */
        }

        /* Browse Header Title (h2) Styling */
        .browse-header h2 {
            font-size: 2.8rem; /* Larger title font size */
            color: var(--white-pop); /* White color */
            margin-bottom: 20px; /* Space below the title */
        }

        /* Container for Search Button and Bar */
        .browse-header > div {
            display: flex; /* Enables flexbox for search elements */
            gap: 10px; /* Space between search elements */
            width: 100%; /* Take full width */
            justify-content: center; /* Centers search elements */
            flex-wrap: wrap; /* Allows search elements to wrap to the next line on smaller screens */
        }

        /* Search Button Styling */
        #searchButton {
            background-color: var(--midnight-green-3); /* Dark green background */
            color: var(--white-pop); /* White text color */
            border: none; /* No border */
            padding: 12px 20px; /* Generous padding */
            font-size: 1.1rem; /* Larger font size */
            border-radius: 10px; /* Rounded corners */
            cursor: pointer; /* Indicates clickable element */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transitions */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Subtle shadow */
        }

        /* Search Button Hover Effect */
        #searchButton:hover {
            background-color: var(--midnight-green); /* Darker green on hover */
            transform: translateY(-2px); /* Lifts the button slightly */
        }

        /* Search Bar (Input Field) Styling */
        #searchBar {
            margin-top: 10px; /* Space above the search bar on mobile */
            padding: 10px 15px; /* Padding inside the input field */
            width: 100%; /* Take full width up to max-width */
            max-width: 350px; /* Maximum width of the search bar */
            border-radius: 10px; /* Rounded corners */
            border: 1px solid var(--delft-blue); /* Subtle border */
            background-color: rgba(255, 255, 255, 0.1); /* Semi-transparent white for frosted effect */
            color: var(--ghost-white); /* Text color for input */
            display: none; /* Hidden by default (controlled by JS) */
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.2); /* Inner shadow for depth */
            transition: all 0.3s ease; /* Smooth transition for all properties */
        }

        /* Placeholder text color for search bar */
        #searchBar::placeholder {
            color: var(--cool-gray);
        }

        /* Search Bar Focus Effect */
        #searchBar:focus {
            outline: none; /* Remove default outline */
            border-color: var(--midnight-green); /* Highlight border on focus */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly less transparent on focus */
        }

        /*
        * Categories Grid Styling
        * Uses CSS Grid to arrange category cards in a responsive layout.
        */
        .categories {
            display: grid; /* Enables CSS Grid */
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Flexible columns, minimum 250px wide */
            gap: 25px; /* Space between grid items (category cards) */
            justify-content: center; /* Centers grid items if they don't fill the row */
            margin-top: 30px; /* Space above the category grid */
            width: 100%; /* Take full width */
        }

        /*
        * Category Card Styling
        * Styles individual category cards with a frosted glass effect.
        */
        .category-card {
            background-color: rgba(255, 255, 255, 0.1); /* Semi-transparent white for frosted glass */
            color: var(--ghost-white); /* Text color for the card */
            padding: 25px; /* Internal padding */
            border-radius: 15px; /* Rounded corners */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Stronger shadow */
            border: 1px solid rgba(255, 255, 255, 0.1); /* Subtle white border */
            backdrop-filter: blur(8px); /* Applies the frosted glass blur effect */
            /* Smooth transitions for hover effects */
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
            text-align: center; /* Centers content within the card */
            cursor: pointer; /* Indicates clickable element */
            min-height: 120px; /* Ensures consistent minimum height for cards */
            display: flex; /* Enables flexbox for content alignment */
            align-items: center; /* Vertically centers content */
            justify-content: center; /* Horizontally centers content */
            flex-direction: column; /* Stacks content vertically */
        }

        /* Category Card Hover Effect */
        .category-card:hover {
            transform: translateY(-5px); /* Lifts the card more pronouncedly */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); /* Stronger shadow on hover */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly less transparent on hover */
        }

        /* Category Card Heading (h3) Styling */
        .category-card h3 {
            font-size: 1.5rem; /* Larger font size for category names */
            margin: 0; /* Remove default margin */
            color: var(--white-pop); /* White text color */
            font-weight: 500; /* Medium font weight */
        }

        /*
        * Search Suggestions Box Styling
        * Styles the dropdown for search suggestions.
        */
        #suggestions {
            position: absolute; /* Positioned relative to its closest positioned ancestor (body or .container) */
            background: var(--oxford-blue); /* Darker background for suggestions */
            border: 1px solid var(--delft-blue); /* Border for the box */
            width: calc(100% - 20px); /* Adjusts width for padding on small screens */
            max-width: 350px; /* Maximum width, matches search bar */
            max-height: 200px; /* Limits height, enables scrolling */
            overflow-y: auto; /* Adds scrollbar if content overflows */
            z-index: 999; /* Ensures it appears above other content */
            border-radius: 8px; /* Rounded corners */
            display: none; /* Hidden by default (controlled by JS) */
            margin-top: 5px; /* Small gap from the search bar */
            box-shadow: 0 5px 15px rgba(0,0,0,0.4); /* Stronger shadow */
            left: 50%; /* Attempts to center horizontally */
            transform: translateX(-50%); /* Fine-tunes horizontal centering */
            text-align: left; /* Aligns text to the left within suggestions */
        }

        /* Individual Suggestion Item Styling */
        #suggestions div {
            padding: 10px 15px; /* Padding for each suggestion */
            cursor: pointer; /* Indicates clickable item */
            color: var(--ghost-white); /* Text color */
            transition: background-color 0.2s ease, color 0.2s ease; /* Smooth transitions */
        }

        /* Suggestion Item Hover Effect */
        #suggestions div:hover {
            background-color: var(--sapphire); /* Highlight on hover */
            color: var(--white-pop); /* Changes text to white on hover */
        }

        /*
        * Back to Dashboard Link Styling
        */
        .back-dashboard-link {
            background-color: var(--cool-gray); /* Gray background */
            color: var(--white-pop); /* White text color */
            padding: 12px 25px; /* Consistent padding */
            text-decoration: none; /* Removes underline */
            border-radius: 10px; /* Consistent border-radius */
            font-weight: 500; /* Medium font weight */
            display: inline-block; /* Allows padding and transitions */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Shadow */
            margin-top: 40px; /* Space above the link */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transitions */
        }

        /* Back to Dashboard Link Hover Effect */
        .back-dashboard-link:hover {
            background-color: var(--paynes-gray); /* Darker gray on hover */
            transform: scale(1.02); /* Slightly scales up on hover */
        }

        /*
        * Message Styling (Success/Error)
        * For displaying temporary feedback to the user.
        */
        .success-message {
            background-color: #d4edda; /* Light green background */
            color: #155724; /* Dark green text */
            border: 1px solid #c3e6cb; /* Green border */
            padding: 10px; /* Padding inside the message box */
            margin: 20px auto; /* Centered, top/bottom margin */
            border-radius: 5px; /* Rounded corners */
            text-align: center; /* Centers the text */
            max-width: 600px; /* Constrains width */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); /* Subtle shadow */
            font-weight: 500; /* Medium font weight */
        }
        .error-message {
            background-color: #f8d7da; /* Light red background */
            color: #721c24; /* Dark red text */
            border: 1px solid #f5c6cb; /* Red border */
            padding: 10px; /* Padding inside the message box */
            margin: 20px auto; /* Centered, top/bottom margin */
            border-radius: 5px; /* Rounded corners */
            text-align: center; /* Centers the text */
            max-width: 600px; /* Constrains width */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); /* Subtle shadow */
            font-weight: 500; /* Medium font weight */
        }


        /*
        * Media Queries for Responsiveness
        * Adjusts styles for different screen sizes to ensure optimal viewing.
        */

        /* Styles for screens larger than 768px (tablets and desktops) */
        @media (min-width: 768px) {
            body {
                padding-top: 50px; /* Adjusted padding for larger screens */
                padding-bottom: 50px;
            }
            .container {
                max-width: 900px; /* Wider container for tablets/desktops */
                padding: 30px; /* More internal padding */
            }
            .browse-header {
                flex-direction: row; /* Arranges items horizontally */
                justify-content: space-between; /* Spreads items */
                padding-top: 0; /* No need for top padding if top-bar positioning is handled */
            }
            .browse-header h2 {
                font-size: 3rem; /* Larger main title */
            }
            .browse-header > div {
                width: auto; /* Allow search elements to take natural width */
                justify-content: flex-end; /* Aligns search elements to the right */
            }
            #searchBar {
                margin-top: 0; /* No top margin needed on larger screens */
            }
            .categories {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Three columns layout */
                gap: 30px; /* Increased gap */
            }
            .category-card {
                padding: 30px; /* More padding for cards */
                border-radius: 18px; /* More rounded corners */
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25); /* Stronger shadow */
                min-height: 140px; /* Increased min-height */
            }
            .category-card h3 {
                font-size: 1.7rem; /* Larger category title font size */
            }
            .back-dashboard-link {
                margin-top: 50px; /* More margin */
                padding: 15px 30px; /* Larger padding */
                font-size: 1.1rem; /* Larger font size */
            }
            #suggestions {
                width: inherit; /* Inherit width from search bar on larger screens */
                left: unset; /* Remove left positioning */
                transform: unset; /* Remove transform centering */
                right: 0; /* Align suggestions box to the right of the search bar */
            }
        }

        /* Styles for screens larger than 1024px (larger desktops) */
        @media (min-width: 1024px) {
            body {
                padding-top: 60px;
                padding-bottom: 60px;
            }
            .container {
                max-width: 1000px; /* Even wider container */
                padding: 40px; /* More internal padding */
            }
            .browse-header h2 {
                font-size: 3.5rem; /* Even larger main title */
            }
            #searchBar {
                max-width: 400px; /* Wider search bar */
            }
            .categories {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Potentially wider cards */
                gap: 35px; /* Increased gap */
            }
            .category-card {
                padding: 35px; /* More padding */
                border-radius: 20px; /* Even more rounded corners */
                min-height: 160px; /* Increased min-height */
            }
            .category-card h3 {
                font-size: 2rem; /* Even larger category titles */
            }
            .back-dashboard-link {
                margin-top: 60px;
                padding: 18px 35px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>

<div class="top-bar">
    QuickBuy
    <a href="viewCart.php" class="cart-link">🛒 View Cart</a>
</div>

<div class="container">
    <div class="browse-header">
        <h2>Browse Categories</h2>
        <div>
            <button id="searchButton">🔍 Search</button>
            <input type="text" id="searchBar" placeholder="Search for categories...">
            <div id="suggestions"></div>
        </div>
    </div>

    <?php
    // Display success message if it exists in the session.
    // htmlspecialchars() is used for security to prevent XSS.
    if (isset($_SESSION['success_message'])) {
        echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']); // Clear the message after displaying it once.
    }

    // Display error message if it exists (e.g., from a previous transaction rollback).
    if (isset($_SESSION['error_message'])) {
        echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']); // Clear the message after displaying it once.
    }
    ?>

    <div class="categories" id="categoryList">
        <?php
        // Check if there are any categories to display.
        if (!empty($productsByCategory)):
            // Loop through each fetched category and create a clickable card for it.
            foreach ($productsByCategory as $category):
        ?>
                <div class="category-card" onclick="window.location.href='CategoryItems.php?category=<?php echo urlencode($category); ?>'">
                    <h3><?php echo htmlspecialchars($category); ?></h3>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No categories available.</p>
        <?php endif; ?>
    </div>
</div>

<div style="text-align: center;">
    <a href="BuyersDashBoard.php" class="back-dashboard-link">⬅ Back to Dashboard</a>
</div>

<script>
    // Get references to DOM elements
    const searchButton = document.getElementById('searchButton');
    const searchBar = document.getElementById('searchBar');
    const suggestionsBox = document.getElementById('suggestions');
    // const categoryCards = document.querySelectorAll('.category-card'); // This variable is declared but not used in the provided JS logic.

    // Array containing all available categories. This data is populated dynamically from PHP.
    // addslashes() is used in PHP to properly escape quotes for JavaScript string literals.
    const allCategories = [<?php
        $categoryStrings = array_map(function($cat) { return "'" . addslashes($cat) . "'"; }, $productsByCategory);
        echo implode(', ', $categoryStrings);
    ?>];

    // Event listener for the search button click.
    searchButton.addEventListener('click', () => {
        // Toggle search bar visibility: if hidden, show it; if visible, perform search or hide.
        if (searchBar.style.display === 'none' || searchBar.style.display === '') {
            searchBar.style.display = 'block'; // Show the search bar
            searchBar.focus(); // Focus on the search bar for immediate typing
            // Position the suggestions box correctly relative to the search bar.
            positionSuggestionsBox();
        } else {
            const searchTerm = searchBar.value.trim(); // Get trimmed search term
            if (searchTerm !== '') {
                // If a search term exists, redirect to CategoryItems.php with the search query.
                window.location.href = `CategoryItems.php?search=${encodeURIComponent(searchTerm)}`;
            } else {
                // If search bar is shown but empty, hide it and the suggestions.
                searchBar.style.display = 'none';
                suggestionsBox.style.display = 'none';
            }
        }
    });

    // Event listener for input changes in the search bar (for live suggestions).
    searchBar.addEventListener('input', () => {
        const query = searchBar.value.toLowerCase(); // Get lowercase search query
        suggestionsBox.innerHTML = ''; // Clear previous suggestions
        suggestionsBox.style.display = 'none'; // Hide suggestions by default

        // Only show suggestions if query has content.
        if (query.length > 0) {
            // Filter categories that include the search query.
            const filteredCategories = allCategories.filter(category =>
                category.toLowerCase().includes(query)
            );

            // If matching categories are found, display them.
            if (filteredCategories.length > 0) {
                filteredCategories.forEach(category => {
                    const div = document.createElement('div'); // Create a new div for each suggestion
                    div.textContent = category; // Set the category name as text
                    // On click, navigate to CategoryItems.php with the selected category.
                    div.addEventListener('click', () => {
                        window.location.href = `CategoryItems.php?category=${encodeURIComponent(category)}`;
                    });
                    suggestionsBox.appendChild(div); // Add suggestion to the suggestions box
                });
                suggestionsBox.style.display = 'block'; // Show the suggestions box
                positionSuggestionsBox(); // Re-position for accuracy
            }
        }
    });

    // Event listener for 'Enter' key press in the search bar.
    searchBar.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            const searchTerm = this.value.trim(); // Get trimmed search term
            if (searchTerm !== '') {
                // If a search term exists, redirect to CategoryItems.php with the search query.
                window.location.href = `CategoryItems.php?search=${encodeURIComponent(searchTerm)}`;
            }
        }
    });

    // Hide suggestions when clicking anywhere outside the search bar, search button, or suggestions box.
    document.addEventListener('click', (event) => {
        if (!searchBar.contains(event.target) && !searchButton.contains(event.target) && !suggestionsBox.contains(event.target)) {
            suggestionsBox.style.display = 'none'; // Hide suggestions
        }
    });

    // Function to accurately position the suggestions box directly below the search bar.
    function positionSuggestionsBox() {
        const searchBarRect = searchBar.getBoundingClientRect(); // Get dimensions and position of search bar
        suggestionsBox.style.width = searchBarRect.width + 'px'; // Match width of search bar
        // Position below search bar, accounting for scroll. Add 5px gap.
        suggestionsBox.style.top = (searchBarRect.bottom + window.scrollY + 5) + 'px';
        suggestionsBox.style.left = (searchBarRect.left + window.scrollX) + 'px'; // Align left edge
        // Special positioning for small screens to ensure centering.
        if (window.innerWidth < 768) {
            suggestionsBox.style.left = '50%';
            suggestionsBox.style.transform = 'translateX(-50%)';
        } else {
            suggestionsBox.style.left = (searchBarRect.left + window.scrollX) + 'px';
            suggestionsBox.style.transform = 'unset'; // Remove transform if not needed
        }
    }

    // Re-position suggestions box on window resize if it's visible.
    window.addEventListener('resize', () => {
        if (suggestionsBox.style.display === 'block') {
            positionSuggestionsBox();
        }
    });
</script>
</body>
</html>