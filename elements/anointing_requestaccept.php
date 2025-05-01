<?php
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
        $FullName = $_POST['fullName'] ?? null;
        $Address = $_POST['address'] ?? null;
        $Age = $_POST['age'] ?? null;
        $PhoneNumber = $_POST['phoneNumber'] ?? null;
        $Gender = $_POST['gender'] ?? null;
        $LocationOfAnointing = $_POST['locationOfAnointing'] ?? null;
        $PreferredDateTime = $_POST['preferredDateTime'] ?? null;

        // Validate required fields
        if (!$FullName || !$Address || !$Age || !$PhoneNumber || !$Gender || !$LocationOfAnointing || !$PreferredDateTime) {
            throw new Exception("All fields are required.");
        }

        // Convert the PreferredDateTime to Date and Time components
        $dateTime = new DateTime($PreferredDateTime);
        $PreferredDate = $dateTime->format('Y-m-d');
        $PreferredTime = $dateTime->format('H:i:s');

        // Conflict Checking: Check for events with status 'Booked' or 'Pre-Booked' only
        $sql_conflict = "
            SELECT * FROM UpcomingEvents 
            WHERE EventDate = ? 
            AND StartTime = ? 
            AND Status IN ('Booked', 'Pre-Booked')
        ";
        $stmt_conflict = $conn->prepare($sql_conflict);
        $stmt_conflict->bind_param('ss', $PreferredDate, $PreferredTime);
        $stmt_conflict->execute();
        $result_conflict = $stmt_conflict->get_result();

        if ($result_conflict->num_rows > 0) {
            throw new Exception("The selected date and time is not available. Please choose another date and time.");
        }

        // Assign a priest (if necessary)
        $PriestID = null; // You may adjust this logic as needed

        // Create a new event in UpcomingEvents with Status 'Pre-Booked'
        $sql_insert_event = "
            INSERT INTO UpcomingEvents (EventDate, StartTime, EndTime, SacramentType, PriestID, Status)
            VALUES (?, ?, ?, ?, ?, 'Pre-Booked')
        ";
        $stmt_insert_event = $conn->prepare($sql_insert_event);
        $endTime = date('H:i:s', strtotime('+1 hour', strtotime($PreferredTime))); // Assuming event lasts 1 hour
        $sacramentType = 'Anointing of the Sick';
        $stmt_insert_event->bind_param("ssssi", $PreferredDate, $PreferredTime, $endTime, $sacramentType, $PriestID);

        if (!$stmt_insert_event->execute()) {
            throw new Exception("Failed to create a new event: " . $stmt_insert_event->error);
        }

        $EventID = $stmt_insert_event->insert_id;

        // Generate a random Reference Number (RefNo)
        $RefNo = strtoupper(bin2hex(random_bytes(4))); // Generates an 8-character alphanumeric RefNo

        // Insert into SacramentRequests table
        $stmt = $conn->prepare("
            INSERT INTO SacramentRequests 
            (RefNo, UserID, SacramentType, PriestID, ScheduleDate, ScheduleTime, Status, EventID) 
            VALUES (?, ?, 'Anointing of the Sick', ?, ?, ?, 'Pending', ?)
        ");
        $stmt->bind_param("siissi", $RefNo, $UserID, $PriestID, $PreferredDate, $PreferredTime, $EventID);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into SacramentRequests: " . $stmt->error);
        }

        // Insert into AnointingOfTheSickRequest table
        $stmt_anointing = $conn->prepare("
            INSERT INTO AnointingOfTheSickRequest 
            (RefNo, FullName, Address, Age, PhoneNumber, Gender, LocationOfAnointing, PreferredDateTime, Status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt_anointing->bind_param(
            "ssisssss", 
            $RefNo, $FullName, $Address, $Age, $PhoneNumber, $Gender, $LocationOfAnointing, $PreferredDateTime
        );

        if (!$stmt_anointing->execute()) {
            throw new Exception("Failed to insert into AnointingOfTheSickRequest: " . $stmt_anointing->error);
        }


        // **Handle File Uploads**
        $uploads_dir = 'anointinguploads/';

        // Ensure upload directory exists
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
        }

        // List of files to be uploaded and their respective document types
        $files = [
            'baptismalCertificate' => 'Baptismal Certificate',
            'proofOfAddress' => 'Proof of Address'
        ];

        foreach ($files as $input_name => $document_type) {
            if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
                $file_name = basename($_FILES[$input_name]['name']);
                $target_path = $uploads_dir . $file_name;

                // Move the uploaded file to the specified directory
                if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target_path)) {
                    // Insert the document details into the UploadedDocuments table
                    $doc_stmt = $conn->prepare("
                        INSERT INTO AnointingOfTheSickRequestUploadedDocuments (RequesterID, DocumentType, FilePath) 
                        VALUES (?, ?, ?)
                    ");
                    $RequesterID = $stmt_anointing->insert_id;
                    $doc_stmt->bind_param("iss", $RequesterID, $document_type, $target_path);
                    if (!$doc_stmt->execute()) {
                        throw new Exception("Failed to insert into AnointingOfTheSickRequestUploadedDocuments: " . $doc_stmt->error);
                    }
                    $doc_stmt->close();
                } else {
                    throw new Exception("Failed to upload file $file_name. Please check directory permissions.");
                }
            }
        }

        // Commit the transaction
        $conn->commit();

        // Clear form data from session
        unset($_SESSION['form_data']);

        // Set a success message and redirect
        $_SESSION['success_message'] = "Anointing of the Sick request successfully submitted. Please wait for approval.";
        header("Location: anointing_request.php");
        exit();

    } catch (Exception $e) {
        // Rollback the transaction if anything goes wrong
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST; // Preserve form data to refill the form
        header("Location: anointing_request.php");
        exit();
    } finally {
        // Close statements and connection
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_anointing)) $stmt_anointing->close();
        if (isset($stmt_conflict)) $stmt_conflict->close();
        if (isset($stmt_insert_event)) $stmt_insert_event->close();
        $conn->close();
    }
}
?>