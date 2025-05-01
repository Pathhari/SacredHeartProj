<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include 'db.php';

$sacramentType = $_POST['sacramentType'];
$priestID = $_POST['priest'];
$eventDate = $_POST['eventDate'];
$startTime = $_POST['startTime'];
$endTime = $_POST['endTime'];

if (empty($sacramentType) || empty($priestID) || empty($eventDate) || empty($startTime) || empty($endTime)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Insert the new event into the database
$sql = "INSERT INTO UpcomingEvents (EventDate, StartTime, EndTime, SacramentType, PriestID) VALUES (?, ?, ?, ?, ?)";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ssssi", $eventDate, $startTime, $endTime, $sacramentType, $priestID);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>



<script>

function openEventModal(dateStr) {
    document.getElementById('eventDate').value = dateStr;
    document.getElementById('eventModal').classList.remove('hidden');
}

function closeEventModal() {
    document.getElementById('eventModal').classList.add('hidden');
}

document.getElementById('eventForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Collect form data
    var formData = new FormData(this);

    // Send data to the server via AJAX
    fetch('add_event.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            // Close the modal
            closeEventModal();
            // Refresh calendar events
            calendar.refetchEvents();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
    });
});

</script>