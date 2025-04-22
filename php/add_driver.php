<?php
include('db.php');
include('php/auth.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    $query = "INSERT INTO drivers (name, phone, email, location, availability_status) VALUES ('$name', '$phone', '$email', '$location', 'Offline')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../drivers2.php?success=Driver added successfully");
    } else {
        header("Location: ../drivers2.php?error=Error adding driver");
    }
}

mysqli_close($conn);
?>
