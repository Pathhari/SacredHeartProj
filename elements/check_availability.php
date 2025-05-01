<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];

    $available_slots = [
        '09:00:00' => true,
        '10:00:00' => true,
        '11:00:00' => true,
        '13:00:00' => true,
        '14:00:00' => true,
        '15:00:00' => true,
    ];

    // Fetch existing events to update availability
    $sql = "SELECT * FROM UpcomingEvents WHERE EventDate = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $start_time = $row['StartTime'];
            if (isset($available_slots[$start_time])) {
                $available_slots[$start_time] = false; // Mark the time slot as unavailable
            }
        }
    }

    echo json_encode($available_slots);
    exit();
}


?>