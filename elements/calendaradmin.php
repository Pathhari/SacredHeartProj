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

// Initialize variables for messages
$success_msg = '';
$error_msg = '';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Invalid CSRF token.";
    } else {
        $event_id = intval($_POST['event_id']);
        $delete_sql = "DELETE FROM UpcomingEvents WHERE EventID = ?";
        if ($stmt_delete = $conn->prepare($delete_sql)) {
            $stmt_delete->bind_param("i", $event_id);
            if ($stmt_delete->execute()) {
                $success_msg = "Event deleted successfully.";
            } else {
                $error_msg = "Error deleting event: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
            $error_msg = "Error preparing delete statement: " . $conn->error;
        }
    }
}

// Handle Edit Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Invalid CSRF token.";
    } else {
        // Retrieve and sanitize input data
        $event_id = intval($_POST['event_id']);
        $event_date = $_POST['event_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $sacrament_type = $_POST['sacrament_type'];
        $priest_id = intval($_POST['priest_id']);
        $status = $_POST['status'];

        // Validate required fields
        if (empty($event_date) || empty($start_time) || empty($end_time) || empty($sacrament_type) || empty($priest_id) || empty($status)) {
            $error_msg = "All fields are required for editing an event.";
        } else {
            // Update the event in the database
            $update_sql = "UPDATE UpcomingEvents SET EventDate = ?, StartTime = ?, EndTime = ?, SacramentType = ?, PriestID = ?, Status = ? WHERE EventID = ?";
            if ($stmt_update = $conn->prepare($update_sql)) {
                $stmt_update->bind_param("sssissi", $event_date, $start_time, $end_time, $sacrament_type, $priest_id, $status, $event_id);
                if ($stmt_update->execute()) {
                    $success_msg = "Event updated successfully.";
                } else {
                    $error_msg = "Error updating event: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $error_msg = "Error preparing update statement: " . $conn->error;
            }
        }
    }
}

// Delete past events
$delete_past_sql = "DELETE FROM UpcomingEvents WHERE EventDate < CURDATE()";
$conn->query($delete_past_sql); // Ignoring the result as per original code

// Fetch events from UpcomingEvents table where EventDate is today or in the future
$sql_events = "
    SELECT e.*, p.PriestName, sr.Status AS SacramentStatus, u.FirstName, u.LastName
    FROM UpcomingEvents e
    LEFT JOIN Priests p ON e.PriestID = p.PriestID
    LEFT JOIN SacramentRequests sr ON e.EventID = sr.EventID
    LEFT JOIN Users u ON sr.UserID = u.UserID
    WHERE EventDate >= CURDATE()
    ORDER BY EventDate ASC, SacramentType ASC
";

$events = [];

if ($stmt_events = $conn->prepare($sql_events)) {
    if ($stmt_events->execute()) {
        $result_events = $stmt_events->get_result();
        while ($row = $result_events->fetch_assoc()) {
            // Set display status based on SacramentStatus
            if ($row['SacramentStatus'] === 'Pending') {
                $row['DisplayStatus'] = 'Pre-Booked';
            } elseif ($row['SacramentStatus'] === 'Approved') {
                $row['DisplayStatus'] = 'Booked';
            } elseif ($row['SacramentStatus'] === 'Rejected') {
                $row['DisplayStatus'] = 'Available';
            } else {
                $row['DisplayStatus'] = 'Available'; // Default to 'Available' if no status
            }
            $events[] = $row;
        }
    } else {
        $error_msg = "Error executing events query: " . $stmt_events->error;
    }
    $stmt_events->close();
} else {
    $error_msg = "Error preparing events query: " . $conn->error;
}

// Group events by SacramentType
$grouped_events = [];

foreach ($events as $event) {
    $sacrament_type = $event['SacramentType'];
    if (!isset($grouped_events[$sacrament_type])) {
        $grouped_events[$sacrament_type] = [];
    }
    $grouped_events[$sacrament_type][] = $event;
}

// Fetch priests for the dropdown
$priests = [];
$priests_sql = "SELECT PriestID, PriestName FROM Priests WHERE Availability = 'Available' ORDER BY PriestName ASC";
$priests_result = $conn->query($priests_sql);
if ($priests_result->num_rows > 0) {
    while ($priest = $priests_result->fetch_assoc()) {
        $priests[] = $priest;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Existing head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sacred Heart Parish</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Include FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <!-- Include FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <!-- Include a lightweight modal library or use custom Tailwind CSS modals -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Existing styles */
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

        .calendar-container {
            flex-direction: column;
        }

        .calendar-box, .event-list {
            margin-bottom: 20px;
            width: 100%;
        }

        .fc-toolbar {
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        /* Sidebar adjustments for smaller screens */
        .sidebar {
            width: 60px;
            transition: width 0.3s ease;
        }

        .sidebar a span {
            display: none;
        }

        .toggle-btn {
            right: -15px;
        }
    }

        /* Sidebar */
        .sidebar {
            background-color: #2d3748;
            width: 220px;
            min-height: 100vh; /* Ensure the sidebar takes the full viewport height */
            padding-top: 20px;
            color: white;
            transition: width 0.3s ease;
            position: relative;
            flex-shrink: 0; /* Prevent sidebar from shrinking */
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

        /* Main content styling */
        .main-content {
            padding: 20px;
        }

        .table-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
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

        /* Calendar styles */
        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }

        /* Scrollable event list */
        ul {
            max-height: 400px; /* Adjust the height as needed */
            overflow-y: auto;
        }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1001; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; /* 10% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px; /* Could be more or less, depending on screen size */
            border-radius: 8px;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Responsive adjustments for modal */
        @media (max-width: 768px) {
            .modal-content {
                margin: 20% auto;
                width: 95%;
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
        <a href="calendaradmin.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Event Calendar</span></a>
        <a href="announcementsadmin.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>

        <!-- Sidebar Toggle Button -->
        <div class="toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-chevron-right"></i>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="p-6 w-full">
        <div class="flex flex-col lg:flex-row">
            <!-- Calendar -->
            <div class="w-full lg:w-2/3">
                <div id="calendar"></div>
            </div>

                      <!-- Event List -->
                      <div class="w-full lg:w-1/3 lg:pl-4 mt-6 lg:mt-0">
                <h2 class="text-xl font-bold mb-4">Upcoming Events</h2>

                <!-- Display Success or Error Messages -->
                <?php if (!empty($success_msg)): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                        <?php echo htmlspecialchars($success_msg); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                        <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>

                <ul class="bg-white shadow rounded-lg p-4" style="max-height: 400px; overflow-y: auto;">
                    <?php if (!empty($grouped_events)): ?>
                        <?php foreach ($grouped_events as $sacrament_type => $events_list): ?>
                            <!-- Sacrament Type Header -->
                            <h3 class="text-lg font-bold mt-4 mb-2"><?php echo htmlspecialchars($sacrament_type); ?></h3>
                            <?php foreach ($events_list as $event): ?>
                                <?php
                                    // Determine the color based on DisplayStatus
                                    $statusColor = ($event['DisplayStatus'] === 'Available') ? 'text-blue-500' :
                                    (($event['DisplayStatus'] === 'Pre-Booked') ? 'text-orange-500' : 'text-green-500');
                                ?>
                                <li class="mb-4 border-b pb-2">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold <?php echo $statusColor; ?>">
                                                <?php echo htmlspecialchars($event['SacramentType']); ?>
                                            </div>
                                            <div class="text-gray-700 text-sm">
                                                <?php echo htmlspecialchars($event['PriestName']); ?> <br>
                                                <?php echo date('F j, Y', strtotime($event['EventDate'])); ?> |
                                                <?php echo date('h:i A', strtotime($event['StartTime'])); ?> - <?php echo date('h:i A', strtotime($event['EndTime'])); ?>
                                            </div>
                                            <div class="text-gray-600 text-sm">
                                                Status: <?php echo htmlspecialchars($event['DisplayStatus']); ?>
                                            </div>
                                        </div>
                                        <div class="flex flex-col space-y-2">
                                            <!-- Edit Button -->
                                            <button
                                                class="text-blue-500 hover:text-blue-700"
                                                onclick="openEditModal(
                                                    <?php echo $event['EventID']; ?>,
                                                    '<?php echo htmlspecialchars(addslashes($event['EventDate'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($event['StartTime'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($event['EndTime'])); ?>',
                                                    '<?php echo htmlspecialchars(addslashes($event['SacramentType'])); ?>',
                                                    <?php echo $event['PriestID']; ?>,
                                                    '<?php echo htmlspecialchars(addslashes($event['DisplayStatus'])); ?>'
                                                )"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <!-- Delete Form -->
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="event_id" value="<?php echo $event['EventID']; ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No upcoming events.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
  <!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow" style="display:none;">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content"></div>
</div>
    </div>
</div>


<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-lg text-center">
        <h2 class="text-3xl font-bold text-green-600 mb-4">Event Created Successfully!</h2>
        <p class="text-gray-700">Your event has been successfully created and added to the calendar.</p>
        <div class="mt-6">
            <button id="closeModal" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                OK
            </button>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeEditModal()">&times;</span>
        <h2 class="text-xl font-bold mb-4">Edit Event</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="event_id" id="edit_event_id">

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="event_date">
                    Event Date
                </label>
                <input
                    type="date"
                    id="edit_event_date"
                    name="event_date"
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">
                    Start Time
                </label>
                <input
                    type="time"
                    id="edit_start_time"
                    name="start_time"
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="end_time">
                    End Time
                </label>
                <input
                    type="time"
                    id="edit_end_time"
                    name="end_time"
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="sacrament_type">
                    Sacrament Type
                </label>
                <select
                    id="edit_sacrament_type"
                    name="sacrament_type"
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
                    <option value="">Select Sacrament Type</option>
                    <option value="Baptism">Baptism</option>
                    <option value="Confirmation">Confirmation</option>
                    <option value="Wedding">Wedding</option>
                    <option value="First Communion">First Communion</option>
                    <option value="Funeral and Burial">Funeral and Burial</option>
                    <option value="Anointing of the Sick">Anointing of the Sick</option>
                    <option value="Blessing">Blessing</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="priest_id">
                    Priest
                </label>
                <select
                    id="edit_priest_id"
                    name="priest_id"
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
                    <option value="">Select Priest</option>
                    <?php
                        // Fetch priests from the database
                        include 'db.php';
                        $priests_sql = "SELECT PriestID, PriestName FROM Priests WHERE Availability = 'Available' ORDER BY PriestName ASC";
                        $priests_result = $conn->query($priests_sql);
                        if ($priests_result->num_rows > 0) {
                            while ($priest = $priests_result->fetch_assoc()) {
                                echo '<option value="' . $priest['PriestID'] . '">' . htmlspecialchars($priest['PriestName']) . '</option>';
                            }
                        }
                        $conn->close();
                    ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                    Status
                </label>
                <select
                    id="edit_status"
                    name="status"
                    required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                >
                    <option value="">Select Status</option>
                    <option value="Available">Available</option>
                    <option value="Pre-Booked">Pre-Booked</option>
                    <option value="Booked">Booked</option>
                </select>
            </div>

            <div class="flex items-center justify-between">
                <button
                    type="submit"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                >
                    Save Changes
                </button>
                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                >
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Calendar Initialization
document.addEventListener("DOMContentLoaded", function() {
    var events = <?php echo json_encode($events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    var calendarEl = document.getElementById('calendar');
    var calendarEvents = events.map(function(event) {
        var eventColor = (event.DisplayStatus === 'Available') ? 'blue' :
                 ((event.DisplayStatus === 'Pre-Booked') ? 'orange' : 'green');


        return {
            title: event.SacramentType + ' (' + event.DisplayStatus + ')',
            start: event.EventDate + 'T' + event.StartTime,
            end: event.EventDate + 'T' + event.EndTime,
            allDay: false,
            color: eventColor,
            extendedProps: {
                priestName: event.PriestName,
                userName: event.FirstName ? event.FirstName + ' ' + event.LastName : null
            }
        };
    });

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: calendarEvents,
        eventOverlap: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        dateClick: function(info) {
            window.location.href = 'create_event.php?date=' + info.dateStr;
        },
        eventDidMount: function(info) {
            // Add tooltip or other custom behavior here
        }
    });

    calendar.render();
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


// Edit Modal Functions
function openEditModal(eventId, eventDate, startTime, endTime, sacramentType, priestId, status) {
    // Populate the form with existing event data
    document.getElementById('edit_event_id').value = eventId;
    document.getElementById('edit_event_date').value = eventDate;
    document.getElementById('edit_start_time').value = startTime;
    document.getElementById('edit_end_time').value = endTime;
    document.getElementById('edit_sacrament_type').value = sacramentType;
    document.getElementById('edit_priest_id').value = priestId;
    document.getElementById('edit_status').value = status;

    // Display the modal
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

</script>

</body>
</html>
