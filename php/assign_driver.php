<?php
include 'db.php'; 
include('php/auth.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require '../vendor/autoload.php';
use AfricasTalking\SDK\AfricasTalking;

// Africa's Talking API credentials
$username   = "mbalike";  
$apiKey     = "atsk_5d0e2349323bc0a46bcf71f083895c3f0b5d06ae90e02d69328f4327817000470a37fa3e"; 

// Initialize the SDK
$AT         = new AfricasTalking($username, $apiKey);
$sms        = $AT->sms();

// Sender ID (optional, should be approved by Africa's Talking)
$from       = "AFRICASTKNG"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $driver_id = $_POST['driver_id'];

    if (!empty($request_id) && !empty($driver_id)) {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Assign driver to request & update status to 'Assigned'
            $query1 = "UPDATE requests SET assigned_driver_id = ?, status = 'Assigned' WHERE id = ?";
            $stmt1 = mysqli_prepare($conn, $query1);
            mysqli_stmt_bind_param($stmt1, "ii", $driver_id, $request_id);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            // Update driver's availability_status to 'Busy'
            $query2 = "UPDATE drivers SET availability_status = 'Busy' WHERE id = ?";
            $stmt2 = mysqli_prepare($conn, $query2);
            mysqli_stmt_bind_param($stmt2, "i", $driver_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

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

                // Updated SMS Message
                $message = "Hello $driver_name, new service request at $location. Client: $customer_name ($customer_phone). Car: $car_model. Issue: $problem_description.";

                try {
                    // Send SMS using Africa's Talking
                    $smsResult = $sms->send([
                        'to'      => $driver_phone, // Ensure the phone number is in the correct format (+2547XXXXXXXX)
                        'message' => $message,
                    ]);

                    // Log SMS response (optional for debugging)
                    file_put_contents("sms_log.txt", print_r($smsResult, true), FILE_APPEND);

                } catch (Exception $e) {
                    throw new Exception("SMS Error: " . $e->getMessage());
                }
            }

            // Commit transaction
            mysqli_commit($conn);

            $_SESSION['success'] = "Driver assigned successfully. SMS notification sent.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Error assigning driver: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please select a driver.";
    }

    header("Location: ../service_requests.php");
    exit();
}
?>
