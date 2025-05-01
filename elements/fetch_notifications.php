<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["userid"])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userID = $_SESSION["userid"];

include 'db.php';

// Fetch unread notifications
$sql = "SELECT * FROM Notifications WHERE UserID = ? AND IsRead = 0 ORDER BY CreatedAt DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>
