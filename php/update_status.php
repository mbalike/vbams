<?php
include('db.php');
include('php/auth.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];

    $query = "UPDATE requests SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $status, $request_id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../service_requests.php?success=status_updated");
        exit();
    } else {
        header("Location: ../service_requests.php?error=failed");
        exit();
    }
}

mysqli_close($conn);
?>
