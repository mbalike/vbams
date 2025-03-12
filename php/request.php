<?php

require 'db.php'; 


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $phone = trim($_POST["phone"]);
    $location = trim($_POST["location"]); // Fix name attribute in form
    $car_model = trim($_POST["car_model"]);
    $problem_description = trim($_POST["problem_description"]);

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO requests (name, phone, location, car_model, problem_description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $phone, $location, $car_model, $problem_description);

    if ($stmt->execute()) {
        echo "Request submitted successfully!";
        header('Location: ../index.html');
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
