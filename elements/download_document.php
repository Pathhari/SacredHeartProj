<?php
// Start session
session_start();

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'admin') {
    die('Unauthorized access.');
}

// Include database connection file
include 'db.php';

// Check if 'doc_id' is set
if (!isset($_GET['doc_id'])) {
    die('No document specified.');
}

$doc_id = intval($_GET['doc_id']);

// Try to fetch the document from both UploadedDocuments and WeddingUploadedDocuments
$sql = "SELECT * FROM BaptismUploadedDocuments WHERE DocumentID = '$doc_id'
        UNION
        SELECT * FROM WeddingUploadedDocuments WHERE DocumentID = '$doc_id'
        UNION
        SELECT * FROM ConfirmationUploadedDocuments WHERE DocumentID = '$doc_id'
        UNION
        SELECT * FROM FirstCommunionUploadedDocuments WHERE DocumentID = '$doc_id'
        UNION
        SELECT * FROM anointingofthesickrequestuploadeddocuments WHERE DocumentID = '$doc_id' ";
        

        
        

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die('Document not found.');
}

$document = $result->fetch_assoc();
$filePath = $document['FilePath'];
$documentType = $document['DocumentType'];

// Serve the file if it exists
if (file_exists($filePath)) {
    // Get the file's MIME type
    $mimeType = mime_content_type($filePath);

    // Set headers
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
