<?php
session_start();

// Check if the session is set, if not redirect to the login page
if (!isset($_SESSION['username'])) {
    header('Location: log-in.php'); // Redirect to login page if session does not exist
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f9f9f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            text-align: center;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-top: 8px solid #ff6a00; /* Orange border */
        }

        .container h1 {
            font-size: 72px;
            margin: 0;
            color: #ff6a00; /* Orange color */
        }

        .container h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
        }

        .container p {
            font-size: 18px;
            margin-bottom: 30px;
            color: #555;
        }

        .container a {
            text-decoration: none;
            padding: 12px 30px;
            background-color: #ff6a00; /* Orange button */
            color: white;
            font-size: 18px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .container a:hover {
            background-color: #e65a00; /* Darker orange on hover */
        }

        .background-decor {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: -1;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>403</h1>
        <h2>Access Denied</h2>
        <p>You do not have permission to view this page.</p>
        <a href="log-in.php">Go Back to Login</a>
    </div>

    <div class="background-decor"></div>

</body>
</html>
