<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'admin') {
    echo json_encode([]);
    exit;
}

include 'db.php';

$sql = "
    SELECT UpcomingEvents.EventID, UpcomingEvents.EventDate, UpcomingEvents.StartTime, UpcomingEvents.EndTime, UpcomingEvents.SacramentType, Priests.PriestName
    FROM UpcomingEvents
    LEFT JOIN Priests ON UpcomingEvents.PriestID = Priests.PriestID
";

$events = [];
if ($stmt = $conn->prepare($sql)) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'title' => $row['SacramentType'] . ' (' . $row['PriestName'] . ')',
                'start' => $row['EventDate'] . 'T' . $row['StartTime'],
                'end' => $row['EventDate'] . 'T' . $row['EndTime'],
                'allDay' => false
            ];
        }
    }
    $stmt->close();
}

echo json_encode($events);
$conn->close();
?>
