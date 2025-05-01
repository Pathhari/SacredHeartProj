<?php
// Start session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: log-in.php");
    exit;
}


if ($_SESSION['role'] != 'user') {
    header('Location: unathorized.php'); // Redirect to an unauthorized access page
    exit;
}

// Initialize variables to avoid undefined errors
$update_success = "";
$error_message = "";
$firstname = isset($_SESSION["firstname"]) ? $_SESSION["firstname"] : "";
$lastname = isset($_SESSION["lastname"]) ? $_SESSION["lastname"] : "";
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "";
$address = "";

// Include database connection file
include 'db.php';

// Fetch the user's details from the database (including address and password)
$sql = "SELECT FirstName, LastName, Email, Address, Password, Gender FROM Users WHERE UserID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION["userid"]);
    if ($stmt->execute()) {
        $stmt->bind_result($firstname, $lastname, $email, $address, $hashed_password, $gender);
        $stmt->fetch();
    }
    $stmt->close();
}

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get new values from form submission
    $new_firstname = trim($_POST["firstname"]);
    $new_lastname = trim($_POST["lastname"]);
    $new_email = trim($_POST["email"]);
    $new_address = trim($_POST["address"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Validate the inputs
    if (empty($new_firstname) || empty($new_lastname) || empty($new_email) || empty($new_address)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // If a new password is set, hash it
        if (!empty($new_password)) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            // Keep the old password if the new one is not provided
            $new_hashed_password = $hashed_password;
        }

        // Prepare the update statement
        $sql = "UPDATE Users SET FirstName = ?, LastName = ?, Email = ?, Address = ?, Password = ? WHERE UserID = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssi", $new_firstname, $new_lastname, $new_email, $new_address, $new_hashed_password, $_SESSION["userid"]);

            if ($stmt->execute()) {
                // Update session variables to reflect the new values
                $_SESSION["firstname"] = $new_firstname;
                $_SESSION["lastname"] = $new_lastname;
                $_SESSION["email"] = $new_email;

                // Set success message
                $update_success = "Profile updated successfully!";
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
            $stmt->close();
        }
    }

    
    // Close the connection
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Sacred Heart Parish</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Gotham', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background-color: #E85C0D;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            color: white;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .header .logo-container {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .header img {
            height: 50px;
            width: auto;
            margin-right: 15px;
        }

        .header h1 {
            font-size: 1.75rem;
            margin: 0;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icons i {
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.25rem;
                white-space: normal;
            }
            .header-icons i {
                font-size: 1.25rem;
            }
        }
        .sidebar {
            background-color: #2d3748;
            width: 220px;
            height: 100vh;
            padding-top: 20px;
            color: white;
            transition: width 0.3s ease;
            position: fixed;
            top: 80px;
            left: 0;
            z-index: 999;
        }


        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            transition: background-color 0.3s;
        }

        .sidebar a i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .sidebar.collapsed a span {
            display: none;
        }

        .sidebar.collapsed i {
            margin-right: 0;
        }

        .sidebar a:hover {
            background-color: #4a5568;
        }

        .toggle-btn {
            position: absolute;
            top: 50%;
            right: 0;
            background-color: #2d3748;
            color: white;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 1000;
            transform: translateY(-50%);
            transition: right 0.3s ease;
        }


        .sidebar.collapsed {
            width: 60px;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 220px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 60px;
        }

     .form-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 30px;
            width: 100%;
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            display: inline-block;
        }

        .form-control {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            width: 100%;
        }

        .form-control:focus {
            border-color: #E85C0D;
            outline: none;
            box-shadow: 0 0 0 3px rgba(232, 92, 13, 0.2);
        }

        .btn-save {
            background-color: #E85C0D;
            color: white;
            padding: 12px 35px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            border: none;
        }

        .btn-save:hover {
            background-color: #CC4A05;
            transform: translateY(-3px);
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-cancel {
            background-color: #f4f4f4;
            color: black;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            border: none;
            margin-right: 10px;
        }

        .btn-cancel:hover {
            background-color: #ddd;
        }

        .alert-success, .alert-error {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
        }

        .alert-success {
            color: green;
            background-color: #e6ffe6;
        }

        .alert-error {
            color: red;
            background-color: #ffe6e6;
        }
        
        .profile-section {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 8px;
            overflow: hidden;
        }

        .profile-dropdown a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .profile-dropdown a:hover {
            background-color: #f1f1f1;
        }

        .profile-section:hover .profile-dropdown {
            display: block;
        }

        .profile-dropdown a[href="log-out.php"]:hover {
            color: red;
        }

        .notifications-window {
            position: absolute;
            right: 20px;
            top: 60px;
            width: 300px;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 1000;
        }

        .notifications-header {
            background-color: #329cc9;
            color: white;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }

        .notifications-content {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Header -->
    <header class="header">
    <div class="logo-container">
        <img src="imgs/mainlogo.png" alt="Parish Logo" class="h-10 w-auto mr-3">
        <h1 class="text-2xl font-bold">Sacred Heart of Jesus Parish</h1>
    </div>
    <div class="header-icons">
        <i class="fas fa-bell fa-2x" onclick="toggleNotifications()" title="Notifications"></i>
        <div class="profile-section">
            <i class="fas fa-user-circle fa-2x"></i>
            <div class="profile-dropdown">
                <a href="user_dashboardeditprofile.php">Edit Profile</a>
                <a href="log-out.php">Log Out</a>
            </div>
        </div>
    </div>

    <div class="notifications-window" id="notificationsWindow">
        <div class="notifications-header">
            Notifications
        </div>
        <div class="notifications-content">
            <div class="notification-item">Your baptism request has been approved.</div>
            <div class="notification-item">Your confirmation request is pending review.</div>
            <div class="notification-item">The schedule for your marriage sacrament has been updated.</div>
        </div>
    </div>
    </header>

    <!-- Sidebar & Content -->
    <div class="flex">
        <!-- Sidebar -->
        <nav class="sidebar collapsed" id="sidebar">
            <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="sacraments.php"><i class="fas fa-cross"></i> <span>Sacraments</span></a>
            <a href="calendar.php"><i class="fas fa-calendar-alt"></i> <span>Calendar</span></a>
            <a href="announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>

            <!-- Sidebar Toggle Button -->
            <div class="toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
                <i class="fas fa-chevron-right"></i>
            </div>
        </nav>

        <main class="flex-grow main-content">
            <div class="form-container">
                <h2 class="text-xl font-bold mb-6">Edit Profile</h2>

                <!-- Display success or error messages -->
                <?php if ($update_success): ?>
                    <div class="alert-success"><?php echo $update_success; ?></div>
                <?php elseif ($error_message): ?>
                    <div class="alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="save_profile.php">
                    <div class="form-group">
                        <label for="firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="gender" class="form-label">Gender</label>
                        <input type="text" class="form-control" name="gender" value="<?php echo htmlspecialchars($gender); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password (optional)</label>
                        <input type="password" class="form-control" name="new_password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password">
                    </div>

                    <div class="btn-container">
                        <a href="user_dashboard.php" class="btn-cancel">Cancel</a>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>



    <script>
        // Collapse the sidebar by default on page load
        document.addEventListener("DOMContentLoaded", function() {
            var sidebar = document.getElementById('sidebar');
            var toggleBtn = document.getElementById('sidebarToggle');
            sidebar.classList.add('collapsed');
            toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        });

        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var toggleBtn = document.getElementById('sidebarToggle');
            sidebar.classList.toggle('collapsed');
            toggleBtn.classList.toggle('collapsed');
            
            // Change arrow direction based on sidebar state
            if (sidebar.classList.contains('collapsed')) {
                toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            } else {
                toggleBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            }
        }

        function toggleNotifications() {
            var notifWindow = document.getElementById('notificationsWindow');
            if (notifWindow.style.display === 'none' || notifWindow.style.display === '') {
                notifWindow.style.display = 'block';
            } else {
                notifWindow.style.display = 'none';
            }
        }

        // Close the notification window if clicked outside
        window.onclick = function(event) {
            var notifWindow = document.getElementById('notificationsWindow');
            if (!event.target.matches('.fa-bell') && !notifWindow.contains(event.target)) {
                notifWindow.style.display = 'none';
            }
        }
    </script>

</body>
</html>
