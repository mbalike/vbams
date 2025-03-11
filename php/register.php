<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = "Phone number must be 10-15 digits.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT email FROM admins WHERE email = ? UNION SELECT email FROM drivers WHERE email = ?");
    $checkEmail->bind_param("ss", $email, $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $errors[] = "Email already registered.";
    }
    $checkEmail->close();

    // If errors exist, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: ../register.html");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user based on type
    if ($user_type === "admin") {
        $stmt = $conn->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
    } elseif ($user_type === "driver") {
        $stmt = $conn->prepare("INSERT INTO drivers (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);
    } else {
        $_SESSION['errors'] = ["Invalid user type."];
        header("Location: ../register.html");
        exit();
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! You can now login.";
        header("Location: ../login.html");
    } else {
        $_SESSION['errors'] = ["Registration failed. Please try again."];
        header("Location: ../register.html");
    }

    $stmt->close();
    $conn->close();
}
?>
