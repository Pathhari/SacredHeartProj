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
        $schedulingOption = $_POST['scheduling_option'] ?? 'pre_scheduled';
        $selectedSlotID = $_POST['SelectedSlot'] ?? null;
        $FullName = $_POST['fullName'] ?? null;
        $Address = $_POST['address'] ?? null;
        $RequesterContact = $_POST['requesterContact'] ?? null;
        $BlessingType = $_POST['blessingType'] ?? null;
        $OtherBlessingType = $_POST['otherBlessingType'] ?? null;
        $LocationOfBlessing = $_POST['locationOfBlessing'] ?? null;

        // If "Other" blessing type is selected, use the other blessing type field
        if ($BlessingType === 'Other' && !empty($OtherBlessingType)) {
            $BlessingType = $OtherBlessingType;
        }

        // Validate location of blessing
        if (empty($LocationOfBlessing)) {
            throw new Exception("Location of the blessing is required.");
        }

        if ($schedulingOption === 'pre_scheduled') {
            // Check if the selected slot is still available
            $sql_check_slot = "SELECT * FROM UpcomingEvents WHERE EventID = ? AND Status = 'Available' FOR UPDATE";
            $stmt_check_slot = $conn->prepare($sql_check_slot);
            $stmt_check_slot->bind_param("i", $selectedSlotID);
            $stmt_check_slot->execute();
            $result_check_slot = $stmt_check_slot->get_result();

            if ($result_check_slot->num_rows == 0) {
                throw new Exception("The selected Blessing slot is no longer available. Please select another slot.");
            }

            // Fetch slot details
            $slot = $result_check_slot->fetch_assoc();
            $PreferredBlessingDate = $slot['EventDate'];
            $PreferredBlessingTime = $slot['StartTime']; // Fetch the StartTime as ScheduleTime
            $PriestID = $slot['PriestID'];

            // Update UpcomingEvents to mark the slot as 'Booked'
            $update_slot = "UPDATE UpcomingEvents SET Status = 'Booked' WHERE EventID = ?";
            $stmt_update_slot = $conn->prepare($update_slot);
            $stmt_update_slot->bind_param("i", $selectedSlotID);

            if (!$stmt_update_slot->execute()) {
                throw new Exception("Failed to update the slot status: " . $stmt_update_slot->error);
            }

        } else {
            throw new Exception("Invalid scheduling option.");
        }

        // Generate a random Reference Number (RefNo)
        $RefNo = strtoupper(bin2hex(random_bytes(4))); // Generates an 8-character alphanumeric RefNo

        // Insert into SacramentRequests table, including ScheduleTime
        $stmt = $conn->prepare("
            INSERT INTO SacramentRequests 
            (RefNo, UserID, SacramentType, PriestID, ScheduleDate, ScheduleTime, Status, EventID) 
            VALUES (?, ?, 'Blessing', ?, ?, ?, 'Pending', ?)
        ");
        $stmt->bind_param("siissi", $RefNo, $UserID, $PriestID, $PreferredBlessingDate, $PreferredBlessingTime, $selectedSlotID);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into SacramentRequests: " . $stmt->error);
        }

        // Insert into BlessingRequest table
        $stmt_blessing = $conn->prepare("
            INSERT INTO BlessingRequest 
            (RefNo, FullName, Address, RequesterContact, BlessingType, BlessingPlace, Status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt_blessing->bind_param(
            "ssssss",
            $RefNo, $FullName, $Address, $RequesterContact, $BlessingType, $LocationOfBlessing
        );

        if (!$stmt_blessing->execute()) {
            throw new Exception("Failed to insert into BlessingRequest: " . $stmt_blessing->error);
        }

        // Commit the transaction
        $conn->commit();

        // Clear form data from session
        unset($_SESSION['form_data']);

        // Set a success message and redirect
        $_SESSION['success_message'] = "Blessing request successfully submitted. Please wait for approval.";
        header("Location: blessing_request.php");
        exit(); // Ensure no further code is executed

    } catch (Exception $e) {
        // Rollback the transaction if anything goes wrong
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST; // Preserve form data to refill the form
        header("Location: blessing_request.php");
        exit();
    } finally {
        // Close statements and connection
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_blessing)) $stmt_blessing->close();
        if (isset($stmt_check_slot)) $stmt_check_slot->close();
        if (isset($stmt_update_slot)) $stmt_update_slot->close();
        $conn->close();
    }
}
?>
