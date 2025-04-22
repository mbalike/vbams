<?php
include('db.php');
include('php/auth.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $query = "DELETE FROM drivers WHERE id='$id'";

    if (mysqli_query($conn, $query)) {
        header("Location: ../drivers2.php?success=deleted");
    } else {
        echo "Error deleting driver: " . mysqli_error($conn);
    }
}
?>
