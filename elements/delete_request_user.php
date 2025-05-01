<?php
session_start();
include 'db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$user_id = $_SESSION['userid'];

if (isset($_POST['ref'])) {
    $refNo = $_POST['ref'];
    $sql = "SELECT RefNo, Status FROM SacramentRequests WHERE RefNo = ? AND UserID = ? AND (Status = 'Approved' OR Status = 'Rejected')";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $refNo, $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $update_sql = "UPDATE SacramentRequests SET Deleted = 1 WHERE RefNo = ?";
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("s", $refNo);
                    if ($update_stmt->execute()) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error updating record.']);
                    }
                    $update_stmt->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error preparing update statement.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Request not found or cannot be deleted.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error executing query.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparing statement.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
