<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection file
include('php/db.php');

// Fetch service requests from the database
$query = "SELECT * FROM requests";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Service Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #343a40;
            color: white;
            padding: 20px;
            position: fixed;
        }
        .sidebar a {
            color: white;
            display: block;
            padding: 10px;
            text-decoration: none;
        }
        .sidebar a:hover {
            background: #495057;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>Admin Panel</h4>
        <a href="dashboard.php">Dashboard</a>
        <a href="service_requests.php">Service Requests</a>
        <a href="#">Drivers</a>
        <a href="#">Reports</a>
        <a href="#">Settings</a>
        <a href="php/logout.php">Logout</a>
    </div>
    
    <div class="content">
        <h2>Service Requests Management</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Issue</th>
                    <th>Status</th>
                    <th>Assigned Driver</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['phone'] ?></td>
                        <td><?= $row['location'] ?></td>
                        <td><?= $row['problem_description'] ?></td>
                        <td><?= $row['status'] ?></td>
                        <td>
                            <?php
                            // Fetch assigned driver details if any
                            if ($row['assigned_driver_id']) {
                                $driver_query = "SELECT * FROM drivers WHERE id = " . $row['assigned_driver_id'];
                                $driver_result = mysqli_query($conn, $driver_query);
                                $driver = mysqli_fetch_assoc($driver_result);
                                echo $driver['name'] ;
                            } else {
                                echo 'Not Assigned';
                            }
                            ?>
                        </td>
                        <td>
                            <!-- Action buttons to update status or assign driver -->
                            <a href="assign_driver.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Assign Driver</a>
                            <a href="update_status.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">Update Status</a>
                            <a href="delete_request.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
// Close database connection
 mysqli_close($conn);
?>
