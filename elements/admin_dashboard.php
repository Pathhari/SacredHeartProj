<?php
// Start session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: log-in.php");
    exit;
}

if ($_SESSION['role'] != 'admin') {
    header('Location: unauthorized.php'); // Redirect to an unauthorized access page
    exit;
}

// Fetch user details from session, with default values if they are not set
$firstname = isset($_SESSION["firstname"]) ? $_SESSION["firstname"] : "User";
$lastname = isset($_SESSION["lastname"]) ? $_SESSION["lastname"] : "";

// Include database connection file
include 'db.php';

// Date range handling
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d', strtotime('-1 month'));
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');

// Fetch the total number of users with the role 'user'
$sql_total_users = "SELECT COUNT(UserID) AS total_users FROM Users WHERE Role = 'user'";
$result_total_users = $conn->query($sql_total_users);
$totalUsers = $result_total_users->fetch_assoc()['total_users'];

// Fetch pending, approved, and rejected requests
$sql_pending_requests = "SELECT COUNT(RefNo) AS pending_requests FROM SacramentRequests WHERE Status = 'Pending'";
$result_pending_requests = $conn->query($sql_pending_requests);
$pendingRequests = $result_pending_requests->fetch_assoc()['pending_requests'];

$sql_approved_requests = "SELECT COUNT(RefNo) AS approved_requests FROM SacramentRequests WHERE Status = 'Approved'";
$result_approved_requests = $conn->query($sql_approved_requests);
$approvedRequests = $result_approved_requests->fetch_assoc()['approved_requests'];

$sql_rejected_requests = "SELECT COUNT(RefNo) AS rejected_requests FROM SacramentRequests WHERE Status = 'Rejected'";
$result_rejected_requests = $conn->query($sql_rejected_requests);
$rejectedRequests = $result_rejected_requests->fetch_assoc()['rejected_requests'];

// Fetch the number of sacrament requests per type
$sql_requests_per_type = "SELECT SacramentType, COUNT(RefNo) AS count 
                          FROM SacramentRequests 
                          WHERE CreatedAt BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                          GROUP BY SacramentType";
$result_requests_per_type = $conn->query($sql_requests_per_type);

$requestsPerType = [];
while ($row = $result_requests_per_type->fetch_assoc()) {
    $requestsPerType[$row['SacramentType']] = (int)$row['count'];
}

// Fetch the number of requests over the past months
$sql_requests_over_time = "SELECT DATE_FORMAT(CreatedAt, '%Y-%m') AS month, COUNT(RefNo) AS count 
                           FROM SacramentRequests 
                           WHERE CreatedAt BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                           GROUP BY month 
                           ORDER BY month";
$result_requests_over_time = $conn->query($sql_requests_over_time);

$requestsOverTime = [];
while ($row = $result_requests_over_time->fetch_assoc()) {
    $requestsOverTime[$row['month']] = (int)$row['count'];
}

// Fetch counts of statuses within date range for the doughnut chart
$sql_status_counts = "SELECT Status, COUNT(*) as count 
                      FROM SacramentRequests 
                      WHERE CreatedAt BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' 
                      GROUP BY Status";
$result_status_counts = $conn->query($sql_status_counts);

$statusCounts = array('Approved' => 0, 'Pending' => 0, 'Rejected' => 0);

while ($row = $result_status_counts->fetch_assoc()) {
    $status = $row['Status'];
    $count = (int)$row['count'];
    $statusCounts[$status] = $count;
}

// Calculate percentages
$totalRequestsInRange = array_sum($statusCounts);
$approvalPercentages = array();

if ($totalRequestsInRange > 0) {
    foreach ($statusCounts as $status => $count) {
        $approvalPercentages[$status] = round(($count / $totalRequestsInRange) * 100, 2);
    }
} else {
    $approvalPercentages = array('Approved' => 0, 'Pending' => 0, 'Rejected' => 0);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sacred Heart Parish</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        /* Main content styling */
        .main-content {
            padding: 20px;
        }

        .p-6 {
            padding: 1.5rem;
            max-width: 100%;
            overflow: auto;
        }
        .dashboard-overview {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 10px;
        }

        .dashboard-card {
            border-radius: 12px;
            padding: 30px;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1); /* Soft shadow for depth */
            transition: transform 0.3s, box-shadow 0.3s;
        }

        /* Hover Effect for a more interactive feel */
        .dashboard-card:hover {
            color: black;
            text-decoration: none;
            transform: translateY(-5px);
            box-shadow: 0px 15px 30px rgba(0, 0, 0, 0.2);
        }

        /* Gradient for each card */
        .card-blue {
            background: linear-gradient(135deg, #007bff, #00c6ff); /* Vibrant Blue Gradient */
        }

        .card-green {
            background: linear-gradient(135deg, #28a745, #85ffbd); /* Strong Green Gradient */
        }

        .card-yellow {
            background: linear-gradient(135deg, #f8c547, #f7e8a3); /* Modern Yellow Gradient */
        }

        .card-red {
            background: linear-gradient(135deg, #dc3545, #ff6b6b); /* Vibrant Red Gradient */
        }

        /* Charts Container */
        .charts-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 40px;
            gap: 40px;
        }
        .chart-card {
            flex: 1 1 45%;
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1);
        }
        .chart-card canvas {
            width: 100%;
            max-height: 400px;
        }
        /* Responsive layout */
        @media (max-width: 1024px) {
            .dashboard-overview {
                grid-template-columns: repeat(2, 1fr); /* 2 cards per row for medium screens */
            }
        }

        @media (max-width: 768px) {
            .dashboard-overview {
                grid-template-columns: repeat(1, 1fr); /* Stack the cards on top of each other for small screens */
            }
            .chart-card {
                flex: 1 1 100%;
            }
            .header {
                flex-direction: column;
                align-items: center;
                padding: 10px 20px;
                text-align: center;
            }
            .header h1 {
                font-size: 1.25rem;
                white-space: normal;
                text-align: center;
                width: 100%;
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
            .toggle-btn {
                display: block;
            }
            .header .logo-container img {
                height: 35px;
                margin-right: 5px;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1rem;
            }
            .header .logo-container img {
                height: 30px;
            }
            .toggle-btn {
                right: 10px;
                top: 15px;
            }
        }
  
        .charts-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
        }

        .chart-card {
            flex: 1 1 45%;
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1);
        }

        /* Responsive layout adjustments */
        @media (max-width: 1024px) {
            .dashboard-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            .chart-card {
                flex: 1 1 100%;
            }
        }

        @media (max-width: 768px) {
            .dashboard-overview {
                grid-template-columns: repeat(1, 1fr);
            }
            .chart-card {
                flex: 1 1 100%;
            }
        }

        

        @media (max-width: 768px) {
    .header {
                flex-direction: column;
                align-items: center;
                padding: 10px 20px;
                text-align: center; /* Center text within the header */
            }
    .header h1 {
                font-size: 1.25rem;
                white-space: normal;
                text-align: center; /* Center only the text */
        width: 100%; /* Ensure it takes full width for centering */
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

    .toggle-btn {
        display: block;
    }

    .header .logo-container img {
        height: 35px;
        margin-right: 5px;
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
    <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="manage_requestsadmin.php"><i class="fas fa-tasks"></i> <span>Manage Requests</span></a>
        <a href="manage_usersadmin.php"><i class="fas fa-users-cog"></i> <span>Manage Users</span></a>
        <a href="calendaradmin.php"><i class="fas fa-calendar-alt"></i> <span>Event Calendar</span></a>
        <a href="announcementsadmin.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>

        <!-- Sidebar Toggle Button -->
        <div class="toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-chevron-right"></i>
        </div>
    </nav>
 <!-- Main Content -->
 <div class="p-6 max-w-7xl mx-auto">
        <!-- Dashboard Overview Cards -->
        <div class="dashboard-overview">
    </a>
    <a href="manage_requestsadmin.php" class="dashboard-card card-blue">
        Pending Requests
        <div class="card-content">
            <?php echo $pendingRequests; ?>
        </div>
    </a>
    <a href="manage_requestsadmin.php" class="dashboard-card card-green">
        Approved Requests
        <div class="card-content">
            <?php echo $approvedRequests; ?>
        </div>
    </a>
    <a href="manage_usersadmin.php" class="dashboard-card card-yellow">
        Total Users
        <div class="card-content">
            <?php echo $totalUsers; ?>
        </div>
    <a href="manage_requestadmin.php" class="dashboard-card card-red">
        Rejected Requests
        <div class="card-content">
            <?php echo $rejectedRequests; ?>
        </div>
    </a>
</div>
 

<!-- Date Range Filter Form -->
<div class="container my-4">
    <div class="card shadow-sm p-4 border-0 rounded-3" style="background-color: #F5E7B2;">
        <h4 class="text-center mb-4" style="color: #4c4c4c;">Filter by Date Range</h4>
        <form method="GET" action="admin_dashboard.php" class="row g-3 justify-content-center">
            <div class="col-md-4">
                <label for="startDate" class="form-label" style="color: #4c4c4c;">Start Date</label>
                <input type="date" id="startDate" name="startDate" value="<?php echo $startDate; ?>" class="form-control form-control-lg rounded-pill shadow-sm" style="border-color: #E85C0D; background-color: #fdf2e9;">
            </div>
            <div class="col-md-4">
                <label for="endDate" class="form-label" style="color: #4c4c4c;">End Date</label>
                <input type="date" id="endDate" name="endDate" value="<?php echo $endDate; ?>" class="form-control form-control-lg rounded-pill shadow-sm" style="border-color: #E85C0D; background-color: #fdf2e9;">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-lg w-100 rounded-pill shadow-sm" style="background-color: #E85C0D; color: white;">Apply Filter</button>
            </div>
        </form>
    </div>
</div>



<div class="charts-container">
    <div class="chart-card">
        <canvas id="requestsPerTypeChart"></canvas>
    </div>
    <div class="chart-card">
        <canvas id="requestsOverTimeChart"></canvas>
    </div>
    <div class="chart-card">
        <canvas id="approvalPercentagesChart"></canvas>
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
// Data from PHP
var requestsPerTypeData = <?php echo json_encode($requestsPerType); ?>;
    var requestsOverTimeData = <?php echo json_encode($requestsOverTime); ?>;
    var approvalPercentages = <?php echo json_encode($approvalPercentages); ?>;

    // Process data for the Sacrament Requests per Type chart
    var types = Object.keys(requestsPerTypeData);
    var counts = Object.values(requestsPerTypeData);

    // Create the Sacrament Requests per Type Chart
    var ctx1 = document.getElementById('requestsPerTypeChart').getContext('2d');
    var requestsPerTypeChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: types,
            datasets: [{
                label: 'Number of Requests',
                data: counts,
                backgroundColor: [
                    '#4dc9f6', '#f67019', '#f53794', '#537bc4', '#acc236', '#166a8f', '#00a950', '#58595b'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Sacrament Requests per Type',
                    font: {
                        size: 18
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Process data for the Sacrament Requests Over Time Chart
    var months = Object.keys(requestsOverTimeData);
    var countsOverTime = Object.values(requestsOverTimeData);

    // Create the Sacrament Requests Over Time Chart
    var ctx2 = document.getElementById('requestsOverTimeChart').getContext('2d');
    var requestsOverTimeChart = new Chart(ctx2, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Number of Requests',
                data: countsOverTime,
                backgroundColor: 'rgba(75,192,192,0.4)',
                borderColor: '#4bc0c0',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Sacrament Requests Over Time',
                    font: {
                        size: 18
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    type: 'category',
                    title: {
                        display: true,
                        text: 'Month'
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Create Approval Percentages Doughnut Chart
    var ctx3 = document.getElementById('approvalPercentagesChart').getContext('2d');
    var approvalPercentagesChart = new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [approvalPercentages['Approved'], approvalPercentages['Pending'], approvalPercentages['Rejected']],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                hoverBackgroundColor: ['#218838', '#e0a800', '#c82333'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Approval Percentages of Sacraments',
                    font: {
                        size: 18
                    }
                },
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            var value = context.parsed;
                            return label + ': ' + value + '%';
                        }
                    }
                }
            }
        }
    });

     // Sidebar Toggle Function
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


    // Collapse the sidebar by default on page load
    document.addEventListener("DOMContentLoaded", function() {
        var sidebar = document.getElementById('sidebar');
        var toggleBtn = document.getElementById('sidebarToggle');
        sidebar.classList.add('collapsed');
        toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    });
</script>

</body>
</html>
