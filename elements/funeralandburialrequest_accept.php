<?php
// Include the database connection
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Retrieve form data
        $PreferredFuneralDateTime = $_POST['preferred_date_time'] ?? null;
        $DeceasedFullName = $_POST['deceasedFullName'] ?? null;
        $DeceasedDateOfBirth = $_POST['dob'] ?? null;
        $DeceasedDateOfDeath = $_POST['dod'] ?? null;
        $Age = $_POST['age'] ?? null;
        $CStatus = $_POST['status'] ?? null;
        $DFatherName = $_POST['father_name'] ?? null;
        $DMotherName = $_POST['mother_name'] ?? null;
        $SpouseName = $_POST['spouse_name'] ?? null;
        $CDeath = $_POST['causeOfDeath'] ?? null;
        $Address = $_POST['address'] ?? null;
        $BLocation = $_POST['burialPlace'] ?? null;
        $BMassTime = $_POST['mass_time'] ?? null;
        $FuneralServiceType = $_POST['funeral_service_type'] ?? null;
        $SacReceived = $_POST['sacraments_received'] ?? null;
        $FamilyRepresentative = $_POST['family_representative'] ?? null;
        $PriestID = $_POST['officiating_priest_id'] ?? null; // Assuming PriestID is fetched via a dropdown or selection.
        $GKK = $_POST['gkk'] ?? null;
        $Parish = $_POST['parish'] ?? null;
        $President = $_POST['president'] ?? null;
        $VicePresident = $_POST['vice_president'] ?? null;
        $Secretary = $_POST['secretary'] ?? null;
        $Treasurer = $_POST['treasurer'] ?? null;
        $PSPRepresentative = $_POST['psp_representative'] ?? null;

        // Fetch UserID from session
        $UserID = $_SESSION['userid'];

        // Split PreferredFuneralDateTime into date and time components
        $dateTime = new DateTime($PreferredFuneralDateTime);
        $PreferredDate = $dateTime->format('Y-m-d');
        $PreferredTime = $dateTime->format('H:i:s');
        $endTime = (new DateTime($PreferredTime))->modify('+1 hour')->format('H:i:s'); // Assuming 1-hour events

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

        // Create a new event in UpcomingEvents with status 'Pre-Booked'
        $stmt_insert_event = $conn->prepare("
            INSERT INTO UpcomingEvents 
            (EventDate, StartTime, EndTime, SacramentType, PriestID, Status) 
            VALUES (?, ?, ?, 'Funeral and Burial', ?, 'Pre-Booked')
        ");
        $stmt_insert_event->bind_param("sssi", $PreferredDate, $PreferredTime, $endTime, $PriestID);

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
            VALUES (?, ?, 'Funeral and Burial', ?, ?, ?, 'Pending', ?)
        ");
        $stmt->bind_param("siissi", $RefNo, $UserID, $PriestID, $PreferredDate, $PreferredTime, $EventID);

        // Execute the statement for SacramentRequests
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into SacramentRequests: " . $stmt->error);
        }

        // Insert into FuneralAndBurialRequest table
// Insert into FuneralAndBurialRequest table
$stmt_funeral = $conn->prepare("
    INSERT INTO FuneralAndBurialRequest 
    (RefNo, PreferredFuneralDate, DeceasedFullName, DeceasedDateOfBirth, DeceasedDateOfDeath, Age, 
    CStatus, DFatherName, DMotherName, SpouseName, CDeath, Address, BLocation, BMassTime, 
    FuneralServiceType, SacReceived, FamilyRepresentative, GKK, Parish, President, 
    VicePresident, Secretary, Treasurer, PSPRepresentative) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt_funeral->bind_param(
    "sssssi" . str_repeat('s', 18),
    $RefNo, $PreferredDate, $DeceasedFullName, $DeceasedDateOfBirth, $DeceasedDateOfDeath, $Age,
    $CStatus, $DFatherName, $DMotherName, $SpouseName, $CDeath, $Address, $BLocation, $BMassTime, 
    $FuneralServiceType, $SacReceived, $FamilyRepresentative, $GKK, $Parish, $President, 
    $VicePresident, $Secretary, $Treasurer, $PSPRepresentative
);

// Execute the statement for FuneralAndBurialRequest
if (!$stmt_funeral->execute()) {
    throw new Exception("Failed to insert into FuneralAndBurialRequest: " . $stmt_funeral->error);
}

        // Commit the transaction
        $conn->commit();

        // Set a success message and redirect
        $_SESSION['success_message'] = "Funeral and Burial Request successfully submitted. Please wait for approval.";
        header("Location: funeralandburialrequest.php");
        exit(); // Ensure no further code is executed

    } catch (Exception $e) {
        // Rollback the transaction if anything goes wrong
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST; // Preserve form data to refill the form
        header("Location: funeralandburialrequest.php");
        exit();
    } finally {
        // Close statements and connection
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_funeral)) $stmt_funeral->close();
        if (isset($stmt_conflict)) $stmt_conflict->close();
        if (isset($stmt_insert_event)) $stmt_insert_event->close();
        $conn->close();
    }
}
?>
