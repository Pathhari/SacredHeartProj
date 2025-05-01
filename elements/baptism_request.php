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

// Include database connection
include 'db.php';

// Fetch available baptism slots
$sql_slots = "
    SELECT e.*, p.PriestName
    FROM UpcomingEvents e
    LEFT JOIN Priests p ON e.PriestID = p.PriestID
    WHERE e.SacramentType = 'Baptism' AND e.Status = 'Available' AND e.EventDate >= CURDATE()
    ORDER BY e.EventDate, e.StartTime
";
$result_slots = $conn->query($sql_slots);

$available_slots = [];
if ($result_slots->num_rows > 0) {
    while ($row = $result_slots->fetch_assoc()) {
        $available_slots[] = $row;
    }
}

// Preserve form data
$form_data = $_SESSION['form_data'] ?? [];

// Include any error messages
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

if (isset($_SESSION['success_message'])) {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "success",
                title: "Success!",
                text: "Your baptism request has been successfully submitted. Please wait for approval.",
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacraments Request - Baptism Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
       /* Global Styles */
       body {
            font-family: 'Gotham', sans-serif;
            background-color: #fffaf0;
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

.sidebar a:hover {
    background-color: #4a5568;
}

.sidebar a.active {
    background-color: #F6AD55;
    color: white;
}

/* Toggle Button */
.toggle-btn {
    display: none; /* Initially hidden, shown on smaller screens */
    background-color: #2d3748;
    color: white;
    padding: 10px;
    cursor: pointer;
    position: absolute;
    top: 20px;
    right: -15px;
    border-radius: 50%;
    z-index: 1000;
    transition: right 0.3s ease;
}

/* Content Layout */
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

/* Back Button */
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
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
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

/* Godparents Section Styling */
.godparent-section {
    background-color: #f9f7f3;
    border-radius: 10px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.godparent-section h3 {
    font-size: 1.2rem;
    color: #e85c0d;
    font-weight: bold;
    margin-bottom: 15px;
}

.godparent-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    border-top: 1px solid #ddd;
    padding-top: 15px;
}

.godparent-item {
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 10px;
}
.submit-button-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    width: 100%; /* Ensures it takes the full width */
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
.btn-container {
    display: flex;
    justify-content: flex-end;
    gap: 20px; /* Space between buttons */
    margin-top: 20px;
}

  .file-upload-section {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .file-upload {
        position: relative;
        display: inline-block;
        width: 100%;
        margin-top: 0.5rem;
    }

    .file-input {
        display: block;
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        color: #4a5568;
        transition: border-color 0.3s ease, background-color 0.3s ease;
    }

    .file-input:hover, .file-input:focus {
        border-color: #E85C0D;
    }

    .file-input.file-selected {
        border-color: #28a745;
        background-color: #e6ffe6;
    }

    .upload-icon {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        font-size: 1.25rem;
        color: #E85C0D;
        transition: color 0.3s ease;
    }

    .upload-icon.uploaded {
        color: #28a745;
    }

    .form-label {
        font-size: 1rem;
        color: #4a5568;
        font-weight: 500;
    }

    .spacing {
    margin-bottom: 50px; /* Adjust the spacing as needed */
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
        <img src="imgs/mainlogo.png" alt="Parish Logo">
        <a href="sacraments.php" class="text-2xl font-bold">Sacred Heart of Jesus Parish</a>
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
        <!-- Back Button -->
        <a href="sacraments.php" class="back-button">
    <i class="fas fa-arrow-left"></i> Back to Sacraments
</a>

        <h2 class="text-3xl font-semibold mb-6 text-gray-800">Baptism Request</h2>

                <!-- Error Message Display -->
                <?php if ($error_message): ?>
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Overview Text -->
        <p class="text-lg text-gray-600 mb-8">Please fill out the required fields before you submit the baptismal request and be sure to double check each information of the fields to complete the request.</p>
        <p class="text-lg font-bold text-red-700 bg-orange-200 p-4 rounded-md mb-4">
        IMPORTANT: If the papers or the files are not complete, no schedule will be set.
    </p>  
    
         <!-- Baptism Request Form -->
         <form action="baptism_requestaccept.php" method="POST" enctype="multipart/form-data" class="form-container">
            <div class="form-grid">
                <div>
                    <label for="GKK" class="form-label">GKK</label>
                    <input type="text" id="GKK" name="GKK" value="<?php echo htmlspecialchars($form_data['GKK'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="BirthCertNo" class="form-label">Birth Certificate Number</label>
                    <input type="text" id="BirthCertNo" name="BirthCertNo" value="<?php echo htmlspecialchars($form_data['BirthCertNo'] ?? ''); ?>" class="form-control">
                </div>
                <div>
    <label for="BaptismalDate" class="form-label">Baptismal Date</label>
    <input type="date" id="BaptismalDate" name="BaptismalDate" value="<?php echo htmlspecialchars($form_data['BaptismalDate'] ?? ''); ?>" class="form-control spacing">
</div>
                
<div>
    <label for="Gender" class="form-label">Gender</label>
    <select id="Gender" name="Gender" class="form-control spacing">
        <option value="Male" <?php if(($form_data['Gender'] ?? '') === 'Male') echo 'selected'; ?>>Male</option>
        <option value="Female" <?php if(($form_data['Gender'] ?? '') === 'Female') echo 'selected'; ?>>Female</option>
    </select>
</div>
                
                <div>
                    <label for="ChildName" class="form-label">Child Name</label>
                    <input type="text" id="ChildName" name="ChildName" value="<?php echo htmlspecialchars($form_data['ChildName'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="ChildDOB" class="form-label">Child Date of Birth</label>
                    <input type="date" id="ChildDOB" name="ChildDOB" value="<?php echo htmlspecialchars($form_data['ChildDOB'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="ChildBPlace" class="form-label">Child Place of Birth</label>
                    <input type="text" id="ChildBPlace" name="ChildBPlace" value="<?php echo htmlspecialchars($form_data['ChildBPlace'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="FatherName" class="form-label">Father Name</label>
                    <input type="text" id="FatherName" name="FatherName" value="<?php echo htmlspecialchars($form_data['FatherName'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="FatherBPlace" class="form-label">Father Birth Place</label>
                    <input type="text" id="FatherBPlace" name="FatherBPlace" value="<?php echo htmlspecialchars($form_data['FatherBPlace'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="MotherMName" class="form-label">Mother Maiden Name</label>
                    <input type="text" id="MotherMName" name="MotherMName" value="<?php echo htmlspecialchars($form_data['MotherMName'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="MotherBPlace" class="form-label">Mother Birth Place</label>
                    <input type="text" id="MotherBPlace" name="MotherBPlace" value="<?php echo htmlspecialchars($form_data['MotherBPlace'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="ParentsResidence" class="form-label">Parents Residence</label>
                    <input type="text" id="ParentsResidence" name="ParentsResidence" value="<?php echo htmlspecialchars($form_data['ParentsResidence'] ?? ''); ?>" class="form-control">
                </div>


                <div>
                    <label for="DMarriage" class="form-label">Date of Marriage</label>
                    <input type="date" id="DMarriage" name="DMarriage" value="<?php echo htmlspecialchars($form_data['DMarriage'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="MCertNo" class="form-label">Marriage Certificate No.</label>
                    <input type="text" id="MCertNo" name="MCertNo" value="<?php echo htmlspecialchars($form_data['MCertNo'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="PMarriage" class="form-label">Church of Marriage</label>
                    <input type="text" id="PMarriage" name="PMarriage" value="<?php echo htmlspecialchars($form_data['PMarriage'] ?? ''); ?>" class="form-control">
                </div>
                <div>
                    <label for="MarriagePlace" class="form-label">Place of Marriage</label>
                    <input type="text" id="MarriagePlace" name="MarriagePlace" value="<?php echo htmlspecialchars($form_data['MarriagePlace'] ?? ''); ?>" class="form-control">
                </div>
            
            <!-- God Parent Section -->
<!-- Godfather Section -->
<div class="godparent-section">
    <h3 class="text-lg font-semibold mt-4 text-gray-700">Godfathers</h3>
    <div class="godparent-grid">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="godparent-item">
                <label for="GodfatherName<?php echo $i; ?>" class="form-label">Godfather <?php echo $i; ?> Name</label>
                <input type="text" id="GodfatherName<?php echo $i; ?>" name="GodfatherName<?php echo $i; ?>" value="<?php echo htmlspecialchars($form_data["GodfatherName$i"] ?? ''); ?>" class="form-control">
            </div>
            <div class="godparent-item">
                <label for="GodfatherAddress<?php echo $i; ?>" class="form-label">Godfather <?php echo $i; ?> Address</label>
                <input type="text" id="GodfatherAddress<?php echo $i; ?>" name="GodfatherAddress<?php echo $i; ?>" value="<?php echo htmlspecialchars($form_data["GodfatherAddress$i"] ?? ''); ?>" class="form-control">
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Godmother Section -->
<div class="godparent-section">
    <h3 class="text-lg font-semibold mt-4 text-gray-700">Godmothers</h3>
    <div class="godparent-grid">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="godparent-item">
                <label for="GodmotherName<?php echo $i; ?>" class="form-label">Godmother <?php echo $i; ?> Name</label>
                <input type="text" id="GodmotherName<?php echo $i; ?>" name="GodmotherName<?php echo $i; ?>" value="<?php echo htmlspecialchars($form_data["GodmotherName$i"] ?? ''); ?>" class="form-control">
            </div>
            <div class="godparent-item">
                <label for="GodmotherAddress<?php echo $i; ?>" class="form-label">Godmother <?php echo $i; ?> Address</label>
                <input type="text" id="GodmotherAddress<?php echo $i; ?>" name="GodmotherAddress<?php echo $i; ?>" value="<?php echo htmlspecialchars($form_data["GodmotherAddress$i"] ?? ''); ?>" class="form-control">
            </div>
        <?php endfor; ?>
    </div>
</div>
</div>
<!-- File Uploads Section -->
<div class="file-upload-section bg-white p-6 rounded-lg shadow-md mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Upload Required Documents</h3>

    <!-- Birth Certificate -->
    <label for="birthCert" class="form-label">1. Birth Certificate sa bata</label>
    <div class="file-upload">
        <input type="file" name="birthCertFile" id="birthCert" accept=".png, .jpg, .jpeg, .pdf" required class="file-input">
        <span class="upload-icon"><i class="fas fa-upload"></i></span>
    </div>

    <!-- Marriage Certificate -->
    <label for="marriageCert" class="form-label mt-4">2. Marriage Certificate sa Simbahan</label>
    <div class="file-upload">
        <input type="file" name="marriageCertFile" id="marriageCert" accept=".png, .jpg, .jpeg, .pdf" required class="file-input">
        <span class="upload-icon"><i class="fas fa-upload"></i></span>
    </div>

    <!-- GKK Certificate -->
    <label for="gkkCert" class="form-label mt-4">3. GKK or Parish Certificate sa Ginikanan ug mga Mangugos</label>
    <div class="file-upload">
        <input type="file" name="gkkCertFile" id="gkkCert" accept=".png, .jpg, .jpeg, .pdf" required class="file-input">
        <span class="upload-icon"><i class="fas fa-upload"></i></span>
    </div>

    <!-- GKK Certification Recommendation -->
    <label for="gkkCertRecommendation" class="form-label mt-4">4. Usa ka ninang ug ninong nga naay GKK Certification</label>
    <p class="text-red-600 mb-4">kung taga laing parokya recommendation permidao sa pari</p>
    <div class="file-upload">
        <input type="file" name="gkkCertRecommendationFile" id="gkkCertRecommendation" accept=".png, .jpg, .jpeg, .pdf" required class="file-input">
        <span class="upload-icon"><i class="fas fa-upload"></i></span>
    </div>
</div>

<div class="mt-6 bg-orange-50 p-6 rounded-lg shadow-lg">
    <h3 class="text-lg font-semibold text-orange-700 mb-4 text-center">Halad sa Bunyag</h3>
    <div class="overflow-x-auto">
        <table class="table-auto min-w-full bg-white shadow-lg rounded-lg overflow-hidden">
            <thead class="bg-orange-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-bold text-orange-900">Category</th>
                    <th class="px-6 py-3 text-left text-sm font-bold text-orange-900">Aktibo</th>
                    <th class="px-6 py-3 text-left text-sm font-bold text-orange-900">Dili Aktibo</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <tr class="bg-gray-50">
                    <td class="px-6 py-3 text-sm">Bata</td>
                    <td class="px-6 py-3 text-sm">₱320.00</td>
                    <td class="px-6 py-3 text-sm">₱420.00</td>
                </tr>
                <tr class="bg-white">
                    <td class="px-6 py-3 text-sm">Hamtung</td>
                    <td class="px-6 py-3 text-sm">₱250.00</td>
                    <td class="px-6 py-3 text-sm">₱430.00</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="px-6 py-3 text-sm">Mangugos</td>
                    <td class="px-6 py-3 text-sm">₱50.00</td>
                    <td class="px-6 py-3 text-sm">₱75.00</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>



          <!-- Scheduling Options -->
          <div class="mt-6 bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Select Scheduling Option</h3>
                <label class="inline-flex items-center">
                    <input type="radio" class="form-radio" name="scheduling_option" value="pre_scheduled" <?php if (($form_data['scheduling_option'] ?? 'pre_scheduled') === 'pre_scheduled') echo 'checked'; ?> onclick="toggleSchedulingOption()">
                    <span class="ml-2">Choose from Available Slots</span>
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" class="form-radio" name="scheduling_option" value="preferred_datetime" <?php if (($form_data['scheduling_option'] ?? '') === 'preferred_datetime') echo 'checked'; ?> onclick="toggleSchedulingOption()">
                    <span class="ml-2">Select Preferred Date and Time (Additional Fee)</span>
                </label>
            </div>

            <!-- Available Slots Section -->
            <div id="preScheduledSlots" class="mt-6 bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Available Baptism Slots</h3>
                <?php if (!empty($available_slots)): ?>
                    <label for="SelectedSlot" class="form-label">Choose a Slot</label>
                    <select id="SelectedSlot" name="SelectedSlot" class="form-control">
                        <option value="">-- Select a Slot --</option>
                        <?php foreach ($available_slots as $slot): ?>
                            <option value="<?php echo $slot['EventID']; ?>" <?php if (($form_data['SelectedSlot'] ?? '') == $slot['EventID']) echo 'selected'; ?>>
                            <?php echo date('F j, Y', strtotime($slot['EventDate'])) . ' - ' . date('h:i A', strtotime($slot['StartTime'])) . ' to ' . date('h:i A', strtotime($slot['EndTime'])) . ' with ' . htmlspecialchars($slot['PriestName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <p class="text-red-600">No available baptism slots at the moment. Please check back later or select a preferred date and time.</p>
                <?php endif; ?>
            </div>

            <!-- Preferred Date and Time Section -->
            <div id="preferredDateTime" class="mt-6 bg-white p-6 rounded-lg shadow-md" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Preferred Date and Time (Additional Fee)</h3>
                <label for="PreferredBaptismDate" class="form-label">Preferred Date</label>
                <input type="date" id="PreferredBaptismDate" name="PreferredBaptismDate" value="<?php echo htmlspecialchars($form_data['PreferredBaptismDate'] ?? ''); ?>" class="form-control">

                <label for="PreferredBaptismTime" class="form-label mt-4">Preferred Time</label>
                <input type="time" id="PreferredBaptismTime" name="PreferredBaptismTime" value="<?php echo htmlspecialchars($form_data['PreferredBaptismTime'] ?? ''); ?>" class="form-control">
            </div>

            <!-- Submit Button -->
            <div class="submit-button-wrapper">
            <button type="button" class="btn btn-primary" onclick="showConfirmationModal()">Submit Request</button>
            </div>
        </form>
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
        <p class="text-gray-700 mb-6">Your baptism request has been successfully submitted. Please wait for approval.</p>
        <button onclick="closeSuccessModal()" class="px-6 py-2 rounded-lg bg-orange-600 text-white hover:bg-orange-700 transition duration-200">OK</button>
    </div>
</div>

<!-- Notification Window -->
<div class="notifications-window" id="notificationsWindow" style="display:none;">
    <div class="notifications-header">Notifications</div>
    <div class="notifications-content"></div>
</div>


<script>

function toggleSchedulingOption() {
        var schedulingOption = document.querySelector('input[name="scheduling_option"]:checked').value;
        if (schedulingOption === 'pre_scheduled') {
            document.getElementById('preScheduledSlots').style.display = 'block';
            document.getElementById('preferredDateTime').style.display = 'none';
        } else {
            document.getElementById('preScheduledSlots').style.display = 'none';
            document.getElementById('preferredDateTime').style.display = 'block';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleSchedulingOption();
    });


// Show the success modal if there is a success message in session
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['success_message'])): ?>
        showSuccessModal();
        <?php unset($_SESSION['success_message']); ?> // Clear the session message after showing the modal
    <?php endif; ?>
});
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

// file upload verifier
 document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function () {
            if (this.files.length > 0) {
                this.classList.add('file-selected');
                this.nextElementSibling.classList.add('uploaded'); // Adds green icon
            } else {
                this.classList.remove('file-selected');
                this.nextElementSibling.classList.remove('uploaded');
            }
        });
    });
    // Sidebar Toggle Function
 document.addEventListener("DOMContentLoaded", function() {
    var sidebar = document.getElementById('sidebar');
    var toggleBtn = document.getElementById('sidebarToggle');
    // Initialize sidebar as collapsed
    sidebar.classList.add('collapsed');
    toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';

    // Attach toggle event
    toggleBtn.addEventListener('click', toggleSidebar);
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
