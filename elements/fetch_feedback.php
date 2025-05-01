<?php
session_start();

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['ref'])) {
        include 'db.php';

        $refNo = $conn->real_escape_string($_GET['ref']);
        $userID = $_SESSION['userid']; // Get the user's ID from the session

        // Fetch the feedback from the Feedback table
        $sql = "SELECT FeedbackText, SubmittedAt FROM Feedback WHERE RefNo = '$refNo' AND UserID = '$userID'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $feedback = $result->fetch_assoc();
            echo json_encode(['success' => true, 'feedback' => $feedback]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No feedback found for this request.']);
        }

        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
