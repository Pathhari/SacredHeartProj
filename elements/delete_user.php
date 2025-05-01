<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    echo json_encode(["success" => false]);
    exit;
}

if (isset($_POST["id"]) && !empty(trim($_POST["id"]))) {
    require_once "db.php";

    $sql = "UPDATE Users SET Deleted = 1 WHERE UserID = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $param_id);
        $param_id = trim($_POST["id"]);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(["success" => false]);
    }
    $conn->close();
} else {
    echo json_encode(["success" => false]);
}
?>
