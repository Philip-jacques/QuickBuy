<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: LoginPage.php");
    exit();
}

require_once 'db.php';

// Query to fetch user login/logout statuses
// This query selects the last login and last logout for all users and admins,
// then combines them and orders by the last login time.
$sql = "SELECT
            u.id AS user_id,
            u.username AS username,
            u.role AS role,
            (SELECT login_time FROM login_logs WHERE user_id = u.id AND role = u.role ORDER BY login_time DESC LIMIT 1) AS last_login,
            (SELECT logout_time FROM login_logs WHERE user_id = u.id AND role = u.role ORDER BY login_time DESC LIMIT 1) AS last_logout
        FROM users u
        UNION ALL -- Use UNION ALL to avoid duplicate removal if user/admin IDs overlap (though unlikely)
        SELECT
            a.id AS user_id,
            a.username AS username,
            CASE
                WHEN a.rank = 'superadmin' THEN 'Super Admin'
                ELSE 'Admin'
            END AS role,
            (SELECT login_time FROM login_logs WHERE user_id = a.id AND role = 'admin' ORDER BY login_time DESC LIMIT 1) AS last_login,
            (SELECT logout_time FROM login_logs WHERE user_id = a.id AND role = 'admin' ORDER BY login_time DESC LIMIT 1) AS last_logout
        FROM admins a
        ORDER BY last_login DESC"; // Order the combined results

$user_statuses = [];
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $user_statuses[] = $row;
        }
    }
    $result->free(); // Free the result set
} else {
    // Handle query error
    error_log("Database query error in view_user_logs.php: " . $conn->error);  
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login Status - QuickBuy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            --ghost-white: #fafaffff;
            --delft-blue: #273469ff;
            --space-cadet: #1e2749ff;
            --paynes-gray: #5c677dff;
            --slate-gray: #7d8597ff;
            --cool-gray: #979dacff;
            --charcoal: #1b3a4bff;

            /* Accent from Browse Categories */
            --white-pop: #FFFFFF;
            --dark-font: #333;
            --light-font: #fefefe;

            /* Admin Dashboard Specific Colors (inherited) */
            --admin-bg-start: var(--deep-space-blue);
            --admin-bg-end: var(--midnight-green-3);
            --dashboard-card-bg: var(--white-pop);
            --card-border: var(--cool-gray);
            --header-color: var(--oxford-blue);
            --section-title-color: var(--sapphire);
            --text-color-primary: var(--dark-font);
            --text-color-secondary: var(--paynes-gray);
            --button-outline-secondary-bg: var(--ghost-white);
            --button-outline-secondary-text: var(--paynes-gray);
            --button-outline-secondary-border: var(--cool-gray);
            --button-outline-secondary-hover-bg: var(--paynes-gray);
            --button-outline-secondary-hover-text: var(--white-pop);
            --table-header-bg: var(--oxford-blue-2);
            --table-border-color: #e0e0e0;
            --table-row-even-bg: #fdfdfd;
            --shadow-light: rgba(0, 0, 0, 0.08);
            --shadow-medium: rgba(0, 0, 0, 0.15);
        }

        /* Universal Box-Sizing */
        html {
            box-sizing: border-box;
        }
        *, *::before, *::after {
            box-sizing: inherit;
        }

        body {
            background: linear-gradient(135deg, var(--admin-bg-start), var(--admin-bg-end));
            background-size: 300% 300%;
            animation: bgShift 20s ease infinite;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: var(--text-color-primary);
        }

        @keyframes bgShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 95%;
            margin: 30px auto;
            padding: 25px;
            background: var(--dashboard-card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            animation: fadeIn 0.8s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 2.2rem;
            color: var(--header-color);
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 70px;
            height: 3px;
            background-color: var(--true-blue);
            border-radius: 2px;
        }

        /* --- Table Styling --- */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .status-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background-color: var(--dashboard-card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px var(--shadow-light);
            border: 1px solid var(--table-border-color);
        }

        .status-table th, .status-table td {
            border: 1px solid var(--table-border-color);
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
            font-size: 0.88rem;
            color: var(--text-color-primary);
        }

        .status-table thead th {
            background-color: var(--table-header-bg);
            color: var(--light-font);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-bottom: 1px solid var(--table-header-bg);
        }

        .status-table tbody tr:nth-child(even) {
            background-color: var(--table-row-even-bg);
        }

        .status-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status colors */
        .status-online {
            color: var(--midnight-green); /* Green for online */
            font-weight: 600;
        }
        .status-offline {
            color: var(--paynes-gray); /* Grey for offline */
        }
        .status-na {
            color: #888; /* Slightly lighter grey for N/A */
        }


        /* --- Back Button --- */
        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.5;
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-secondary {
            background-color: transparent;
            color: var(--button-outline-secondary-text);
            border-color: var(--button-outline-secondary-border);
        }
        .btn-outline-secondary:hover {
            background-color: var(--button-outline-secondary-hover-bg);
            color: var(--button-outline-secondary-hover-text);
            border-color: var(--button-outline-secondary-hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }

        /* --- Empty state --- */
        .empty-message {
            text-align: center;
            padding: 30px;
            color: var(--text-color-secondary);
            font-size: 1rem;
        }

        /* --- Media Queries --- */
        @media (min-width: 768px) {
            .container {
                max-width: 900px;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 6px 20px var(--shadow-medium);
            }
            h2 {
                font-size: 2.5rem;
                margin-bottom: 40px;
            }
            h2::after {
                width: 90px;
            }
            .status-table th, .status-table td {
                font-size: 0.9rem;
                padding: 15px 18px;
            }
            .btn {
                padding: 12px 22px;
                font-size: 1rem;
            }
        }

        @media (max-width: 575px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 15px;
                border-radius: 8px;
            }
            h2 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }
            .status-table th, .status-table td {
                font-size: 0.75rem;
                padding: 8px 10px;
            }
            .status-table tbody tr {
                display: block; /* Make table rows stack on small screens */
                margin-bottom: 10px;
                border: 1px solid var(--table-border-color);
                border-radius: 8px;
                overflow: hidden;
            }
            .status-table thead {
                display: none; /* Hide table header on small screens */
            }
            .status-table tbody td {
                display: flex; /* Use flexbox for label-value pairing */
                justify-content: space-between; /* Space out label and value */
                padding: 8px 10px;
                border-bottom: 1px solid var(--table-border-color);
            }
            .status-table tbody td:last-child {
                border-bottom: none;
            }
            .status-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--section-title-color); /* Use a themed color for labels */
                margin-right: 10px;
            }
            /* Assign data-labels for stacked table cells */
            .status-table tbody td:nth-of-type(1)::before { content: "User:"; }
            .status-table tbody td:nth-of-type(2)::before { content: "Role:"; }
            .status-table tbody td:nth-of-type(3)::before { content: "Last Login:"; }
            .status-table tbody td:nth-of-type(4)::before { content: "Status:"; }

            .btn {
                padding: 8px 15px;
                font-size: 0.85rem;
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
    <h2>User Login Status</h2>

    <?php if (!empty($user_statuses)): ?>
        <div class="table-responsive">
            <table class="status-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_statuses as $user):
                        // Determine status based on last_login and last_logout
                        $status = 'Offline';
                        $status_class = 'status-offline';

                        if (!empty($user['last_login'])) {
                            // Check if last_logout is null OR if last_logout is older than last_login
                            // This means the user logged in more recently than they logged out, implying they are still online.
                            if (empty($user['last_logout']) || strtotime($user['last_logout']) < strtotime($user['last_login'])) {
                                $status = 'Online';
                                $status_class = 'status-online';
                            }
                        } else {
                            $status = 'N/A'; // No login record
                            $status_class = 'status-na';
                        }
                    ?>
                        <tr>
                            <td data-label="User:"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td data-label="Role:"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td data-label="Last Login:"><?php echo htmlspecialchars($user['last_login'] ?? 'N/A'); ?></td>
                            <td data-label="Status:" class="<?= $status_class ?>"><?php echo $status; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="empty-message">No user login records found.</p>
    <?php endif; ?>

    <p class="back-link"><a href="adminDashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a></p>
</div>

</body>
</html>
