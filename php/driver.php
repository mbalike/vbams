<?php
include('config.php');

$driverId = $_GET['id'];

$query = "SELENT * FROM drivers WHERE id = $driverId";
$result = $conn->query($query);

if($result->num_rows > 0){
    $driver = $result->fetch_assoc();
    echo json_encode($driver);
}else{
    echo json_encode(['error' => 'Driver not found']);
}
$conn->closed();
?>
