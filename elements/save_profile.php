<?php
// Start session
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: log-in.php");
    exit;
}

// Include database connection file
include 'db.php';

// Initialize variables
$update_success = "";
$error_message = "";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get new values from form submission
    $new_firstname = trim($_POST["firstname"]);
    $new_lastname = trim($_POST["lastname"]);
    $new_email = trim($_POST["email"]);
    $new_address = trim($_POST["address"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Validate the inputs
    if (empty($new_firstname) || empty($new_lastname) || empty($new_email) || empty($new_address)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Fetch the existing password if the new password is not provided
        $sql = "SELECT Password FROM Users WHERE UserID = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $_SESSION["userid"]);
            if ($stmt->execute()) {
                $stmt->bind_result($hashed_password);
                $stmt->fetch();
            }
            $stmt->close();
        }

        // If a new password is set, hash it, otherwise keep the old one
        if (!empty($new_password)) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            $new_hashed_password = $hashed_password;
        }

        // Prepare the update statement
        $sql = "UPDATE Users SET FirstName = ?, LastName = ?, Email = ?, Address = ?, Password = ? WHERE UserID = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssi", $new_firstname, $new_lastname, $new_email, $new_address, $new_hashed_password, $_SESSION["userid"]);

            if ($stmt->execute()) {
                // Update session variables to reflect the new values
                $_SESSION["firstname"] = $new_firstname;
                $_SESSION["lastname"] = $new_lastname;
                $_SESSION["email"] = $new_email;

                // Set success message
                $_SESSION["update_success"] = "Profile updated successfully!";
            } else {
                $_SESSION["error_message"] = "Error updating profile. Please try again.";
            }
            $stmt->close();
        }
    }

    // Close the connection
    $conn->close();

    // Redirect back to the edit profile page
    header("location: user_dashboardeditprofile.php");
    exit;
}
?>
