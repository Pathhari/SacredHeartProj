<?php
// Start session
session_start();
header('Content-Type: application/json');

// Check if the user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

// Include database connection file
include 'db.php';

// Collect form data and validate input
$event_date = $_POST['event_date'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;
$sacrament_type = $_POST['sacrament_type'] ?? null;
$priest_id = $_POST['priest_id'] ?? null;
$status = 'Available'; // Default status

if (!$event_date || !$start_time || !$end_time || !$sacrament_type || !$priest_id) {
    echo json_encode(["success" => false, "message" => "Incomplete form data."]);
    exit;
}

// Convert times to DateTime objects for accurate comparison
$new_start = new DateTime("$event_date $start_time");
$new_end = new DateTime("$event_date $end_time");

if ($new_end <= $new_start) {
    echo json_encode(["success" => false, "message" => "End time must be after start time."]);
    exit;
}

$conn->begin_transaction();

try {
    // Check for time conflicts with existing events
    $sql_conflict = "
        SELECT * FROM UpcomingEvents 
        WHERE EventDate = ? 
        AND ((StartTime < ? AND EndTime > ?) OR (StartTime >= ? AND StartTime < ?))
    ";
    $stmt_conflict = $conn->prepare($sql_conflict);
    $stmt_conflict->bind_param("sssss", $event_date, $end_time, $start_time, $start_time, $end_time);
    $stmt_conflict->execute();
    $result_conflict = $stmt_conflict->get_result();

    if ($result_conflict->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "There is a scheduling conflict with another event."]);
        exit;
    }

    // Check priest availability
    $sql_priest_conflict = "
        SELECT * FROM UpcomingEvents 
        WHERE EventDate = ? 
        AND ((StartTime < ? AND EndTime > ?) OR (StartTime >= ? AND StartTime < ?)) 
        AND PriestID = ?
    ";
    $stmt_priest_conflict = $conn->prepare($sql_priest_conflict);
    $stmt_priest_conflict->bind_param("sssssi", $event_date, $end_time, $start_time, $start_time, $end_time, $priest_id);
    $stmt_priest_conflict->execute();
    $result_priest_conflict = $stmt_priest_conflict->get_result();

    if ($result_priest_conflict->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "The selected priest is not available at the chosen time."]);
        exit;
    }

    // Insert the event
    $sql_insert = "
        INSERT INTO UpcomingEvents (EventDate, StartTime, EndTime, SacramentType, PriestID, Status)
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssssis", $event_date, $start_time, $end_time, $sacrament_type, $priest_id, $status);

    if ($stmt_insert->execute()) {
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Event created successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error inserting event: " . $stmt_insert->error]);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Transaction failed: " . $e->getMessage()]);
}
?>
