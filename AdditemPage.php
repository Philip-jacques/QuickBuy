<?php
// Start a new session or resume the existing one. This is crucial for managing user states (like login status).
session_start();

// Check if the 'user_id' is NOT set in the session OR if the 'role' in the session is NOT 'seller'.
// This is a security measure to ensure only logged-in sellers can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    // If the user is not logged in or is not a seller, redirect them to the LoginPage.php.
    header("Location: LoginPage.php");
    // Terminate the script execution to prevent further output.
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item - QuickBuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Define CSS custom properties (variables) for consistent color usage throughout the stylesheet. */
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
        }

        /* Universal Box-Sizing for better layout control */
        /* Set the box-sizing property to 'border-box' for all elements.
           This makes it easier to manage element dimensions, as padding and border are included in the element's total width and height. */
        html {
            box-sizing: border-box;
        }
        /* Inherit box-sizing from the html element for all elements and their pseudo-elements. */
        *, *::before, *::after {
            box-sizing: inherit;
        }

        /* Basic styling for html and body elements. */
        html, body {
            margin: 0; /* Remove default margins */
            padding: 0; /* Remove default padding */
            min-height: 100vh; /* Set minimum height to 100% of the viewport height */
            overflow-x: hidden; /* Prevent horizontal scroll from elements overflowing */
        }

        /* Styling for the body element. */
        body {
            /* Apply a linear gradient background with specified colors and angle. */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%; /* Make the background larger than the viewport for animation */
            animation: bgShift 25s ease infinite; /* Apply the background shift animation */
            font-family: 'Poppins', sans-serif; /* Set the font family */
            color: var(--ghost-white); /* Set the default text color */
            display: flex; /* Use flexbox for layout */
            justify-content: center; /* Center content horizontally */
            align-items: center; /* Center content vertically */
            flex-direction: column; /* Arrange items in a column */
            padding: 20px; /* Add padding around the content */
        }

        /* Keyframes for the background shift animation. */
        @keyframes bgShift {
            0% { background-position: 0% 50%; } /* Start position of the background */
            50% { background-position: 100% 50%; } /* Mid-point position of the background */
            100% { background-position: 0% 50%; } /* End position of the background (back to start) */
        }

        /* Styling for the form card container. */
        .form-card {
            background-color: rgba(27, 42, 75, 0.4); /* Semi-transparent background color */
            border: 1px solid rgba(255, 255, 255, 0.05); /* Subtle border */
            backdrop-filter: blur(12px); /* Apply a blur effect to the background behind the card */
            padding: 30px; /* Padding inside the card */
            border-radius: 15px; /* Rounded corners */
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.5); /* Shadow for depth */
            width: 100%; /* Take full width on smaller screens */
            max-width: 550px; /* Maximum width for larger screens */
            text-align: center; /* Center text within the card */
            color: var(--ghost-white); /* Text color for the card */
            margin: auto; /* Center horizontally with auto margins */
        }

        /* Styling for the heading inside the form card. */
        .form-card h2 {
            font-size: 2em; /* Font size for the heading */
            color: var(--white-pop); /* Color of the heading text */
            margin-bottom: 25px; /* Margin below the heading */
        }

        /* Styling for form labels. */
        .form-label {
            color: var(--cool-gray); /* Color of the labels */
            font-size: 1em; /* Font size for labels */
            margin-bottom: 8px; /* Margin below each label */
            display: block; /* Make labels block-level elements */
            text-align: left; /* Align label text to the left */
        }

        /* Styling for form input controls. */
        .form-control {
            width: 100%; /* Take full width of its parent */
            padding: 12px 15px; /* Padding inside input fields */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Border style */
            background-color: rgba(255, 255, 255, 0.08); /* Semi-transparent background */
            border-radius: 10px; /* Rounded corners */
            font-size: 1em; /* Font size */
            color: var(--ghost-white); /* Text color inside input fields */
            transition: border-color 0.3s ease, background-color 0.3s ease; /* Smooth transition for focus effects */
        }

        /* Styling for form controls when they are focused (clicked or tabbed into). */
        .form-control:focus {
            outline: none; /* Remove the default outline */
            border-color: var(--true-blue); /* Change border color on focus */
            background-color: rgba(255, 255, 255, 0.15); /* Slightly change background on focus */
            box-shadow: none; /* Remove box shadow on focus */
        }

        /* Specific styling for textarea form controls. */
        textarea.form-control {
            resize: vertical; /* Allow vertical resizing of textareas */
        }

        /* Styling for input placeholders and default select option. */
        input::placeholder,
        select.form-control option[value=""] {
            color: var(--cool-gray); /* Color of placeholder text */
            opacity: 0.8; /* Opacity of placeholder text */
        }

        /* Date Input Specifics */
        /* Reset default appearance for date input to allow custom styling. */
        .form-control[type="date"] {
            -webkit-appearance: none; /* For WebKit browsers (Chrome, Safari) */
            -moz-appearance: none; /* For Mozilla Firefox */
            appearance: none; /* Standard property */
            position: relative; /* Needed for positioning the custom calendar picker indicator */
        }
        /* Style the calendar picker indicator for WebKit browsers. */
        .form-control[type="date"]::-webkit-calendar-picker-indicator {
            background: transparent; /* Make background transparent */
            bottom: 0; /* Position at the bottom */
            color: transparent; /* Make icon color transparent (so custom style can be used) */
            cursor: pointer; /* Change cursor to pointer */
            height: auto; /* Auto height */
            left: 0; /* Position at the left */
            position: absolute; /* Absolute positioning within the input */
            right: 0; /* Position at the right */
            top: 0; /* Position at the top */
            width: auto; /* Auto width */
        }
        /* Style for date input text color across different browser rendering engines. */
        .form-control[type="date"]::-webkit-datetime-edit-fields-wrapper,
        .form-control[type="date"]::-webkit-datetime-edit-text,
        .form-control[type="date"]::-webkit-datetime-edit-month-field,
        .form-control[type="date"]::-webkit-datetime-edit-day-field,
        .form-control[type="date"]::-webkit-datetime-edit-year-field {
            color: var(--ghost-white); /* Set the color of the date text */
        }

        /* Styling for options within a select dropdown. */
        .form-control option {
            background-color: var(--prussian-blue); /* Background color for dropdown options */
            color: var(--white-pop); /* Text color for dropdown options */
        }

        /* Styling for the image preview. */
        .img-preview {
            max-width: 100%; /* Ensure image doesn't overflow its container */
            max-height: 150px; /* Maximum height for the preview */
            margin-top: 15px; /* Margin above the image */
            display: none; /* Initially hidden */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Border around the preview */
            border-radius: 10px; /* Rounded corners */
            object-fit: contain; /* Ensures image scales without distortion within its boundaries */
        }

        /* Styling for the button group container. */
        .btn-group {
            display: flex; /* Use flexbox for button layout */
            justify-content: center; /* Center buttons horizontally */
            margin-top: 25px; /* Margin above the button group */
            gap: 15px; /* Space between buttons */
            flex-wrap: wrap; /* Allow buttons to wrap to the next line if space is limited */
        }

        /* Styling for success and secondary buttons. */
        .btn-success, .btn-secondary {
            background-color: var(--midnight-green); /* Default background color */
            color: var(--ghost-white); /* Text color */
            padding: 14px 25px; /* Padding inside buttons */
            border: none; /* Remove default border */
            border-radius: 12px; /* Rounded corners */
            font-size: 1em; /* Font size */
            font-weight: bold; /* Bold text */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3); /* Shadow for depth */
            cursor: pointer; /* Change cursor to pointer on hover */
            text-decoration: none; /* Remove underline from links styled as buttons */
            transition: background-color 0.3s ease, transform 0.2s; /* Smooth transitions for hover effects */
            width: 100%; /* Default to full width on smallest screens */
            max-width: 220px; /* Limit max width for buttons */
            text-align: center; /* Ensure text is centered */
        }

        /* Hover effect for success button. */
        .btn-success:hover {
            background-color: var(--caribbean-current); /* Change background on hover */
            transform: scale(1.02); /* Slightly enlarge on hover */
            color: var(--ghost-white); /* Maintain text color on hover */
        }

        /* Specific background color for secondary button. */
        .btn-secondary {
            background-color: var(--paynes-gray);
        }

        /* Hover effect for secondary button. */
        .btn-secondary:hover {
            background-color: var(--slate-gray); /* Change background on hover */
            transform: scale(1.02); /* Slightly enlarge on hover */
            color: var(--ghost-white); /* Maintain text color on hover */
        }

        /* Styling for the progress bar container. */
        #progressBarContainer {
            display: none; /* Initially hidden */
            margin-top: 25px; /* Margin above the container */
            height: 18px; /* Height of the container */
            background-color: rgba(255, 255, 255, 0.1); /* Background color of the track */
            border-radius: 10px; /* Rounded corners */
        }

        /* Styling for the progress bar itself. */
        #progressBar {
            height: 100%; /* Take full height of its container */
            border-radius: 10px; /* Rounded corners */
            background-color: var(--caribbean-current); /* Color of the progress fill */
            font-size: 0.85em; /* Font size for the percentage text */
            color: var(--white-pop); /* Text color for the percentage */
            display: flex; /* Use flexbox for centering text */
            align-items: center; /* Center text vertically */
            justify-content: center; /* Center text horizontally */
            transition: width 0.3s ease; /* Smooth transition for width changes */
        }

        /* --- Media Queries for various devices --- */

        /* Styles for Larger Desktops / Laptops (viewport width 1200px and up) */
        @media (min-width: 1200px) {
            body {
                padding: 40px; /* More padding for larger screens */
            }
            .form-card {
                max-width: 700px;
                padding: 45px;
                border-radius: 25px;
            }
            .form-card h2 {
                font-size: 2.8em;
                margin-bottom: 35px;
            }
            .form-label {
                font-size: 1.15em;
            }
            .form-control {
                padding: 16px 22px;
                font-size: 1.1em;
            }
            .img-preview {
                max-height: 220px;
            }
            .btn-group {
                margin-top: 35px;
                gap: 20px;
            }
            .btn-success, .btn-secondary {
                padding: 18px 45px;
                font-size: 1.15em;
                border-radius: 15px;
                width: auto; /* Allow buttons to size naturally side-by-side */
            }
            #progressBarContainer {
                height: 22px;
                margin-top: 35px;
            }
            #progressBar {
                font-size: 0.9em;
            }
        }

        /* Styles for Tablets and smaller Laptops (viewport width between 768px and 1199px) */
        @media (min-width: 768px) and (max-width: 1199px) {
            body {
                padding: 30px; /* More padding for tablets */
            }
            .form-card {
                max-width: 650px;
                padding: 40px;
                border-radius: 20px;
            }
            .form-card h2 {
                font-size: 2.4em;
                margin-bottom: 30px;
            }
            .form-label {
                font-size: 1.05em;
            }
            .form-control {
                padding: 15px 20px;
                font-size: 1.05em;
            }
            .img-preview {
                max-height: 180px;
            }
            .btn-group {
                margin-top: 30px;
                gap: 15px;
            }
            .btn-success, .btn-secondary {
                padding: 16px 35px;
                font-size: 1.05em;
                width: auto; /* Allow buttons to size naturally side-by-side */
            }
            #progressBarContainer {
                height: 20px;
                margin-top: 30px;
            }
            #progressBar {
                font-size: 0.9em;
            }
        }

        /* Styles for Larger Smartphones and smaller Tablets (viewport width between 576px and 767px) */
        @media (min-width: 576px) and (max-width: 767px) {
            body {
                padding: 25px; /* Slightly more padding for larger phones/small tablets */
            }
            .form-card {
                padding: 25px;
                border-radius: 12px;
                max-width: 480px;
            }
            .form-card h2 {
                font-size: 2.1em;
                margin-bottom: 28px;
            }
            .form-label {
                font-size: 1em;
            }
            .form-control {
                padding: 14px 18px;
                font-size: 1em;
            }
            .img-preview {
                max-height: 160px;
            }
            .btn-group {
                margin-top: 28px;
                gap: 12px;
            }
            /* Here, allow buttons to be side-by-side if there's enough space, but ensure they don't get too small. */
            .btn-success, .btn-secondary {
                padding: 14px 30px;
                font-size: 1em;
                flex-grow: 1; /* Allow them to grow if needed */
                min-width: 180px; /* Minimum width before wrapping */
                width: auto; /* Override the 100% default for smaller screens */
            }
            #progressBarContainer {
                height: 18px;
                margin-top: 28px;
            }
            #progressBar {
                font-size: 0.85em;
            }
        }

        /* Styles for Extra Small Devices (phones less than 576px wide) */
        @media (max-width: 575px) {
            body {
                padding: 15px; /* Reduced padding for smallest screens */
            }
            .form-card {
                padding: 20px;
                border-radius: 10px;
                width: calc(100% - 30px); /* Use calc to ensure 15px margin on each side */
                max-width: none; /* Remove max-width constraint here, let calc manage width */
            }
            .form-card h2 {
                font-size: 1.8em;
                margin-bottom: 22px;
            }
            .form-label {
                font-size: 0.95em;
            }
            .form-control {
                padding: 12px 16px;
                font-size: 0.95em;
            }
            .img-preview {
                max-height: 140px;
            }
            .btn-group {
                margin-top: 25px;
                gap: 10px; /* Gap between stacked buttons */
                flex-direction: column; /* Force buttons to stack vertically */
                align-items: stretch; /* Make stacked buttons fill available width */
            }
            .btn-success, .btn-secondary {
                padding: 12px 25px;
                font-size: 0.95em;
                width: 100%; /* Ensure stacked buttons take full width */
                max-width: none; /* Remove max-width constraint for stacked buttons */
            }
            #progressBarContainer {
                height: 16px;
                margin-top: 25px;
            }
            #progressBar {
                font-size: 0.8em;
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

<div class="form-card">
    <h2 class="mb-4 text-center">Add a New Item</h2>

    <form id="itemForm" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="itemName" class="form-label">Item Name</label>
            <input type="text" name="itemName" id="itemName" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="itemDescription" class="form-label">Item Description</label>
            <textarea name="itemDescription" id="itemDescription" rows="4" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label for="itemPicture" class="form-label">Item Picture</label>
            <input type="file" name="itemPicture" id="itemPicture" class="form-control" accept="image/*" onchange="previewImage()">
            <img id="imagePreview" class="img-preview" alt="Image Preview">
            <input type="text" name="altText" class="form-control mt-2" placeholder="Describe the image (for accessibility)">
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select name="category" id="category" class="form-control" required>
                <option value="">Select a category</option>
                <option value="Electronics">Electronics</option>
                <option value="Furniture">Furniture</option>
                <option value="Clothing">Clothing</option>
                <option value="Books">Books</option>
                <option value="Toys">Toys</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="dateAdded" class="form-label">Date Added</label>
            <input type="date" name="dateAdded" id="dateAdded" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">Price (R)</label>
            <input type="number" step="0.01" name="price" id="price" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" name="quantity" id="quantity" class="form-control" required>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-success">Submit Item</button>
            <a href="SellersDashBoard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div id="progressBarContainer" class="progress mt-4">
            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
        </div>
    </form>
</div>

<script>
    /**
     * @function previewImage
     * @description Displays a preview of the selected image file in the 'imagePreview' element.
     * This function is called when the 'itemPicture' input's value changes.
     */
    function previewImage() {
        // Get the file input element.
        const input = document.getElementById('itemPicture');
        // Get the image preview element.
        const preview = document.getElementById('imagePreview');
        // Get the first file selected by the user.
        const file = input.files[0];

        // Check if a file was selected.
        if (file) {
            // Create a new FileReader object. This object reads the contents of files.
            const reader = new FileReader();
            // Set the onload event handler for the reader. This function executes once the file has been successfully read.
            reader.onload = function(e) {
                // Set the 'src' attribute of the image preview to the result of the FileReader (the base64 encoded image data).
                preview.src = e.target.result;
                // Make the image preview visible.
                preview.style.display = 'block';
            };
            // Read the contents of the selected file as a Data URL (base64 encoded string).
            reader.readAsDataURL(file);
        } else {
            // If no file is selected, clear the image preview and hide it.
            preview.src = '';
            preview.style.display = 'none';
        }
    }

    /**
     * @function validateImage
     * @description Validates the selected image file based on type and size constraints.
     * @param {File} file - The image file to validate.
     * @returns {boolean} - True if the file is valid, false otherwise.
     */
    function validateImage(file) {
        // Check if a file exists.
        if (file) {
            // Define an array of valid image MIME types.
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            // Define the maximum allowed image size (2MB in bytes).
            const maxSize = 2 * 1024 * 1024; // 2MB

            // Check if the file type is not included in the valid types.
            if (!validTypes.includes(file.type)) {
                alert('Only JPEG, PNG, or WebP images are allowed.'); // Alert user about invalid type.
                return false; // Return false for invalid file.
            }
            // Check if the file size exceeds the maximum allowed size.
            if (file.size > maxSize) {
                alert('Image size must be under 2MB.'); // Alert user about excessive size.
                return false; // Return false for excessive size.
            }
        }
        // If no file or all checks pass, return true.
        return true;
    }

    // Add an event listener to the form's submit event.
    document.getElementById('itemForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission behavior (which would cause a page reload).

        // Get the form element.
        const form = document.getElementById('itemForm');
        // Create a new FormData object from the form. This allows easy handling of form data, including file uploads.
        const formData = new FormData(form);
        // Get the selected image file.
        const file = document.getElementById('itemPicture').files[0];

        // Validate the image file. If validation fails, stop the submission process.
        if (!validateImage(file)) return;

        // Create a new XMLHttpRequest object to send form data asynchronously.
        const xhr = new XMLHttpRequest();
        // Configure the request: POST method, target URL 'SubmitItems.php', asynchronous (true).
        xhr.open('POST', 'SubmitItems.php', true);

        // Add an event listener to track the progress of the file upload.
        xhr.upload.addEventListener('progress', function(e) {
            // Calculate the upload percentage.
            const percent = Math.round((e.loaded / e.total) * 100);
            // Get the progress bar elements.
            const progressBar = document.getElementById('progressBar');
            const progressContainer = document.getElementById('progressBarContainer');
            // Display the progress bar container.
            progressContainer.style.display = 'block';
            // Update the width of the progress bar.
            progressBar.style.width = percent + '%';
            // Update the text content of the progress bar to show the percentage.
            progressBar.textContent = percent + '%';
        });

        // Define what happens when the AJAX request completes (either successfully or with an error).
        xhr.onload = function() {
            // Check if the HTTP status code is 200 (OK).
            if (xhr.status === 200) {
                alert('Item successfully submitted!'); // Inform the user of success.
                window.location.href = 'SellersDashBoard.php'; // Redirect to the seller's dashboard.
            } else {
                alert('Something went wrong. Please try again.'); // Inform the user of an error.
            }
        };

        // Send the form data (including the file) to the server.
        xhr.send(formData);
    });

    // Set default date to today for convenience when the DOM content is fully loaded.
    document.addEventListener('DOMContentLoaded', (event) => {
        const today = new Date(); // Get the current date.
        const yyyy = today.getFullYear(); // Get the full year.
        // Get the month (0-indexed), add 1, and pad with a leading zero if necessary.
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        // Get the day of the month, and pad with a leading zero if necessary.
        const dd = String(today.getDate()).padStart(2, '0');
        // Set the value of the 'dateAdded' input to today's date in YYYY-MM-DD format.
        document.getElementById('dateAdded').value = `${yyyy}-${mm}-${dd}`;
    });
</script>

</body>
</html>
