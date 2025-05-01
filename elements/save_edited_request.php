<?php
// save_edited_request.php
session_start();

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

$refNo = isset($_POST['RefNo']) ? $_POST['RefNo'] : '';

if (empty($refNo)) {
    echo json_encode(['success' => false, 'message' => 'Reference number not provided']);
    exit;
}

// Fetch the sacrament request to ensure it belongs to the user and is pending
$sql = "SELECT * FROM SacramentRequests WHERE RefNo = ? AND UserID = ? AND Deleted = 0 AND Status = 'Pending'";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("si", $refNo, $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();

            $sacramentType = $request['SacramentType'];

            // Process the form data based on sacrament type
            $success = false;
            $message = '';

            switch ($sacramentType) {
                case 'Baptism':
                    $childName = $_POST['ChildName'];
                    $childDOB = $_POST['ChildDOB'];
                    $childBPlace = $_POST['ChildBPlace'];
                    $fatherName = $_POST['FatherName'];
                    $motherMName = $_POST['MotherMName'];
                    // Add other fields as needed

                    // Validate input
                    if (empty($childName)) {
                        echo json_encode(['success' => false, 'message' => 'Child Name is required']);
                        exit;
                    }

                    // Update the BaptismRequest
                    $sql_update = "UPDATE BaptismRequest SET ChildName = ?, ChildDOB = ?, ChildBPlace = ?, FatherName = ?, MotherMName = ? WHERE RefNo = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("ssssss", $childName, $childDOB, $childBPlace, $fatherName, $motherMName, $refNo);
                        if ($stmt_update->execute()) {
                            $success = true;
                        } else {
                            $message = 'Error updating baptism details';
                        }
                        $stmt_update->close();
                    } else {
                        $message = 'Error preparing update statement';
                    }

                    break;

                case 'Wedding':
                    $applicantName = $_POST['ApplicantName'];
                    // Add other fields as needed

                    // Validate input
                    if (empty($applicantName)) {
                        echo json_encode(['success' => false, 'message' => 'Applicant Name is required']);
                        exit;
                    }

                    // Update the WeddingRequest
                    $sql_update = "UPDATE WeddingRequest SET ApplicantName = ? WHERE RefNo = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("ss", $applicantName, $refNo);
                        if ($stmt_update->execute()) {
                            $success = true;
                        } else {
                            $message = 'Error updating wedding details';
                        }
                        $stmt_update->close();
                    } else {
                        $message = 'Error preparing update statement';
                    }

                    break;

                case 'Confirmation':
                    $fullName = $_POST['FullName'];
                    $fatherFullName = $_POST['FatherFullName'];
                    $motherFullName = $_POST['MotherFullName'];
                    $residence = $_POST['Residence'];

                    // Validate input
                    if (empty($fullName)) {
                        echo json_encode(['success' => false, 'message' => 'Full Name is required']);
                        exit;
                    }

                    // Update ConfirmationRequest
                    $sql_update = "UPDATE ConfirmationRequest SET FullName = ?, FatherFullName = ?, MotherFullName = ?, Residence = ? WHERE RefNo = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("sssss", $fullName, $fatherFullName, $motherFullName, $residence, $refNo);
                        if ($stmt_update->execute()) {
                            $success = true;
                        } else {
                            $message = 'Error updating confirmation details';
                        }
                        $stmt_update->close();
                    } else {
                        $message = 'Error preparing update statement';
                    }

                    break;

                case 'First Communion':
                    $fullNameChild = $_POST['FullNameChild'];
                    // Add other fields and validations as needed

                    // Update FirstCommunionRequest
                    $sql_update = "UPDATE FirstCommunionRequest SET FullNameChild = ? WHERE RefNo = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("ss", $fullNameChild, $refNo);
                        if ($stmt_update->execute()) {
                            $success = true;
                        } else {
                            $message = 'Error updating first communion details';
                        }
                        $stmt_update->close();
                    } else {
                        $message = 'Error preparing update statement';
                    }

                    break;

                case 'Anointing of the Sick':
                    $fullName = $_POST['FullName'];
                    // Add other fields and validations as needed

                    // Update AnointingOfTheSickRequest
                    $sql_update = "UPDATE AnointingOfTheSickRequest SET FullName = ? WHERE RefNo = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("ss", $fullName, $refNo);
                        if ($stmt_update->execute()) {
                            $success = true;
                        } else {
                            $message = 'Error updating anointing of the sick details';
                        }
                        $stmt_update->close();
                    } else {
                        $message = 'Error preparing update statement';
                    }

                    break;

                case 'Funeral and Burial':
                    $deceasedFullName = $_POST['DeceasedFullName'];
                    // Add other fields and validations as needed

                    // Update FuneralAndBurialRequest
                    $sql_update = "UPDATE FuneralAndBurialRequest SET DeceasedFullName = ? WHERE RefNo = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("ss", $deceasedFullName, $refNo);
                        if ($stmt_update->execute()) {
                            $success = true;
                        } else {
                            $message = 'Error updating funeral and burial details';
                        }
                        $stmt_update->close();
                    } else {
                        $message = 'Error preparing update statement';
                    }

                    break;

                case 'Blessing':
                    $fullName = $_POST['FullName'];
                    $address = $_POST['Address'];
                    $requesterContact = $_POST['RequesterContact'];
                    $blessingType = $_POST['BlessingType'];
                    $otherBlessingType = isset($_POST['OtherBlessingType']) ? $_POST['OtherBlessingType'] : null;
                    $blessingPlace = $_POST['BlessingPlace'];

                    // Validate input
                    if (empty($fullName) || empty($blessingType)) {
                        echo json_encode(['success' => false, 'message' => 'Full Name and Blessing Type are required']);
                        exit;
                    }

                    // Update the BlessingRequest
                    $sql_update = "UPDATE BlessingRequest SET FullName = ?, Address = ?, RequesterContact = ?, BlessingType = ?, OtherBlessingType = ?, BlessingPlace = ? WHERE RefNo = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("sssssss", $fullName, $address, $requesterContact, $blessingType, $otherBlessingType, $blessingPlace, $refNo);
                        if ($stmt_update->execute()) {
                            $success = true;
                        } else {
                            $message = 'Error updating blessing details';
                        }
                        $stmt_update->close();
                    } else {
                        $message = 'Error preparing update statement';
                    }

                    break;

                default:
                    $message = 'Cannot edit this sacrament type';
            }

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Request updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => $message]);
            }

        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found or cannot be edited']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error executing query']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement']);
}

$conn->close();
?>
