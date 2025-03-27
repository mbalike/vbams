<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];

    $query = "UPDATE requests SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $request_id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../driver_requests.php");
        exit();
    } else {
        echo "Error updating request.";
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
