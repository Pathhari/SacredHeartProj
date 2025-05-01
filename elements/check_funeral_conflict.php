<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferredDateTime = $_POST['preferredDateTime'];

    // Convert the preferredDateTime to Date and Time components
    $dateTime = new DateTime($preferredDateTime);
    $preferredDate = $dateTime->format('Y-m-d');
    $preferredTime = $dateTime->format('H:i:s');

    // Check for conflicts in the UpcomingEvents table
    $sql_conflict = "
        SELECT * FROM UpcomingEvents 
        WHERE EventDate = ? AND StartTime = ? AND Status IN ('Booked', 'Pre-Booked')
    ";
    $stmt = $conn->prepare($sql_conflict);
    $stmt->bind_param('ss', $preferredDate, $preferredTime);
    $stmt->execute();
    $result = $stmt->get_result();

    $conflict = $result->num_rows > 0;

    echo json_encode(['conflict' => $conflict]);
}
?>
