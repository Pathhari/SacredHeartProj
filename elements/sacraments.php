<?php
// Start session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: log-in.php");
    exit;
}

// Fetch user details from session, with default values if they are not set
$firstname = isset($_SESSION["firstname"]) ? $_SESSION["firstname"] : "User";
$lastname = isset($_SESSION["lastname"]) ? $_SESSION["lastname"] : "";
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "Not Available";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacraments Request - Sacred Heart Parish</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- jQuery Library (Essential for AJAX and DOM Manipulation) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS (Optional, if you plan to use Bootstrap components) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle (Includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Gotham', sans-serif;
            background-color: #fffaf0; /* Light orange background */
        }

        /* Header */
        .header {
            background-color: #E85C0D; /* Deep orange */
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            color: white;
            flex-wrap: wrap;
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
            white-space: nowrap;
            flex-shrink: 1;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }



        /* Sidebar */
        .sidebar {
    background-color: #2d3748;
    width: 220px;
    min-height: 100vh; /* Change height to min-height */
    padding-top: 20px;
    color: white;
    transition: width 0.3s ease;
    position: relative;
    flex-shrink: 0; /* Ensure sidebar doesn't shrink */
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

        .sidebar a.active {
            background-color: #F6AD55; /* Orange */
            color: white;
        }

        .sidebar a.active i {
            color: white;
        }
        .flex {
    display: flex;
    min-height: 100vh; /* Ensure the container takes the full viewport height */
}
        /* Sidebar Toggle Button */
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

        .sidebar.collapsed .toggle-btn {
            right: -15px;
        }

        /* Profile Dropdown */
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
        /* Notification styles */
        .notifications-window {
    position: fixed;
    right: 20px;
    top: 60px;
    width: 320px;
    background-color: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    display: none;
    z-index: 1000;
}

.notifications-header {
    background-color: #ff9800;
    color: #fff;
    padding: 15px;
    border-radius: 12px 12px 0 0;
    font-weight: bold;
    font-size: 1.1rem;
}

.notifications-content {
    max-height: 350px;
    overflow-y: auto;
}

.notification-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background-color: #ffe0b2;
} 

/* Smooth scrolling for modern feel */
.notifications-content {
    scroll-behavior: smooth;
}

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: center;
                padding: 10px 20px;
            }

            .header h1 {
                font-size: 1.25rem;
                white-space: normal;
            }

            .header-icons {
                gap: 10px;
                justify-content: space-between;
                width: 100%;
                margin-top: 10px;
            }

            .header-icons span {
                font-size: 1rem;
            }

            .header-icons i {
                font-size: 1.2rem;
            }
        }
        /* Button Styling for Sacraments */
        .sacrament-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
            background-color: #FFEDD5; /* Light orange background */
            border: 2px solid #FBD38D; /* Lighter orange border */
            color: #2d3748;
            min-height: 200px;
            transition: all 0.3s ease-in-out;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sacrament-button:hover {
            background-color: #FEEBC8; /* Slightly darker orange on hover */
            border-color: #F6AD55;
            color: #2d3748;
        }

        .sacrament-icon {
            font-size: 3rem;
            color: #E85C0D; /* Orange for icons */
        }

        .sacrament-title {
            margin-top: 10px;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
        }

        .sacrament-description {
            font-size: 0.875rem;
            text-align: center;
            margin-top: 10px;
            color: #718096;
        }

     

    </style>
</head>
<body class="bg-gray-100">

<header class="header">
    <div class="logo-container">
        <img src="imgs/mainlogo.png" alt="Parish Logo" class="h-10 w-auto mr-3">
        <h1 class="text-2xl font-bold">Sacred Heart of Jesus Parish</h1>
    </div>
    <div class="header-icons">
    <i class="fas fa-bell fa-2x" onclick="toggleNotifications()" title="Notifications" style="position: relative;">
    <span id="notificationCount" style="display:none; position:absolute; top:-5px; right:-10px; background:red; color:white; border-radius:50%; padding:2px 6px; font-size:12px;">0</span>
</i>
        <span class="text-lg font-medium">Welcome, <?php echo htmlspecialchars($firstname); ?>!</span>

        <div class="profile-section">
            <i class="fas fa-user-circle fa-2x"></i>
            <div class="profile-dropdown">
                <a href="user_dashboardeditprofile.php">Edit Profile</a>
                <a href="log-out.php">Log Out</a>
            </div>
        </div>
    </div>
</header>

<!-- Sidebar -->
<div class="flex">
    <nav class="sidebar collapsed" id="sidebar">
        <a href="user_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="sacraments.php" class="active"><i class="fas fa-cross"></i> <span>Sacraments</span></a>
        <a href="calenderuser.php"><i class="fas fa-calendar-alt"></i> <span>Calendar</span></a>
        <a href="announcementsuser.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>

        <!-- Sidebar Toggle Button -->
        <div class="toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-chevron-right"></i>
        </div>
    </nav>

    <div class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-semibold mb-6 text-gray-800">Sacraments</h2>

    <!-- Welcome Text -->
    <p class="mb-6 text-lg leading-relaxed text-gray-800 bg-gray-100 p-4 rounded-lg shadow-sm border border-gray-300">
    Below are the <strong class="text-orange-600">Sacraments offered by our parish.</strong>
        Please select the sacrament you wish to request and follow the steps to complete the request.
    </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Baptism Button -->
            <a href="baptism_request.php" class="sacrament-button">
                <i class="fas fa-water sacrament-icon"></i>
                <span class="sacrament-title">Baptism</span>
                <p class="sacrament-description">The sacrament of initiation into the Christian faith.</p>
            </a>

            <!-- Wedding Button -->
            <a href="wedding_request.php" class="sacrament-button">
                <i class="fas fa-ring sacrament-icon"></i>
                <span class="sacrament-title">Wedding</span>
                <p class="sacrament-description">The holy union of marriage in the church.</p>
            </a>

            <!-- Confirmation Button -->
            <a href="confirmation_request.php" class="sacrament-button">
                <i class="fas fa-dove sacrament-icon"></i>
                <span class="sacrament-title">Confirmation</span>
                <p class="sacrament-description">A sacrament of strengthening one's faith through the Holy Spirit.</p>
            </a>

            <!-- First Communion Button -->
            <a href="firstcommunion_request.php" class="sacrament-button">
                <i class="fas fa-bread-slice sacrament-icon"></i>
                <span class="sacrament-title">First Communion</span>
                <p class="sacrament-description">The first reception of the Eucharist, the body of Christ.</p>
            </a>

            <!-- Anointing of the Sick Button -->
            <a href="anointing_request.php" class="sacrament-button">
                <i class="fas fa-hand-holding-medical sacrament-icon"></i>
                <span class="sacrament-title">Anointing of the Sick</span>
                <p class="sacrament-description">A sacrament of healing for those who are seriously ill or near death.</p>
            </a>

            <!-- Funeral and Burial Button -->
            <a href="funeralandburialrequest.php" class="sacrament-button">
                <i class="fas fa-cross sacrament-icon"></i>
                <span class="sacrament-title">Funeral and Burial</span>
                <p class="sacrament-description">A service to honor the life and faith of the deceased.</p>
            </a>

            <!-- Blessing Button -->
            <a href="blessing_request.php" class="sacrament-button">
                <i class="fas fa-praying-hands sacrament-icon"></i>
                <span class="sacrament-title">Blessing</span>
                <p class="sacrament-description">A special blessing for individuals or objects.</p>
            </a>
        </div>
    </div>
</div>

<!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow" style="display:none;">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content"></div>
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
        
        if (sidebar.classList.contains('collapsed')) {
            toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        } else {
            toggleBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        }
    }

    $(document).ready(function() {
        // Fetch notifications count every 30 seconds
        setInterval(fetchNotificationsCount, 30000); // 30 seconds

        fetchNotificationsCount();
    });

    // Function to fetch notifications count
    function fetchNotificationsCount() {
        $.ajax({
            url: 'fetch_notifications.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    var notificationsCount = data.notifications.length;
                    if (notificationsCount > 0) {
                        // Update the notification count badge
                        $('#notificationCount').text(notificationsCount).show();
                    } else {
                        $('#notificationCount').hide();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching notifications:', error);
            }
        });
    }

    function toggleNotifications() {
        var notifWindow = document.getElementById('notificationsWindow');
        if (notifWindow.style.display === 'none' || notifWindow.style.display === '') {
            // Fetch and display notifications
            fetchNotifications();
            notifWindow.style.display = 'block';
        } else {
            notifWindow.style.display = 'none';
        }
    }

    function fetchNotifications() {
        $.ajax({
            url: 'fetch_notifications.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    var notificationsContent = $('.notifications-content');
                    notificationsContent.empty(); // Clear existing notifications

                    if (data.notifications.length > 0) {
                        data.notifications.forEach(function(notification) {
                            var notificationItem = $('<div class="notification-item"></div>').text(notification.NotificationText);
                            notificationsContent.append(notificationItem);
                        });
                    } else {
                        notificationsContent.append('<div class="notification-item">No new notifications.</div>');
                    }

                    // Mark notifications as read
                    markNotificationsAsRead();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching notifications:', error);
            }
        });
    }

    function markNotificationsAsRead() {
        $.ajax({
            url: 'mark_notifications_read.php',
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    // Hide the notification count badge
                    $('#notificationCount').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error marking notifications as read:', error);
            }
        });
    }

    // Hide notification window when clicking outside
    window.onclick = function(event) {
        var notifWindow = document.getElementById('notificationsWindow');
        if (!event.target.matches('.fa-bell') && !notifWindow.contains(event.target)) {
            notifWindow.style.display = 'none';
        }
    }
</script>

</body>
</html>
