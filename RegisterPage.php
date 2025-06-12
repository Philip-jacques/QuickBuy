<?php
/**
 * @file register.php
 * This file handles user registration, including form submission, validation,
 * and database insertion. It also displays success or error messages to the user.
 */

// Initialize empty messages for success and error.
$successMessage = "";
$errorMessage = "";

// Check if the form has been submitted using the POST method.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Include database connection file
    include 'db.php';

    // Sanitize and retrieve form data from the POST request.
    $username = htmlspecialchars($_POST["username"]);
    $email = htmlspecialchars($_POST["email"]);
    $password = $_POST["password"]; // Password will be hashed
    $confirmPassword = $_POST["confirm_password"];
    $role = htmlspecialchars($_POST["role"]);
    // Check if the terms checkbox was checked.
    $terms = isset($_POST["terms"]);

    // Retrieve phone number, and it must now be provided.
    $phone = htmlspecialchars($_POST["phone"] ?? ''); // Keep htmlspecialchars for safety, but validation handles emptiness

    // Validate if terms and conditions are accepted.
    if (!$terms) {
        $errorMessage = "Please accept the terms and conditions.";
    }
    // ADDED: Validate if phone number is empty
    elseif (empty($phone)) {
        $errorMessage = "Phone number is required.";
    }
    // Validate if passwords match.
    elseif ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match.";
    }
    // If initial validations pass, proceed with password strength and database insertion.
    else {
        // Validate password strength: at least 8 characters, one uppercase, one number, one special character.
        if (strlen($password) < 8 ||
            !preg_match("/[A-Z]/", $password) ||
            !preg_match("/[0-9]/", $password) ||
            !preg_match("/[^a-zA-Z0-9]/", $password)) {
            $errorMessage = "Password does not meet strength requirements (min 8 chars, 1 uppercase, 1 number, 1 special).";
        }
        // If password meets strength requirements, proceed with registration.
        else {
            // Hash the password for secure storage.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Prepare an SQL statement to insert user data into the 'users' table.
            // Using prepared statements prevents SQL injection.
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
            // Bind parameters to the prepared statement. 'sssss' indicates five string parameters.
            $stmt->bind_param("sssss", $username, $email, $hashedPassword, $role, $phone);

            
            try {
                if ($stmt->execute()) {
                    // Set success message if registration is successful.
                    $successMessage = "🎉 Registration successful. <a href='LoginPage.php'>Login here</a>.";
                } else {
                    // This 'else' block would typically be for non-exception errors,
                    // but with mysqli in error mode, most errors throw exceptions.
                    // Keep it for robustness, though it might not be hit often.
                    $errorMessage = "An unexpected error occurred during registration. Please try again.";
                }
            } catch (mysqli_sql_exception $e) {
                // Check if the error code indicates a duplicate entry
                if ($e->getCode() == 1062) {
                    // Check if the error message specifically mentions 'email'
                    if (strpos($e->getMessage(), 'email') !== false) {
                        $errorMessage = "Sorry, that **email address is already in use**. Please log in or use a different email.";
                    }
                    // Check if the error message specifically mentions 'phone'
                    // This assumes you've added a UNIQUE constraint to the 'phone' column
                    elseif (strpos($e->getMessage(), 'phone') !== false) {
                        $errorMessage = "Sorry, that **phone number is already registered**. Please use a different phone number.";
                    }
                    // If neither 'email' nor 'phone' is explicitly mentioned but it's a duplicate entry
                    else {
                        $errorMessage = "Sorry, your registration could not be completed as **some of the information provided is already in use**.";
                    }
                } else {
                    // Handle other SQL exceptions (e.g., connection issues, syntax errors)
                    // For production, you might log the specific error ($e->getMessage())
                    // but show a generic message to the user for security.
                    $errorMessage = "An unexpected database error occurred. Please try again later.";
                }
            }
            

            // Close the prepared statement.
            $stmt->close();
        }
    }
    // Close the database connection.
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - QuickBuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
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

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            box-sizing: border-box;
        }

        body {
            /* Galactic Market Background - Copied from LoginPage */
            background: linear-gradient(135deg,
                var(--deep-space-blue),
                var(--midnight-green-3),
                var(--prussian-blue),
                var(--oxford-blue),
                var(--true-blue)
            );
            background-size: 300% 300%;
            animation: bgShift 25s ease infinite;
            font-family: 'Poppins', sans-serif;
            color: var(--ghost-white);
            display: flex;
            flex-direction: column;
            padding-top: 50px;
        	padding-bottom: 50px;
        	min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
            box-sizing: border-box;
        }

        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Register Container - Adapted from .login-container */
.register-container {
    background-color: rgba(27, 42, 75, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.5);
    width: 90%;
    max-width: 450px;
    text-align: center;
    box-sizing: border-box;
    color: var(--ghost-white);
    /* ADD these lines */
    flex-shrink: 0;
    margin: auto;
}

        .register-container h2 {
            font-size: 2.5em;
            color: var(--white-pop);
            margin-bottom: 30px;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        /* Input field styling - Applied to all relevant input types */
        .register-container input[type="text"],
        .register-container input[type="email"],
        .register-container input[type="password"] {
            width: 100%;
            padding: 15px 50px 15px 20px; /* Match LoginPage input padding and space for icon */
            border: 1px solid rgba(255, 255, 255, 0.2);
            background-color: rgba(255, 255, 255, 0.08); /* Matches LoginPage input background */
            border-radius: 10px;
            font-size: 1.1em;
            color: var(--ghost-white);
            box-sizing: border-box;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }

        .register-container input:focus,
        .select-wrapper select:focus { /* Added select focus */
            outline: none;
            border-color: var(--true-blue);
            background-color: rgba(255, 255, 255, 0.15);
        }

        input::placeholder {
            color: var(--cool-gray);
            opacity: 0.8;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--cool-gray);
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: var(--white-pop);
        }

        /* Password Info Icon and Tooltip */
        .password-info {
            position: absolute;
            right: 50px; /* Position next to toggle-password */
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--cool-gray);
            font-size: 1.1rem;
            transition: color 0.2s ease;
            z-index: 2;
        }

        .password-info:hover {
            color: var(--white-pop);
        }

        .password-tooltip {
            display: none;
            position: absolute;
            top: -10px;
            left: calc(100% + 15px);
            background-color: var(--oxford-blue-2);
            color: var(--ghost-white);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            width: 250px;
            text-align: left;
            z-index: 100;
            white-space: normal;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .password-info:hover .password-tooltip {
            display: block;
        }

        .password-tooltip::before {
            content: '';
            position: absolute;
            top: 20px;
            left: -10px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent var(--oxford-blue-2) transparent transparent;
        }

        /* Select Role Dropdown Styles */
        .select-wrapper {
            position: relative;
            margin-bottom: 25px; /* Match form-group margin */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Matches input border */
            background-color: rgba(255, 255, 255, 0.08); /* Matches input background */
            border-radius: 10px;
            transition: border-color 0.3s ease, background-color 0.3s ease;
            box-sizing: border-box;
        }

        .select-wrapper:focus-within {
            outline: none;
            border-color: var(--true-blue);
            background-color: rgba(255, 255, 255, 0.15);
        }

        .select-wrapper select {
            -webkit-appearance: none; /* Remove default dropdown arrow for Webkit */
            -moz-appearance: none;    /* Remove default dropdown arrow for Firefox */
            appearance: none;         /* Remove default dropdown arrow for other browsers */
            width: 100%;
            padding: 15px 50px 15px 20px; /* Match input padding and make space for custom arrow */
            border: none;
            background-color: transparent; /* Make the select transparent to show wrapper's background */
            font-size: 1.1em;
            color: var(--ghost-white); /* Color for the *selected* text (e.g., "Select Role" or "Buyer") */
            cursor: pointer;
            outline: none;
            box-sizing: border-box;
        }

        /* Style the placeholder option */
        .select-wrapper select option[value=""] {
            color: var(--cool-gray);
            opacity: 0.8;
        }

        /* THIS IS THE KEY FIX FOR THE GREY OPTIONS */
        .select-wrapper select option {
            /* When the dropdown is open, these styles apply to the visible options */
            background-color: var(--prussian-blue); /* Use a dark background from your palette */
            color: var(--white-pop); /* Ensure text is white and clearly visible */
            padding: 10px 20px; /* Example padding for options */
            font-size: 1em;
        }

        /* Custom arrow for the dropdown */
        .select-wrapper::after {
            content: '▼';
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--cool-gray);
            font-size: 1.2rem;
        }
        /* End of Select Role Dropdown Styles */


        .btn-custom {
            background-color: var(--midnight-green);
            color: var(--ghost-white);
            padding: 16px 40px;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: bold;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s;
            display: inline-block;
            width: 100%;
            margin-top: 15px;
        }

        .btn-custom:hover {
            background-color: var(--caribbean-current);
            transform: scale(1.02);
        }

        .btn-custom:disabled {
            background-color: var(--paynes-gray);
            color: rgba(255, 255, 255, 0.6);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
.back-button {
        background-color: var(--sapphire); /* A slightly different blue from your palette for variety */
       color: var(--ghost-white);
       padding: 16px 40px;
       border: none;
       border-radius: 12px;
   font-size: 1.1em;
        font-weight: bold;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        cursor: pointer;
        text-decoration: none; /* Remove underline from link */
        transition: background-color 0.3s ease, transform 0.2s;
        display: inline-block; /* Allows padding and width to be applied */
        width: 100%; /* Make it full width like the register button */
        margin-top: 20px; /* Add some space above it */
}

.back-button:hover {
    background-color: var(--true-blue); /* A brighter blue on hover */
    transform: scale(1.02);
}

        /* Message styling (for success/error messages) */
        .message {
            margin-bottom: 20px;
            font-size: 1em;
            text-align: center;
            padding: 12px;
            border-radius: 10px;
            backdrop-filter: blur(5px);
            border: 1px solid;
            color: var(--white-pop);
        }

        .message:not(.success) { /* Styles for error messages, similar to alert-danger from login */
            background-color: rgba(255, 0, 0, 0.2);
            border-color: rgba(255, 0, 0, 0.4);
        }

        .message.success {
            background-color: rgba(0, 100, 102, 0.2); /* Caribbean Current with transparency */
            border-color: rgba(0, 100, 102, 0.4);
        }

        .hidden {
            display: none;
        }

        .terms-container {
            display: flex;
            align-items: center;
            margin-top: 20px; /* Added margin for spacing */
            margin-bottom: 20px;
            text-align: left;
            color: var(--cool-gray);
            font-size: 1em;
        }

        .terms-container input[type="checkbox"] {
            margin-right: 10px;
            width: auto;
            min-width: 18px;
            min-height: 18px;
            accent-color: var(--caribbean-current);
        }

        .terms-container label {
            cursor: pointer;
            color: var(--cool-gray);
        }

        /* Terms and Conditions link styling - Consistent with LoginPage's "Register" link */
        .terms-container label a {
            color: var(--white-pop); /* Change link color to pure white for high contrast */
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease, text-shadow 0.2s ease;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0);
        }

        .terms-container label a:hover {
            color: var(--caribbean-current); /* Change to a vibrant green on hover */
            text-decoration: none;
            text-shadow: 0 0 10px var(--caribbean-current), 0 0 20px var(--caribbean-current); /* Glow effect */
        }

        /* Modal Styling (for Terms and Conditions) */
        .terms-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        }

        .terms-modal-content {
            background-color: var(--oxford-blue-2);
            color: var(--ghost-white);
            margin: 8% auto;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 90%;
            max-width: 700px;
            border-radius: 15px;
            position: relative;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.6);
            animation: slideInFromTop 0.4s ease-out;
        }

        .terms-modal-content h3 {
            font-size: 2em;
            color: var(--white-pop);
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }

        .terms-modal-content h4 { /* Style for sub-headings in modal */
            font-size: 1.2em;
            color: var(--true-blue);
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .terms-close-button {
            color: var(--cool-gray);
            float: right;
            font-size: 32px;
            font-weight: normal;
            transition: color 0.2s ease;
            position: absolute;
            right: 20px;
            top: 15px;
        }

        .terms-close-button:hover,
        .terms-close-button:focus {
            color: var(--white-pop);
            text-decoration: none;
            cursor: pointer;
        }

        .terms-content {
            padding: 20px 0;
            text-align: left;
            font-size: 0.95rem;
            line-height: 1.7;
            color: var(--slate-gray);
            max-height: 400px;
            overflow-y: auto;
            padding-right: 15px;
        }

        .terms-content::-webkit-scrollbar {
            width: 8px;
        }

        .terms-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .terms-content::-webkit-scrollbar-thumb {
            background: var(--sapphire);
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .terms-content::-webkit-scrollbar-thumb:hover {
            background: var(--true-blue);
        }

        .terms-content ul {
            list-style: none;
            padding-left: 0;
        }

        .terms-content ul li {
            position: relative;
            margin-bottom: 10px;
            padding-left: 25px;
        }

        .terms-content ul li::before {
            content: '•';
            color: var(--caribbean-current);
            font-size: 1.2em;
            position: absolute;
            left: 0;
            top: 0;
        }

        .terms-modal-content button {
            background-color: var(--midnight-green);
            color: var(--white-pop);
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .terms-modal-content button:hover {
            background-color: var(--caribbean-current);
        }


        .register-back-container {
            margin-top: 25px;
            text-align: center;
        }


        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInFromTop {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }


        /* Responsive adjustments - Aligned with LoginPage */
        @media (max-width: 991px) {
            .register-container {
                padding: 35px;
                border-radius: 18px;
                max-width: 400px;
            }
            .register-container h2 {
                font-size: 2.2em;
            }
            .register-container input[type="text"],
            .register-container input[type="email"],
            .register-container input[type="password"],
            .select-wrapper select {
                padding: 14px 45px 14px 18px;
                font-size: 1em;
            }
            .toggle-password {
                right: 15px;
                font-size: 1.1rem;
            }
            .password-info {
                right: 45px;
                font-size: 1rem;
            }
            .password-tooltip {
                font-size: 0.8rem;
                width: 220px;
                left: 105%;
            }
            .btn-custom {
                padding: 14px 30px;
                font-size: 1em;
            }
            .message {
                padding: 10px;
                font-size: 0.9em;
            }
            .terms-modal-content {
                margin: 10% auto;
                padding: 25px;
            }
            .terms-modal-content h3 {
                font-size: 1.8em;
            }
            .terms-content {
                font-size: 0.9em;
            }
            .back-button {
                padding: 10px 25px;
                font-size: 1em;
            }
            .select-wrapper::after {
                right: 15px;
                font-size: 1.1rem;
            }
            .select-wrapper select option {
                font-size: 0.95em;
            }
        }

        @media (max-width: 575px) {
            body {
                padding: 15px;
                align-items: center;
            }
            .register-container {
                padding: 30px;
                border-radius: 15px;
                max-width: 95%;
            }
            .register-container h2 {
                font-size: 2em;
                margin-bottom: 25px;
            }
            .register-container input[type="text"],
            .register-container input[type="email"],
            .register-container input[type="password"],
            .select-wrapper select {
                padding: 12px 40px 12px 15px;
                font-size: 0.95em;
            }
            .toggle-password {
                right: 12px;
                font-size: 1rem;
            }
            .password-info {
                right: 40px;
                font-size: 0.95rem;
            }
            .password-tooltip {
                font-size: 0.75rem;
                width: 200px;
                left: 100%;
                top: 0px;
            }
            .btn-custom {
                padding: 12px 25px;
                font-size: 0.95em;
                margin-top: 10px;
            }
            .message {
                padding: 8px;
                font-size: 0.85em;
                margin-bottom: 20px;
            }
            .terms-container {
                font-size: 0.9em;
            }
            .terms-modal-content {
                margin: 15% auto;
                padding: 20px;
            }
            .terms-modal-content h3 {
                font-size: 1.6em;
            }
            .terms-content {
                font-size: 0.85em;
            }
            .terms-modal-content button {
                padding: 8px 20px;
            }
            .back-button {
                padding: 8px 20px;
                font-size: 1em;
            }
            .select-wrapper::after {
                right: 12px;
                font-size: 1rem;
            }
            .select-wrapper select option {
                font-size: 0.9em;
            }
        }

        @media (max-width: 400px) {
            .register-container {
                padding: 25px;
                border-radius: 12px;
            }
            .register-container h2 {
                font-size: 1.8em;
                margin-bottom: 20px;
            }
            .register-container input[type="text"],
            .register-container input[type="email"],
            .register-container input[type="password"],
            .select-wrapper select {
                font-size: 0.9em;
                padding: 10px 35px 10px 12px;
                margin-bottom: 10px;
            }
            .toggle-password {
                right: 10px;
                font-size: 0.9rem;
            }
            .password-info {
                right: 35px;
                font-size: 0.85rem;
            }
            .password-tooltip {
                font-size: 0.7rem;
                width: 180px;
                left: 98%;
                top: -5px;
            }
            .btn-custom {
                font-size: 0.9em;
                padding: 10px 20px;
            }
            .message {
                font-size: 0.8em;
                padding: 6px;
            }
            .terms-container {
                font-size: 0.8em;
            }
            .terms-modal-content {
                margin: 20% auto;
            }
            .terms-modal-content h3 {
                font-size: 1.4em;
            }
            .terms-content {
                font-size: 0.8em;
            }
            .terms-modal-content button {
                padding: 8px 20px;
            }
            .back-button {
                font-size: 0.85em;
                padding: 7px 18px;
            }
            .select-wrapper::after {
                right: 10px;
                font-size: 0.9rem;
            }
            .select-wrapper select option {
                font-size: 0.85em;
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
<div class="register-container">
    <h2>Create Your Account</h2>

    <?php
    // Display error message if it's not empty.
    if (!empty($errorMessage)): ?>
        <div class="message"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <?php
    // Display success message if it's not empty.
    if (!empty($successMessage)): ?>
        <div class="message success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <form id="registerForm" method="POST" action="">
        <input type="text" name="username" id="username" placeholder="Username" required oninput="validateForm()">
        <input type="email" name="email" id="email" placeholder="Email" required oninput="validateForm()">

        <div class="form-group password-container">
            <input type="password" name="password" id="password" placeholder="Password" required oninput="handlePasswordInput(); validateForm()">
            <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
            <span class="password-info">ℹ️
                <span class="password-tooltip">
                    Password must be at least 8 characters, have one capital letter, number, and special character.
                </span>
            </span>
        </div>

        <div class="form-group password-container" id="confirmContainer">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required oninput="validateForm()">
            <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
        </div>
        <input type="text" name="phone" id="phone" placeholder="Phone Number" required oninput="validateForm()">

        <div class="select-wrapper">
            <select name="role" id="role" required onchange="validateForm()">
                <option value="">Select Role</option>
                <option value="buyer">Buyer</option>
                <option value="seller">Seller</option>
            </select>
        </div>

        <div class="terms-container">
            <input type="checkbox" name="terms" id="terms" onchange="validateForm()">
            <label for="terms">I agree to the <a href="#termsModal" onclick="openTermsModal()">Terms and Conditions</a> and have read the <a href="private.policy.php" target="_blank">Privacy Policy</a></label>
        </div>

        <button type="submit" class="btn-custom" id="registerButton" disabled>Register</button>
    </form>

    <div class="register-back-container">
        <a href="LoginPage.php" class="back-button">Back to Login</a>
    </div>
    </div>

<div id="termsModal" class="terms-modal">
    <div class="terms-modal-content">
        <span class="terms-close-button" onclick="closeTermsModal()">&times;</span>
        <h3>Terms and Conditions</h3>
        <div class="terms-content">
            <p>By registering for an account and proceeding with any purchase on **QuickBuy**, you agree to be bound by the following terms and conditions:</p>

            <h4>1. General Agreement</h4>
            <ul>
                <li>We may collect and view your activities within the website to improve our services and ensure compliance.</li>
                <li>You are responsible for the accuracy and legality of the content you post.</li>
                <li>We reserve the right to remove any content that violates our guidelines or is deemed inappropriate.</li>
                <li>These terms and conditions may be updated from time to time without prior notice. Your continued use of the website constitutes acceptance of any changes.</li>
                <li>You agree not to engage in any activity that could harm the website or its users.</li>
                <li>All intellectual property rights related to the website belong to us.</li>
            </ul>

            <h4>2. Seller Specific Terms (if applicable)</h4>
            <ul>
                <li>A 5% commission fee will be added to the price of all items you submit for viewing and display on the platform. This fee will be clearly outlined during the listing process.</li>
            </ul>

            <h4>3. Item Accuracy & No Refunds on Discrepancies</h4>
            <ul>
                <li>We strive to display our products as accurately as possible. However, variations in color, size, texture, and other characteristics may occur due to monitor settings, photographic lighting, and the inherent nature of certain products.</li>
                <li><strong>No Refunds on Purchased Items if Items are Not As Displayed:</strong> While we endeavor to provide accurate representations, please be aware that <strong>we do not offer refunds on purchased items solely because they may not appear exactly as displayed within the item lists.</strong> We encourage you to carefully review all product descriptions, images, and specifications before making a purchase. If you have any questions or require further clarification about an item, please contact the seller or our support team prior to placing your order.</li>
            </ul>

            <h4>4. Order Placement and Final Sale Policy</h4>
            <ul>
                <li>Once an order is placed and confirmed, it is considered final.</li>
                <li><strong>Buyers are solely responsible for ensuring that they have selected the correct items they wish to purchase before completing their order.</strong> We strongly advise you to carefully review your cart contents, quantities, and selected variations before proceeding to checkout.</li>
                <li><strong>All sales are final, and we do not offer refunds or exchanges on items once an order has been 
            </ul>
        </div>
        <button type="button" onclick="closeTermsModal()">Close</button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        validateForm(); // Initial validation on page load
    });

    function togglePassword(id) {
        const input = document.getElementById(id);
        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }

    function validateForm() {
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const phone = document.getElementById('phone').value.trim();
        const role = document.getElementById('role').value;
        const terms = document.getElementById('terms').checked;
        const registerButton = document.getElementById('registerButton');

        let isValid = true;

        // Check if all fields are filled
        if (username === '' || email === '' || password === '' || confirmPassword === '' || phone === '' || role === '') {
            isValid = false;
        }

        // Validate email format (basic check)
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            isValid = false;
        }

        // Validate passwords match
        if (password !== confirmPassword) {
            isValid = false;
        }

        // Validate password strength
        const passwordRegex = /^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,}$/;
        if (!passwordRegex.test(password)) {
            isValid = false;
        }

        // Validate terms and conditions
        if (!terms) {
            isValid = false;
        }

        registerButton.disabled = !isValid;
    }

    function handlePasswordInput() {
        const passwordField = document.getElementById('password');
        const confirmContainer = document.getElementById('confirmContainer');

        // Show confirm password field once user starts typing in password
        if (passwordField.value.length > 0) {
            confirmContainer.classList.remove('hidden');
        } else {
            // Optional: Hide if password field becomes empty.
            // If confirm password is always required, remove this else block.
            confirmContainer.classList.add('hidden');
        }
        validateForm(); // Re-validate after password input changes
    }

    // Call handlePasswordInput on load to correctly set visibility if there's pre-filled data
    document.addEventListener('DOMContentLoaded', handlePasswordInput);


    // Modal functions
    function openTermsModal() {
        const modal = document.getElementById('termsModal');
        modal.style.display = 'block';
    }

    function closeTermsModal() {
        const modal = document.getElementById('termsModal');
        modal.style.display = 'none';
    }

    // Close the modal if the user clicks anywhere outside of the modal content
    window.onclick = function(event) {
        const modal = document.getElementById('termsModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>
</body>
</html>
