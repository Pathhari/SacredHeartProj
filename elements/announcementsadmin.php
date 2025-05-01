<?php
// Start session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: log-in.php");
    exit;
}

if ($_SESSION['role'] != 'admin') {
    header('Location: unauthorized.php');
    exit;
}

// Include database connection file
include 'db.php';

// Fetch user details from session, with default values if they are not set
$firstname = isset($_SESSION["firstname"]) ? $_SESSION["firstname"] : "User";
$lastname = isset($_SESSION["lastname"]) ? $_SESSION["lastname"] : "";
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "Not Available";

// Add new announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_announcement'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $announcement_date = date('Y-m-d');

    $sql = "INSERT INTO Announcement (UserID, Title, Content, AnnouncementDates) VALUES (?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isss", $_SESSION['userid'], $title, $content, $announcement_date);
        $stmt->execute();
        $stmt->close();
    }
    // Redirect to prevent form resubmission
    header("Location: announcementsadmin.php");
    exit;
}

// Delete announcement
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM Announcement WHERE AnnouncementID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    // Redirect to prevent resubmission
    header("Location: announcementsadmin.php");
    exit;
}

// Edit announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_announcement'])) {
    $id = intval($_POST['id']);
    $title = $_POST['title'];
    $content = $_POST['content'];

    $sql = "UPDATE Announcement SET Title = ?, Content = ? WHERE AnnouncementID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssi", $title, $content, $id);
        $stmt->execute();
        $stmt->close();
    }
    // Redirect to prevent resubmission
    header("Location: announcementsadmin.php");
    exit;
}

// Fetch announcement to edit
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM Announcement WHERE AnnouncementID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $announcement_to_edit = $result->fetch_assoc();
        $stmt->close();
    }
}

// Fetch all announcements
$announcements = [];
$sql = "SELECT * FROM Announcement ORDER BY AnnouncementDates DESC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sacred Heart Parish</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        /* Your existing styles */
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

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
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

        /* Sidebar */
        .sidebar {
            background-color: #2d3748;
            width: 220px;
            height: 100vh;
            padding-top: 20px;
            color: white;
            transition: width 0.3s ease;
            position: relative;
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

        /* Main content styling */
        .main-content {
            padding: 20px;
            flex-grow: 1;
        }

        /* Responsive layout */
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
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
                <a href="log-out.php">Log Out</a>
            </div>
        </div>
    </div>
</header>


<!-- Sidebar -->
<div class="flex">
    <nav class="sidebar collapsed" id="sidebar">
        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="manage_requestsadmin.php"><i class="fas fa-tasks"></i> <span>Manage Requests</span></a>
        <a href="manage_usersadmin.php"><i class="fas fa-users-cog"></i> <span>Manage Users</span></a>
        <a href="calendaradmin.php"><i class="fas fa-calendar-alt"></i> <span>Event Calendar</span></a>
        <a href="announcementsadmin.php"  class="active"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>

        <!-- Sidebar Toggle Button -->
        <div class="toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-chevron-right"></i>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mx-auto mt-8 px-4">
        <h2 class="text-2xl font-bold mb-4">Add New Announcement</h2>
         <!-- Welcome Text -->
         <p class="mb-6 text-lg leading-relaxed text-gray-800 bg-gray-100 p-4 rounded-lg shadow-sm border border-gray-300">
            Welcome to the <strong class="text-orange-600">Announcement Section!</strong>
            This dashboard provides an addition or edit on the announcement that can be seen by the user.
        </p>

            <?php if (isset($announcement_to_edit)): ?>
                <!-- Edit Announcement Form -->
                <h2 class="text-2xl font-bold mb-4">Edit Announcement</h2>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <form action="announcementsadmin.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $announcement_to_edit['AnnouncementID']; ?>">
                        <div class="mb-4">
                            <label for="title" class="block text-gray-700 font-semibold">Title</label>
                            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($announcement_to_edit['Title']); ?>" class="border border-gray-300 p-2 w-full rounded" required>
                        </div>
                        <div class="mb-4">
                            <label for="content" class="block text-gray-700 font-semibold">Content</label>
                            <textarea name="content" id="content" class="border border-gray-300 p-2 w-full rounded" required><?php echo htmlspecialchars($announcement_to_edit['Content']); ?></textarea>
                        </div>
                        <button type="submit" name="edit_announcement" class="bg-blue-500 text-white px-4 py-2 rounded">Save Changes</button>
                        <a href="announcementsadmin.php" class="bg-gray-500 text-white px-4 py-2 rounded ml-2">Cancel</a>
                    </form>
                </div>
            <?php else: ?>
                <!-- Add Announcement Form -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <form action="announcementsadmin.php" method="POST">
                        <div class="mb-4">
                            <label for="title" class="block text-gray-700 font-semibold">Title</label>
                            <input type="text" name="title" id="title" class="border border-gray-300 p-2 w-full rounded" required>
                        </div>
                        <div class="mb-4">
                            <label for="content" class="block text-gray-700 font-semibold">Content</label>
                            <textarea name="content" id="content" class="border border-gray-300 p-2 w-full rounded" required></textarea>
                        </div>
                        <button type="submit" name="add_announcement" class="bg-orange-500 text-white px-4 py-2 rounded">Add Announcement</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- List of Announcements -->
            <h2 class="text-2xl font-bold mb-4">Recent Announcements</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($announcement['Title']); ?></h3>
                        <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($announcement['Content'])); ?></p>
                        <p class="text-sm text-gray-500 mb-4">Date: <?php echo htmlspecialchars($announcement['AnnouncementDates']); ?></p>
                        <div class="flex space-x-2">
                            <a href="announcementsadmin.php?action=edit&id=<?php echo $announcement['AnnouncementID']; ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Edit</a>
                            <a href="announcementsadmin.php?action=delete&id=<?php echo $announcement['AnnouncementID']; ?>" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600" onclick="return confirm('Are you sure you want to delete this announcement?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
   <!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow" style="display:none;">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content"></div>
</div>
    </div>
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

    // Fetch notifications count on page load
    fetchNotificationsCount();
});

// Function to fetch notifications count
function fetchNotificationsCount() {
    $.ajax({
        url: 'fetch_admin_notifications.php',
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
  // Function to toggle the visibility of the notifications window
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
 // Function to fetch and display notifications
 function fetchNotifications() {
        $.ajax({
            url: 'fetch_admin_notifications.php',
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

    // Function to mark notifications as read
    function markNotificationsAsRead() {
        $.ajax({
            url: 'mark_admin_notifications_read.php',
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
