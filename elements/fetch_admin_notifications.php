<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION["userid"]) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin not logged in']);
    exit;
}

include 'db.php';

// Fetch pending sacrament requests
$sql = "SELECT RefNo, SacramentType, CreatedAt FROM SacramentRequests WHERE Status = 'Pending' ORDER BY CreatedAt DESC";
$result = $conn->query($sql);

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'NotificationText' => 'New ' . $row['SacramentType'] . ' request (Ref No: ' . $row['RefNo'] . ') submitted on ' . date('F j, Y, g:i a', strtotime($row['CreatedAt'])),
        'RefNo' => $row['RefNo'],
        'SacramentType' => $row['SacramentType'],
        'CreatedAt' => $row['CreatedAt']
    ];
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>
