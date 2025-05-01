<?php
// fetch_request_details_for_edit.php
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION["userid"];

if ($_SESSION['role'] != 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include 'db.php';

$refNo = isset($_GET['ref']) ? $_GET['ref'] : '';

if (empty($refNo)) {
    echo json_encode(['success' => false, 'message' => 'Reference number not provided']);
    exit;
}

// Fetch the sacrament request
$sql = "SELECT SR.*, U.FirstName, U.LastName
        FROM SacramentRequests SR
        JOIN Users U ON SR.UserID = U.UserID
        WHERE SR.RefNo = ? AND SR.Deleted = 0 AND SR.UserID = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("si", $refNo, $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();

            // Check if the request status is Pending
            if ($request['Status'] != 'Pending') {
                echo json_encode(['success' => false, 'message' => 'Only pending requests can be edited']);
                exit;
            }

            // Fetch the specific details based on SacramentType
            $details = [];
            $sacramentType = $request['SacramentType'];
            switch ($sacramentType) {
                case 'Baptism':
                    $sql_details = "SELECT * FROM BaptismRequest WHERE RefNo = ?";
                    break;
                case 'Wedding':
                    $sql_details = "SELECT * FROM WeddingRequest WHERE RefNo = ?";
                    break;
                case 'Confirmation':
                    $sql_details = "SELECT * FROM ConfirmationRequest WHERE RefNo = ?";
                    break;
                case 'First Communion':
                    $sql_details = "SELECT * FROM FirstCommunionRequest WHERE RefNo = ?";
                    break;
                case 'Anointing of the Sick':
                    $sql_details = "SELECT * FROM AnointingOfTheSickRequest WHERE RefNo = ?";
                    break;
                case 'Funeral and Burial':
                    $sql_details = "SELECT * FROM FuneralAndBurialRequest WHERE RefNo = ?";
                    break;
                case 'Blessing':
                    $sql_details = "SELECT * FROM BlessingRequest WHERE RefNo = ?";
                    break;
                default:
                    $sql_details = "";
            }

            if (!empty($sql_details)) {
                if ($stmt_details = $conn->prepare($sql_details)) {
                    $stmt_details->bind_param("s", $refNo);
                    if ($stmt_details->execute()) {
                        $details_result = $stmt_details->get_result();
                        if ($details_result->num_rows > 0) {
                            $details = $details_result->fetch_assoc();
                        } else {
                            $details = [];
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error executing details query: ' . $stmt_details->error]);
                        exit;
                    }
                    $stmt_details->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error preparing details statement: ' . $conn->error]);
                    exit;
                }
            }

            echo json_encode([
                'success' => true,
                'request' => $request,
                'details' => $details
            ]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error executing query: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
}

$conn->close();
?>
