<?php
include('db.php');
include('php/auth.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    $query = "DELETE FROM requests WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $request_id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../service_requests.php?success=deleted");
        exit();
    } else {
        header("Location: ../service_requests.php?error=failed");
        exit();
    }
}

mysqli_close($conn);
?>
