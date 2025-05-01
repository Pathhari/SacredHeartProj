<?php
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

// Check if 'ref' is set in GET parameter
if (!isset($_GET['ref'])) {
    echo json_encode(['success' => false, 'message' => 'No reference number provided']);
    exit;
}

$refNo = $_GET['ref'];

// Fetch the SacramentRequest with user and priest information
// Fetch the SacramentRequest with user and priest information
$sql = "SELECT SacramentRequests.*, Users.FirstName, Users.LastName, Users.Email, Priests.PriestName 
        FROM SacramentRequests 
        JOIN Users ON SacramentRequests.UserID = Users.UserID 
        LEFT JOIN Priests ON SacramentRequests.PriestID = Priests.PriestID 
        WHERE SacramentRequests.RefNo = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $refNo);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'No request found']);
    exit;
}
$request = $result->fetch_assoc();


// Determine the SacramentType
$sacramentType = $request['SacramentType'];

// Initialize variables
$details = [];
$godparents = [];
$documents = [];



// Fetch details from the specific table based on SacramentType
switch ($sacramentType) {
    case 'Baptism':
        // Fetch details from BaptismRequest table
        $sql_details = "SELECT * FROM BaptismRequest WHERE RefNo = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param('s', $refNo);
        $stmt_details->execute();
        $details_result = $stmt_details->get_result();

        if ($details_result->num_rows > 0) {
            $details = $details_result->fetch_assoc();

            // Fetch godparents
            $requesterID = $details['RequesterID'];
            $sql_godparents = "SELECT * FROM Godparents WHERE RequesterID = ?";
            $stmt_godparents = $conn->prepare($sql_godparents);
            $stmt_godparents->bind_param('i', $requesterID);
            $stmt_godparents->execute();
            $godparents_result = $stmt_godparents->get_result();

            while ($gp = $godparents_result->fetch_assoc()) {
                $godparents[] = $gp;
            }

            // Fetch uploaded documents
            $sql_documents = "SELECT * FROM BaptismUploadedDocuments WHERE RequesterID = ?";
            $stmt_documents = $conn->prepare($sql_documents);
            $stmt_documents->bind_param('i', $requesterID);
            $stmt_documents->execute();
            $documents_result = $stmt_documents->get_result();

            while ($doc = $documents_result->fetch_assoc()) {
                $documents[] = $doc;
            }
        }
        break;

    case 'Wedding':
        // Fetch details from WeddingRequest table
        $sql_details = "SELECT * FROM WeddingRequest WHERE RefNo = ?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param('s', $refNo);
        $stmt_details->execute();
        $details_result = $stmt_details->get_result();

        if ($details_result->num_rows > 0) {
            $details = $details_result->fetch_assoc();

            // Fetch uploaded wedding documents
            $requesterID = $details['RequesterID'];
            $sql_documents = "SELECT * FROM WeddingUploadedDocuments WHERE RequesterID = ?";
            $stmt_documents = $conn->prepare($sql_documents);
            $stmt_documents->bind_param('i', $requesterID);
            $stmt_documents->execute();
            $documents_result = $stmt_documents->get_result();

            while ($doc = $documents_result->fetch_assoc()) {
                $documents[] = $doc;
            }
        }
        break;
        
            case 'Confirmation':
                // Fetch details from ConfirmationRequest table
                $sql_details = "SELECT * FROM ConfirmationRequest WHERE RefNo = ?";
                $stmt_details = $conn->prepare($sql_details);
                $stmt_details->bind_param('s', $refNo);
                $stmt_details->execute();
                $details_result = $stmt_details->get_result();
            
                if ($details_result->num_rows > 0) {
                    $details = $details_result->fetch_assoc();
            
                    // Fetch uploaded confirmation documents
                    $requesterID = $details['RequesterID'];
                    $sql_documents = "SELECT * FROM ConfirmationUploadedDocuments WHERE RequesterID = ?";
                    $stmt_documents = $conn->prepare($sql_documents);
                    $stmt_documents->bind_param('i', $requesterID);
                    $stmt_documents->execute();
                    $documents_result = $stmt_documents->get_result();
            
                    while ($doc = $documents_result->fetch_assoc()) {
                        $documents[] = $doc;
                    }
                }
                break;
            
                case 'First Communion':
                    // Fetch details from FirstCommunionRequest table
                    $sql_details = "SELECT * FROM FirstCommunionRequest WHERE RefNo = ?";
                    $stmt_details = $conn->prepare($sql_details);
                    $stmt_details->bind_param('s', $refNo);
                    $stmt_details->execute();
                    $details_result = $stmt_details->get_result();
            
                    if ($details_result->num_rows > 0) {
                        $details = $details_result->fetch_assoc();
            
                        // Fetch uploaded First Communion documents
                        $requesterID = $details['RequesterID'];
                        $sql_documents = "SELECT * FROM FirstCommunionUploadedDocuments WHERE RequesterID = ?";
                        $stmt_documents = $conn->prepare($sql_documents);
                        $stmt_documents->bind_param('i', $requesterID);
                        $stmt_documents->execute();
                        $documents_result = $stmt_documents->get_result();
            
                        while ($doc = $documents_result->fetch_assoc()) {
                            $documents[] = $doc;
                        }
                    }
                    break;

                    case 'Anointing of the Sick':
                        // Fetch details from AnointingOfTheSickRequest table
                        $sql_details = "SELECT * FROM AnointingOfTheSickRequest WHERE RefNo = ?";
                        $stmt_details = $conn->prepare($sql_details);
                        $stmt_details->bind_param('s', $refNo);
                        $stmt_details->execute();
                        $details_result = $stmt_details->get_result();
                
                        if ($details_result->num_rows > 0) {
                            $details = $details_result->fetch_assoc();
                
                            // Fetch uploaded Anointing of the Sick documents
                            $requesterID = $details['RequesterID'];
                            $sql_documents = "SELECT * FROM AnointingOfTheSickRequestUploadedDocuments WHERE RequesterID = ?";
                            $stmt_documents = $conn->prepare($sql_documents);
                            $stmt_documents->bind_param('i', $requesterID);
                            $stmt_documents->execute();
                            $documents_result = $stmt_documents->get_result();
                
                            while ($doc = $documents_result->fetch_assoc()) {
                                $documents[] = $doc;
                            }
                        }
                        break;


                case 'Funeral and Burial':
                        // Fetch details from FuneralAndBurialRequest table
                        $sql_details = "SELECT * FROM FuneralAndBurialRequest WHERE RefNo = ?";
                        $stmt_details = $conn->prepare($sql_details);
                        $stmt_details->bind_param('s', $refNo);
                        $stmt_details->execute();
                        $details_result = $stmt_details->get_result();
                    
                        if ($details_result->num_rows > 0) {
                            $details = $details_result->fetch_assoc();
                        }
                        break;  


                case 'Blessing':
                            // Fetch details from BlessingRequest table
                            $sql_details = "SELECT * FROM BlessingRequest WHERE RefNo = ?";
                            $stmt_details = $conn->prepare($sql_details);
                            $stmt_details->bind_param('s', $refNo);
                            $stmt_details->execute();
                            $details_result = $stmt_details->get_result();
                        
                            if ($details_result->num_rows > 0) {
                                $details = $details_result->fetch_assoc();
                            }
                            break;              

    default:
        // Handle other types if necessary
        break;
}


// Fetch feedback for the request
$feedback_sql = "SELECT * FROM Feedback WHERE RefNo = ?";
$feedback_stmt = $conn->prepare($feedback_sql);
$feedback_stmt->bind_param('s', $refNo);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();

if ($feedback_result->num_rows > 0) {
    $feedbackExists = true;
    $feedback = $feedback_result->fetch_assoc();
} else {
    $feedbackExists = false;
}

if ($request['EventID']) {
    $stmt_event = $conn->prepare("SELECT PriestID FROM UpcomingEvents WHERE EventID = ?");
    $stmt_event->bind_param('i', $request['EventID']);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();
    if ($result_event->num_rows > 0) {
        $event = $result_event->fetch_assoc();
        if (!empty($event['PriestID'])) {
            $request['PriestID'] = $event['PriestID'];
        }
    }
    $stmt_event->close();
}

$sacramentsRequiringPriest = ['Baptism', 'Wedding', 'Anointing of the Sick', 'Funeral and Burial'];
$requiresPriestAssignment = in_array($request['SacramentType'], $sacramentsRequiringPriest) && empty($request['PriestID']);

$conn->close();

// Prepare the response
$response = array(
    'success' => true,
    'request' => $request,
    'details' => $details,
    'godparents' => $godparents,
    'documents' => $documents,
    'hasFeedback' => $feedbackExists,
    'requiresPriestAssignment' => $requiresPriestAssignment,
);
if ($feedbackExists) {
    $response['feedback'] = $feedback;
}

// Send the response in JSON format
echo json_encode($response);
?>