<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php'; // Ensure this file connects to your database

// Beem SMS API credentials
$api_key = '<api_key>';
$secret_key = '<secret_key>';
$sms_sender = 'INFO'; // Sender ID

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

            // Fetch driver's phone number
            $query3 = "SELECT phone, name FROM drivers WHERE id = ?";
            $stmt3 = mysqli_prepare($conn, $query3);
            mysqli_stmt_bind_param($stmt3, "i", $driver_id);
            mysqli_stmt_execute($stmt3);
            $result = mysqli_stmt_get_result($stmt3);
            $driver = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt3);

            if ($driver) {
                $driver_phone = $driver['phone'];
                $driver_name = $driver['name'];

                // SMS Message
                $message = "Hello $driver_name, you have been assigned a new service request. Please check your dashboard for details.";

                // Send SMS
                $postData = array(
                    'source_addr' => $sms_sender,
                    'encoding' => 0,
                    'message' => $message,
                    'recipients' => [array('recipient_id' => '1', 'dest_addr' => $driver_phone)]
                );

                $url = 'https://apisms.beem.africa/v1/send';
                $ch = curl_init($url);

                curl_setopt_array($ch, array(
                    CURLOPT_POST => TRUE,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Basic ' . base64_encode("$api_key:$secret_key"),
                        'Content-Type: application/json'
                    ),
                    CURLOPT_POSTFIELDS => json_encode($postData)
                ));

                $response = curl_exec($ch);

                if ($response === FALSE) {
                    throw new Exception("SMS Error: " . curl_error($ch));
                }

                curl_close($ch);
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
