<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php'; // Ensure this file connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $driver_id = $_POST['driver_id'];

    if (!empty($request_id) && !empty($driver_id)) {
        // Start transaction to ensure consistency
        mysqli_begin_transaction($conn);

        try {
            // Assign driver to request & update status to 'Accepted'
            $query1 = "UPDATE requests SET assigned_driver_id = ?, status = 'Accepted' WHERE id = ?";
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

            // Commit the transaction
            mysqli_commit($conn);

            $_SESSION['success'] = "Driver assigned successfully.";
        } catch (Exception $e) {
            // Rollback in case of error
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
