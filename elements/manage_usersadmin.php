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
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "Not Available";

// Include database connection file
include 'db.php';

// Set the number of records to display per page
$limit = 10;

// Get the current page number from the query string or default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch users from the database excluding 'admin' and 'staff' roles, with pagination
$sql = "SELECT UserID, FirstName, LastName, Role FROM Users 
        WHERE Role NOT IN ('admin') 
        AND Deleted = 0
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Count the total number of users excluding 'admin' and 'staff'
$sql_count = "SELECT COUNT(*) as total FROM Users WHERE Role NOT IN ('admin') AND Deleted = 0";
$count_result = $conn->query($sql_count);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <style>
        /* Global Styles */
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

        /* Main Content */
        .main-content {
            padding: 20px;
            margin-top: 80px; /* Adjusted for fixed header */
            margin-left: 220px; /* Width of the sidebar */
        }

        /* Table Container */
        .table-container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .table-container h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #E85C0D; 
            font-weight: bold;
        }

        table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px; 
    }

    th, td {
        text-align: left;
        padding: 12px 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    th {
        background-color: #f7fafc;
        font-weight: bold;
        color: #4a5568;
        font-size: 0.95rem;
    }

    td {
        font-size: 0.9rem;
        color: #4a5568;
    }

        .action-buttons a {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        td:first-child {
        width: 45%; 
    }

    td:nth-child(2) {
        width: 32%;
    }

    td:nth-child(3) {
        width: 40%;
    }

        .welcome-text {
            background-color: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            color: #4a5568;
        }

        .welcome-text strong {
            color: #E85C0D;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px 10px;
            }
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
        .modal-header {
            background-color: #E85C0D; 
            color: white;
        }

        .modal-title {
            font-weight: bold;
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
        <a href="manage_usersadmin.php" class="active"><i class="fas fa-users-cog"></i> <span>Manage Users</span></a>
        <a href="calendaradmin.php"><i class="fas fa-calendar-alt"></i> <span>Event Calendar</span></a>
        <a href="announcementsadmin.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>

        <!-- Sidebar Toggle Button -->
        <div class="toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-chevron-right"></i>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="p-6 max-w-7xl mx-auto">
        <h2 class="text-3xl font-semibold mb-6 text-gray-800">Manage Users</h2>

        <!-- Welcome Text -->
        <p class="mb-6 text-lg leading-relaxed text-gray-800 bg-gray-100 p-4 rounded-lg shadow-sm border border-gray-300">
            Welcome to the <strong class="text-orange-600">Sacred Heart of Jesus Parish Management System</strong>!
            This dashboard provides an overview of the users registered in the system.
        </p>

        <!-- Table Container -->
        <div class="table-container">
            <h2 class="mb-4">User List</h2>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>User Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($row['Role'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="#" class="btn btn-info btn-sm view-user-btn" data-userid="<?php echo $row['UserID']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="#" class="btn btn-danger btn-sm delete-user-btn" data-userid="<?php echo $row['UserID']; ?>" data-username="<?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?>">
    <i class="fas fa-trash-alt"></i> Delete
</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <!-- Previous page link -->
        <li class="page-item <?php if ($page <= 1) { echo 'disabled'; } ?>">
            <a class="page-link" href="<?php if ($page > 1) { echo "?page=" . ($page - 1); } ?>">Previous</a>
        </li>

        <!-- Page numbers -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php if ($i == $page) { echo 'active'; } ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next page link -->
        <li class="page-item <?php if ($page >= $total_pages) { echo 'disabled'; } ?>">
            <a class="page-link" href="<?php if ($page < $total_pages) { echo "?page=" . ($page + 1); } ?>">Next</a>
        </li>
    </ul>
</nav>
        </div>
<!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow" style="display:none;">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content"></div>
</div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-white" id="userDetailsModalLabel">User Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Details will be loaded here -->
        <div id="modal-content-placeholder">
          Loading...
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="deleteUserName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>



<!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content">
        <!-- Notifications content here -->
    </div>
</div>

<!-- JavaScript -->
<!-- Include Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap Bundle JS includes Popper JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>

$(document).ready(function() {
    $('.view-user-btn').on('click', function(e) {
        e.preventDefault();
        var userID = $(this).data('userid');
        
        console.log("UserID to fetch:", userID);  // Debugging output

        // Show the modal
        var modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
        modal.show();

        // Fetch the user details via AJAX
        $.ajax({
url: 'fetch_users_details.php',
    type: 'GET',
    data: { id: userID },
    dataType: 'json',
    success: function(data) {
        console.log("AJAX response:", data);
        if (data.success) {
    var user = data.user; // For easier reference
    var htmlContent = '';
    htmlContent += '<div class="card mb-4">';
    htmlContent += '<div class="card-body">';
    htmlContent += '<h5 class="card-title text-orange-600">User Information</h5>';
    htmlContent += '<div class="row">';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Name:</strong> ' + user.FirstName + ' ' + user.LastName + '</div>';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Email:</strong> ' + user.Email + '</div>';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Gender:</strong> ' + user.Gender + '</div>';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Address:</strong> ' + user.Address + '</div>';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Role:</strong> ' + user.Role.charAt(0).toUpperCase() + user.Role.slice(1) + '</div>';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Member Since:</strong> ' + user.CreatedAt + '</div>';
    htmlContent += '</div>';
    htmlContent += '</div>';
    htmlContent += '</div>';
    $('#modal-content-placeholder').html(htmlContent);
} else {
    $('#modal-content-placeholder').html('<p>Error fetching details: ' + data.message + '</p>');
}
            },
            error: function(xhr, status, error) {
        console.log("AJAX Error:", xhr.responseText);
        $('#modal-content-placeholder').html('<p>Error fetching details.</p>');

            },
            dataType: 'json'
        });
    });
});

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
    let deleteUserId;
    let deleteUserRow;

    // Open delete confirmation modal
    $('.delete-user-btn').on('click', function(e) {
        e.preventDefault();
        deleteUserId = $(this).data('userid');
        deleteUserRow = $(this).closest('tr');
        const userName = $(this).data('username');
        
        // Set the username in the modal
        $('#deleteUserName').text(userName);
        
        // Show the modal
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        deleteModal.show();
    });

    // Confirm delete action
    $('#confirmDeleteBtn').on('click', function() {
        $.ajax({
            url: 'delete_user.php',
            type: 'POST',
            data: { id: deleteUserId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    deleteUserRow.remove();
                    const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
                    deleteModal.hide();
                } else {
                    alert('Failed to delete user.');
                }
            },
            error: function() {
                alert('Error deleting user.');
            }
        });
    });
});


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
