<?php
// api.php - Create this file on your InfinityFree hosting

// Include your existing database connection
include('php/db.php');

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Be careful with this in production
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Fetch drivers from the database using your existing query
$query = "SELECT * FROM drivers";
$result = mysqli_query($conn, $query);

if (!$result) {
    // Return error as JSON
    echo json_encode(['error' => 'Error fetching drivers: ' . mysqli_error($conn)]);
    exit;
}

// Convert the result to an array
$drivers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $drivers[] = $row;
}

// Return the data as JSON
echo json_encode($drivers);

// Close the connection
mysqli_close($conn);
?>