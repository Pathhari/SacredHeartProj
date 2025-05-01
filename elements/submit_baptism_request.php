<?php
include 'db.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$submissionStatus = "";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $GKK_BEC = mysqli_real_escape_string($conn, $_POST['GKK_BEC']);
    $BirthCertNo = mysqli_real_escape_string($conn, $_POST['BirthCertNo']);
    $BaptismalDate = mysqli_real_escape_string($conn, $_POST['BaptismalDate']);
    $Gender = mysqli_real_escape_string($conn, $_POST['Gender']);
    $ChildName = mysqli_real_escape_string($conn, $_POST['ChildName']);
    $ChildDOB = mysqli_real_escape_string($conn, $_POST['ChildDOB']);
    $ChildBPlace = mysqli_real_escape_string($conn, $_POST['ChildBPlace']);
    $FatherName = mysqli_real_escape_string($conn, $_POST['FatherName']);
    $FatherBPlace = mysqli_real_escape_string($conn, $_POST['FatherBPlace']);
    $MotherMName = mysqli_real_escape_string($conn, $_POST['MotherMName']);
    $MotherBPlace = mysqli_real_escape_string($conn, $_POST['MotherBPlace']);
    $ParentsResidence = mysqli_real_escape_string($conn, $_POST['ParentsResidence']);

    // Prepare and bind the SQL statement
    $stmt = $conn->prepare("INSERT INTO BaptismRequest (GKK_BEC, BirthCertNo, BaptismalDate, Gender, ChildName, ChildDOB, ChildBPlace, FatherName, FatherBPlace, MotherMName, MotherBPlace, ParentsResidence, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("ssssssssssss", $GKK_BEC, $BirthCertNo, $BaptismalDate, $Gender, $ChildName, $ChildDOB, $ChildBPlace, $FatherName, $FatherBPlace, $MotherMName, $MotherBPlace, $ParentsResidence);

    if ($stmt->execute()) {
        $submissionStatus = "Baptism request submitted successfully!";
    } else {
        $submissionStatus = "Error submitting request. Please try again.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Baptism Request</title>
    <style>
        /* Modal Background */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        /* Modal Content */
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.3);
        }

        /* Close Button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
        }
    </style>
</head>
<body>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('submissionModal');
        const closeModal = document.getElementById('closeModal');

        // Show modal if there is a submission status
        <?php if (!empty($submissionStatus)) : ?>
            modal.style.display = "block";
        <?php endif; ?>

        // Close the modal when the 'X' is clicked
        closeModal.onclick = function () {
            modal.style.display = "none";
        }

        // Close the modal when clicking outside the modal content
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
</script>

</body>
</html>
