<?php
// Start session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: log-in.php");
    exit;
}

// Check if the user has the correct role, if not redirect
if ($_SESSION['role'] != 'user') {
    header('Location: unauthorized.php'); // Redirect to an unauthorized access page
    exit;
}

// Fetch user details from session, with default values if they are not set
$firstname = isset($_SESSION["firstname"]) ? $_SESSION["firstname"] : "User";
$lastname = isset($_SESSION["lastname"]) ? $_SESSION["lastname"] : "";
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "Not Available";
$user_id = $_SESSION["userid"];

// Include database connection file
include 'db.php';

// Set the number of records to display per page
$limit = 8;



// Get the current page number from the query string or default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch user sacrament requests with pagination
$sql = "SELECT SR.RefNo, SR.SacramentType, P.PriestName AS AssignedPriest, SR.ScheduleDate, SR.CreatedAt, SR.Status
        FROM SacramentRequests SR
        LEFT JOIN Priests P ON SR.PriestID = P.PriestID
        WHERE SR.UserID = ? AND SR.Deleted = 0
        LIMIT ? OFFSET ?";
$requests = [];

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    } else {
        echo "Error executing query.";
    }
    $stmt->close();
} else {
    echo "Error preparing statement.";
}

// Count the total number of records for pagination
$sql_count = "SELECT COUNT(*) as total FROM SacramentRequests WHERE UserID = ? AND Deleted = 0";
$total_records = 0;

if ($stmt_count = $conn->prepare($sql_count)) {
    $stmt_count->bind_param("i", $user_id);
    if ($stmt_count->execute()) {
        $count_result = $stmt_count->get_result();
        $total_records = $count_result->fetch_assoc()['total'];
    }
    $stmt_count->close();
} else {
    echo "Error preparing count statement.";
}

$total_pages = ceil($total_records / $limit);



$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Sacred Heart Parish</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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

        /* Main content styling */
        .main-content {
            padding: 20px;
        }
    /* Table Styling */
    .table-container {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        text-align: left;
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    th {
        background-color: #f7fafc;
        font-weight: 600;
        color: #2d3748;
    }

    td {
        word-wrap: break-word;
        color: #4a5568;
    }

    .badge {
        font-size: 0.9rem;
    }

    .btn {
        transition: background-color 0.3s, transform 0.2s;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .pagination a {
        padding: 10px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        color: #2d3748;
        text-decoration: none;
        transition: background-color 0.2s ease;
    }

    .pagination a:hover {
        background-color: #f7fafc;
    }

    .pagination a.active {
        background-color: #e85c0d;
        color: white;
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
         /* Responsive Table */
    @media (max-width: 768px) {
        table thead {
            display: none;
        }

        table, table tbody, table tr, table td {
            display: block;
            width: 100%;
        }

        table tr {
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
        }

        table td {
            text-align: right;
            padding-left: 50%;
            position: relative;
        }

        table td::before {
            content: attr(data-label);
            position: absolute;
            left: 15px;
            width: calc(50% - 20px);
            font-weight: 600;
            text-align: left;
            color: #2d3748;
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

/* Modal Styles */
.modal-header {
    background-color: #E85C0D; /* Deep orange */
    color: white;
}

.modal-title {
    font-weight: bold;
}

/* Adjust card titles */
.card-title {
    font-weight: bold;
    color: #E85C0D; /* Match the deep orange color */
}

/* Adjust badge styles */
.badge {
    font-size: 0.9rem;
}

/* Adjust list group item */
.list-group-item {
    border: none;
    padding-left: 0;
}

/* Hover effect ONLY for uploaded documents */
.uploaded-documents-list .list-group-item a:hover {
    color: #0d6efd; /* Bootstrap primary blue color */
    text-decoration: none;
}

.uploaded-documents-list .list-group-item:hover {
    background-color: #e9f5ff; /* Light blue background */
}

/* Ensure godparents list items do NOT have hover effect */
.godfathers-list .list-group-item:hover,
.godmothers-list .list-group-item:hover {
    background-color: transparent;
}

/* Badges */
.badge {
    font-size: 0.9rem;
    transition: transform 0.2s;
}

.badge:hover {
    transform: scale(1.05);
}

/* Buttons */
.btn {
    transition: background-color 0.3s, transform 0.2s;
}

.btn:hover {
    transform: translateY(-2px);
}

/* Custom color for orange text */
.text-orange-600 {
    color: #E85C0D; /* Use the same color code as in the admin page */
}
.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: 100vh; /* Full viewport height for vertical centering */
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
        <a href="user_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="sacraments.php"><i class="fas fa-cross"></i> <span>Sacraments</span></a>
        <a href="calenderuser.php"><i class="fas fa-calendar-alt"></i> <span>Calendar</span></a>
        <a href="announcementsuser.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>

        <!-- Sidebar Toggle Button -->
        <div class="toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-chevron-right"></i>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="p-6 max-w-7xl mx-auto">
    <h2 class="text-3xl font-semibold mb-6 text-gray-800">Dashboard</h2>

    <!-- Welcome Text -->
    <p class="mb-6 text-lg leading-relaxed text-gray-800 bg-gray-100 p-4 rounded-lg shadow-sm border border-gray-300">
        Welcome to the <strong class="text-orange-600">Sacred Heart of Jesus Parish Management System</strong>!
        This dashboard provides an overview of the sacrament requests you have submitted.
        You can easily track the status of your requests, see which priest is assigned to each sacrament,
        and review or edit any pending requests. The table below summarizes your recent requests.
        If you have no requests, feel free to submit one through the Sacraments section on the sidebar.
    </p>

       
          <!-- Sacraments Request Table -->
          <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Ref No.</th>
                        <th>Sacrament Type</th>
                        <th>Assigned Priest</th>
                        <th>Schedule Date</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($requests)) {
                        foreach ($requests as $request) {
                            $scheduleDate = new DateTime($request['ScheduleDate']);
                            $createdAt = new DateTime($request['CreatedAt']);
                        
                            echo '<tr>';
                            echo '<td data-label="Ref No.">' . htmlspecialchars($request['RefNo']) . '</td>';
                            echo '<td data-label="Sacrament Type">' . htmlspecialchars($request['SacramentType']) . '</td>';
                            echo '<td data-label="Assigned Priest">' . htmlspecialchars($request['AssignedPriest']) . '</td>';
                            echo '<td data-label="Schedule Date">' . htmlspecialchars($scheduleDate->format('F j, Y')) . '</td>';
                            echo '<td data-label="Submitted At">' . htmlspecialchars($createdAt->format('F j, Y')) . '</td>';
                            
                            // Handle status with badges
                            echo '<td data-label="Status">';
                            if ($request['Status'] == 'Pending') {
                                echo '<span class="badge bg-warning text-dark">Pending</span>';
                            } elseif ($request['Status'] == 'Approved') {
                                echo '<span class="badge bg-success">Approved</span>';
                            } elseif ($request['Status'] == 'Rejected') {
                                echo '<span class="badge bg-danger">Rejected</span>';
                            } else {
                                echo htmlspecialchars($request['Status']);
                            }
                            echo '</td>';
                            
                        // Action buttons
                        echo '<td data-label="Actions">';
                        echo '<div class="btn-group" role="group">';
                        echo '<a href="#" class="btn btn-info btn-sm view-details-btn" data-ref="' . htmlspecialchars($request['RefNo']) . '" title="View"><i class="fas fa-eye"></i> View</a>';
                        if ($request['Status'] == 'Pending') {
                            echo '<a href="#" class="btn btn-primary btn-sm edit-request-btn" data-ref="' . htmlspecialchars($request['RefNo']) . '" title="Edit"><i class="fas fa-edit"></i> Edit</a>';
                        }
                        

                        if ($request['Status'] == 'Approved' || $request['Status'] == 'Rejected') {
                            echo '<a href="#" class="btn btn-danger btn-sm delete-request-btn" data-ref="' . htmlspecialchars($request['RefNo']) . '" title="Delete"><i class="fas fa-trash"></i> Delete</a>';
                        }

                        echo '</div>';
                        echo '</td>';

                            echo '</tr>';
                        }                        
                    } else {
                        echo '<tr>';
                        echo '<td colspan="7" class="text-center">No requests found</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <!-- Pagination -->
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <!-- Previous page link -->
        <li class="page-item <?php if ($page <= 1) { echo 'disabled'; } ?>">
            <a class="page-link" href="<?php if ($page > 1) { echo '?page=' . ($page - 1); } ?>">Previous</a>
        </li>

        <!-- Page numbers -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php if ($i == $page) { echo 'active'; } ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next page link -->
        <li class="page-item <?php if ($page >= $total_pages) { echo 'disabled'; } ?>">
            <a class="page-link" href="<?php if ($page < $total_pages) { echo '?page=' . ($page + 1); } ?>">Next</a>
        </li>
    </ul>
</nav>
        </div>

<!-- Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-labelledby="requestDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
      <h5 class="modal-title" id="requestDetailsModalLabel">Sacrament Request Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Details will be loaded here -->
        <div id="modal-content-placeholder">
          Loading...
        </div>
      </div>
      <div class="modal-footer">
    <div id="modal-action-buttons"></div>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
    </div>
  </div>
</div>

<!-- Edit Request Modal -->
<div class="modal fade" id="editRequestModal" tabindex="-1" aria-labelledby="editRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRequestModalLabel">Edit Sacrament Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Edit form will be loaded here -->
        <div id="edit-modal-content-placeholder">
          Loading...
        </div>
      </div>
      <div class="modal-footer">
        <!-- Action buttons -->
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveEditButton">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> <!-- Add modal-dialog-centered here -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this request?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>




<!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow" style="display:none;">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content"></div>
</div>


<!-- Include necessary JS files -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script>
    $(document).ready(function() {
    $('.view-details-btn').on('click', function(e) {
        e.preventDefault();
        var refNo = $(this).data('ref');

        // Show the modal
        var modal = new bootstrap.Modal(document.getElementById('requestDetailsModal'));
        modal.show();

        // Fetch the details via AJAX
        $.ajax({
            url: 'fetch_request_detailsusers.php',
            type: 'GET',
            data: { ref: refNo },
            beforeSend: function() {
                $('#modal-content-placeholder').html('<p>Loading...</p>');
                $('#modal-action-buttons').html(''); // Clear any previous action buttons
            },
            success: function(data) {
    if (data.success) {
        // Build the HTML content
        var htmlContent = '';

// General Information Card
// General Information Card
htmlContent += '<div class="card mb-4">';
htmlContent += '<div class="card-body">';
htmlContent += '<h5 class="card-title text-orange-600">General Information</h5>';
htmlContent += '<div class="row">';
htmlContent += '<div class="col-md-6 mb-2"><strong>Reference Number:</strong> ' + data.request.RefNo + '</div>';
htmlContent += '<div class="col-md-6 mb-2"><strong>Requester Name:</strong> ' + data.request.FirstName + ' ' + data.request.LastName + '</div>';
htmlContent += '<div class="col-md-6 mb-2"><strong>Sacrament Type:</strong> ' + data.request.SacramentType + '</div>';
htmlContent += '<div class="col-md-6 mb-2"><strong>Schedule Date:</strong> ' + new Date(data.request.ScheduleDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) + '</div>';
htmlContent += '<div class="col-md-6 mb-2"><strong>Schedule Time:</strong> ' + new Date("1970-01-01T" + data.request.ScheduleTime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) + '</div>';

// Check if AssignedPriest is available
if (data.request.AssignedPriest && data.request.AssignedPriest !== "") {
    // Remove ", SDB" if present
    var priestName = data.request.AssignedPriest.replace(', SDB', '');
    htmlContent += '<div class="col-md-6 mb-2"><strong>Assigned Priest:</strong> ' + priestName + '</div>';
} else {
    htmlContent += '<div class="col-md-6 mb-2"><strong>Assigned Priest:</strong> Not assigned</div>';
}

htmlContent += '<div class="col-md-6 mb-2"><strong>Submitted Date:</strong> ' + new Date(data.request.CreatedAt).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) + '</div>';
htmlContent += '<div class="col-md-6 mb-2"><strong>Status:</strong> <span class="badge ' + (data.request.Status === 'Approved' ? 'bg-success' : 'bg-warning text-dark') + '">' + data.request.Status + '</span></div>';
htmlContent += '</div>'; // End row
htmlContent += '</div>'; // End card body
htmlContent += '</div>'; // End card



        // Specific Details Card
        htmlContent += '<div class="card mb-4">';
        htmlContent += '<div class="card-body">';
        htmlContent += '<h5 class="card-title text-orange-600">Specific Details</h5>';

                    switch (data.request.SacramentType) {
                        case 'Baptism':
                            htmlContent += '<div class="row">';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Child Name:</strong> ' + data.details.ChildName + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Date of Birth:</strong> ' + data.details.ChildDOB + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Place of Birth:</strong> ' + data.details.ChildBPlace + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Father\'s Name:</strong> ' + data.details.FatherName + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Mother\'s Maiden Name:</strong> ' + data.details.MotherMName + '</div>';
                            htmlContent += '</div>'; // End row

                            // Display godparents side by side
                            if (data.godparents && data.godparents.length > 0) {
                                htmlContent += '<h6 class="mt-4 text-orange-600">Godparents</h6>';

                                // Separate godfathers and godmothers
                                var godfathers = [];
                                var godmothers = [];
                                data.godparents.forEach(function(gp) {
                                    if (gp.GodparentType.toLowerCase() === 'godfather') {
                                        godfathers.push(gp.GodparentName);
                                    } else if (gp.GodparentType.toLowerCase() === 'godmother') {
                                        godmothers.push(gp.GodparentName);
                                    }
                                });

                                // Display godparents in two columns
                                htmlContent += '<div class="row">';
                                htmlContent += '<div class="col-md-6">';
                                htmlContent += '<ul class="list-group list-group-flush godfathers-list">';
                                godfathers.forEach(function(name, index) {
                                    htmlContent += '<li class="list-group-item">Godfather ' + (index + 1) + ': ' + name + '</li>';
                                });
                                htmlContent += '</ul>';
                                htmlContent += '</div>'; // End col-md-6

                                htmlContent += '<div class="col-md-6">';
                                htmlContent += '<ul class="list-group list-group-flush godmothers-list">';
                                godmothers.forEach(function(name, index) {
                                    htmlContent += '<li class="list-group-item">Godmother ' + (index + 1) + ': ' + name + '</li>';
                                });
                                htmlContent += '</ul>';
                                htmlContent += '</div>'; // End col-md-6
                                htmlContent += '</div>'; // End row
                            }

                            // Display uploaded documents with hover effect
                            if (data.documents && data.documents.length > 0) {
                                htmlContent += '<h6 class="mt-4 card-title text-orange-600">Uploaded Documents</h6>';
                                htmlContent += '<ul class="list-group list-group-flush uploaded-documents-list">';
                                data.documents.forEach(function(doc) {
                                    htmlContent += '<li class="list-group-item"><a href="download_documentuser.php?doc_id=' + doc.DocumentID + '" target="_blank"><i class="fas fa-file-alt me-2"></i>' + doc.DocumentType + '</a></li>';
                                });
                                htmlContent += '</ul>';
                            } else {
                                htmlContent += '<p class="mt-3">No uploaded documents available.</p>';
                            }
                            break;

                        case 'Wedding':
                            htmlContent += '<div class="row">';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Applicant Name:</strong> ' + data.details.ApplicantName + '</div>';
                            htmlContent += '</div>'; // End row

                            // Display uploaded wedding documents with hover effect
                            if (data.documents && data.documents.length > 0) {
                                htmlContent += '<h6 class="mt-4 card-title text-orange-600">Uploaded Wedding Documents</h6>';
                                htmlContent += '<ul class="list-group list-group-flush uploaded-documents-list">';
                                data.documents.forEach(function(doc) {
                                    htmlContent += '<li class="list-group-item"><a href="download_documentuser.php?doc_id=' + doc.DocumentID + '" target="_blank"><i class="fas fa-file-alt me-2"></i>' + doc.DocumentType + '</a></li>';
                                });
                                htmlContent += '</ul>';
                            } else {
                                htmlContent += '<p class="mt-3">No uploaded wedding documents available.</p>';
                            }
                            break;

                            case 'Confirmation':
    htmlContent += '<div class="row">';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Full Name:</strong> ' + data.details.FullName + '</div>';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Father\'s Full Name:</strong> ' + data.details.FatherFullName + '</div>';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Mother\'s Full Name:</strong> ' + data.details.MotherFullName + '</div>';
    htmlContent += '<div class="col-md-6 mb-2"><strong>Residence:</strong> ' + data.details.Residence + '</div>';
    htmlContent += '</div>'; // End row

    // Display uploaded confirmation documents
    if (data.documents && data.documents.length > 0) {
    htmlContent += '<h6 class="mt-4 card-title text-orange-600">Uploaded Documents</h6>';
    htmlContent += '<ul class="list-group list-group-flush uploaded-documents-list">';
    data.documents.forEach(function(doc) {
                                    htmlContent += '<li class="list-group-item"><a href="download_documentuser.php?doc_id=' + doc.DocumentID + '" target="_blank"><i class="fas fa-file-alt me-2"></i>' + doc.DocumentType + '</a></li>';
                                });
    htmlContent += '</ul>';
} else {
    htmlContent += '<p class="mt-3">No uploaded documents available.</p>';
}
    break;

        case 'First Communion':
        htmlContent += '<div class="row">';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Child\'s Full Name:</strong> ' + data.details.FullNameChild + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Date of Birth:</strong> ' + data.details.DateOfBirth + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Place of Birth:</strong> ' + data.details.PlaceOfBirth + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Baptismal Date:</strong> ' + data.details.BaptismalDate + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Baptismal Parish:</strong> ' + data.details.BaptismalParish + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Father\'s Name:</strong> ' + data.details.FatherName + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Mother\'s Name:</strong> ' + data.details.MotherName + '</div>';
        htmlContent += '</div>'; // End row

        // Display uploaded documents for First Communion
        if (data.documents && data.documents.length > 0) {
            htmlContent += '<h6 class="mt-4 card-title text-orange-600">Uploaded Documents</h6>';
            htmlContent += '<ul class="list-group list-group-flush uploaded-documents-list">';
            data.documents.forEach(function(doc) {
                                    htmlContent += '<li class="list-group-item"><a href="download_documentuser.php?doc_id=' + doc.DocumentID + '" target="_blank"><i class="fas fa-file-alt me-2"></i>' + doc.DocumentType + '</a></li>';
                                });
            htmlContent += '</ul>';
        } else {
            htmlContent += '<p class="mt-3">No uploaded documents available.</p>';
        }
        break;

        case 'Anointing of the Sick':
        htmlContent += '<div class="row">';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Person\'s Full Name:</strong> ' + data.details.FullName + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Gender:</strong> ' + data.details.Gender + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Age:</strong> ' + data.details.Age + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Address:</strong> ' + data.details.Address + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Phone Number:</strong> ' + data.details.PhoneNumber + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Location of Anointing:</strong> ' + data.details.LocationOfAnointing + '</div>';
        htmlContent += '<div class="col-md-6 mb-2"><strong>Preferred Date and Time:</strong> ' + data.details.PreferredDateTime + '</div>';
        htmlContent += '</div>'; // End row

        // Display uploaded documents for Anointing of the Sick
        if (data.documents && data.documents.length > 0) {
            htmlContent += '<h6 class="mt-4 card-title text-orange-600">Uploaded Documents</h6>';
            htmlContent += '<ul class="list-group list-group-flush uploaded-documents-list">';
            data.documents.forEach(function(doc) {
                                    htmlContent += '<li class="list-group-item"><a href="download_documentuser.php?doc_id=' + doc.DocumentID + '" target="_blank"><i class="fas fa-file-alt me-2"></i>' + doc.DocumentType + '</a></li>';
                                });
            htmlContent += '</ul>';
        } else {
            htmlContent += '<p class="mt-3">No uploaded documents available.</p>';
        }
        break;

                    case 'Funeral and Burial':
                            // Funeral and Burial specific details
                            htmlContent += '<div class="row">';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Deceased Full Name:</strong> ' + data.details.DeceasedFullName + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Date of Birth:</strong> ' + data.details.DeceasedDateOfBirth + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Date of Death:</strong> ' + data.details.DeceasedDateOfDeath + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Age:</strong> ' + data.details.Age + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Cause of Death:</strong> ' + data.details.CDeath + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Address:</strong> ' + data.details.Address + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Burial Location:</strong> ' + data.details.BLocation + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Mass Time:</strong> ' + data.details.BMassTime + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Funeral Service Type:</strong> ' + data.details.FuneralServiceType + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Family Representative:</strong> ' + data.details.FamilyRepresentative + '</div>';
                            htmlContent += '</div>'; // End row
                            
                            
                            break;

                            case 'Blessing':
                            // Blessing-specific details
                            htmlContent += '<div class="row">';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Full Name:</strong> ' + data.details.FullName + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Address:</strong> ' + data.details.Address + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Contact Number:</strong> ' + data.details.RequesterContact + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Blessing Type:</strong> ' + data.details.BlessingType + '</div>';
                            htmlContent += '<div class="col-md-6 mb-2"><strong>Location of Blessing:</strong> ' + data.details.BlessingPlace + '</div>';
                            htmlContent += '</div>'; // End row

                            
                            break;


                        default:
                            htmlContent += '<p>No additional details available.</p>';
                    }

                    htmlContent += '</div>'; // End card body
                    htmlContent += '</div>'; // End card

                    $('#modal-content-placeholder').html(htmlContent);

            // Add action buttons based on status
                    var actionButtons = '';
                    
    
        if (data.request.Status === 'Approved' || data.request.Status === 'Rejected') {
    actionButtons += '<button class="btn btn-info me-2 view-feedback-btn" data-ref="' + encodeURIComponent(data.request.RefNo) + '"><i class="fas fa-comments me-1"></i> View Feedback</button>';
}
                    $('#modal-action-buttons').html(actionButtons);
                } else {
                    $('#modal-content-placeholder').html('<p>Error fetching details: ' + data.message + '</p>');
                }
            },
            error: function() {
                $('#modal-content-placeholder').html('<p>Error fetching details.</p>');
            },
            dataType: 'json' // Ensure the response is parsed as JSON
        });
    });
});
// Handle the click event for the "View Feedback" button
$(document).on('click', '.view-feedback-btn', function(e) {
    e.preventDefault();
    var refNo = $(this).data('ref');

    // Clear any previous content and show a loading message initially
    $('#modal-content-placeholder').html('<p class="text-center mt-4">Loading feedback...</p>');
    $('#feedbackModal').modal('show');

    // Fetch the feedback via AJAX
    $.ajax({
        url: 'fetch_feedback.php',
        type: 'GET',
        data: { ref: refNo },
        dataType: 'json',
        success: function(data) {
            if (data.success && data.feedback) {
                // Populate the modal with feedback content
                var feedbackContent = '<div class="card mt-4">';
                feedbackContent += '<div class="card-body">';
                feedbackContent += '<h5 class="card-title text-orange-600">Feedback</h5>';
                feedbackContent += '<p>' + data.feedback.FeedbackText + '</p>';
                feedbackContent += '<p><small class="text-muted">Submitted on: ' + data.feedback.SubmittedAt + '</small></p>';
                feedbackContent += '</div></div>';
                $('#modal-content-placeholder').html(feedbackContent);
            } else {
                // Show "No Feedback Available" message
                $('#modal-content-placeholder').html('<p class="text-center mt-4 text-warning">No Feedback Available</p>');
            }
        },
        error: function() {
            // Display a distinct error message in the modal
            $('#modal-content-placeholder').html('<div class="alert alert-danger text-center mt-4" role="alert">Unable to retrieve feedback at this time. Please try again later.</div>');
        }
    });
});

    $(document).on('click', '.edit-request-btn', function(e) {
            e.preventDefault();
            var refNo = $(this).data('ref');

            // Show the edit modal
            var modal = new bootstrap.Modal(document.getElementById('editRequestModal'));
            modal.show();

            // Fetch the request details via AJAX
            $.ajax({
                url: 'fetch_request_details_for_edit.php',
                type: 'GET',
                data: { ref: refNo },
                beforeSend: function() {
                    $('#edit-modal-content-placeholder').html('<p>Loading...</p>');
                },
                success: function(data) {
                    if (data.success) {
                        // Build the HTML form content
                        var formContent = '<form id="editRequestForm">';

                        // Hidden input for RefNo
                        formContent += '<input type="hidden" name="RefNo" value="' + data.request.RefNo + '">';

                        // Build the form based on SacramentType
                        switch (data.request.SacramentType) {
                            case 'Baptism':
                                formContent += '<h5>Baptism Details</h5>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="ChildName" class="form-label">Child Name</label>';
                                formContent += '<input type="text" class="form-control" id="ChildName" name="ChildName" value="' + data.details.ChildName + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="ChildDOB" class="form-label">Date of Birth</label>';
                                formContent += '<input type="date" class="form-control" id="ChildDOB" name="ChildDOB" value="' + data.details.ChildDOB + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="ChildBPlace" class="form-label">Place of Birth</label>';
                                formContent += '<input type="text" class="form-control" id="ChildBPlace" name="ChildBPlace" value="' + data.details.ChildBPlace + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="FatherName" class="form-label">Father\'s Name</label>';
                                formContent += '<input type="text" class="form-control" id="FatherName" name="FatherName" value="' + data.details.FatherName + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="MotherMName" class="form-label">Mother\'s Maiden Name</label>';
                                formContent += '<input type="text" class="form-control" id="MotherMName" name="MotherMName" value="' + data.details.MotherMName + '">';
                                formContent += '</div>';

                                // Add more fields as needed

                                break;

                            case 'Wedding':
                                formContent += '<h5>Wedding Details</h5>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="ApplicantName" class="form-label">Applicant Name</label>';
                                formContent += '<input type="text" class="form-control" id="ApplicantName" name="ApplicantName" value="' + data.details.ApplicantName + '">';
                                formContent += '</div>';

                                // Add other fields as needed

                                break;

                            case 'Confirmation':
                                formContent += '<h5>Confirmation Details</h5>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="FullName" class="form-label">Full Name</label>';
                                formContent += '<input type="text" class="form-control" id="FullName" name="FullName" value="' + data.details.FullName + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="FatherFullName" class="form-label">Father\'s Full Name</label>';
                                formContent += '<input type="text" class="form-control" id="FatherFullName" name="FatherFullName" value="' + data.details.FatherFullName + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="MotherFullName" class="form-label">Mother\'s Full Name</label>';
                                formContent += '<input type="text" class="form-control" id="MotherFullName" name="MotherFullName" value="' + data.details.MotherFullName + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="Residence" class="form-label">Residence</label>';
                                formContent += '<input type="text" class="form-control" id="Residence" name="Residence" value="' + data.details.Residence + '">';
                                formContent += '</div>';

                                break;

                            case 'First Communion':
                                formContent += '<h5>First Communion Details</h5>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="FullNameChild" class="form-label">Child\'s Full Name</label>';
                                formContent += '<input type="text" class="form-control" id="FullNameChild" name="FullNameChild" value="' + data.details.FullNameChild + '">';
                                formContent += '</div>';

                                // Add other fields as needed

                                break;

                            case 'Anointing of the Sick':
                                formContent += '<h5>Anointing of the Sick Details</h5>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="FullName" class="form-label">Full Name</label>';
                                formContent += '<input type="text" class="form-control" id="FullName" name="FullName" value="' + data.details.FullName + '">';
                                formContent += '</div>';

                                // Add other fields as needed

                                break;

                            case 'Funeral and Burial':
                                formContent += '<h5>Funeral and Burial Details</h5>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="DeceasedFullName" class="form-label">Deceased Full Name</label>';
                                formContent += '<input type="text" class="form-control" id="DeceasedFullName" name="DeceasedFullName" value="' + data.details.DeceasedFullName + '">';
                                formContent += '</div>';

                                // Add other fields as needed

                                break;

                            case 'Blessing':
                                formContent += '<h5>Blessing Details</h5>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="FullName" class="form-label">Full Name</label>';
                                formContent += '<input type="text" class="form-control" id="FullName" name="FullName" value="' + data.details.FullName + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="Address" class="form-label">Address</label>';
                                formContent += '<input type="text" class="form-control" id="Address" name="Address" value="' + data.details.Address + '">';
                                formContent += '</div>';

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="RequesterContact" class="form-label">Contact Number</label>';
                                formContent += '<input type="text" class="form-control" id="RequesterContact" name="RequesterContact" value="' + data.details.RequesterContact + '">';
                                formContent += '</div>';

                                // Blessing Type dropdown
                                formContent += '<div class="mb-3">';
                                formContent += '<label for="BlessingType" class="form-label">Blessing Type</label>';
                                formContent += '<select class="form-control" id="BlessingType" name="BlessingType">';
                                ['House Blessing', 'Vehicle Blessing', 'Office Blessing', 'Religious Articles', 'Other'].forEach(function(type) {
                                    formContent += '<option value="' + type + '"' + (data.details.BlessingType === type ? ' selected' : '') + '>' + type + '</option>';
                                });
                                formContent += '</select>';
                                formContent += '</div>';

                                // If 'Other' is selected, show the input field
                                if (data.details.BlessingType === 'Other') {
                                    formContent += '<div class="mb-3">';
                                    formContent += '<label for="OtherBlessingType" class="form-label">Specify Blessing Type</label>';
                                    formContent += '<input type="text" class="form-control" id="OtherBlessingType" name="OtherBlessingType" value="' + data.details.OtherBlessingType + '">';
                                    formContent += '</div>';
                                }

                                formContent += '<div class="mb-3">';
                                formContent += '<label for="BlessingPlace" class="form-label">Blessing Place</label>';
                                formContent += '<input type="text" class="form-control" id="BlessingPlace" name="BlessingPlace" value="' + data.details.BlessingPlace + '">';
                                formContent += '</div>';

                                break;

                            default:
                                formContent += '<p>No editable fields available for this sacrament type.</p>';
                        }

                        formContent += '</form>';

                        $('#edit-modal-content-placeholder').html(formContent);

                        // Handle dynamic display for 'Other' blessing type
                        $('#BlessingType').on('change', function() {
                            if ($(this).val() === 'Other') {
                                if ($('#OtherBlessingType').length === 0) {
                                    var otherField = '<div class="mb-3">';
                                    otherField += '<label for="OtherBlessingType" class="form-label">Specify Blessing Type</label>';
                                    otherField += '<input type="text" class="form-control" id="OtherBlessingType" name="OtherBlessingType">';
                                    otherField += '</div>';
                                    $(this).parent().after(otherField);
                                }
                            } else {
                                $('#OtherBlessingType').parent().remove();
                            }
                        });

                    } else {
                        $('#edit-modal-content-placeholder').html('<p>Error fetching details: ' + data.message + '</p>');
                    }
                },
                error: function() {
                    $('#edit-modal-content-placeholder').html('<p>Error fetching details.</p>');
                },
                dataType: 'json'
            });
        });

        // Save Edit Button Click Handler
        $('#saveEditButton').on('click', function() {
            var formData = $('#editRequestForm').serialize();

            $.ajax({
                url: 'save_edited_request.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        alert('Request updated successfully.');
                        // Close the modal
                        var modalElement = document.getElementById('editRequestModal');
                        var modal = bootstrap.Modal.getInstance(modalElement);
                        modal.hide();
                        // Refresh the page or update the table row to reflect changes
                        location.reload();
                    } else {
                        alert('Error updating request: ' + data.message);
                    }
                },
                error: function() {
                    alert('Error updating request.');
                }
            });
        });

        var deleteRefNo; // Variable to hold the reference number for deletion

// Show the confirmation modal when delete button is clicked
$(document).on('click', '.delete-request-btn', function(e) {
    e.preventDefault();
    deleteRefNo = $(this).data('ref'); // Store refNo in a global variable
    $('#deleteConfirmationModal').modal('show'); // Show the confirmation modal
});

// Handle the delete action when the "Delete" button in the modal is clicked
$('#confirmDeleteBtn').on('click', function() {
    // Hide the confirmation modal
    $('#deleteConfirmationModal').modal('hide');

    // Proceed with the AJAX request to delete the item
    $.ajax({
        url: 'delete_request_user.php',
        type: 'POST',
        data: { ref: deleteRefNo },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                // Display a success message in a notification or message box
                $('#modal-content-placeholder').html('<p class="text-center text-success mt-4">Request deleted successfully.</p>');
                // Remove the row from the table
                $('a.delete-request-btn[data-ref="' + deleteRefNo + '"]').closest('tr').remove();
            } else {
                // Display error message in the modal content
                $('#modal-content-placeholder').html('<p class="text-center text-danger mt-4">Error deleting request: ' + data.message + '</p>');
            }
        },
        error: function() {
            // Display generic error message in the modal content
            $('#modal-content-placeholder').html('<p class="text-center text-danger mt-4">Error deleting request. Please try again later.</p>');
        }
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
