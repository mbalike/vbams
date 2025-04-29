<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session to handle messages between pages
session_start();
include 'php/db.php'; // Database connection
require 'vendor/autoload.php'; // Africa's Talking SDK

use AfricasTalking\SDK\AfricasTalking;

// SMS Log file path
define('SMS_LOG_FILE', __DIR__ . '/sms_log.txt');

// Function to log SMS activities
function log_sms($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(SMS_LOG_FILE, $log_entry, FILE_APPEND);
}

// Africa's Talking API credentials
$username = "mbalike";  
$apiKey = "atsk_5d0e2349323bc0a46bcf71f083895c3f0b5d06ae90e02d69328f4327817000470a37fa3e"; 

// Initialize Africa's Talking SDK
$AT = new AfricasTalking($username, $apiKey);
$sms = $AT->sms();

// Check if the form was submitted
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

  if (!empty($phone)) {
    // Remove all non-digit characters
    $phone = preg_replace('/\D/', '', $phone);

    // If the phone starts with 0, replace it with 255
    if (preg_match('/^0/', $phone)) {
        $phone = '255' . substr($phone, 1);
    }
    // If it starts with country code and is prefixed with + or 00, normalize it
    elseif (preg_match('/^(?:255|00255|\+255)/', $phone)) {
        $phone = preg_replace('/^(?:\+|00)?255/', '255', $phone);
    }

    // Final validation
    if (!preg_match('/^255[0-9]{9}$/', $phone)) {
        $errors[] = "Invalid phone number format";
    }
}


    // If errors exist, store them in session and go back to form
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        $_SESSION['form_data'] = $_POST; // Store the form data to repopulate fields
        header("Location: request-service.php");
        exit();
    }

    // No errors, proceed with database insertion
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
        if (mysqli_stmt_execute($stmt)) {
            // Success - get the ID of the new request
            $request_id = mysqli_insert_id($conn);
            log_sms("New service request #$request_id created for customer: $name ($phone)");
            
            // Fetch the admin's phone number
            $adminQuery = "SELECT phone FROM admins ORDER BY id ASC LIMIT 1";
            $adminResult = mysqli_query($conn, $adminQuery);
            
            if ($adminResult && mysqli_num_rows($adminResult) > 0) {
                $adminRow = mysqli_fetch_assoc($adminResult);
                $admin_phone = $adminRow['phone'];
                
                // Log the original phone number for debugging
                log_sms("Original admin phone: " . $admin_phone);
                
                // Make sure it has the correct format (add the plus if missing)
                if (!empty($admin_phone) && !preg_match('/^\+/', $admin_phone)) {
                    $admin_phone = '+' . ltrim($admin_phone, '0'); // Remove leading zero if present
                }
                
                log_sms("Formatted admin phone: " . $admin_phone);

                // Prepare SMS for the admin
                $adminMessage = "New service request #$request_id from $name at $location. Car: $car_model. Issue: $problem_description. Contact: $phone.";
                log_sms("Preparing to send SMS to admin with message: $adminMessage");

                // Send SMS to the admin
                try {
                    log_sms("Attempting to send SMS via Africa's Talking API...");
                    $result = $sms->send([
                        'to'      => $admin_phone,
                        'message' => $adminMessage,
                        
                    ]);
                    
                    // Log the API response
                    log_sms("SMS API Raw Response: " . print_r($result, true));
                    
                    // Check if the message was sent successfully
                    if (isset($result['data']) && !empty($result['data']->SMSMessageData->Recipients)) {
                        $recipient = $result['data']->SMSMessageData->Recipients[0];
                        if ($recipient->status === 'Success') {
                            log_sms("SMS sent successfully to admin. MessageId: " . $recipient->messageId);
                            $_SESSION['sms_success'] = "Admin notification sent successfully.";
                        } else {
                            log_sms("SMS delivery failed with status: " . $recipient->status . " - " . ($recipient->statusReason ?? 'No reason provided'));
                            $_SESSION['sms_warning'] = "Admin notification might not have been delivered: " . $recipient->status;
                        }
                    } else {
                        log_sms("SMS send API returned an unexpected response format");
                        $_SESSION['sms_warning'] = "Failed to send admin notification, but your request was recorded.";
                    }
                } catch (Exception $e) {
                    log_sms("ERROR: Exception when sending SMS: " . $e->getMessage());
                    $_SESSION['sms_error'] = "Admin notification could not be sent, but your request was recorded.";
                }
            } else {
                log_sms("ERROR: No admin found in the database to notify.");
                $_SESSION['sms_warning'] = "No admin found to notify, but your request was recorded.";
            }

            // Store success message and redirect to thank you page
            $_SESSION['success_message'] = "Your service request has been submitted successfully!";
            header("Location: thank_you.html");
            exit();
        } else {
            // Database insertion failed
            $error = mysqli_error($conn);
            log_sms("ERROR: Database insertion failed: " . $error);
            $_SESSION['error_message'] = "Failed to submit request: " . $error;
            header("Location: request-service.php");
            exit();
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
    } else {
        // Statement preparation failed
        $error = mysqli_error($conn);
        log_sms("ERROR: Statement preparation failed: " . $error);
        $_SESSION['error_message'] = "Database error: " . $error;
        header("Location: request-service.php");
        exit();
    }
} else {
    // If script is accessed directly, just redirect to the form
    header("Location: request-service.php");
    exit();
}
