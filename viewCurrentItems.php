<?php

/**
 * @brief This page displays items listed by the currently logged-in seller.
 *
 * It allows sellers to view, edit, and delete their listed products.
 * The products are grouped by category for easier navigation.
 */

// Start the session at the very beginning of the file to ensure session variables are available.
session_start();

// Include the database connection file. 

include 'db.php';

// Check if the user is logged in.
// If the 'user_id' session variable is not set, the user is not authenticated.
if (!isset($_SESSION['user_id'])) {
    // Redirect the unauthenticated user to the login page.
    header("Location: LoginPage.php");
    // Terminate script execution after redirection to prevent further code execution.
    exit;
}

// Get the seller ID from the session. This ID is used to fetch products
// specifically listed by the current seller.
$seller_id = $_SESSION['user_id'];

// SQL query to fetch all products listed by the current seller.
// The results are ordered by category for structured display.
$sql = "SELECT * FROM products WHERE seller_id = ? ORDER BY category";

// Prepare the SQL statement to prevent SQL injection.
$stmt = $conn->prepare($sql);

// Check if the statement preparation failed.
if (!$stmt) {
    // Display an error message if preparation fails and exit.
    echo "error: Prepare failed: " . $conn->error;
    exit;
}

// Bind the seller ID parameter to the prepared statement.
// "i" specifies that the parameter is an integer.
$stmt->bind_param("i", $seller_id);

// Execute the prepared statement.
$stmt->execute();

// Get the result set from the executed statement.
$result = $stmt->get_result();

// Check if the database query failed to return a result.
if (!$result) {
    // Display an error message if the query fails and exit.
    echo "error:Database query failed: " . $conn->error;
    exit;
}

// Initialize an empty array to store items grouped by their category.
$groupedItems = [];

// Loop through each row in the result set.
while ($row = $result->fetch_assoc()) {
    // Group items by their 'category' key. Each category will contain an array of its items.
    $groupedItems[$row['category']][] = $row;
}

// Close the prepared statement to free up resources.
$stmt->close();

// Close the database connection after all data has been fetched.
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Listed Items</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /*
         * @brief CSS styles for the Your Listed Items page.
         *
         * This section defines the visual presentation of the page, including
         * color variables, universal box-sizing, background, typography,
         * and responsive adjustments for various screen sizes.
         */

        /* Root CSS variables for consistent color palette. */
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
            --midnight-green: #065a60ff;      /* Your desired lighter green */
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

        /* Universal Box-Sizing for consistent layout calculation. */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Base styles for html and body to ensure full page height and prevent horizontal overflow. */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden; /* Prevents horizontal scroll */
        }

        /* Body styling: background gradient, font, color, and layout. */
        body {
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Ensures the gradient covers enough area for animation */
            animation: bgShift 25s ease infinite; /* Animates the background position */
            font-family: 'Poppins', sans-serif;
            color: var(--ghost-white);
            display: flex;
            flex-direction: column;
            align-items: center; /* Centers content horizontally */
            padding: 15px; /* Adds padding around the content */
        }

        /* Keyframe animation for the background gradient shift. */
        @keyframes bgShift {
            0% { background-position: 0% 50%; } /* Starting background position */
            50% { background-position: 100% 50%; } /* Middle background position */
            100% { background-position: 0% 50%; } /* Ending background position (loops back) */
        }

        /* Container for main page content, limits width and centers it. */
        .container {
            width: 100%;
            max-width: 900px; /* Maximum width of the container */
            margin: 0 auto; /* Centers the container */
            padding: 20px 0; /* Vertical padding */
        }

        /* Styling for the main heading. */
        h2 {
            color: var(--white-pop);
            text-align: center;
            margin-bottom: 25px;
            font-size: 2.2em;
        }

        /* Styles for the "Back to Dashboard" button. */
        .back-btn {
            background-color: var(--midnight-green); /* Changed to green */
            color: var(--ghost-white);
            padding: 10px 20px;
            margin-bottom: 20px;
            display: inline-block; /* Allows padding and specific sizing */
            border-radius: 8px;
            text-decoration: none; /* Removes underline from link */
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s; /* Smooth transitions for hover effects */
        }
        /* Hover effect for the back button. */
        .back-btn:hover {
            background-color: var(--caribbean-current); /* Darker green on hover */
            transform: scale(1.02); /* Slightly enlarges the button on hover */
            color: var(--ghost-white);
        }

        /* Styling for sections grouping items by category. */
        .category-section {
            background-color: rgba(27, 42, 75, 0.4); /* Semi-transparent background */
            border: 1px solid rgba(255, 255, 255, 0.05); /* Subtle border */
            backdrop-filter: blur(12px); /* Blurs content behind the element */
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3); /* Soft shadow for depth */
        }
        /* Styling for category titles within sections. */
        .category-section h3 {
            font-size: 1.6em;
            color: var(--white-pop);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); /* Separator line */
        }
        /* Styling for individual item cards. */
        .item-card {
            background: rgba(255, 255, 255, 0.1); /* Semi-transparent white background */
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            display: flex; /* Uses flexbox for internal layout */
            flex-direction: column; /* Stacks items vertically on small screens */
            gap: 10px; /* Space between flex items */
            align-items: flex-start; /* Aligns items to the start of the cross axis */
            color: var(--ghost-white);
            transition: transform 0.2s ease, background-color 0.3s ease; /* Smooth transitions */
        }
        /* Hover effect for item cards. */
        .item-card:hover {
            transform: translateY(-3px); /* Lifts the card slightly */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly less transparent */
        }

        /* Styling for item images within cards. */
        .item-card img {
            max-width: 120px;
            height: auto;
            border-radius: 6px;
            margin-bottom: 10px;
            object-fit: cover; /* Ensures image covers the area without distortion */
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        /* Styling for strong text within item cards (e.g., item name). */
        .item-card strong {
            font-size: 1.1em;
            color: var(--white-pop);
        }
        /* Styling for paragraph text within item cards. */
        .item-card p {
            font-size: 0.95em;
            margin: 0;
            color: var(--cool-gray);
        }
        /* Styling for strong text within paragraphs (e.g., labels). */
        .item-card p strong {
            color: var(--ghost-white);
        }

        /* Styling for the action buttons container within item cards. */
        .item-card .actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap; /* Allows buttons to wrap to next line on small screens */
        }

        /* General button styles. */
        .btn {
            padding: 10px 15px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95em;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s;
            color: var(--ghost-white);
        }
        /* Specific styling for the edit button. */
        .edit-btn {
            background-color: var(--yale-blue);
        }
        /* Hover effect for the edit button. */
        .edit-btn:hover {
            background-color: var(--true-blue);
            transform: scale(1.02);
        }
        /* Specific styling for the delete button. */
        .delete-btn {
            background-color: var(--charcoal);
        }
        /* Hover effect for the delete button. */
        .delete-btn:hover {
            background-color: var(--gunmetal);
            transform: scale(1.02);
        }

        /* Styles for the modal (popup) container. */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stays in place even when scrolling */
            z-index: 1000; /* Stays on top of other content */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Enables scrolling if content is too large */
            background-color: rgba(0,0,0,0.6); /* Semi-transparent black background */
            align-items: center; /* Centers modal content vertically */
            justify-content: center; /* Centers modal content horizontally */
            padding: 15px;
        }
        /* Styles for the modal's content box. */
        .modal-content {
            background-color: var(--prussian-blue);
            margin: auto; /* Centers the content */
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.5);
            max-width: 500px; /* Maximum width for the modal */
            color: var(--ghost-white);
            position: relative; /* Needed for positioning the close button */
        }
        /* Styling for modal headings. */
        .modal-content h3 {
            color: var(--white-pop);
            margin-bottom: 20px;
            font-size: 1.8em;
            text-align: center;
        }

        /* Styling for labels within the edit form. */
        #editForm label {
            display: block; /* Each label on its own line */
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.95em;
            color: var(--cool-gray);
            text-align: left;
        }
        /* Styling for text inputs, textareas, number inputs, and file inputs within the edit form. */
        #editForm input[type="text"],
        #editForm textarea,
        #editForm input[type="number"],
        #editForm input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.08); /* Semi-transparent background */
            border-radius: 10px;
            color: var(--ghost-white);
            font-size: 1em;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }
        /* Focus styles for form inputs and textareas. */
        #editForm input:focus, #editForm textarea:focus {
            outline: none; /* Removes default outline */
            border-color: var(--true-blue); /* Highlights border on focus */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly changes background on focus */
        }
        /* Allows vertical resizing of textareas. */
        #editForm textarea {
            resize: vertical;
        }
        /* Styling for small text (e.g., hints) within the edit form. */
        #editForm small {
            display: block;
            margin-top: -10px;
            margin-bottom: 15px;
            color: var(--cool-gray);
            font-size: 0.8em;
            text-align: left;
        }
        /* Styling for the submit button within the edit form. */
        #editForm button[type="submit"] {
            background: var(--midnight-green); /* Changed to green */
            color: var(--white-pop);
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: background-color 0.3s ease, transform 0.2s;
        }
        /* Hover effect for the submit button. */
        #editForm button[type="submit"]:hover {
            background: var(--caribbean-current); /* Darker green on hover */
            transform: scale(1.01);
        }

        /* Styling for the modal close button. */
        .close {
            color: var(--cool-gray);
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5em;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        /* Hover and focus effects for the close button. */
        .close:hover,
        .close:focus {
            color: var(--white-pop);
            text-decoration: none;
        }

        /* General styling for message (success/error) containers. */
        .message {
            padding: 12px;
            margin: 15px auto;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        /* Specific styles for success messages. */
        .success { background: #d4edda; color: #155724; }
        /* Specific styles for error messages. */
        .error { background: #f8d7da; color: #721c24; }

        /* Styling for the category filter dropdown. */
        #categoryFilter {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.08);
            width: 100%;
            color: var(--ghost-white);
            font-size: 1em;
            appearance: none; /* Removes default dropdown arrow */
            /* Custom arrow using SVG background image. */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23fafaff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center; /* Original position for mobile */
            background-size: 1em;
        }
        /* Styling for options within the category filter dropdown. */
        #categoryFilter option {
            background-color: var(--prussian-blue);
            color: var(--white-pop);
        }
        /* Focus style for the category filter. */
        #categoryFilter:focus {
            outline: none;
            border-color: var(--true-blue);
            background-color: rgba(255, 255, 255, 0.15);
        }
        /* Styling for the label associated with the category filter. */
        #categoryFilter + label {
            display: block;
            text-align: center;
            margin-bottom: 10px;
            font-size: 1.1em;
            color: var(--white-pop);
        }

        /* Current image preview in modal */
        .current-image-preview {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        .current-image-preview p {
            color: var(--cool-gray);
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        .current-image-preview img {
            max-width: 100px;
            height: auto;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .current-image-preview input[type="checkbox"] {
            margin-right: 5px;
            vertical-align: middle;
            transform: scale(1.2);
        }
        .current-image-preview label {
            display: inline-block;
            margin-left: 5px;
            font-weight: normal;
            color: var(--ghost-white);
            font-size: 0.9em;
        }

        /* No items found message */
        body > p {
            color: var(--cool-gray);
            text-align: center;
            font-size: 1.1em;
            margin-top: 30px;
        }


        /* --- Media Queries for various devices --- */

        /* Tablets and smaller Laptops */
        @media (min-width: 768px) {
            body {
                padding: 30px;
            }
            h2 {
                font-size: 2.8em;
            }
            .back-btn {
                padding: 12px 25px;
                font-size: 1.1em;
            }
            .category-section {
                padding: 30px;
            }
            .category-section h3 {
                font-size: 1.8em;
            }
            .item-card {
                flex-direction: row; /* Arranges items horizontally */
                align-items: center;
                gap: 20px;
                padding: 20px;
            }
            .item-card img {
                max-width: 100px;
                margin-bottom: 0;
            }
            .item-card > div:not(.actions), .item-card strong, .item-card p {
                flex-basis: auto;
                flex-grow: 1; /* Allows text content to take available space */
                text-align: left;
            }
            .item-card strong {
                flex-shrink: 0; /* Prevents item name from shrinking */
                min-width: 150px;
            }
            .item-card .actions {
                flex-shrink: 0; /* Prevents action buttons from shrinking */
                margin-top: 0;
                justify-content: flex-end; /* Aligns buttons to the right */
            }
            .btn {
                padding: 10px 18px;
                font-size: 1em;
            }
            .modal-content {
                padding: 35px;
            }
            #editForm input[type="text"],
            #editForm textarea,
            #editForm input[type="number"],
            #editForm input[type="file"] {
                padding: 14px 18px;
                font-size: 1.05em;
            }
            #editForm button[type="submit"] {
                padding: 18px 30px;
                font-size: 1.2em;
            }
            #categoryFilter {
                width: auto; /* Allows filter to take natural width */
                display: inline-block;
                margin-left: 10px;
                padding-right: 35px;
                background-position: right 10px center;
            }
            #categoryFilter + label {
                display: inline-block;
                text-align: left;
                margin-bottom: 20px;
            }
            .message {
                margin: 20px auto;
            }
        }

        /* Desktops / Laptops */
        @media (min-width: 768px) { /* This media query condition is identical to the one above. Consider adjusting if different styles are intended for larger desktops. */
            body {
                padding: 40px;
            }
            h2 {
                font-size: 3.2em;
                margin-bottom: 40px;
            }
            .back-btn {
                padding: 15px 30px;
                font-size: 1.2em;
            }
            .category-section {
                padding: 40px;
            }
            .category-section h3 {
                font-size: 2.2em;
            }
            .item-card {
                padding: 25px;
            }
            .item-card img {
                max-width: 150px;
            }
            .btn {
                padding: 12px 20px;
                font-size: 1.05em;
            }
            .modal-content {
                max-width: 600px;
            }
            #categoryFilter {
                padding-right: 35px;
                background-position: right 10px center;
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
    <a href="SellersDashBoard.php" class="btn back-btn">← Back to Dashboard</a>

    <h2>Your Listed Items</h2>

    <label for="categoryFilter"><strong>Filter by Category:</strong></label>
    <select id="categoryFilter" onchange="filterByCategory()">
        <option value="all">Show All</option>
        <?php
        // Populate category filter options from the fetched grouped items.
        if (!empty($groupedItems)):
            foreach (array_keys($groupedItems) as $category): ?>
                <option value="<?= htmlspecialchars($category ?? '') ?>"><?= htmlspecialchars($category ?? '') ?></option>
            <?php
            endforeach;
        endif; ?>
    </select>

    <div id="messageContainer"></div>

    <?php
    // Display a message if no items are found for the seller.
    if (empty($groupedItems)) {
        echo "<p>No items found. Start by adding new items from your dashboard!</p>";
    }
    ?>

    <?php
    // Loop through each category and display items within it.
    if (!empty($groupedItems)):
        foreach ($groupedItems as $category => $items): ?>
            <div class="category-section" data-category="<?= htmlspecialchars($category ?? '') ?>">
                <h3><?= htmlspecialchars($category ?? '') ?></h3>
                <?php
                // Loop through each item in the current category.
                foreach ($items as $row): ?>
                    <div class="item-card">
                        <?php
                        // Display item picture if available.
                        if (!empty($row['item_picture'])): ?>
                            <img src="<?= htmlspecialchars($row['item_picture']) ?>" alt="<?= htmlspecialchars($row['altText'] ?? $row['itemName'] ?? 'Item Image') ?>">
                        <?php endif; ?>
                        <div>
                            <strong><?= htmlspecialchars($row['itemName'] ?? '') ?></strong>
                            <p><strong>Description:</strong> <?= htmlspecialchars($row['itemDescription'] ?? '') ?></p>
                            <p><strong>Price:</strong> R<?= htmlspecialchars(number_format((float)$row['price'], 2)) ?></p>
                            <p><strong>Quantity:</strong> <?= htmlspecialchars($row['quantity'] ?? '') ?></p>
                            <p><strong>Date Added:</strong> <?= htmlspecialchars($row['dateAdded'] ?? '') ?></p>
                        </div>
                        <div class="actions">
                            <button class="btn edit-btn" onclick='openEditModal(<?= json_encode(array_map(fn($v) => $v ?? '', $row)) ?>)'>Edit</button>
                            <button class="btn delete-btn" onclick="deleteItem(<?= $row['id'] ?>)">Delete</button>
                        </div>
                    </div>
                <?php
                endforeach; ?>
            </div>
        <?php
        endforeach;
    endif; ?>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Item</h3>
        <form id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="id" id="itemId">
            <label for="itemNameModal">Item Name:</label>
            <input type="text" name="itemName" id="itemNameModal" required>
            <label for="itemDescriptionModal">Description:</label>
            <textarea name="itemDescription" id="itemDescriptionModal" rows="3" required></textarea>
            <label for="itemPrice">Price:</label>
            <input type="number" name="price" id="itemPrice" step="0.01" required>
            <label for="itemQuantity">Quantity:</label>
            <input type="number" name="quantity" id="itemQuantity" required>

            <label for="itemPicture">Item Picture:</label>
            <div id="currentImagePreview" class="current-image-preview" style="display:none;">
                <p>Current Image:</p>
                <img id="modalCurrentImage" src="" alt="Current Item Image">
                <input type="checkbox" id="removePictureCheckbox" name="remove_picture" value="yes">
                <label for="removePictureCheckbox">Remove Current Picture</label>
            </div>
            <input type="file" name="item_picture" id="itemPicture" accept="image/*">
            <small>Leave blank to keep current image. Max file size: 2MB. Allowed types: JPG, JPEG, PNG, GIF, WebP.</small>

            <button type="submit" class="btn edit-btn">Save Changes</button>
        </form>
    </div>
</div>

<script>
    /**
     * @brief JavaScript functions for interactive elements on the page.
     *
     * This script handles modal operations for editing items,
     * form submission via Fetch API, item deletion, and category filtering.
     */

    /**
     * Opens the edit modal and populates it with the selected item's data.
     * @param {object} item - The item object containing details like id, name, description, price, quantity, and item_picture.
     */
    function openEditModal(item) {
        // Populate form fields with item data.
        document.getElementById('itemId').value = item.id;
        document.getElementById('itemNameModal').value = item.itemName;
        document.getElementById('itemDescriptionModal').value = item.itemDescription;
        document.getElementById('itemPrice').value = item.price;
        document.getElementById('itemQuantity').value = item.quantity;

        const currentImagePreviewDiv = document.getElementById('currentImagePreview');
        const modalCurrentImage = document.getElementById('modalCurrentImage');
        const removePictureCheckbox = document.getElementById('removePictureCheckbox');

        // Display current image preview if an image exists.
        if (item.item_picture && item.item_picture !== 'NULL') {
            modalCurrentImage.src = item.item_picture;
            modalCurrentImage.alt = item.itemName + ' image';
            currentImagePreviewDiv.style.display = 'block';
            removePictureCheckbox.checked = false; // Ensure checkbox is unchecked by default
        } else {
            // Hide preview if no image.
            currentImagePreviewDiv.style.display = 'none';
            modalCurrentImage.src = '';
            modalCurrentImage.alt = '';
            removePictureCheckbox.checked = false;
        }

        // Clear the file input when opening the modal.
        document.getElementById('itemPicture').value = '';
        // Display the modal.
        document.getElementById('editModal').style.display = 'flex';
    }

    /**
     * Closes the edit modal.
     */
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    /**
     * Displays a temporary message (success or error) on the page.
     * @param {string} text - The message content.
     * @param {string} type - The type of message ('success' or 'error').
     */
    function showMessage(text, type = 'success') {
        const msg = document.createElement('div');
        msg.className = 'message ' + type; // Adds 'message' and type class
        msg.innerText = text;
        document.getElementById('messageContainer').appendChild(msg);
        // Remove the message after 4 seconds.
        setTimeout(() => msg.remove(), 4000);
    }

    /**
     * Handles the submission of the edit form.
     * Performs client-side validation and sends data via Fetch API.
     */
    document.getElementById('editForm').onsubmit = function(e) {
        e.preventDefault(); // Prevent default form submission.
        const formData = new FormData(this); // Collect form data, including files.

        const fileInput = document.getElementById('itemPicture');
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            const maxSize = 2 * 1024 * 1024; // 2MB in bytes

            // Validate image type.
            if (!validTypes.includes(file.type)) {
                showMessage('Only JPEG, PNG, GIF, or WebP images are allowed for upload.', 'error');
                return; // Stop form submission.
            }
            // Validate image size.
            if (file.size > maxSize) {
                showMessage('Image size must be under 2MB.', 'error');
                return; // Stop form submission.
            }
        }

        // Send form data to editItem.php using Fetch API.
        fetch('editItem.php?id=' + formData.get('id'), {
            method: 'POST',
            body: formData // FormData object handles multipart/form-data.
        })
        .then(res => res.text()) // Get response as plain text.
        .then(response => {
            // Check response prefix for success or error.
            if (response.startsWith("success:")) {
                showMessage(response.substring(8), 'success');
                closeEditModal();
                // Reload page after a short delay to reflect changes.
                setTimeout(() => location.reload(), 1500);
            } else if (response.startsWith("error:")) {
                showMessage(response.substring(6), 'error');
            } else {
                showMessage("An unexpected response occurred: " + response, 'error');
            }
        })
        .catch(error => {
            // Log and display network/server errors.
            console.error('Fetch error:', error);
            showMessage("Network error or server unreachable.", 'error');
        });
    };

    /**
     * Handles the deletion of an item.
     * Prompts for confirmation before sending a delete request.
     * @param {number} id - The ID of the item to be deleted.
     */
    function deleteItem(id) {
        // Confirm deletion with the user.
        if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            // Send delete request to deleteItem.php via Fetch API.
            fetch('deleteItem.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // Specify content type for POST request.
                body: 'id=' + id // Send item ID in the request body.
            }).then(res => res.text())
                .then(response => {
                    if (response.startsWith("success")) {
                        showMessage(response, 'success');
                        // Reload page after a short delay to reflect changes.
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(response, 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showMessage("Network error or server unreachable.", 'error');
                });
        }
    }

    /**
     * Filters displayed item categories based on the selected value in the dropdown.
     * Hides or shows category sections accordingly.
     */
    function filterByCategory() {
        const selected = document.getElementById('categoryFilter').value.toLowerCase();
        const sections = document.querySelectorAll('.category-section'); // Get all category sections.
        sections.forEach(section => {
            const category = section.dataset.category ? section.dataset.category.toLowerCase() : '';
            // Display section if 'all' is selected or if category matches.
            if (selected === 'all' || category === selected) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none'; // Hide section otherwise.
            }
        });
    }
</script>

</body>
</html>
