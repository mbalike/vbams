<?php
session_start();
include('db.php');
include('php/auth.php');
require '../vendor/autoload.php'; // Load Africa's Talking SDK

use AfricasTalking\SDK\AfricasTalking;

// Africa's Talking API credentials
$username   = "mbalike";  
$apiKey     = "atsk_5d0e2349323bc0a46bcf71f083895c3f0b5d06ae90e02d69328f4327817000470a37fa3e"; 

// Initialize Africa's Talking SDK
$AT         = new AfricasTalking($username, $apiKey);
$sms        = $AT->sms();

// Check if the logged-in user is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];

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
        echo "Request not found.";
        exit();
    }

    $customer_name = $row['customer_name'];
    $customer_phone = $row['customer_phone'];
    $admin_phone = $row['admin_phone'];

    // Update request status
    $updateQuery = "UPDATE requests SET status = ? WHERE id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "si", $new_status, $request_id);
    
    if (mysqli_stmt_execute($updateStmt)) {
        // Sender ID (optional, must be approved)
        $from = "AFRICASTKNG";

        try {
            if ($new_status == "Accepted") {
                // Send SMS to the customer including their name
                $messageToCustomer = "Hello $customer_name, your request has been accepted. The driver is on the way!";
                $sms->send([
                    'to'      => $customer_phone,
                    'message' => $messageToCustomer,
                    'from'    => $from
                ]);

                // Notify admin
                $messageToAdmin = "Request #$request_id has been accepted by a driver.";
                $sms->send([
                    'to'      => $admin_phone,
                    'message' => $messageToAdmin,
                    'from'    => $from
                ]);

            } elseif ($new_status == "Declined") {
                // Notify admin so they can reassign
                $messageToAdmin = "Request #$request_id was declined. Please assign another driver.";
                $sms->send([
                    'to'      => $admin_phone,
                    'message' => $messageToAdmin,
                    'from'    => $from
                ]);
            }
        } catch (Exception $e) {
            echo "Error sending SMS: " . $e->getMessage();
        }

        header("Location: ../driver_requests.php");
        exit();
    } else {
        echo "Error updating request.";
    }

    mysqli_stmt_close($stmt);
    mysqli_stmt_close($updateStmt);
}

mysqli_close($conn);
?>
