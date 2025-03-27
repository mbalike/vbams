<?php
session_start();
include('db.php');

if (isset($_SESSION['user_id'])) {
    // Prevent redirection loop
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'driver') {
        header("Location: ../driver_requests.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE phone = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: ../dashboard.php");
                exit();
            } elseif ($user['role'] == 'driver') {
                header("Location: ../driver_requests.php");
                exit();
            }
        } else {
            echo "Invalid password";
        }
    } else {
        echo "User not found";
    }
}
?>