<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check in the admins table first, then in the drivers table
    $query = "SELECT id, email, password, 'admin' AS role FROM admins WHERE email = ? 
              UNION 
              SELECT id, email, password, 'driver' AS role FROM drivers WHERE email = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $email);
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
