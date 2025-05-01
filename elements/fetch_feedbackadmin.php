<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['ref'])) {
        include 'db.php';

        $refNo = $conn->real_escape_string($_GET['ref']);

        // Fetch the feedback from the Feedback table
        $sql = "SELECT FeedbackText, SubmittedAt FROM Feedback WHERE RefNo = '$refNo'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $feedback = $result->fetch_assoc();
            // Sanitize the output
            $feedback['FeedbackText'] = htmlspecialchars($feedback['FeedbackText']);
            $feedback['SubmittedAt'] = htmlspecialchars($feedback['SubmittedAt']);
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
