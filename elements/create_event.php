<?php
// Start session
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'admin') {
    header("location: log-in.php");
    exit;
}

// Include database connection file
include 'db.php';

$firstname = $_SESSION["firstname"] ?? "User";

// Get the selected date from the POST data if available, otherwise from the GET parameter
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_date = $_POST['event_date'] ?? date('Y-m-d');
} else {
    $selected_date = $_GET['date'] ?? date('Y-m-d');
}

// Fetch priests for the dropdown
$sql_priests = "SELECT PriestID, PriestName FROM Priests WHERE Availability = 'Available'";
$priests = [];
if ($result_priests = $conn->query($sql_priests)) {
    while ($row = $result_priests->fetch_assoc()) {
        $priests[] = $row;
    }
    $result_priests->free();
}

// Initialize error variable
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $sacrament_type = $_POST['sacrament_type'];
    $priest_id = $_POST['priest_id'];
    $status = 'Available'; // Default status

    // Convert times to DateTime objects for accurate comparison
    $new_start = new DateTime("$event_date $start_time");
    $new_end = new DateTime("$event_date $end_time");

    if ($new_end <= $new_start) {
        $error = "End time must be after start time.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Check for time conflicts with existing events
            $sql_conflict = "
                SELECT * FROM UpcomingEvents 
                WHERE EventDate = ? 
                AND ((StartTime < ? AND EndTime > ?) OR (StartTime >= ? AND StartTime < ?))
            ";
            $stmt_conflict = $conn->prepare($sql_conflict);
            $stmt_conflict->bind_param("sssss", $event_date, $end_time, $start_time, $start_time, $end_time);
            $stmt_conflict->execute();
            $result_conflict = $stmt_conflict->get_result();

            if ($result_conflict->num_rows > 0) {
                $error = "There is a scheduling conflict with another event.";
            } else {
                // Check priest availability only if no scheduling conflict
                $sql_priest_conflict = "
                    SELECT * FROM UpcomingEvents 
                    WHERE EventDate = ? 
                    AND ((StartTime < ? AND EndTime > ?) OR (StartTime >= ? AND StartTime < ?)) 
                    AND PriestID = ?
                ";
                $stmt_priest_conflict = $conn->prepare($sql_priest_conflict);
                $stmt_priest_conflict->bind_param("sssssi", $event_date, $end_time, $start_time, $start_time, $end_time, $priest_id);
                $stmt_priest_conflict->execute();
                $result_priest_conflict = $stmt_priest_conflict->get_result();

                if ($result_priest_conflict->num_rows > 0) {
                    $error = "The selected priest is not available at the chosen time.";
                } else {
                    // No conflicts, proceed to insert the event
                    $sql_insert = "
                        INSERT INTO UpcomingEvents (EventDate, StartTime, EndTime, SacramentType, PriestID, Status)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("ssssis", $event_date, $start_time, $end_time, $sacrament_type, $priest_id, $status);

                    if ($stmt_insert->execute()) {
                        $conn->commit();
                        header("Location: calendaradmin.php");
                        exit;
                    } else {
                        $error = "Error inserting event: " . $stmt_insert->error;
                    }
                }
            }

            // Rollback if there was an error
            if (!empty($error)) {
                $conn->rollback();
            }

            // Close statements if they were initialized
            if (isset($stmt_conflict)) $stmt_conflict->close();
            if (isset($stmt_priest_conflict)) $stmt_priest_conflict->close();
            if (isset($stmt_insert)) $stmt_insert->close();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Head content similar to calendaradmin.php -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - Sacred Heart Parish</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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

        /* Calendar styles */
        #calendar {
            max-width: 100%;
            margin: 0 auto;
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
        <i class="fas fa-bell fa-2x" onclick="toggleNotifications()" title="Notifications"></i>
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
        <h2 class="text-2xl font-bold mb-6">Create Event for <?php echo (new DateTime($selected_date))->format('F j, Y'); ?></h2>

        <form action="create_event.php" method="POST" class="bg-white p-6 rounded-lg shadow-md">
            <input type="hidden" name="event_date" value="<?php echo htmlspecialchars($selected_date); ?>">

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Sacrament Type</label>
                <select name="sacrament_type" class="w-full border border-gray-300 p-2 rounded" required>
                    <option value="">Select Sacrament</option>
                    <option value="Baptism" <?php if(isset($_POST['sacrament_type']) && $_POST['sacrament_type'] == 'Baptism') echo 'selected'; ?>>Baptism</option>
                    <option value="Wedding" <?php if(isset($_POST['sacrament_type']) && $_POST['sacrament_type'] == 'Wedding') echo 'selected'; ?>>Wedding</option>
                    <option value="Confirmation" <?php if(isset($_POST['sacrament_type']) && $_POST['sacrament_type'] == 'Confirmation') echo 'selected'; ?>>Confirmation</option>
                    <option value="First Communion" <?php if(isset($_POST['sacrament_type']) && $_POST['sacrament_type'] == 'First Communion') echo 'selected'; ?>>First Communion</option>
                    <option value="Blessing" <?php if(isset($_POST['sacrament_type']) && $_POST['sacrament_type'] == 'Blessing') echo 'selected'; ?>>Blessing</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Start Time</label>
                <input type="time" name="start_time" class="w-full border border-gray-300 p-2 rounded" required value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : ''; ?>">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">End Time</label>
                <input type="time" name="end_time" class="w-full border border-gray-300 p-2 rounded" required value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : ''; ?>">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Assign Priest</label>
                <select name="priest_id" class="w-full border border-gray-300 p-2 rounded" required>
                    <option value="">Select Priest</option>
                    <?php foreach ($priests as $priest): ?>
                        <option value="<?php echo $priest['PriestID']; ?>" <?php if(isset($_POST['priest_id']) && $_POST['priest_id'] == $priest['PriestID']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($priest['PriestName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center">
                <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded">Create Event</button>
                <a href="calendaradmin.php" class="ml-4 text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Error Modal -->
<?php if (!empty($error)): ?>
<div id="errorModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-lg text-center">
        <h2 class="text-3xl font-bold text-red-600 mb-4">Error</h2>
        <p class="text-gray-700"><?php echo htmlspecialchars($error); ?></p>
        <div class="mt-6">
            <button onclick="closeErrorModal()" class="bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600">
                OK
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow" style="display: none;">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content">
        <div class="notification-item">Your baptism request has been approved.</div>
        <div class="notification-item">Your confirmation request is pending review.</div>
        <div class="notification-item">The schedule for your marriage sacrament has been updated.</div>
    </div>
</div>

<script>
    // Function to close the modal when "OK" is clicked
    function closeErrorModal() {
        var modal = document.getElementById('errorModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

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

    function toggleNotifications() {
        var notifWindow = document.getElementById('notificationsWindow');
        if (notifWindow.style.display === 'none' || notifWindow.style.display === '') {
            notifWindow.style.display = 'block';
            // Add event listener to close notifications when clicking outside
            document.addEventListener('click', closeNotificationsOnClickOutside);
        } else {
            notifWindow.style.display = 'none';
            // Remove the event listener when notifications window is closed
            document.removeEventListener('click', closeNotificationsOnClickOutside);
        }
    }

    function closeNotificationsOnClickOutside(event) {
        var notifWindow = document.getElementById('notificationsWindow');
        if (!event.target.matches('.fa-bell') && !notifWindow.contains(event.target)) {
            notifWindow.style.display = 'none';
            // Remove the event listener after closing
            document.removeEventListener('click', closeNotificationsOnClickOutside);
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

