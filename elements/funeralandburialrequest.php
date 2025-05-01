<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: log-in.php");
    exit;
}

// Fetch user details from session
$firstname = $_SESSION["firstname"] ?? "User";
$lastname = $_SESSION["lastname"] ?? "";
$email = $_SESSION["email"] ?? "Not Available";

// Include database connection
include 'db.php';

// Preserve form data
$form_data = $_SESSION['form_data'] ?? [];

// Include any error messages
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

// Success message handling
if (isset($_SESSION['success_message'])) {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "success",
                title: "Success!",
                text: "Your Funeral and Burial request has been successfully submitted. Please wait for approval.",
                confirmButtonColor: "#E85C0D",
                confirmButtonText: "OK"
            });
        });
    </script>';
    unset($_SESSION['success_message']); // Clear the success message after displaying it
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacraments Request - Funeral and Burial Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Include jQuery and Bootstrap CSS/JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include SweetAlert2 -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
       body {
            font-family: 'Gotham', sans-serif;
            background-color: #fffaf0; /* Light orange background */
        }

        /* Header */
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
        }

        .header img {
            height: 50px;
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
            position: relative;
        }

        .header-icons .fas.fa-bell {
            cursor: pointer;
            position: relative;
        }
        /* Sidebar */
        .sidebar {
    background-color: #2d3748;
    width: 220px;
    min-height: 100vh;
    padding-top: 20px;
    color: white;
    position: fixed; /* Changed from relative to fixed */
    top: 80px; /* Adjust based on the header height */
    left: 0;
    z-index: 999; /* Ensure it stays above other content */
    transition: width 0.3s ease;
}
.flex {
    display: flex;
    min-height: 100vh;
    padding-top: 80px; /* Adjust based on header height */
}
.p-6 {
    padding: 1.5rem;
    max-width: 100%;
    overflow: auto;
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
        .btn-primary {
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

.btn-primary:hover {
    background-color: #CC4A05;
    transform: translateY(-3px); /* Slight hover lift effect */
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.15); /* Add shadow on hover */
}

.btn-primary:active {
    transform: translateY(1px); /* Button pushes down slightly on click */
    box-shadow: none;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    font-size: 16px;
    font-weight: bold;
    display: inline-block;
    text-align: center;
    border: none;
    margin-right: 10px; /* Adds space between Previous and Submit button */
}

.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-3px);
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.15);
}

.btn-secondary:active {
    transform: translateY(1px);
    box-shadow: none;
}

.btn-container {
    display: flex;
    justify-content: flex-end;
    gap: 20px; /* Space between buttons */
    margin-top: 20px;
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


        .back-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        border-radius: 8px;
        background-color: #E85C0D; /* Deep orange */
        color: white;
        font-weight: 600;
        transition: background-color 0.3s ease;
        text-decoration: none;
        margin-bottom: 20px;
    }

    .back-button:hover {
        background-color: #CC4A05;
    }

    .back-button i {
        margin-right: 8px;
    }
   /* Form Styling */
.form-container {
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin-top: 20px;
}

.form-label {
    color: #4a5568;
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    width: 100%;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #E85C0D;
    outline: none;
    box-shadow: 0 0 0 3px rgba(232, 92, 13, 0.2);
}

/* Grid layout for form */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

      /* Responsive Styles */
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
    .sidebar {
        position: fixed;
        height: 100vh;
        left: -220px;
        transition: left 0.3s;
    }

    .sidebar.active {
        left: 0;
    }

    .toggle-btn {
        display: block;
    }

    .header .logo-container img {
        height: 35px;
        margin-right: 5px;
    }


    /* Adjust form grid */
    .form-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .godparent-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .header h1 {
        font-size: 1rem;
    }

    .form-container {
        padding: 20px;
    }

    .back-button {
        padding: 8px 15px;
    }

    .header .logo-container img {
        height: 30px;
    }

    .toggle-btn {
        right: 10px;
        top: 15px;
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

    <!-- Sacraments Page Content -->
    <div class="p-6 max-w-7xl mx-auto">
    <a href="sacraments.php" class="back-button">
    <i class="fas fa-arrow-left"></i> Back to Sacraments
</a>


        <h2 class="text-3xl font-semibold mb-6 text-gray-800">Funeral & Burial Request</h2>
        
            <!-- Error Message Display -->
            <?php if ($error_message): ?>
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>


        <!-- Overview Text -->
        <p class="text-lg text-gray-600 mb-8">Please fill out the required fields before you submit the funeral request and be sure to double check each information of the fields to complete the request.</p>
        <p class="text-lg font-bold text-red-700 bg-orange-200 p-4 rounded-md mb-4">
        IMPORTANT: If the papers or the files are not complete, no schedule will be set.
    </p>  
        
        <!-- Text Introduction Block -->
           <div class="intro-text">
            <p><strong>Alang sa Hingtungdan:</strong></p>
            <p>Kaming mga opisyales apostolado sa GKK 
                <input type="text" id="gkk" name="gkk" class="form-control inline w-auto" placeholder="GKK Name"> 
                nagpamatuod nga ang nahisulat sa ubos, among igsoon sa pagtoo ni kristo nga mihalin na sa laing kinabuhi.
            </p>
        </div>
        <div class="form-container">
           <form action="funeralandburialrequest_accept.php" method="POST">
           
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="deceasedFullName" class="form-label">Pangalan sa namatay (Name of the deceased)</label>
                        <input type="text" class="form-control block w-full" id="deceasedFullName" name="deceasedFullName">
                    </div>
                    <div>
                        <label for="address" class="form-label">Pinuy-anan (Address)</label>
                        <input type="text" class="form-control block w-full" id="address" name="address">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="dob" class="form-label">Petsa ug bulan sa pagkatawo (Date of birth)</label>
                        <input type="date" class="form-control block w-full" id="dob" name="dob">
                    </div>
                    <div>
                        <label for="age" class="form-label">Idad sa namatay (Age of the deceased)</label>
                        <input type="number" class="form-control block w-full" id="age" name="age">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-control block w-full">
                            <option value="">Select Status</option>
                            <option value="Child">Bata (Child)</option>
                            <option value="Single">Dalaga/ulitawo (Single - Female/Male)</option>
                            <option value="Married">Kasado (Married)</option>
                            <option value="Cohabiting">Nag-ipon (Cohabiting)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="dod" class="form-label">Petsa sa pagkamatay (Date of death)</label>
                        <input type="date" class="form-control block w-full" id="dod" name="dod">
                    </div>
                    <div>
                        <label for="causeOfDeath" class="form-label">Hinungdan sa kamatayon (Cause of death)</label>
                        <input type="text" class="form-control block w-full" id="causeOfDeath" name="causeOfDeath">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="burialPlace" class="form-label">Dapit sa pagalubngan (Burial place)</label>
                        <input type="text" class="form-control block w-full" id="burialPlace" name="burialPlace">
                    </div>
                </div>
                <div class="mt-6">
                <h3 class="text-xl font-semibold mb-4 text-red-400">ALANG SA ULITWAO, DALAGA, O BATA NGA NAMATAY</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <label class="form-label">Father's Name <span class="translation">/ Ngalan sa Amahan</span></label>
                <input type="text" name="father_name" class="form-control" placeholder="Enter Father's Name">
                <label class="form-label">Mother's Name <span class="translation">/ Ngalan sa Inahan</span></label>
                <input type="text" name="mother_name" class="form-control" placeholder="Enter Mother's Name">
                </div>
                </div>
                <div class="mt-6">
                <h3 class="text-xl font-semibold mb-4 text-red-400">ALANG SA MINYO UG NAGPUYO NGA NAMATAY</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <label class="form-label">Spouse's Name <span class="translation">/ Ngalan sa Bana o Asawa</span></label>
                <input type="text" name="spouse_name" class="form-control" placeholder="Enter Spouse's Name">
                </div>
                </div>

                <div class="mt-6">
                    
                <label class="form-label">Funeral Service Type <span class="translation">/ Tipo sa Serbisyo sa Lubong</span></label>
                <select name="funeral_service_type" class="form-control">
                    <option value="" disabled selected>Select Service Type</option>
                    <option value="mass">Mass / Misahan</option>
                    <option value="rite">Rite / Kasaulugan</option>
                    <option value="blessing">Blessing</option>
                </select>

                <label class="form-label">Sacraments Received <span class="translation">/ Mga Sakramento nga Nadawat</span></label>
                <select name="sacraments_received" class="form-control">
                    <option value="" disabled selected>Select a Sacrament</option>
                    <option value="baptism">Baptism / Bunyag</option>
                    <option value="confirmation">Confirmation / Kunpirma</option>
                    <option value="marriage">Marriage / Kaminyu-on</option>
                    <option value="holy_eucharist">Holy Eucharist / Santa lana</option>
                </select>

                <label class="form-label">Family Representative's Name <span class="translation">/ Ngalan sa Kaabag nga Mag-asulog</span></label>
                <input type="text" name="family_representative" class="form-control" placeholder="Enter Representative's Name">

                </div>


                <div class="mt-6">
                <h3 class="text-xl font-semibold mb-4 text-red-400">DAPIT NGA MISAHAN O SAULOGAN</h3>
                <label class="form-label">GKK <span class="translation">/ GKK</span></label>
                <input type="text" name="gkk" class="form-control" placeholder="Enter GKK">

                <label class="form-label">Parish <span class="translation">/ Parokya</span></label>
                <input type="text" name="parish" class="form-control" placeholder="Enter Parish">

                <label class="form-label">President <span class="translation">/ Presidente</span></label>
                <input type="text" name="president" class="form-control" placeholder="Enter President's Name">

                <label class="form-label">Vice President <span class="translation">/ Bise Presidente</span></label>
                <input type="text" name="vice_president" class="form-control" placeholder="Enter Vice President's Name">

                <label class="form-label">Secretary <span class="translation">/ Secretary</span></label>
                <input type="text" name="secretary" class="form-control" placeholder="Enter Secretary's Name">

                <label class="form-label">Treasurer <span class="translation">/ Treasurer</span></label>
                <input type="text" name="treasurer" class="form-control" placeholder="Enter Treasurer's Name">

                <label class="form-label">PSP Representative <span class="translation">/ Kaabag o PSP</span></label>
                <input type="text" name="psp_representative" class="form-control" placeholder="Enter PSP Representative's Name">

                <label class="form-label">Preferred Date and Time <span class="translation">/ Preferred Date and Time</span></label>
                <input type="datetime-local" name="preferred_date_time" class="form-control">
                
                <!-- Submit Button -->
                <div class="text-center">
                    <!-- Update the button to trigger the confirmation modal -->
                    <button type="button" class="btn btn-primary px-6 py-3 rounded-lg text-white font-semibold" onclick="showConfirmationModal()">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-8 w-3/4 max-w-md text-center">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Confirmation</h2>
        <p class="text-gray-700 mb-6">Are you sure all the information is true and double-checked for the request approval?</p>
        <div class="flex justify-center gap-4">
            <button onclick="confirmSubmit()" class="px-6 py-2 rounded-lg bg-orange-600 text-white hover:bg-orange-700 transition duration-200">Yes, Submit</button>
            <button onclick="closeConfirmationModal()" class="px-6 py-2 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition duration-200">Cancel</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md text-center">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Success!</h2>
        <p class="text-gray-700 mb-6">Your anointing request has been successfully submitted. Please wait for approval.</p>
        <button onclick="closeSuccessModal()" class="px-6 py-2 rounded-lg bg-orange-600 text-white hover:bg-orange-700 transition duration-200">OK</button>
    </div>
</div>


<!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow" style="display:none;">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content"></div>
</div>
>

<script>
  function showConfirmationModal() {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Are you certain all the information is correct and ready for submission?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#E85C0D', // Match your theme color
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the form
            document.querySelector('form').submit();
        }
    });
}
    // Function to check for date and time conflicts
    function checkConflict() {
    var preferredDateTime = document.querySelector('input[name="preferred_date_time"]').value;

    if (preferredDateTime === '') {
        alert('Please select a preferred date and time.');
        return;
    }

    // Make an AJAX call to check for conflicts
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'check_funeral_conflict.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.conflict) {
                alert('The selected date and time conflicts with an existing scheduled event. Please choose another date and time.');
            } else {
                document.getElementById('confirmationModal').classList.remove('hidden');
            }
        } else {
            alert('An error occurred while checking the date and time. Please try again.');
        }
    };
    xhr.send('preferredDateTime=' + encodeURIComponent(preferredDateTime));
}
    
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
