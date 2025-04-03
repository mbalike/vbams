<?php
include('db.php');
include('php/auth.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $status = $_POST['status'];

    $query = "UPDATE drivers SET name='$name', phone='$phone', email='$email', availability_status='$status' WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        header("Location: ../drivers.php?success=updated");
    } else {
        echo "Error updating driver: " . mysqli_error($conn);
    }
}
?>
