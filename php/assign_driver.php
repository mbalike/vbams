<?php
include 'db.php'; 
include('php/auth.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require '../vendor/autoload.php';
use AfricasTalking\SDK\AfricasTalking;

// SMS Log file path
define('SMS_LOG_FILE', __DIR__ . '/sms_log.txt');

// Function to log SMS activities
function log_sms($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(SMS_LOG_FILE, $log_entry, FILE_APPEND);
}

// Log initial script execution
log_sms("Driver assignment script started");

// Africa's Talking API credentials
$username = "mbalike";  
$apiKey = "atsk_5d0e2349323bc0a46bcf71f083895c3f0b5d06ae90e02d69328f4327817000470a37fa3e"; 

// Initialize the SDK
$AT = new AfricasTalking($username, $apiKey);
$sms = $AT->sms();

// Note: We're not using the sender ID as it was causing issues
// $from = "AFRICASTKNG"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $driver_id = $_POST['driver_id'];

    log_sms("Processing assignment request: Driver ID $driver_id to Request ID $request_id");

    if (!empty($request_id) && !empty($driver_id)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        log_sms("Database transaction started");

        try {
            // Assign driver to request & update status to 'Assigned'
            $query1 = "UPDATE requests SET assigned_driver_id = ?, status = 'Pending' WHERE id = ?";
            $stmt1 = mysqli_prepare($conn, $query1);
            mysqli_stmt_bind_param($stmt1, "ii", $driver_id, $request_id);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);
            log_sms("Updated request status to 'Assigned' for request #$request_id");

            // Update driver's availability_status to 'Busy'
            $query2 = "UPDATE drivers SET availability_status = 'Busy' WHERE id = ?";
            $stmt2 = mysqli_prepare($conn, $query2);
            mysqli_stmt_bind_param($stmt2, "i", $driver_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
            log_sms("Updated driver #$driver_id status to 'Busy'");

            // Fetch driver's phone number and name
            $query3 = "SELECT phone, name FROM drivers WHERE id = ?";
            $stmt3 = mysqli_prepare($conn, $query3);
            mysqli_stmt_bind_param($stmt3, "i", $driver_id);
            mysqli_stmt_execute($stmt3);
            $result = mysqli_stmt_get_result($stmt3);
            $driver = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt3);

            // Fetch request details
            $query4 = "SELECT location, name, phone, car_model, problem_description FROM requests WHERE id = ?";
            $stmt4 = mysqli_prepare($conn, $query4);
            mysqli_stmt_bind_param($stmt4, "i", $request_id);
            mysqli_stmt_execute($stmt4);
            $result4 = mysqli_stmt_get_result($stmt4);
            $request = mysqli_fetch_assoc($result4);
            mysqli_stmt_close($stmt4);

            if ($driver && $request) {
                $driver_phone = $driver['phone'];
                $driver_name = $driver['name'];

                $location = $request['location'];
                $customer_name = $request['name'];
                $customer_phone = $request['phone'];
                $car_model = $request['car_model'];
                $problem_description = $request['problem_description'];

                log_sms("Driver details - Name: $driver_name, Phone: $driver_phone");
                log_sms("Request details - Location: $location, Customer: $customer_name ($customer_phone)");

                // Format driver phone number correctly
                if (!empty($driver_phone) && !preg_match('/^\+/', $driver_phone)) {
                    $driver_phone = '+' . ltrim($driver_phone, '0'); // Remove leading zero if present
                }
                log_sms("Formatted driver phone: $driver_phone");

                // Updated SMS Message
                $message = "Hello $driver_name, new service request at $location. Client: $customer_name ($customer_phone). Car: $car_model. Issue: $problem_description.";
                log_sms("Preparing to send SMS with message: $message");

                try {
                    // Send SMS using Africa's Talking - without sender ID
                    log_sms("Attempting to send SMS via Africa's Talking API...");
                    $smsResult = $sms->send([
                        'to'      => $driver_phone,
                        'message' => $message
                        // Removed 'from' parameter to fix InvalidSenderId issue
                    ]);

                    // Log the detailed API response
                    log_sms("SMS API Raw Response: " . print_r($smsResult, true));
                    
                    // Check if the message was sent successfully
                    if (isset($smsResult['data']) && isset($smsResult['data']->SMSMessageData)) {
                        if (isset($smsResult['data']->SMSMessageData->Recipients) && !empty($smsResult['data']->SMSMessageData->Recipients)) {
                            $recipient = $smsResult['data']->SMSMessageData->Recipients[0];
                            if ($recipient->status === 'Success') {
                                log_sms("SMS sent successfully to driver. MessageId: " . $recipient->messageId);
                                $_SESSION['sms_success'] = "Driver notification sent successfully.";
                            } else {
                                log_sms("SMS delivery failed with status: " . $recipient->status . " - " . ($recipient->statusReason ?? 'No reason provided'));
                                $_SESSION['sms_warning'] = "Driver notification might not have been delivered: " . $recipient->status;
                            }
                        } else {
                            // Handle the InvalidSenderId case
                            if (isset($smsResult['data']->SMSMessageData->Message) && $smsResult['data']->SMSMessageData->Message === 'InvalidSenderId') {
                                log_sms("ERROR: Invalid Sender ID. Your sender ID is not registered or approved.");
                                $_SESSION['sms_error'] = "SMS could not be sent: Invalid Sender ID.";
                            } else {
                                log_sms("SMS send API returned an unexpected response format: " . print_r($smsResult, true));
                                $_SESSION['sms_warning'] = "Failed to send driver notification.";
                            }
                        }
                    } else {
                        log_sms("SMS send API returned an unexpected response format: " . print_r($smsResult, true));
                        $_SESSION['sms_warning'] = "Failed to send driver notification.";
                    }
                } catch (Exception $e) {
                    $errorMsg = $e->getMessage();
                    log_sms("ERROR: Exception when sending SMS: " . $errorMsg);
                    throw new Exception("SMS Error: " . $errorMsg);
                }
            } else {
                log_sms("ERROR: Could not fetch driver or request details");
                throw new Exception("Could not fetch driver or request details");
            }

            // Commit transaction
            mysqli_commit($conn);
            log_sms("Database transaction committed successfully");

            $_SESSION['success'] = "Driver assigned successfully. SMS notification sent.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errorMsg = $e->getMessage();
            log_sms("ERROR: Transaction rolled back due to error: " . $errorMsg);
            $_SESSION['error'] = "Error assigning driver: " . $errorMsg;
        }
    } else {
        log_sms("ERROR: Missing driver_id or request_id in form submission");
        $_SESSION['error'] = "Please select a driver.";
    }

    log_sms("Redirecting to service_requests.php");
    header("Location: ../service_requests.php");
    exit();
}
?>