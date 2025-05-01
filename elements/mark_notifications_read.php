<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["userid"])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userID = $_SESSION["userid"];

include 'db.php';

// Mark notifications as read
$sql = "UPDATE Notifications SET IsRead = 1 WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();

echo json_encode(['success' => true]);
?>
