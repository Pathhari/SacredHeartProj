<?php
// Start session
session_start();

// Include database connection file
include 'db.php';

// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if email is empty
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if(empty($email_err) && empty($password_err)){
        // Prepare a select statement to retrieve role as well
        $sql = "SELECT UserID, FirstName, Password, Role FROM Users WHERE Email = ? AND Deleted = 0";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement
            $stmt->bind_param("s", $email);

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Store result
                $stmt->store_result();

                // Check if email exists, if yes, verify password
                if($stmt->num_rows == 1){
                    // Bind result variables
                    $stmt->bind_result($id, $firstname, $hashed_password, $role);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["userid"] = $id;
                            $_SESSION["firstname"] = $firstname;
                            $_SESSION["role"] = $role;

                            // Redirect user based on their role
                            if ($role == 'admin') {
                                header("location: admin_dashboard.php");
                                exit(); // Ensure no further code is executed
                            } else if ($role == 'staff') {
                                header("location: staff_dashboard.php");
                                exit(); // Ensure no further code is executed
                            } else {
                                header("location: user_dashboard.php");
                                exit(); // For regular users
                            }
                        } else {
                            // Password is not valid
                            $login_err = "Invalid password.";
                        }
                    }
                } else {
                    // Email doesn't exist or account is inactive
                    $login_err = "No account found with that email or account is inactive.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacred Heart Parish</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Gotham, sans-serif;
        }

        body, html {
            height: 100%;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .left-section {
            flex: 1;
            background: linear-gradient(to right, #E85C0D, #FF9933);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
            text-align: center;
            padding: 20px;
            position: relative;
        }

        .left-section img {
            max-width: 200px;
            margin-bottom: 10px;
            padding-bottom: 20px;
        }

        .left-section h2 {
            font-size: 46px;
            margin-top: 10px;
            font-weight: bold;
            padding-bottom: 20px;
        }

        .left-section p {
            font-size: 18px;
            padding-bottom: 20px;
        }

        .right-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .login-section {
            width: 100%;
            max-width: 500px;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-section h2 {
            margin-bottom: 20px;
            font-size: 26px;
            color: #333;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            margin-bottom: 5px;
            font-size: 16px;
            color: #555;
            text-align: left;
            display: block;
        }

        .password-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .show-password {
            position: absolute;
            right: 12px;
            top: 12px;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #E85C0D;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background-color: #cc4a06;
        }

        .login-section .error {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .login-section p {
            padding-top: 10px;
            font-size: 15px;
            color: #555;
        }

        .login-section p a {
            color: #E85C0D;
            text-decoration: none;
        }

        .login-section p a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .left-section, .right-section {
                width: 100%;
                padding: 20px;
            }

            .login-section {
                max-width: 100%;
            }
        }

        /* Back Button Styling */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 8px;
            background-color: #E85C0D;
            color: white;
            font-weight: 600;
            transition: background-color 0.3s ease;
            text-decoration: none;
        }

        .back-button:hover {
            background-color: #CC4A05;
        }

        .back-button i {
            margin-right: 8px;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Left Section -->
    <div class="left-section">
        <!-- Back Button -->
        <a href="landingpage.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Landing Page
        </a>
        <img src="imgs/mainlogo.png" alt="logo">
        <h2>Sacred Heart of Jesus Parish</h2>
        <p>Don Bosco-Mati, Davao Oriental</p>
    </div>

    <!-- Right Section -->
    <div class="right-section">
        <div class="login-section">
            <h2>Login</h2>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="text" name="email" class="form-control" value="<?php echo $email; ?>">
                    <p class="error"><?php echo $email_err; ?></p>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="form-control">
                        <i class="fas fa-eye show-password" onclick="togglePasswordVisibility()"></i>
                    </div>
                    <p class="error"><?php echo $password_err; ?></p>
                </div>
                
                <?php 
                if(!empty($login_err)){
                    echo '<div class="error">' . $login_err . '</div>';
                }        
                ?>

                <button type="submit" class="btn-primary">Sign-in</button>
            </form>

            <p>Don't have an account yet? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility() {
        var passwordField = document.getElementById("password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
</script>

</body>
</html>
