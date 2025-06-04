<?php
session_start(); // Start the PHP session to manage user data
require_once 'db.php'; // Include the database connection file

// Check if the user is logged in and has the 'buyer' role
// If not, redirect them to the LoginPage.php and exit the script
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: LoginPage.php");
    exit();
}

$result = null; // Initialize $result to null; it will hold the database query result
$categoryTitle = ''; // Initialize $categoryTitle to an empty string for the page title
$stockError = $_SESSION['stock_error'] ?? null; // Retrieve stock error message from session, if any
unset($_SESSION['stock_error']); // Clear the stock error message from the session after retrieval

// Check if a search query is present in the URL
if (isset($_GET['search'])) {
    $search = $_GET['search']; // Get the search term
    // Prepare a SQL statement to select products where the itemName matches the search term
    // MODIFIED: Added `AND status = 'approved'` to only show approved products
    $stmt = $conn->prepare("SELECT * FROM products WHERE itemName LIKE ? AND status = 'approved'");
    $searchParam = "%" . $search . "%"; // Add wildcards for partial matching
    $stmt->bind_param("s", $searchParam); // Bind the search parameter as a string
    $stmt->execute(); // Execute the prepared statement
    $result = $stmt->get_result(); // Get the result set
    $categoryTitle = "Search Results for \"" . htmlspecialchars($search) . "\""; // Set the page title for search results
} elseif (isset($_GET['category'])) { // Check if a category is present in the URL
    $category = $_GET['category']; // Get the category name
    // Prepare a SQL statement to select products where the category matches
    // MODIFIED: Added `AND status = 'approved'` to only show approved products
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND status = 'approved'");
    $stmt->bind_param("s", $category); // Bind the category parameter as a string
    $stmt->execute(); // Execute the prepared statement
    $result = $stmt->get_result(); // Get the result set
    $categoryTitle = "Items in \"" . htmlspecialchars($category) . "\""; // Set the page title for category items
} else {
    // If no category or search term is provided, show all approved items
    // MODIFIED: Added `WHERE status = 'approved'` to ensure only approved products are displayed
    $stmt = $conn->prepare("SELECT * FROM products WHERE status = 'approved'");
    $stmt->execute(); // Execute the prepared statement
    $result = $stmt->get_result(); // Get the result set
    $categoryTitle = "All Items"; // Set the page title to "All Items"
}
$stmt->close(); // Close the prepared statement

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $categoryTitle; ?> - QuickBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables for consistent theming */
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

            /* Red for danger/logout */
            --danger-red: #dc3545;
            --danger-red-hover: #bd2130;

            /* Additional colors for this page */
            --price-color: #ffe066; /* A soft yellow for prices */
            --stock-positive: #4CAF50; /* Green for in stock */
            --stock-negative: var(--danger-red); /* Red for out of stock */
            --add-to-cart-bg: var(--caribbean-current);
            --add-to-cart-hover: var(--midnight-green);

            /* New for confirmation message */
            --success-green: #28a745;
        }

        /* Universal Box-Sizing for consistent layout */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Body and HTML styles */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        body {
            /* Gradient background with animation */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Larger background to allow for animation */
            animation: bgShift 25s ease infinite; /* Background animation */
            font-family: 'Poppins', sans-serif; /* Global font */
            color: var(--ghost-white); /* Default text color */
            display: flex;
            flex-direction: column;
            align-items: center; /* Center content horizontally */
            justify-content: flex-start; /* Align content to the top */
            padding-top: 30px;
            padding-bottom: 30px;
            overflow-y: auto; /* Allow vertical scrolling */
        }

        /* Keyframe animation for background gradient */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Container for content */
        .container {
            max-width: 95%; /* Responsive max width */
            width: 100%;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
            /* No background for container, items will have frosted glass */
        }

        /* Main heading style */
        h2 {
            font-size: 2.5rem;
            color: var(--white-pop);
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        /* Back link styling */
        .back-link {
            display: inline-block;
            margin-bottom: 25px; /* Spacing below the link */
            text-decoration: none;
            background-color: var(--cool-gray);
            color: var(--white-pop);
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            align-self: flex-start; /* Align to the start of the container */
            margin-left: 2.5%; /* Match container padding for alignment */
            margin-right: auto; /* Push it to the left */
        }

        .back-link:hover {
            background-color: var(--cool-gray); /* Keep same color on hover for subtlety */
            transform: scale(1.02); /* Slight scale effect on hover */
        }

        /* Alert messages (general) */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-size: 1rem;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.1); /* Frosted glass effect */
            backdrop-filter: blur(8px); /* Blur effect */
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Specific alert styles for danger messages */
        .alert-danger {
            color: var(--danger-red);
            border-color: var(--danger-red-hover);
        }

        /* New styles for success message */
        .alert-success {
            color: var(--success-green);
            border-color: var(--success-green);
        }

        /* Search box layout */
        .search-box {
            margin-bottom: 30px;
            position: relative; /* For positioning suggestions (if implemented) */
            display: flex;
            justify-content: center; /* Center the search input */
            width: 100%;
        }

        /* Search input field styling */
        #itemSearch {
            padding: 12px 20px;
            width: 100%;
            max-width: 400px; /* Limit width */
            font-size: 1rem;
            border-radius: 10px;
            border: 1px solid var(--delft-blue);
            background-color: rgba(255, 255, 255, 0.1); /* Frosted background */
            color: var(--ghost-white);
            box-sizing: border-box; /* Include padding in element's total width and height */
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.2); /* Inner shadow */
            transition: all 0.3s ease; /* Smooth transitions */
        }

        #itemSearch::placeholder {
            color: var(--cool-gray); /* Placeholder text color */
        }

        #itemSearch:focus {
            outline: none; /* Remove default outline */
            border-color: var(--true-blue); /* Highlight border on focus */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly less transparent on focus */
        }

        /* Grid layout for item cards */
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Responsive grid columns */
            gap: 25px; /* Spacing between grid items */
            justify-content: center; /* Center items in the grid */
        }

        /* Individual item card styling */
        .item-card {
            background-color: rgba(255, 255, 255, 0.1); /* Frosted glass effect */
            color: var(--ghost-white); /* Text color for card */
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Soft shadow */
            border: 1px solid rgba(255, 255, 255, 0.1); /* Subtle border */
            backdrop-filter: blur(8px); /* Blur effect */
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease; /* Smooth transitions */
            display: flex;
            flex-direction: column;
            align-items: center; /* Center content horizontally */
            text-align: center;
            min-height: 380px; /* Ensure consistent card height */
        }

        .item-card:hover {
            transform: translateY(-5px); /* Lift effect on hover */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); /* Larger shadow on hover */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly less transparent on hover */
        }

        /* Item image styling */
        .item-card img {
            max-width: 100%;
            height: 180px; /* Slightly adjusted height */
            object-fit: cover; /* Cover the area, cropping if necessary */
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Item name heading */
        .item-card h3 {
            margin-top: 0;
            font-size: 1.4rem;
            color: var(--white-pop);
            margin-bottom: 8px;
            font-weight: 500;
        }

        /* Item description paragraph */
        .item-card p {
            margin: 5px 0;
            font-size: 0.95rem;
            color: var(--cool-gray);
            flex-grow: 1; /* Allow description to take up available space */
            line-height: 1.4;
        }

        /* Styling for strong/bold text within item card paragraphs */
        .item-card strong {
            color: var(--light-font); /* Highlight bold text */
        }

        /* Price styling */
        .item-card .price {
            font-size: 1.2rem;
            color: var(--price-color); /* Yellow for price */
            font-weight: 600;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        /* Form for adding to cart */
        .item-card form {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            align-items: center; /* Center form elements */
            width: 100%;
        }

        /* Label for quantity input */
        .item-card label {
            font-size: 0.9rem;
            color: var(--cool-gray);
            margin-bottom: 8px;
        }

        /* Quantity input field */
        .item-card input[type="number"] {
            width: 80px; /* Wider input */
            padding: 8px;
            border-radius: 8px;
            border: 1px solid var(--delft-blue);
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--ghost-white);
            font-size: 0.95rem;
            text-align: center;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .item-card input[type="number"]:focus {
            outline: none;
            border-color: var(--true-blue);
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Add to Cart button */
        .item-card button {
            background-color: var(--add-to-cart-bg); /* Green for add to cart */
            color: var(--white-pop);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 15px;
            font-weight: 500;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .item-card button:hover:not(:disabled) {
            background-color: var(--add-to-cart-hover);
            transform: translateY(-2px); /* Slight lift on hover */
        }

        .item-card button:disabled {
            background-color: var(--paynes-gray); /* Grey out disabled button */
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: none;
        }

        /* Stock information display */
        .stock-info {
            font-size: 0.85rem;
            color: var(--stock-positive); /* Green for in-stock */
            margin-top: 5px;
            font-weight: 500;
        }

        /* Out of stock error message */
        .stock-error {
            color: var(--stock-negative); /* Red for out of stock */
            margin-top: 5px;
            font-weight: 500;
        }

        /* Styles for the cart confirmation message (fixed at top) */
        .cart-confirmation-message {
            position: fixed; /* Fixed position relative to viewport */
            top: 20px;
            left: 50%;
            transform: translateX(-50%); /* Center horizontally */
            background-color: rgba(40, 167, 69, 0.9); /* Success green with some opacity */
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 1000; /* Ensure it's on top of other content */
            opacity: 0; /* Hidden by default */
            visibility: hidden; /* Hidden by default */
            transition: opacity 0.5s ease, visibility 0.5s ease; /* Smooth transition for showing/hiding */
            font-weight: 500;
            min-width: 250px;
            text-align: center;
        }

        .cart-confirmation-message.show {
            opacity: 1; /* Fully visible when 'show' class is added */
            visibility: visible;
        }


        /* Responsive adjustments for different screen sizes */
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
                margin-bottom: 30px;
            }
            .back-link {
                padding: 12px 25px;
                font-size: 1.05rem;
                margin-bottom: 30px;
                margin-left: 0; /* Reset for larger screens, as body is centered */
            }
            .search-box {
                margin-bottom: 40px;
            }
            #itemSearch {
                padding: 15px 25px;
                max-width: 500px;
            }
            .item-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 30px;
            }
            .item-card {
                padding: 25px;
                border-radius: 18px;
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
                min-height: 420px; /* Taller cards */
            }
            .item-card img {
                height: 200px;
                border-radius: 12px;
            }
            .item-card h3 {
                font-size: 1.6rem;
            }
            .item-card p {
                font-size: 1rem;
            }
            .item-card .price {
                font-size: 1.4rem;
            }
            .item-card button {
                padding: 12px 25px;
                font-size: 1.1rem;
            }
            .stock-info, .stock-error {
                font-size: 0.95rem;
            }
        }

        @media (min-width: 1024px) {
            body {
                padding-top: 60px;
                padding-bottom: 60px;
            }
            .container {
                max-width: 1200px;
                padding: 40px;
            }
            h2 {
                font-size: 3.5rem;
                margin-bottom: 40px;
            }
            .back-link {
                padding: 15px 30px;
                font-size: 1.15rem;
                margin-bottom: 40px;
            }
            #itemSearch {
                max-width: 600px;
            }
            .item-grid {
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); /* Potentially three columns */
                gap: 35px;
            }
            .item-card {
                padding: 30px;
                border-radius: 20px;
                min-height: 450px; /* Even taller cards for more content */
            }
            .item-card img {
                height: 220px;
            }
            .item-card h3 {
                font-size: 1.8rem;
            }
            .item-card .price {
                font-size: 1.6rem;
            }
            .item-card button {
                padding: 15px 30px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <a class="back-link" href="BrowseItemsPage.php">&larr; Back to Categories</a>
    <h2><?php echo $categoryTitle; ?></h2>

    <?php if ($stockError): ?>
        <div class="alert alert-danger"><?= $stockError ?></div>
    <?php endif; ?>

    <div id="cartConfirmationMessage" class="cart-confirmation-message"></div>

    <div class="search-box">
        <input type="text" id="itemSearch" placeholder="Search items on this page...">
    </div>

    <div class="item-grid" id="itemsContainer">
        <?php if ($result && $result->num_rows > 0): // Check if there are any items to display ?>
            <?php while ($item = $result->fetch_assoc()): // Loop through each item from the database ?>
                <div class="item-card">
                    <?php if (!empty($item['item_picture'])): ?>
                        <img src="<?= htmlspecialchars($item['item_picture']) ?>" alt="<?= htmlspecialchars($item['itemName'] ?? 'Item Image') ?>">
                    <?php else: ?>
                        <img src="images/placeholder.jpg" alt="No Image Available" style="object-fit: contain;">
                    <?php endif; ?>
                    <h3 class="item-name"><?= htmlspecialchars($item['itemName'] ?? '') ?></h3>
                    <p class="item-desc"><?= htmlspecialchars($item['itemDescription'] ?? '') ?></p>
                    <p class="price"><strong>Price:</strong> R<?= number_format($item['price'], 2) ?></p>
                    <?php if (isset($item['quantity'])): // Check if quantity information is available ?>
                        <p class="stock-info"><strong>In Stock:</strong> <span class="stock-quantity-<?= $item['id'] ?>"><?= htmlspecialchars($item['quantity']) ?></span></p>
                        <form class="add-to-cart-form" data-product-id="<?= $item['id'] ?>">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <label>Quantity: <input type="number" name="quantity" value="1" min="1" max="<?= htmlspecialchars($item['quantity']) ?>"></label>
                            <button type="submit" class="add-to-cart-btn" <?= ($item['quantity'] <= 0) ? 'disabled' : '' ?>>Add to Cart</button>
                            <?php if ($item['quantity'] <= 0): // Display "Out of Stock" if quantity is zero or less ?>
                                <p class="stock-error stock-message-<?= $item['id'] ?>">Out of Stock</p>
                            <?php endif; ?>
                        </form>
                    <?php else: // Fallback if stock quantity is not set ?>
                        <p class="stock-info">Stock information not available.</p>
                        <form class="add-to-cart-form" data-product-id="<?= $item['id'] ?>">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <label>Quantity: <input type="number" name="quantity" value="1" min="1"></label>
                            <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: // Message if no items are found ?>
            <p style="color: var(--cool-gray); font-size: 1.1rem; margin-top: 30px;">No items found matching your criteria.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// JavaScript for client-side search/filter of items
document.getElementById('itemSearch').addEventListener('input', function () {
    // Get search query, trim whitespace, convert to lowercase, split by spaces, and filter out empty strings
    const query = this.value.trim().toLowerCase().split(' ').filter(word => word.length > 0);
    const itemCards = document.querySelectorAll('.item-card'); // Get all item cards

    itemCards.forEach(card => {
        // Get item name and description, convert to lowercase
        const name = card.querySelector('.item-name')?.textContent.toLowerCase() || '';
        const desc = card.querySelector('.item-desc')?.textContent.toLowerCase() || '';
        const content = name + ' ' + desc; // Combine for searching

        // If no query, show all cards
        if (query.length === 0) {
            card.style.display = 'flex'; // Display as flex to maintain card layout
            return;
        }

        // Check if all query words are present in the item's name or description
        const match = query.every(word => content.includes(word));
        card.style.display = match ? 'flex' : 'none'; // Show or hide based on match
    });
});

// New JavaScript for AJAX Add to Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    const cartForms = document.querySelectorAll('.add-to-cart-form'); // Select all add to cart forms
    const confirmationMessageDiv = document.getElementById('cartConfirmationMessage'); // Get the confirmation message div
    let confirmationTimeout; // Variable to hold the timeout ID for hiding the message

    cartForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission (which would reload the page)

            const formData = new FormData(form); // Create FormData object from the form
            const productId = form.dataset.productId; // Get the product ID from the form's data attribute

            // Send an AJAX POST request to 'addToCart.php'
            fetch('addToCart.php', {
                method: 'POST',
                body: formData // Send form data
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                clearTimeout(confirmationTimeout); // Clear any existing timeout for the message

                if (data.success) { // If the addition to cart was successful
                    confirmationMessageDiv.textContent = data.message; // Set message content
                    confirmationMessageDiv.classList.add('alert-success'); // Add success styling
                    confirmationMessageDiv.classList.remove('alert-danger'); // Remove danger styling
                    confirmationMessageDiv.classList.add('show'); // Show the message

                    // Optional: Update stock quantity displayed on the page
                    if (data.newQuantity !== undefined) {
                        const stockSpan = document.querySelector(`.stock-quantity-${productId}`);
                        if (stockSpan) {
                            stockSpan.textContent = data.newQuantity; // Update the stock quantity displayed
                        }

                        // Also update the max attribute of the quantity input field
                        const quantityInput = form.querySelector('input[name="quantity"]');
                        if (quantityInput) {
                            quantityInput.setAttribute('max', data.newQuantity); // Set new max quantity
                            if (parseInt(quantityInput.value) > data.newQuantity) {
                                // Adjust input value if it's now higher than available stock
                                quantityInput.value = data.newQuantity > 0 ? data.newQuantity : 1;
                            }
                            if (data.newQuantity <= 0) { // If stock is zero or less
                                form.querySelector('.add-to-cart-btn').disabled = true; // Disable the add to cart button
                                const stockErrorSpan = document.querySelector(`.stock-message-${productId}`);
                                if (stockErrorSpan) {
                                    stockErrorSpan.textContent = "Out of Stock"; // Update "Out of Stock" message
                                    stockErrorSpan.classList.add('stock-error');
                                } else {
                                    // If no error span exists, create and append one
                                    const newStockErrorSpan = document.createElement('p');
                                    newStockErrorSpan.classList.add('stock-error', `stock-message-${productId}`);
                                    newStockErrorSpan.textContent = "Out of Stock";
                                    form.appendChild(newStockErrorSpan);
                                }
                            } else { // If stock is available
                                form.querySelector('.add-to-cart-btn').disabled = false; // Enable the button
                                const stockErrorSpan = document.querySelector(`.stock-message-${productId}`);
                                if (stockErrorSpan) {
                                    stockErrorSpan.textContent = ''; // Clear error message
                                    stockErrorSpan.classList.remove('stock-error');
                                }
                            }
                        }
                    }

                } else { // If there was an error adding to cart
                    confirmationMessageDiv.textContent = data.message; // Set error message
                    confirmationMessageDiv.classList.remove('alert-success'); // Remove success styling
                    confirmationMessageDiv.classList.add('alert-danger'); // Add danger styling
                    confirmationMessageDiv.classList.add('show'); // Show the message
                }

                // Hide the message after 3 seconds
                confirmationTimeout = setTimeout(() => {
                    confirmationMessageDiv.classList.remove('show');
                }, 3000);
            })
            .catch(error => { // Catch any network or parsing errors
                console.error('Error:', error);
                clearTimeout(confirmationTimeout); // Clear any existing timeout
                confirmationMessageDiv.textContent = 'An error occurred. Please try again.'; // Generic error message
                confirmationMessageDiv.classList.remove('alert-success');
                confirmationMessageDiv.classList.add('alert-danger');
                confirmationMessageDiv.classList.add('show');
                confirmationTimeout = setTimeout(() => {
                    confirmationMessageDiv.classList.remove('show');
                }, 3000);
            });
        });
    });
});
</script>

</body>
</html>
<?php
// Close the database connection at the very end of the script to free up resources
if ($conn) {
    $conn->close();
}
?>