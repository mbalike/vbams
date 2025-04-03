<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'php/db.php'; // Ensure this file contains your database connection
require 'vendor/autoload.php'; // Load Africa's Talking SDK

use AfricasTalking\SDK\AfricasTalking;

// Africa's Talking API credentials
$username   = "mbalike";  
$apiKey     = "atsk_5d0e2349323bc0a46bcf71f083895c3f0b5d06ae90e02d69328f4327817000470a37fa3e"; 

// Initialize Africa's Talking SDK
$AT         = new AfricasTalking($username, $apiKey);
$sms        = $AT->sms();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, trim($_POST['name'])) : '';
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : '';
    $location = isset($_POST['location']) ? mysqli_real_escape_string($conn, trim($_POST['location'])) : '';
    $car_model = isset($_POST['car_model']) ? mysqli_real_escape_string($conn, trim($_POST['car_model'])) : '';
    $problem_description = isset($_POST['problem_description']) ? mysqli_real_escape_string($conn, trim($_POST['problem_description'])) : '';

    // Validate inputs
    $errors = [];

    if (empty($name)) $errors[] = "Name is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($car_model)) $errors[] = "Car model is required";
    if (empty($problem_description)) $errors[] = "Problem description is required";

    // Phone number validation (optional, adjust as needed)
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Invalid phone number format";
    }

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Prepare SQL statement
        $query = "INSERT INTO requests (
            name, 
            phone, 
            location, 
            car_model, 
            problem_description, 
            status, 
            created_at
        ) VALUES (
            ?, 
            ?, 
            ?, 
            ?, 
            ?, 
            'Pending', 
            NOW()
        )";

        // Prepare and bind parameters
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt, 
                "sssss", 
                $name, 
                $phone, 
                $location, 
                $car_model, 
                $problem_description
            );

            // Execute the statement
            try {
                if (mysqli_stmt_execute($stmt)) {
                    // Fetch the admin's phone number
                    $adminQuery = "SELECT phone FROM admins ORDER BY id ASC LIMIT 1";
                    $adminResult = mysqli_query($conn, $adminQuery);
                    $adminRow = mysqli_fetch_assoc($adminResult);
                    $admin_phone = $adminRow['phone'];

                    // Prepare SMS for the admin
                    $adminMessage = "New service request from $name at $location. Car: $car_model. Issue: $problem_description. Contact: $phone.";

                    // Send SMS to the admin
                    try {
                        $sms->send([
                            'to'      => $admin_phone,
                            'message' => $adminMessage,
                            'from'    => "AFRICASTKNG"
                        ]);
                    } catch (Exception $e) {
                        error_log("Error sending SMS: " . $e->getMessage());
                    }

                    // Success
                    $_SESSION['success_message'] = "Your service request has been submitted successfully!";
                    header("Location: thank_you.html"); // Redirect to a thank you page
                    exit();
                } else {
                    // Database insertion failed
                    $_SESSION['error_message'] = "Failed to submit request. Please try again.";
                    header("Location: index.html");
                    exit();
                }
            } catch (Exception $e) {
                // Handle any exceptions
                $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
                header("Location: request_form.php");
                exit();
            } finally {
                // Close statement
                mysqli_stmt_close($stmt);
            }
        } else {
            // Statement preparation failed
            $_SESSION['error_message'] = "Database error. Please try again later.";
            header("Location: request_form.php");
            exit();
        }
    } else {
        // Validation failed
        $_SESSION['error_messages'] = $errors;
        header("Location: request_form.php");
        exit();
    }
} else {
    // Direct access to the script
    $_SESSION['error_message'] = "Invalid access method.";
    header("Location: request_form.php");
    exit();
}
?>
