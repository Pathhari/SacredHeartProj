<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['date'] ?? null;
    $time = $_GET['time'] ?? null;

    if (!$date || !$time) {
        echo json_encode(['success' => false, 'message' => 'Date and time are required.']);
        exit();
    }

    // Calculate end time (assuming 1-hour duration)
    $startTime = $time;
    $endTime = date('H:i:s', strtotime($startTime) + 3600);

    // Fetch priests who are available at the given date and time
    $sql = "
        SELECT PriestID, PriestName
        FROM Priests
        WHERE Availability = 'Available'
        AND PriestID NOT IN (
            SELECT PriestID FROM UpcomingEvents
            WHERE EventDate = ?
            AND StartTime < ?
            AND EndTime > ?
            AND PriestID IS NOT NULL
        )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $date, $endTime, $startTime);
    $stmt->execute();
    $result = $stmt->get_result();

    $priests = [];
    while ($row = $result->fetch_assoc()) {
        $priests[] = $row;
    }

    if (count($priests) > 0) {
        echo json_encode(['success' => true, 'priests' => $priests]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No priests available at the selected date and time.']);
    }
}
?>
