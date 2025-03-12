<?php
include 'db_connect.php'; // Ensure this file connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $driver_id = $_POST['driver_id'];

    if (!empty($request_id) && !empty($driver_id)) {
        $query = "UPDATE srequests SET assigned_driver_id = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $driver_id, $request_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Driver assigned successfully.";
        } else {
            $_SESSION['error'] = "Error assigning driver.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Please select a driver.";
    }

    header("Location: ./service_requests.php");
    exit();
}
?>
