<?php
// reject_request.php

// Start session and check permissions
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include database connection file
include 'db.php';

try {
    $refNo = $_GET['ref'] ?? $_POST['ref'] ?? null;

    if (!$refNo) {
        throw new Exception('No reference number provided.');
    }

    // Begin transaction
    $conn->begin_transaction();

    // Fetch and lock the request row
    $stmt_request = $conn->prepare("SELECT Status, EventID, SacramentType, UserID FROM SacramentRequests WHERE RefNo = ? FOR UPDATE");
    $stmt_request->bind_param('s', $refNo);
    $stmt_request->execute();
    $result_request = $stmt_request->get_result();
    $request = $result_request->fetch_assoc();

    if (!$request) {
        throw new Exception('Sacrament request not found.');
    }

    if ($request['Status'] !== 'Pending') {
        throw new Exception('This request has already been processed.');
    }

    $eventID = $request['EventID'];
    $userID = $request['UserID']; // Get the UserID from the request

    // Delete the event from UpcomingEvents if it's an Anointing of the Sick or Funeral and Burial request
    if ($request['SacramentType'] === 'Anointing of the Sick' || $request['SacramentType'] === 'Funeral and Burial') {
        // Delete the event
        $stmt_delete_event = $conn->prepare("DELETE FROM UpcomingEvents WHERE EventID = ?");
        $stmt_delete_event->bind_param('i', $eventID);
        $stmt_delete_event->execute();
    } else {
        // For other sacrament types, update the event status to 'Available'
        $stmt_update_event = $conn->prepare("UPDATE UpcomingEvents SET Status = 'Available', PriestID = NULL WHERE EventID = ?");
        $stmt_update_event->bind_param('i', $eventID);
        $stmt_update_event->execute();
    }

    // Log the admin action
    $adminUserID = $_SESSION['userid'];
    $currentTime = date('Y-m-d H:i:s');

    // Update the request status to 'Rejected' and log the action
    $stmt_update_request = $conn->prepare("UPDATE SacramentRequests SET Status = 'Rejected', ProcessedBy = ?, ProcessedAt = ? WHERE RefNo = ?");
    $stmt_update_request->bind_param('iss', $adminUserID, $currentTime, $refNo);
    $stmt_update_request->execute();

    // Insert a notification for the user
    $notificationText = "Your sacrament request (RefNo: $refNo) has been rejected.";
    $stmt_insert_notification = $conn->prepare("INSERT INTO Notifications (UserID, NotificationText) VALUES (?, ?)");
    $stmt_insert_notification->bind_param('is', $userID, $notificationText);
    $stmt_insert_notification->execute();

    // Commit transaction
    $conn->commit();

    // Send email notification (optional)
    $stmt_user = $conn->prepare("SELECT Email, FirstName FROM Users WHERE UserID = ?");
    $stmt_user->bind_param('i', $userID);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();

    if ($user && !empty($user['Email'])) {
        $to = $user['Email'];
        $subject = "Your Sacrament Request has been Rejected";
        $message = "Dear " . $user['FirstName'] . ",\n\nWe regret to inform you that your sacrament request (RefNo: $refNo) has been rejected.\n\nBest regards,\nSacred Heart of Jesus Parish";
        $headers = "From: no-reply@parish.com";
        // Uncomment the next line to send the email (ensure mail settings are configured)
        // mail($to, $subject, $message, $headers);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Close statements and connection
    $stmt_request->close();
    if (isset($stmt_delete_event)) $stmt_delete_event->close();
    if (isset($stmt_update_event)) $stmt_update_event->close();
    $stmt_update_request->close();
    if (isset($stmt_user)) $stmt_user->close();
    if (isset($stmt_insert_notification)) $stmt_insert_notification->close(); // Close the new statement
    $conn->close();
}
?>
