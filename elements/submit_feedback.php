<?php
session_start();

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['ref']) && isset($_POST['feedback'])) {
        include 'db.php';

        $refNo = $conn->real_escape_string($_POST['ref']);
        $feedbackText = $conn->real_escape_string($_POST['feedback']);

        // Fetch the UserID associated with the RefNo from SacramentRequests
        $sqlGetUser = "SELECT UserID FROM SacramentRequests WHERE RefNo = '$refNo'";
        $result = $conn->query($sqlGetUser);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userID = $row['UserID']; // This is the ID of the user who made the request

            // Insert the feedback into the Feedback table
            $sqlInsertFeedback = "INSERT INTO Feedback (RefNo, UserID, FeedbackText) VALUES ('$refNo', '$userID', '$feedbackText')";
            if ($conn->query($sqlInsertFeedback) === TRUE) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error inserting feedback: ' . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid RefNo or user not found.']);
        }

        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
