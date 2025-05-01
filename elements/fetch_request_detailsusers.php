<?php
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in and has the user role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] != 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include the database connection file
include 'db.php';

// Check if 'ref' is set in GET parameter
if (!isset($_GET['ref'])) {
    echo json_encode(['success' => false, 'message' => 'No reference number provided']);
    exit;
}

$refNo = $_GET['ref'];
$userId = $_SESSION["userid"];

// Fetch the SacramentRequest for the given RefNo, ensuring it belongs to the logged-in user
$sql = "SELECT SacramentRequests.*, Users.FirstName, Users.LastName, Users.Email, Priests.PriestName AS AssignedPriest
        FROM SacramentRequests 
        JOIN Users ON SacramentRequests.UserID = Users.UserID 
        LEFT JOIN Priests ON SacramentRequests.PriestID = Priests.PriestID
        WHERE SacramentRequests.RefNo = ? AND SacramentRequests.UserID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement']);
    exit;
}

$stmt->bind_param('si', $refNo, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'No request found or access denied']);
    exit;
}

$request = $result->fetch_assoc();

// Remove ", SDB" suffix if present in AssignedPriest
if (!empty($request['AssignedPriest'])) {
    $request['AssignedPriest'] = str_replace(', SDB', '', $request['AssignedPriest']);
}

// Determine the SacramentType
$sacramentType = $request['SacramentType'];

// Initialize variables
$details = [];
$godparents = [];
$documents = [];

// Fetch details from the specific table based on SacramentType
switch ($sacramentType) {
    case 'Baptism':
        $details = fetchDetails($conn, 'BaptismRequest', $refNo);
        $godparents = fetchGodparents($conn, $details['RequesterID']);
        $documents = fetchDocuments($conn, 'BaptismUploadedDocuments', $details['RequesterID']);
        break;

    case 'Wedding':
        $details = fetchDetails($conn, 'WeddingRequest', $refNo);
        $documents = fetchDocuments($conn, 'WeddingUploadedDocuments', $details['RequesterID']);
        break;

    case 'Confirmation':
        $details = fetchDetails($conn, 'ConfirmationRequest', $refNo);
        $documents = fetchDocuments($conn, 'ConfirmationUploadedDocuments', $details['RequesterID']);
        break;

    case 'First Communion':
        $details = fetchDetails($conn, 'FirstCommunionRequest', $refNo);
        $documents = fetchDocuments($conn, 'FirstCommunionUploadedDocuments', $details['RequesterID']);
        break;

    case 'Anointing of the Sick':
        $details = fetchDetails($conn, 'AnointingOfTheSickRequest', $refNo);
        $documents = fetchDocuments($conn, 'AnointingOfTheSickRequestUploadedDocuments', $details['RequesterID']);
        break;

    case 'Funeral and Burial':
        $details = fetchDetails($conn, 'FuneralAndBurialRequest', $refNo);
        break;

    case 'Blessing':
        $details = fetchDetails($conn, 'BlessingRequest', $refNo);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown sacrament type']);
        exit;
}

$conn->close();

// Prepare the response
$response = [
    'success' => true,
    'request' => $request,
    'details' => $details,
    'godparents' => $godparents,  // This will be empty for non-baptism requests
    'documents' => $documents
];

// Send the response in JSON format
echo json_encode($response);

// Function to fetch sacrament request details
function fetchDetails($conn, $tableName, $refNo) {
    $sql_details = "SELECT * FROM $tableName WHERE RefNo = ?";
    $stmt_details = $conn->prepare($sql_details);
    $stmt_details->bind_param('s', $refNo);
    $stmt_details->execute();
    $result = $stmt_details->get_result();
    return $result->fetch_assoc();
}

// Function to fetch godparents
function fetchGodparents($conn, $requesterID) {
    $godparents = [];
    $sql_godparents = "SELECT * FROM Godparents WHERE RequesterID = ?";
    $stmt_godparents = $conn->prepare($sql_godparents);
    $stmt_godparents->bind_param('i', $requesterID);
    $stmt_godparents->execute();
    $result = $stmt_godparents->get_result();
    while ($gp = $result->fetch_assoc()) {
        $godparents[] = $gp;
    }
    return $godparents;
}

// Function to fetch documents
function fetchDocuments($conn, $tableName, $requesterID) {
    $documents = [];
    $sql_documents = "SELECT * FROM $tableName WHERE RequesterID = ?";
    $stmt_documents = $conn->prepare($sql_documents);
    $stmt_documents->bind_param('i', $requesterID);
    $stmt_documents->execute();
    $result = $stmt_documents->get_result();
    while ($doc = $result->fetch_assoc()) {
        $documents[] = $doc;
    }
    return $documents;
}
?>
