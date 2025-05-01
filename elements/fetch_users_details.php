<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include database connection file
include 'db.php';

// Check if 'id' is set in GET parameter
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No user ID provided']);
    exit;
}

$userID = $_GET['id'];

// Validate that $userID is numeric to prevent SQL errors
if (!is_numeric($userID)) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Fetch the user details using prepared statements
$sql = "SELECT UserID, FirstName, LastName, Email, Role, Gender, Address, DATE_FORMAT(CreatedAt, '%M %d, %Y') as CreatedAt FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}


$stmt->bind_param('i', $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'No user found for UserID: ' . $userID]);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Prepare the response
$response = [
    'success' => true,
    'user' => $user
];

// Send the response in JSON format
echo json_encode($response);
?>
