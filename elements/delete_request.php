<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ref'])) {
    $refNo = $_POST['ref'];

    // Include database connection file
    include 'db.php';

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Update the SacramentRequests table to set Deleted = 1
        $stmt = $conn->prepare("UPDATE SacramentRequests SET Deleted = 1 WHERE RefNo = ?");
        $stmt->bind_param("s", $refNo);
        $stmt->execute();
        $stmt->close();

        // Optionally, you can also mark related Feedbacks, Documents, etc., as deleted
        // by adding a Deleted field to those tables and updating them here.

        $conn->commit();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete the request.']);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
