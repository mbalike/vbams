<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h4>Register</h4>
                    </div>
                    <div class="card-body">
                        <!-- <?php session_start(); if (isset($_SESSION['errors'])): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($_SESSION['errors'] as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; unset($_SESSION['errors']); ?>
                                </ul>
                            </div>
                        <?php endif; ?> -->
                        <form action="php/request.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Car Model</label>
                                <input type="text" name="car_model" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Problem Description</label>
                                <input type="text" name="problem_description" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
