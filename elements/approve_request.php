<?php
// Start session and check permissions
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'admin') {
    header("Location: log-in.php");
    exit;
}

// Include database connection file
include 'db.php';

// Function to log errors to a file for debugging
function log_error($message) {
    error_log($message . "\n", 3, 'error_log.txt'); // Ensure 'error_log.txt' is writable
}

try {
    // Get the reference number and priestID from the POST parameters
    $refNo = isset($_POST['ref']) ? $_POST['ref'] : null;
    $priestID = isset($_POST['priestID']) ? $_POST['priestID'] : null;

    if (!$refNo) {
        throw new Exception('No reference number provided.');
    }

    // Begin transaction
    $conn->begin_transaction();

    // Fetch and lock the request row
    $stmt_request = $conn->prepare("SELECT * FROM SacramentRequests WHERE RefNo = ? FOR UPDATE");
    if (!$stmt_request) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt_request->bind_param('s', $refNo);
    if (!$stmt_request->execute()) {
        throw new Exception('Execute failed: ' . $stmt_request->error);
    }
    $result_request = $stmt_request->get_result();
    $request = $result_request->fetch_assoc();
    $stmt_request->close(); // Close the statement after fetching

    if (!$request) {
        throw new Exception('Sacrament request not found.');
    }

    if ($request['Status'] !== 'Pending') {
        throw new Exception('This request has already been processed.');
    }

    // Extract necessary data from the request
    $eventID = $request['EventID'];
    $sacramentType = $request['SacramentType'];
    $scheduleDate = $request['ScheduleDate'];
    $scheduleTime = $request['ScheduleTime']; // Start Time set by admin
    $userID = $request['UserID']; // Get the UserID from the request

    // **Check if ScheduleTime is set**
    if (!$scheduleTime) {
        throw new Exception('Schedule time is not set. Please set the schedule time before approving the request.');
    }

    // Sacraments that require priest assignment and event creation
    $sacramentsRequiringPriest = ['Baptism', 'Wedding', 'Anointing of the Sick', 'Funeral and Burial'];

    // Initialize a flag to indicate if priest assignment is required
    $requiresPriestAssignment = in_array($sacramentType, $sacramentsRequiringPriest);

    // Fetch existing PriestID from the request if available
    $existingPriestID = $request['PriestID'];

    // If the sacrament requires priest assignment
    if ($requiresPriestAssignment) {
        if ($eventID) {
            // Fetch the PriestID from the event
            $stmt_event = $conn->prepare("SELECT PriestID FROM UpcomingEvents WHERE EventID = ?");
            if (!$stmt_event) {
                throw new Exception('Prepare statement failed: ' . $conn->error);
            }
            $stmt_event->bind_param('i', $eventID);
            if (!$stmt_event->execute()) {
                throw new Exception('Execute failed: ' . $stmt_event->error);
            }
            $result_event = $stmt_event->get_result();
            if ($result_event->num_rows > 0) {
                $event = $result_event->fetch_assoc();
                $stmt_event->close();

                if (!$event['PriestID']) {
                    // PriestID is null, allow admin to assign a PriestID
                    if (!$priestID) {
                        throw new Exception('No priest assigned to the event, and no priest selected.');
                    }
                } else {
                    // Use the existing PriestID from the event
                    $priestID = $event['PriestID'];
                }
            } else {
                $stmt_event->close();
                throw new Exception('Event not found.');
            }
        } else {
            // No event exists; ensure a priestID is provided
            if (!$priestID) {
                throw new Exception('No priest selected for assignment.');
            }
        }

        // Validate priestID is an integer
        if (!filter_var($priestID, FILTER_VALIDATE_INT)) {
            throw new Exception('Invalid priest ID.');
        }

        // Check if the priest exists
        $stmt_check_priest = $conn->prepare("SELECT PriestID FROM Priests WHERE PriestID = ?");
        if (!$stmt_check_priest) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $stmt_check_priest->bind_param('i', $priestID);
        if (!$stmt_check_priest->execute()) {
            throw new Exception('Execute failed: ' . $stmt_check_priest->error);
        }
        $result_priest = $stmt_check_priest->get_result();
        if ($result_priest->num_rows === 0) {
            $stmt_check_priest->close();
            throw new Exception('Selected priest does not exist.');
        }
        $stmt_check_priest->close();
    } else {
        // For sacraments that do not require priest assignment
        // Retain existing PriestID if any; do not set it to null
        if ($priestID === null) {
            $priestID = $existingPriestID;
        }
    }

    // **Determine the event duration and end time**
    // Use the ScheduleTime set by the admin and calculate end time based on sacrament type
    $startTimeStr = $scheduleTime;
    $startDateTime = new DateTime($scheduleDate . ' ' . $startTimeStr);
    
    switch ($sacramentType) {
        case 'Baptism':
        case 'Anointing of the Sick':
            $duration = '+1 hour';
            break;
    
        case 'Wedding':
        case 'Funeral and Burial':
            $duration = '+2 hours';
            break;
    
        default:
            $duration = '+1 hour';
            break;
    }
    
    $endDateTime = clone $startDateTime;
    $endDateTime->modify($duration);
    $endTimeStr = $endDateTime->format('H:i:s');

    // Check if the priest is already assigned to an event at overlapping times
    if ($priestID !== null && $requiresPriestAssignment) {
        $stmt_check_availability_sql = "
        SELECT * FROM UpcomingEvents 
        WHERE PriestID = ? 
        AND EventDate = ? 
        AND StartTime < ? 
        AND EndTime > ?
        AND (Status = 'Booked' OR Status = 'Pending')
    ";
    
        // If eventID exists, exclude it from the conflict check
        if ($eventID) {
            $stmt_check_availability_sql .= " AND EventID != ?";
        }
    
        $stmt_check_availability = $conn->prepare($stmt_check_availability_sql);
        if (!$stmt_check_availability) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
    
        if ($eventID) {
            // Include EventID in bind_param
            $stmt_check_availability->bind_param('isssi', $priestID, $scheduleDate, $endTimeStr, $startTimeStr, $eventID);
        } else {
            $stmt_check_availability->bind_param('isss', $priestID, $scheduleDate, $endTimeStr, $startTimeStr);
        }
    
        if (!$stmt_check_availability->execute()) {
            throw new Exception('Execute failed: ' . $stmt_check_availability->error);
        }
        $result_availability = $stmt_check_availability->get_result();
        if ($result_availability->num_rows > 0) {
            $stmt_check_availability->close();
            throw new Exception('The selected priest is not available at the specified time.');
        }
        $stmt_check_availability->close();
    }

    // Update the request status to 'Approved' and assign the priest
    $adminUserID = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
    if (!$adminUserID || !filter_var($adminUserID, FILTER_VALIDATE_INT)) {
        throw new Exception('Admin user ID is not set or invalid.');
    }
    $currentTime = date('Y-m-d H:i:s');

    // Prepare the UPDATE query for SacramentRequests
    $stmt_update_request = $conn->prepare("
        UPDATE SacramentRequests 
        SET Status = 'Approved', 
            PriestID = ?, 
            ProcessedBy = ?, 
            ProcessedAt = ? 
        WHERE RefNo = ?
    ");
    if (!$stmt_update_request) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt_update_request->bind_param('iiss', $priestID, $adminUserID, $currentTime, $refNo);
    if (!$stmt_update_request->execute()) {
        throw new Exception('Execute failed: ' . $stmt_update_request->error);
    }
    $stmt_update_request->close();

    // Check if an event already exists
    if ($eventID) {
        // Update the existing event
        $stmt_update_event = $conn->prepare("
            UPDATE UpcomingEvents 
            SET Status = 'Booked', 
                PriestID = ?, 
                StartTime = ?, 
                EndTime = ?, 
                EventDate = ? 
            WHERE EventID = ?
        ");
        if (!$stmt_update_event) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $stmt_update_event->bind_param('isssi', $priestID, $startTimeStr, $endTimeStr, $scheduleDate, $eventID);
        if (!$stmt_update_event->execute()) {
            throw new Exception('Execute failed: ' . $stmt_update_event->error);
        }
        $stmt_update_event->close();
    } else {
        // Create a new event
        $stmt_insert_event = $conn->prepare("
            INSERT INTO UpcomingEvents 
            (EventDate, StartTime, EndTime, SacramentType, PriestID, Status) 
            VALUES (?, ?, ?, ?, ?, 'Booked')
        ");
        if (!$stmt_insert_event) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $stmt_insert_event->bind_param('ssssi', $scheduleDate, $startTimeStr, $endTimeStr, $sacramentType, $priestID);
        if (!$stmt_insert_event->execute()) {
            throw new Exception('Execute failed: ' . $stmt_insert_event->error);
        }

        // Get the new EventID
        $newEventID = $conn->insert_id;

        // Update the SacramentRequest with the new EventID
        $stmt_update_request_event = $conn->prepare("UPDATE SacramentRequests SET EventID = ? WHERE RefNo = ?");
        if (!$stmt_update_request_event) {
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $stmt_update_request_event->bind_param('is', $newEventID, $refNo);
        if (!$stmt_update_request_event->execute()) {
            throw new Exception('Execute failed: ' . $stmt_update_request_event->error);
        }
        $stmt_update_request_event->close();
        $stmt_insert_event->close();
    }

    // Insert a notification for the user
    $notificationText = "Your sacrament request (RefNo: $refNo) has been approved.";
    $stmt_insert_notification = $conn->prepare("INSERT INTO Notifications (UserID, NotificationText) VALUES (?, ?)");
    if (!$stmt_insert_notification) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt_insert_notification->bind_param('is', $userID, $notificationText);
    if (!$stmt_insert_notification->execute()) {
        throw new Exception('Execute failed: ' . $stmt_insert_notification->error);
    }
    $stmt_insert_notification->close();

    // Send email notification (optional)
    // Fetch user's email
    $stmt_user = $conn->prepare("SELECT Email, FirstName FROM Users WHERE UserID = ?");
    if (!$stmt_user) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt_user->bind_param('i', $userID);
    if (!$stmt_user->execute()) {
        throw new Exception('Execute failed: ' . $stmt_user->error);
    }
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();
    $stmt_user->close();

    if ($user && !empty($user['Email'])) {
        $to = $user['Email'];
        $subject = "Your Sacrament Request has been Approved";
        $message = "Dear " . $user['FirstName'] . ",\n\nYour sacrament request (RefNo: $refNo) has been approved.\n\nBest regards,\nSacred Heart of Jesus Parish";
        $headers = "From: no-reply@parish.com";
        // Uncomment the next line to send the email (ensure mail settings are configured)
        // mail($to, $subject, $message, $headers);
    }

    // Commit transaction
    $conn->commit();

    // Redirect back with success message
    header("Location: manage_requestsadmin.php?message=Request approved successfully.");
    exit();

} catch (Exception $e) {
    // Rollback the transaction
    $conn->rollback();

    // Log the error for debugging
    log_error("Approve Request Error: " . $e->getMessage());

    // Redirect back with error message
    header("Location: manage_requestsadmin.php?error=" . urlencode($e->getMessage()));
    exit();
} finally {
    // Close statements and connection if they are set
    if (isset($stmt_request) && $stmt_request) $stmt_request->close();
    if (isset($stmt_update_request) && $stmt_update_request) $stmt_update_request->close();
    if (isset($stmt_check_priest) && $stmt_check_priest) $stmt_check_priest->close();
    if (isset($stmt_check_availability) && $stmt_check_availability) $stmt_check_availability->close();
    if (isset($stmt_update_event) && $stmt_update_event) $stmt_update_event->close();
    if (isset($stmt_insert_event) && $stmt_insert_event) $stmt_insert_event->close();
    if (isset($stmt_update_request_event) && $stmt_update_request_event) $stmt_update_request_event->close();
    if (isset($stmt_insert_notification) && $stmt_insert_notification) $stmt_insert_notification->close();
    if (isset($stmt_user) && $stmt_user) $stmt_user->close();
    if (isset($stmt_event) && $stmt_event) $stmt_event->close();
    $conn->close();
}
?>
