<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('php/db.php');
session_start();

$message = '';
$messageClass = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    
    if(empty($email) || empty($user_type)) {
        $message = "Please provide email and select user type";
        $messageClass = "alert-danger";
    } else {
        // Check if email exists in the selected table
        $table = ($user_type == 'admin') ? 'admins' : 'drivers';
        $query = "SELECT * FROM $table WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        
        if($result && mysqli_num_rows($result) > 0) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $update_query = "UPDATE $table SET reset_token = '$token', token_expires = '$expires' WHERE email = '$email'";
            if(mysqli_query($conn, $update_query)) {
                // Send email with reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=$token&type=$user_type";
                
                $to = $email;
                $subject = "Password Reset Request";
                $message_body = "Hello,\n\nPlease click the link below to reset your password:\n\n$reset_link\n\nThis link will expire in 1 hour.\n\nIf you did not request this password reset, please ignore this email.";
                $headers = "From: noreply@yourcompany.com";
                
                if(mail($to, $subject, $message_body, $headers)) {
                    $message = "Password reset instructions have been sent to your email";
                    $messageClass = "alert-success";
                } else {
                    $message = "Email could not be sent. Please try again later";
                    $messageClass = "alert-danger";
                }
            } else {
                $message = "Error generating reset token. Please try again";
                $messageClass = "alert-danger";
            }
        } else {
            // Don't reveal if email exists for security
            $message = "If your email exists in our system, you will receive reset instructions";
            $messageClass = "alert-success";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
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
            <h2 class="text-center mb-4">Reset Password</h2>
            
            <?php if(!empty($message)): ?>
                <div class="alert <?php echo $messageClass; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">User Type</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="user_type" id="admin" value="admin" checked>
                        <label class="form-check-label" for="admin">Admin</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="user_type" id="driver" value="driver">
                        <label class="form-check-label" for="driver">Driver</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>
            
            <div class="text-center mt-3">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>