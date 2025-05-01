<?php
// Start session
session_start();

// Include database connection file
include 'db.php';

// Define variables and initialize with empty values
$firstname = $lastname = $email = $password = $confirm_password = $address = $gender = "";
$firstname_err = $lastname_err = $email_err = $password_err = $confirm_password_err = $address_err = $gender_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate first name
    if(empty(trim($_POST["firstname"]))){
        $firstname_err = "Please enter your first name.";
    } else {
        $firstname = trim($_POST["firstname"]);
    }
    
    // Validate last name
    if(empty(trim($_POST["lastname"]))){
        $lastname_err = "Please enter your last name.";
    } else {
        $lastname = trim($_POST["lastname"]);
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        // Check if email already exists
        $sql = "SELECT UserID FROM Users WHERE Email = ?";
        
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if($stmt->num_rows > 0){
                $email_err = "This email is already taken.";
            }
            $stmt->close();
        }
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter your address.";
    } else {
        $address = trim($_POST["address"]);
    }

    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm your password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if($password != $confirm_password){
            $confirm_password_err = "Passwords do not match.";
        }
    }

    // Validate gender
    if(empty(trim($_POST["gender"]))){
        $gender_err = "Please select your gender.";
    } else {
        $gender = trim($_POST["gender"]);
    }

    // Check input errors before inserting in database
    if(empty($firstname_err) && empty($lastname_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($address_err) && empty($gender_err)){
        
        // Prepare an insert statement with the 'user' role as default
        $sql = "INSERT INTO Users (FirstName, LastName, Email, Password, Address, Role, Gender) VALUES (?, ?, ?, ?, ?, 'user', ?)";
         
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Encrypt the password
            $stmt->bind_param("ssssss", $firstname, $lastname, $email, $hashed_password, $address, $gender);
            
            if($stmt->execute()){
                // Redirect to login page
                header("location: log-in.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later.";
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
    <title>Sacred Heart Parish - Register</title>
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

        .register-section {
            width: 100%;
            max-width: 500px;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .register-section h2 {
            margin-bottom: 20px;
            font-size: 26px;
            color: #333;
            font-weight: bold;
        }

        form {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            margin-bottom: 5px;
            font-size: 16px;
            color: #555;
            text-align: left;
            display: block;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .password-wrapper {
            position: relative;
        }

        .show-password {
            position: absolute;
            right: 12px;
            top: 50%; /* Adjusts icon position */
            transform: translateY(-50%); /* Centers the icon vertically */
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

        .register-section .error {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .register-section p {
            padding-top: 10px;
            font-size: 15px;
            color: #555;
        }

        .register-section p a {
            color: #E85C0D;
            text-decoration: none;
        }

        .register-section p a:hover {
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

            .register-section {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Left Section -->
    <div class="left-section">
        <img src="imgs/mainlogo.png" alt="logo">
        <h2>Sacred Heart of Jesus Parish</h2>
        <p>Don Bosco-Mati, Davao Oriental</p>
    </div>

    <!-- Right Section -->
    <div class="right-section">
        <div class="register-section">
            <h2>Register</h2>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstname" value="<?php echo $firstname; ?>">
                    <p><?php echo $firstname_err; ?></p>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastname" value="<?php echo $lastname; ?>">
                    <p><?php echo $lastname_err; ?></p>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo $email; ?>">
                    <p><?php echo $email_err; ?></p>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?php echo $address; ?>">
                    <p><?php echo $address_err; ?></p>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php if($gender == 'Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if($gender == 'Female') echo 'selected'; ?>>Female</option>
                    </select>
                    <p><?php echo $gender_err; ?></p>
                </div>
                <div class="form-group password-wrapper">
                    <label>Password</label>
                    <input type="password" name="password" id="password">
                    <span class="show-password" onclick="togglePasswordVisibility('password')">👁️</span>
                    <p><?php echo $password_err; ?></p>
                </div>
                <div class="form-group password-wrapper">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password">
                    <span class="show-password" onclick="togglePasswordVisibility('confirm_password')">👁️</span>
                    <p><?php echo $confirm_password_err; ?></p>
                </div>
                
                <?php 
                if(!empty($register_err)){
                    echo '<div class="error">' . $register_err . '</div>';
                }        
                ?>

                <button type="submit" class="btn-primary">Register</button>
            </form>

            <p>Already have an account? <a href="log-in.php">Login here</a></p>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility(fieldId) {
        var passwordField = document.getElementById(fieldId);
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
</script>

</body>
</html>
