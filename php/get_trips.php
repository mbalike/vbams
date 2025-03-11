<?php
include('config.php');

$driverId = $_GET['id'];

$query= "SELECT * FROM trips WHERE driver_id = $driverId";
$result = $conn->query($query);

$trips = [];
if($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    echo json_encode($trips);
}else{
    echo json_encode(['error' => 'No trips found']);
}

$conn->close();
?>
