<?php
// Start session
session_start();

// Check if the user is logged in and has the 'user' role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    die('Unauthorized access.');
}

// Include database connection file
include 'db.php';

// Check if 'doc_id' is set
if (!isset($_GET['doc_id'])) {
    die('No document specified.');
}

$doc_id = intval($_GET['doc_id']);
$userId = $_SESSION['userid'];

// Initialize variables
$found = false;
$filePath = '';
$documentType = '';
$refNo = '';  // Initialize $refNo here to avoid undefined variable error

// List of document tables and their corresponding request tables
$doc_tables = [
    'BaptismUploadedDocuments' => 'BaptismRequest',
    'WeddingUploadedDocuments' => 'WeddingRequest',
    'ConfirmationUploadedDocuments' => 'ConfirmationRequest',
    'FirstCommunionUploadedDocuments' => 'FirstCommunionRequest',
    'AnointingOfTheSickRequestUploadedDocuments' => 'AnointingOfTheSickRequest',
];

// Iterate through document tables to find the document
foreach ($doc_tables as $doc_table => $request_table) {
    $sql = "SELECT d.FilePath, d.DocumentType, r.RefNo
            FROM $doc_table d
            JOIN $request_table r ON d.RequesterID = r.RequesterID
            WHERE d.DocumentID = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $doc_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $document = $result->fetch_assoc();
            $filePath = $document['FilePath'];
            $documentType = $document['DocumentType'];
            $refNo = $document['RefNo'];
            $found = true;
            $stmt->close();
            break; // Exit the loop once the document is found
        }
        $stmt->close();
    }
}

if (!$found) {
    die('Document not found.');
}

// Verify if the RefNo belongs to the logged-in user
$sql = "SELECT UserID FROM SacramentRequests WHERE RefNo = ? AND UserID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('si', $refNo, $userId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die('Access denied.');
    }
    $stmt->close();
} else {
    die('Database error.');
}

// Serve the file if it exists
if (file_exists($filePath)) {
    // Get the file's MIME type
    $mimeType = mime_content_type($filePath);

    // Set headers to initiate file download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} else {
    die('File does not exist.');
}


?>
