<?php
session_start();
include('db.php');
include('php/auth.php');
require '../vendor/autoload.php'; // Load Africa's Talking SDK

use AfricasTalking\SDK\AfricasTalking;

// SMS Log file path
define('SMS_LOG_FILE', __DIR__ . '/sms_log.txt');

// Function to log SMS activities
function log_sms($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(SMS_LOG_FILE, $log_entry, FILE_APPEND);
}

// Log script execution start
log_sms("Driver status update script started");

// Africa's Talking API credentials
$username   = "mbalike";  
$apiKey     = "atsk_5d0e2349323bc0a46bcf71f083895c3f0b5d06ae90e02d69328f4327817000470a37fa3e"; 

// Initialize Africa's Talking SDK
$AT         = new AfricasTalking($username, $apiKey);
$sms        = $AT->sms();

// Check if the logged-in user is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    log_sms("Unauthorized access attempt - redirecting to login");
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    
    log_sms("Processing status update - Request ID: $request_id, New Status: $new_status");

    // Get customer details and admin phone number
    $query = "SELECT r.name AS customer_name, r.phone AS customer_phone, 
                     (SELECT phone FROM admins ORDER BY id ASC LIMIT 1) AS admin_phone 
              FROM requests r
              WHERE r.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        log_sms("ERROR: Request #$request_id not found in database");
        echo "Request not found.";
        exit();
    }

    $customer_name = $row['customer_name'];
    $customer_phone = $row['customer_phone'];
    $admin_phone = $row['admin_phone'];
    
    log_sms("Retrieved data - Customer: $customer_name ($customer_phone), Admin phone: $admin_phone");
    
    // Format phone numbers correctly for SMS API
    if (!empty($customer_phone) && !preg_match('/^\+/', $customer_phone)) {
        $customer_phone = '+' . ltrim($customer_phone, '0');
        log_sms("Formatted customer phone: $customer_phone");
    }
    
    if (!empty($admin_phone) && !preg_match('/^\+/', $admin_phone)) {
        $admin_phone = '+' . ltrim($admin_phone, '0');
        log_sms("Formatted admin phone: $admin_phone");
    }

    // Update request status
    $updateQuery = "UPDATE requests SET status = ? WHERE id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "si", $new_status, $request_id);
    
    if (mysqli_stmt_execute($updateStmt)) {
        log_sms("Successfully updated request #$request_id status to '$new_status'");

        try {
            if ($new_status == "Accepted") {
                // Send SMS to the customer including their name
                $messageToCustomer = "Hello $customer_name, your request has been accepted. The driver is on the way!";
                log_sms("Preparing to send customer notification: $messageToCustomer");
                
                log_sms("Sending SMS to customer ($customer_phone)...");
                $customerResult = $sms->send([
                    'to'      => $customer_phone,
                    'message' => $messageToCustomer
                    // Removed 'from' parameter to avoid InvalidSenderId error
                ]);
                
                log_sms("Customer SMS API Response: " . print_r($customerResult, true));
                
                // Check customer SMS status
                if (isset($customerResult['data']) && isset($customerResult['data']->SMSMessageData)) {
                    if (isset($customerResult['data']->SMSMessageData->Recipients) && !empty($customerResult['data']->SMSMessageData->Recipients)) {
                        $recipient = $customerResult['data']->SMSMessageData->Recipients[0];
                        if ($recipient->status === 'Success') {
                            log_sms("SMS sent successfully to customer. MessageId: " . $recipient->messageId);
                        } else {
                            log_sms("SMS to customer failed: " . $recipient->status . " - " . ($recipient->statusReason ?? 'No reason provided'));
                        }
                    } else if (isset($customerResult['data']->SMSMessageData->Message)) {
                        log_sms("SMS to customer failed: " . $customerResult['data']->SMSMessageData->Message);
                    }
                }

                // Notify admin
                $messageToAdmin = "Request #$request_id has been accepted by a driver.";
                log_sms("Preparing to send admin notification: $messageToAdmin");
                
                log_sms("Sending SMS to admin ($admin_phone)...");
                $adminResult = $sms->send([
                    'to'      => $admin_phone,
                    'message' => $messageToAdmin
                    // Removed 'from' parameter to avoid InvalidSenderId error
                ]);
                
                log_sms("Admin SMS API Response: " . print_r($adminResult, true));
                
                // Check admin SMS status
                if (isset($adminResult['data']) && isset($adminResult['data']->SMSMessageData)) {
                    if (isset($adminResult['data']->SMSMessageData->Recipients) && !empty($adminResult['data']->SMSMessageData->Recipients)) {
                        $recipient = $adminResult['data']->SMSMessageData->Recipients[0];
                        if ($recipient->status === 'Success') {
                            log_sms("SMS sent successfully to admin. MessageId: " . $recipient->messageId);
                        } else {
                            log_sms("SMS to admin failed: " . $recipient->status . " - " . ($recipient->statusReason ?? 'No reason provided'));
                        }
                    } else if (isset($adminResult['data']->SMSMessageData->Message)) {
                        log_sms("SMS to admin failed: " . $adminResult['data']->SMSMessageData->Message);
                    }
                }

            } elseif ($new_status == "Declined") {
                // Notify admin so they can reassign
                $messageToAdmin = "Request #$request_id was declined. Please assign another driver.";
                log_sms("Preparing to send admin notification about declined request: $messageToAdmin");
                
                log_sms("Sending SMS to admin ($admin_phone)...");
                $adminResult = $sms->send([
                    'to'      => $admin_phone,
                    'message' => $messageToAdmin
                    // Removed 'from' parameter to avoid InvalidSenderId error
                ]);
                
                log_sms("Admin SMS API Response: " . print_r($adminResult, true));
                
                // Check admin SMS status
                if (isset($adminResult['data']) && isset($adminResult['data']->SMSMessageData)) {
                    if (isset($adminResult['data']->SMSMessageData->Recipients) && !empty($adminResult['data']->SMSMessageData->Recipients)) {
                        $recipient = $adminResult['data']->SMSMessageData->Recipients[0];
                        if ($recipient->status === 'Success') {
                            log_sms("SMS sent successfully to admin. MessageId: " . $recipient->messageId);
                        } else {
                            log_sms("SMS to admin failed: " . $recipient->status . " - " . ($recipient->statusReason ?? 'No reason provided'));
                        }
                    } else if (isset($adminResult['data']->SMSMessageData->Message)) {
                        log_sms("SMS to admin failed: " . $adminResult['data']->SMSMessageData->Message);
                    }
                }
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            log_sms("ERROR: Exception when sending SMS: " . $errorMsg);
            echo "Error sending SMS: " . $errorMsg;
        }

        log_sms("Redirecting to driver_requests2.php");
        header("Location: ../driver_requests2.php");
        exit();
    } else {
        $errorMsg = mysqli_error($conn);
        log_sms("ERROR: Failed to update request status in database: " . $errorMsg);
        echo "Error updating request: " . $errorMsg;
    }

    mysqli_stmt_close($stmt);
    mysqli_stmt_close($updateStmt);
}

log_sms("Script execution completed");
mysqli_close($conn);
?>