<?php
/**
 * Start the session.
 * This function initializes or resumes the session, allowing access to session variables.
 */
session_start();

/**
 * Include the database connection file.
 * This file (db.php) is expected to contain the database connection logic,
 * typically setting up the $conn variable for database interaction.
 */
include('db.php');

/**
 * Check if the user is logged in and has the 'seller' role.
 * If the 'user_id' session variable is not set or the 'role' is not 'seller',
 * the user is redirected to the LoginPage.php and the script execution is terminated.
 * This acts as an authorization and authentication gate.
 */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: LoginPage.php");
    exit();
}

/**
 * Retrieve the user ID from the session.
 * This ID will be used to fetch and update the seller's profile information.
 */
$user_id = $_SESSION['user_id'];

/**
 * Initialize an empty success message variable.
 * This variable will store a message to be displayed to the user upon successful profile update.
 */
$successMessage = "";

/**
 * Prepare and execute a SQL query to fetch the seller's current profile data.
 * The query selects all columns from the 'users' table where the 'id' matches the current session's user ID.
 * A prepared statement is used to prevent SQL injection.
 */
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
/**
 * Bind the user ID parameter to the prepared statement.
 * 'i' indicates that the parameter is an integer.
 */
$stmt->bind_param("i", $user_id);
/**
 * Execute the prepared statement.
 */
$stmt->execute();
/**
 * Get the result set from the executed statement.
 */
$result = $stmt->get_result();
/**
 * Fetch the associative array containing the seller's data.
 */
$seller = $result->fetch_assoc();

/**
 * Assign seller's profile data to variables.
 * The null coalescing operator (??) is used to provide empty strings as defaults
 * if the keys are not found in the $seller array, preventing undefined index notices.
 */
$username = $seller['username'] ?? '';
$email = $seller['email'] ?? '';
$phone = $seller['phone'] ?? '';
$address = $seller['address'] ?? '';

/**
 * Handle POST request for profile updates.
 * This block of code executes only when the form is submitted using the POST method.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    /**
     * Retrieve updated profile data from the POST request.
     * The null coalescing operator (??) provides empty strings if the POST variables are not set.
     */
    $newEmail = $_POST['email'] ?? '';
    $newPhone = $_POST['phone'] ?? '';
    $newAddress = $_POST['address'] ?? '';

    /**
     * Prepare a SQL UPDATE statement to modify the seller's profile.
     * Updates email, phone, and address for the user matching the session's user ID.
     * A prepared statement is used for security.
     */
    $update = $conn->prepare("UPDATE users SET email = ?, phone = ?, address = ? WHERE id = ?");
    /**
     * Bind the new email, phone, address, and user ID parameters to the prepared statement.
     * 'sssi' indicates that the parameters are string, string, string, and integer respectively.
     */
    $update->bind_param("sssi", $newEmail, $newPhone, $newAddress, $user_id);

    /**
     * Execute the update statement.
     * If the update is successful, set the success message and update session variables.
     */
    if ($update->execute()) {
        $successMessage = "Profile updated successfully!";
        /**
         * Update the 'email' in the session to reflect the new email immediately.
         * Consider updating other relevant session variables if they also store profile data.
         */
        $_SESSION['email'] = $newEmail; 
        
        /**
         * Update the local variables to reflect the new data, ensuring the form displays
         * the most current information without requiring a page reload to fetch from DB.
         */
        $email = $newEmail;
        $phone = $newPhone;
        $address = $newAddress;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - QuickBuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap">
    <style>
        /* Define CSS variables for color palette */
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

        /* Basic HTML and body styling */
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            box-sizing: border-box;
            overflow-x: hidden; /* Prevent horizontal scroll */
        }

        /* Body background and font styling */
        body {
            /* Galactic Market Background */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%;
            animation: bgShift 25s ease infinite; /* Background animation */
            font-family: 'Poppins', sans-serif;
            color: var(--ghost-white);
            display: flex;
            justify-content: center;
            align-items: center; /* Center vertically for a single card layout */
            padding: 20px; /* Default padding for overall content */
            min-height: 100vh; /* Ensure body takes full viewport height */
        }

        /* Keyframe animation for background shift */
        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Styling for the profile card container */
        .profile-card {
            background-color: rgba(27, 42, 75, 0.4); /* Semi-transparent background */
            color: var(--ghost-white); /* Text color */
            padding: 30px; /* Default padding */
            border-radius: 20px; /* Rounded corners */
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.4); /* Shadow effect */
            border: 1px solid rgba(255, 255, 255, 0.08); /* Subtle border */
            backdrop-filter: blur(10px); /* Frosted glass effect */
            max-width: 700px; /* Max-width for larger screens */
            width: 100%; /* Full width up to max-width */
            box-sizing: border-box; /* Include padding and border in element's total width and height */
            overflow-y: auto; /* Allow scrolling if content is too tall */
            max-height: 95vh; /* Limit card height to viewport, allow scroll */
        }

        /* Styling for the main heading */
        h2 {
            font-size: 2.5em;
            color: var(--white-pop);
            text-align: center;
            margin-bottom: 25px;
            letter-spacing: 1px;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.2); /* Text glow effect */
            word-break: break-word; /* Break long words */
            white-space: normal; /* Allow normal white-space handling */
        }

        /* Styling for muted text (e.g., descriptive paragraphs) */
        .text-muted {
            font-size: 1.1em;
            color: var(--ghost-white);
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        /* Styling for form labels */
        .form-label {
            font-size: 1em;
            color: var(--ghost-white);
            margin-bottom: 5px;
        }

        /* Styling for form input controls */
        .form-control {
            background-color: rgba(255, 255, 255, 0.1); /* Translucent input background */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Light border */
            color: var(--ghost-white); /* Text color */
            font-size: 1em;
            padding: 10px 15px;
            border-radius: 10px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2); /* Inner shadow */
            transition: border-color 0.3s ease, background-color 0.3s ease; /* Smooth transitions */
        }

        /* Focus state for form controls */
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: var(--caribbean-current); /* Accent color on focus */
            box-shadow: 0 0 0 0.25rem rgba(0, 100, 102, 0.25); /* Focus glow */
            color: var(--ghost-white);
        }

        /* Disabled state for form controls */
        .form-control:disabled {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--cool-gray);
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* General button styling */
        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s; /* Smooth transitions */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Button shadow */
        }

        /* Success button specific styling */
        .btn-success {
            background-color: var(--midnight-green);
            border-color: var(--midnight-green);
            color: var(--white-pop);
        }
        /* Hover effect for success button */
        .btn-success:hover {
            background-color: var(--caribbean-current);
            border-color: var(--caribbean-current);
            transform: translateY(-2px); /* Slight lift on hover */
        }

        /* Secondary button specific styling */
        .btn-secondary {
            background-color: var(--paynes-gray);
            border-color: var(--paynes-gray);
            color: var(--white-pop);
        }
        /* Hover effect for secondary button */
        .btn-secondary:hover {
            background-color: var(--slate-gray);
            border-color: var(--slate-gray);
            transform: translateY(-2px); /* Slight lift on hover */
        }

        /* Styling for success alert messages */
        .alert-success {
            background-color: rgba(0, 100, 102, 0.2); /* Semi-transparent green */
            color: var(--caribbean-current);
            border-color: var(--caribbean-current);
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Styling for button group to align to the end with a gap */
        .d-flex.justify-content-end.gap-2 {
            display: flex;
            justify-content: flex-end;
            gap: 15px; /* Standard gap for buttons */
        }

        /* --- Responsive Adjustments --- */

        /* Tablets (portrait) and larger phones */
        @media (min-width: 481px) and (max-width: 767px) {
            body {
                padding: 15px;
            }
            .profile-card {
                padding: 25px;
                border-radius: 18px;
                max-width: 90%;
            }
            h2 {
                font-size: 2em;
                margin-bottom: 20px;
            }
            .text-muted {
                font-size: 1em;
                margin-bottom: 25px;
            }
            .form-label, .form-control, .btn {
                font-size: 0.95em;
            }
            .btn {
                padding: 10px 20px;
            }
            .d-flex.justify-content-end.gap-2 {
                gap: 10px;
            }
        }

        /* Smartphones (most common sizes) - Portrait Only */
        @media (min-width: 376px) and (max-width: 480px) and (orientation: portrait) {
            body {
                padding: 10px;
                align-items: flex-start; /* Align to top to give more vertical room */
            }
            .profile-card {
                padding: 18px;
                border-radius: 15px;
                max-width: 98%; /* More width utilization */
                margin-top: 10px; /* Small margin from the top */
                max-height: 98vh; /* Allow max height, then scroll */
            }
            h2 {
                font-size: 1.5em;
                margin-bottom: 15px;
            }
            .text-muted {
                font-size: 0.9em;
                margin-bottom: 20px;
            }
            .form-label {
                font-size: 0.9em;
            }
            .form-control {
                font-size: 0.85em;
                padding: 8px 12px;
            }
            .btn {
                font-size: 0.85em;
                padding: 8px 15px;
            }
            .d-flex.justify-content-end.gap-2 {
                flex-direction: column; /* Stack buttons vertically */
                align-items: stretch; /* Make buttons full width */
                gap: 8px; /* Gap between stacked buttons */
            }
        }

        /* Smaller Smartphones (e.g., iPhone SE/5) - Portrait Only */
        @media (min-width: 321px) and (max-width: 375px) and (orientation: portrait) {
            body {
                padding: 5px;
                align-items: flex-start;
            }
            .profile-card {
                padding: 12px;
                border-radius: 12px;
                max-width: 98%;
                margin-top: 5px;
                max-height: 98vh;
            }
            h2 {
                font-size: 1.2em;
                margin-bottom: 10px;
            }
            .text-muted {
                font-size: 0.8em;
                margin-bottom: 15px;
            }
            .form-label {
                font-size: 0.85em;
            }
            .form-control {
                font-size: 0.8em;
                padding: 6px 10px;
            }
            .btn {
                font-size: 0.8em;
                padding: 7px 12px;
            }
            .d-flex.justify-content-end.gap-2 {
                flex-direction: column;
                align-items: stretch;
                gap: 5px;
            }
        }

        /* Smartphones in Landscape Mode (critical for vertical space) */
        @media (min-width: 481px) and (max-width: 820px) and (orientation: landscape) {
            body {
                padding: 5px; /* Minimal body padding */
                align-items: flex-start; /* Align content to the very top */
            }
            .profile-card {
                padding: 10px; /* Reduced card padding */
                border-radius: 10px;
                max-width: 98%;
                margin-top: 5px; /* Small margin from top */
                max-height: 98vh; /* Allow card to take almost full height and scroll */
            }
            h2 {
                font-size: 1.1em; /* Aggressively smaller header */
                margin-bottom: 8px; /* Reduced margin */
                line-height: 1.1; /* Tighter line height */
            }
            .text-muted {
                font-size: 0.75em; /* Smaller intro text */
                margin-bottom: 10px;
            }
            .form-label {
                font-size: 0.8em;
            }
            .form-control {
                font-size: 0.75em; /* Smaller input text */
                padding: 5px 8px;
            }
            .btn {
                font-size: 0.75em; /* Smaller button text */
                padding: 6px 10px;
            }
            .d-flex.justify-content-end.gap-2 {
                flex-direction: row; /* Try to keep buttons inline if space allows, but tightly packed */
                justify-content: center; /* Center buttons */
                gap: 5px; /* Very small gap between buttons */
                flex-wrap: wrap; /* Allow buttons to wrap if they can't fit in one row */
            }
        }

        /* Smartwatches and very tiny viewports (max-width: 320px) */
        @media (max-width: 320px) {
            body {
                padding: 0px;
                align-items: flex-start;
            }
            .profile-card {
                padding: 5px;
                border-radius: 8px;
                max-width: 100%;
                margin-top: 2px;
                max-height: 99vh;
            }
            h2 {
                font-size: 1em;
                margin-bottom: 5px;
            }
            .text-muted {
                font-size: 0.7em;
                margin-bottom: 8px;
            }
            .form-label {
                font-size: 0.75em;
            }
            .form-control {
                font-size: 0.7em;
                padding: 4px 6px;
            }
            .btn {
                font-size: 0.7em;
                padding: 5px 8px;
            }
            .d-flex.justify-content-end.gap-2 {
                flex-direction: column;
                align-items: stretch;
                gap: 3px;
            }
        }
    </style>
</head>
<body>

    <div class="profile-card">
        <h2 class="mb-3 text-center">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <p class="text-center">Manage your seller profile below.</p>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($address); ?>" class="form-control">
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-success">Save Changes</button>
                <a href="SellersDashBoard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>

</body>
</html>