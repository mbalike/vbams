<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($email) || empty($password)) {
        $errors[] = "Both fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: ../login.html");
        exit();
    }

    // Check in both tables (admins and drivers)
    $query = "(SELECT id, name, email, password, 'admin' AS user_type FROM admins WHERE email = ?) 
              UNION 
              (SELECT id, name, email, password, 'driver' AS user_type FROM drivers WHERE email = ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_type'] = $user['user_type'];
            header("Location: ../admin-dash.html");
            exit();
        } else {
            $errors[] = "Incorrect password.";
        }
    } else {
        $errors[] = "No account found with that email.";
    }

    $_SESSION['errors'] = $errors;
    header("Location: ../login.html");
    exit();
}
?>
