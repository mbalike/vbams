<?php
session_start();
include('db.php');
include('php/auth.php');
require 'AfricasTalkingGateway.php'; // Ensure this file is included

// Check if the logged-in user is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];

    // Get customer phone number and admin phone number
    $query = "SELECT r.phone AS customer_phone, 
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

    $customer_phone = $row['customer_phone'];
    $admin_phone = $row['admin_phone'];

    // Update request status
    $updateQuery = "UPDATE requests SET status = ? WHERE id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "si", $new_status, $request_id);
    
    if (mysqli_stmt_execute($updateStmt)) {
        // Initialize Africa’s Talking API
        $username = "your_username"; // Replace with your Africa's Talking username
        $apiKey = "your_api_key"; // Replace with your API key
        $gateway = new AfricasTalkingGateway($username, $apiKey);

        if ($new_status == "Accepted") {
            // Send SMS to the customer
            $messageToCustomer = "Your request has been accepted. The driver is on the way!";
            $gateway->sendMessage($customer_phone, $messageToCustomer);

            // Notify admin
            $messageToAdmin = "Request #$request_id has been accepted by a driver.";
            $gateway->sendMessage($admin_phone, $messageToAdmin);
        } elseif ($new_status == "Declined") {
            // Notify admin so they can reassign
            $messageToAdmin = "Request #$request_id was declined. Please assign another driver.";
            $gateway->sendMessage($admin_phone, $messageToAdmin);
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
