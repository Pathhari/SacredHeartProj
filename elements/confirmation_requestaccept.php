<?php
// Include the database connection
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch UserID from session
        $UserID = $_SESSION['userid'];

        // Store form data in session to retain values if needed
        $_SESSION['form_data'] = $_POST;

        // Retrieve form data
        $selectedSlotID = $_POST['SelectedSlot'] ?? null;
        $fullName = $_POST['fullName'] ?? null;
        $fatherFullName = $_POST['fatherFullName'] ?? null;
        $motherFullName = $_POST['motherFullName'] ?? null;
        $residence = $_POST['residence'] ?? null;

        if (!$selectedSlotID) {
            throw new Exception("No confirmation slot selected. Please select an available slot.");
        }

        // Check if the selected slot is still available
        $sql_check_slot = "SELECT * FROM UpcomingEvents WHERE EventID = ? AND Status = 'Available' FOR UPDATE";
        $stmt_check_slot = $conn->prepare($sql_check_slot);
        $stmt_check_slot->bind_param("i", $selectedSlotID);
        $stmt_check_slot->execute();
        $result_check_slot = $stmt_check_slot->get_result();

        if ($result_check_slot->num_rows == 0) {
            throw new Exception("The selected confirmation slot is no longer available. Please select another slot.");
        }

        // Fetch slot details
        $slot = $result_check_slot->fetch_assoc();
        $PriestID = $slot['PriestID'];

        // Format ScheduleDate and ScheduleTime
        $ScheduleDate = date('Y-m-d', strtotime($slot['EventDate']));
        $ScheduleTime = date('H:i:s', strtotime($slot['StartTime']));

        // Debugging statements
        error_log("ScheduleDate: " . $ScheduleDate);
        error_log("ScheduleTime: " . $ScheduleTime);

        // Update UpcomingEvents to mark the slot as 'Booked'
        $update_slot = "UPDATE UpcomingEvents SET Status = 'Booked' WHERE EventID = ?";
        $stmt_update_slot = $conn->prepare($update_slot);
        $stmt_update_slot->bind_param("i", $selectedSlotID);

        if (!$stmt_update_slot->execute()) {
            throw new Exception("Failed to update the slot status: " . $stmt_update_slot->error);
        }

        // Generate a random Reference Number (RefNo)
        $RefNo = strtoupper(bin2hex(random_bytes(4))); // Generates an 8-character alphanumeric RefNo

        // Insert into SacramentRequests table, including ScheduleDate and ScheduleTime
        $stmt = $conn->prepare("
            INSERT INTO SacramentRequests 
            (RefNo, UserID, SacramentType, PriestID, ScheduleDate, ScheduleTime, Status, EventID) 
            VALUES (?, ?, 'Confirmation', ?, ?, ?, 'Pending', ?)
        ");
        $stmt->bind_param("siissi", $RefNo, $UserID, $PriestID, $ScheduleDate, $ScheduleTime, $selectedSlotID);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into SacramentRequests: " . $stmt->error);
        }

        // Insert into ConfirmationRequest table
        $stmt_confirmation = $conn->prepare("
            INSERT INTO ConfirmationRequest 
            (RefNo, FullName, FatherFullName, MotherFullName, Residence, Status) 
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");
        
        $stmt_confirmation->bind_param(
            "sssss", 
            $RefNo, $fullName, $fatherFullName, $motherFullName, $residence
        );

        if (!$stmt_confirmation->execute()) {
            throw new Exception("Failed to insert into ConfirmationRequest: " . $stmt_confirmation->error);
        }

        // Get the last inserted RequestID
        $RequesterID = $stmt_confirmation->insert_id;

        // Handle file uploads
        $uploads_dir = 'confirmationuploads/';

        // Ensure upload directory exists
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true); // Create the directory if it doesn't exist
        }

        // List of files to be uploaded and their respective document types
        $files = [
            'baptismalCertificate' => 'Baptismal Certificate',
            'birthCertificate' => 'Birth Certificate',
            'romanCatholicCertificate' => 'Roman Catholic Baptismal Certificate',
            'confirmationRecommendation' => 'Confirmation Recommendation'
        ];

        foreach ($files as $input_name => $document_type) {
            if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
                $file_name = basename($_FILES[$input_name]['name']);
                $target_path = $uploads_dir . $file_name;

                // Move the uploaded file to the specified directory
                if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target_path)) {
                    // Insert the document details into the ConfirmationUploadedDocuments table
                    $doc_stmt = $conn->prepare("
                        INSERT INTO ConfirmationUploadedDocuments (RequesterID, DocumentType, FilePath) 
                        VALUES (?, ?, ?)
                    ");
                    $doc_stmt->bind_param("iss", $RequesterID, $document_type, $target_path);                
                    if (!$doc_stmt->execute()) {
                        throw new Exception("Failed to insert into ConfirmationUploadedDocuments: " . $doc_stmt->error);
                    }
                    $doc_stmt->close();
                } else {
                    throw new Exception("Failed to upload file $file_name. Please check directory permissions.");
                }
            }
        }

        $conn->commit();

        // Clear form data from session
        unset($_SESSION['form_data']);

        // Set a success message and redirect
        $_SESSION['success_message'] = "Confirmation request successfully submitted. Please wait for approval.";
        header("Location: confirmation_request.php");
        exit(); // Ensure no further code is executed

    } catch (Exception $e) {
        // Rollback the transaction if anything goes wrong
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST; // Preserve form data to refill the form
        header("Location: confirmation_request.php");
        exit();
    } finally {
        // Close statements and connection
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_confirmation)) $stmt_confirmation->close();
        if (isset($stmt_check_slot)) $stmt_check_slot->close();
        if (isset($stmt_update_slot)) $stmt_update_slot->close();
        $conn->close();
    }
}
?>