<?php
include('config.php');

$driverId =$_POST['id'];
$status =$_POST['status'];

$query = "UPDATE drivers SET status = '$status' WHERE id = $driverId";
if ($conn->query($query)=== TRUE){
    echo json_encode(['success' => true, 'status'=> $status]);
}else{
    echo json_encode(['error' => 'Failed to update status']);
}

$conn->close();
?>
