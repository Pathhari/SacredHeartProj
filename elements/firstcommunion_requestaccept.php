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

        // Retrieve form data
        $selectedSlotID = $_POST['SelectedSlot'] ?? null;
        $FullNameChild = $_POST['fullNameChild'] ?? null;
        $DateOfBirth = $_POST['dateOfBirth'] ?? null;
        $PlaceOfBirth = $_POST['placeOfBirth'] ?? null;
        $BaptismalDate = $_POST['baptismalDate'] ?? null;
        $BaptismalParish = $_POST['baptismalParish'] ?? null;
        $FatherName = $_POST['fatherName'] ?? null;
        $MotherName = $_POST['motherName'] ?? null;
        $ParentGuardianName = $_POST['parentGuardianName'] ?? null;
        $PhoneNumber = $_POST['phoneNumber'] ?? null;
        $EmailAddress = $_POST['emailAddress'] ?? null;

        // Validate the selected slot
        if (empty($selectedSlotID)) {
            throw new Exception("No First Communion slot selected. Please select an available slot.");
        }

        // Check if the selected slot is still available
        $sql_check_slot = "SELECT * FROM UpcomingEvents WHERE EventID = ? AND Status = 'Available' FOR UPDATE";
        $stmt_check_slot = $conn->prepare($sql_check_slot);
        $stmt_check_slot->bind_param("i", $selectedSlotID);
        $stmt_check_slot->execute();
        $result_check_slot = $stmt_check_slot->get_result();

        if ($result_check_slot->num_rows === 0) {
            throw new Exception("The selected First Communion slot is no longer available. Please select another slot.");
        }

        // Fetch slot details
        $slot = $result_check_slot->fetch_assoc();
        $PriestID = $slot['PriestID'];
        $ScheduleDate = $slot['EventDate'];
        $ScheduleTime = $slot['StartTime'];

        // Mark the slot as "Booked"
        $sql_update_slot = "UPDATE UpcomingEvents SET Status = 'Booked' WHERE EventID = ?";
        $stmt_update_slot = $conn->prepare($sql_update_slot);
        $stmt_update_slot->bind_param("i", $selectedSlotID);
        if (!$stmt_update_slot->execute()) {
            throw new Exception("Failed to update the slot status: " . $stmt_update_slot->error);
        }

        // Generate a random Reference Number (RefNo)
        $RefNo = strtoupper(bin2hex(random_bytes(4))); // Generates an 8-character alphanumeric RefNo

        // Insert into SacramentRequests table
        $stmt = $conn->prepare("
            INSERT INTO SacramentRequests 
            (RefNo, UserID, SacramentType, PriestID, ScheduleDate, ScheduleTime, Status, EventID) 
            VALUES (?, ?, 'First Communion', ?, ?, ?, 'Pending', ?)
        ");
        $stmt->bind_param("siissi", $RefNo, $UserID, $PriestID, $ScheduleDate, $ScheduleTime, $selectedSlotID);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into SacramentRequests: " . $stmt->error);
        }

        // Insert into FirstCommunionRequest table
        $stmt_communion = $conn->prepare("
            INSERT INTO FirstCommunionRequest 
            (RefNo, FullNameChild, DateOfBirth, PlaceOfBirth, BaptismalDate, BaptismalParish, 
            FatherName, MotherName, ParentGuardianName, PhoneNumber, EmailAddress, Status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt_communion->bind_param(
            "sssssssssss", 
            $RefNo, $FullNameChild, $DateOfBirth, $PlaceOfBirth, $BaptismalDate, $BaptismalParish, 
            $FatherName, $MotherName, $ParentGuardianName, $PhoneNumber, $EmailAddress
        );

        if (!$stmt_communion->execute()) {
            throw new Exception("Failed to insert into FirstCommunionRequest: " . $stmt_communion->error);
        }


        

        // Handle file uploads
        $uploads_dir = 'communionuploads/'; // Specify the upload directory

        // Ensure upload directory exists
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true); // Create the directory if it doesn't exist
        }

        // List of files to be uploaded and their respective document types
        $files = [
            'baptismalCertificate' => 'Baptismal Certificate',
            'becCertification' => 'BEC Certification',
            'proofOfAddress' => 'Proof of Address'
        ];

        foreach ($files as $input_name => $document_type) {
            if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
                $file_name = basename($_FILES[$input_name]['name']);
                $target_path = $uploads_dir . $file_name;

                // Move the uploaded file to the specified directory
                if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target_path)) {
                    // Insert the document details into the FirstCommunionUploadedDocuments table
                    $doc_stmt = $conn->prepare("
                        INSERT INTO FirstCommunionUploadedDocuments (RequesterID, DocumentType, FilePath) 
                        VALUES (?, ?, ?)
                    ");
                    $doc_stmt->bind_param("iss", $RequesterID, $document_type, $target_path);
                    if (!$doc_stmt->execute()) {
                        throw new Exception("Failed to insert into FirstCommunionUploadedDocuments: " . $doc_stmt->error);
                    }
                    $doc_stmt->close();
                } else {
                    throw new Exception("Failed to upload file $file_name. Please check directory permissions.");
                }
            }
        }


        // Commit the transaction
        $conn->commit();

        // Set a success message and redirect
        $_SESSION['success_message'] = "First Communion request successfully submitted for {$ScheduleDate} at {$ScheduleTime}. Please wait for approval.";
        header("Location: firstcommunion_request.php");
        exit();

    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: firstcommunion_request.php");
        exit();
    } finally {
        // Close statements and connection
        $stmt_check_slot->close();
        $stmt_update_slot->close();
        $stmt->close();
        $stmt_communion->close();
        $conn->close();
    }
}
?>

