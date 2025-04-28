<?php
// reset_password.php - Form to create new password after clicking email link
require_once '/php/db.php';
session_start();

$message = '';
$messageClass = '';
$valid_token = false;
$token = '';
$user_type = '';

if(isset($_GET['token']) && isset($_GET['type'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    $user_type = mysqli_real_escape_string($conn, $_GET['type']);
    
    // Verify token exists and hasn't expired
    $table = ($user_type == 'admin') ? 'admins' : 'drivers';
    $query = "SELECT * FROM $table WHERE reset_token = '$token' AND token_expires > NOW()";
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $valid_token = true;
    } else {
        $message = "Invalid or expired reset link. Please request a new one.";
        $messageClass = "alert-danger";
    }
} else {
    $message = "Invalid reset link. Please request a new password reset.";
    $messageClass = "alert-danger";
}

if($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($new_password) || empty($confirm_password)) {
        $message = "Please fill all required fields";
        $messageClass = "alert-danger";
    } elseif($new_password != $confirm_password) {
        $message = "Passwords do not match";
        $messageClass = "alert-danger";
    } elseif(strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters long";
        $messageClass = "alert-danger";
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $update_query = "UPDATE $table SET 
                          password = '$hashed_password', 
                          reset_token = NULL, 
                          token_expires = NULL 
                          WHERE reset_token = '$token'";
                          
        if(mysqli_query($conn, $update_query)) {
            $message = "Password has been updated successfully! You can now login with your new password.";
            $messageClass = "alert-success";
            $valid_token = false; // Hide the form after successful reset
        } else {
            $message = "Error updating password. Please try again.";
            $messageClass = "alert-danger";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 50px;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Create New Password</h2>
            
            <?php if(!empty($message)): ?>
                <div class="alert <?php echo $messageClass; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($valid_token): ?>
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>