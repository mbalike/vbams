<?php
session_start();
include('php/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Restrict drivers from accessing this page
if ($_SESSION['role'] == 'admin') {
    header("Location: driver_requests.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']); 
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

        .sidebar a.active {
            background-color: #007bff; /* Highlight color */
            color: white;
            font-weight: bold;
            border-radius: 5px;
            padding: 10px;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h4>Admin Panel</h4>
    <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">Dashboard</a>
    <a href="service_requests.php" class="<?= ($current_page == 'service_requests.php') ? 'active' : '' ?>">Service Requests</a>

    <?php if ($_SESSION['role'] == 'admin'): ?> <!-- Hide for drivers -->
        <a href="drivers.php" class="<?= ($current_page == 'drivers.php') ? 'active' : '' ?>">Drivers</a>
        <a href="reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : '' ?>">Reports</a>
    <?php endif; ?>

    <a href="settings.php" class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">Settings</a>
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
                        if ($row['assigned_driver_id']) {
                            $driver_query = "SELECT name FROM drivers WHERE id = " . $row['assigned_driver_id'];
                            $driver_result = mysqli_query($conn, $driver_query);
                            $driver = mysqli_fetch_assoc($driver_result);
                            echo $driver['name'] ;
                        } else {
                            echo 'Not Assigned';
                        }
                        ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="update_status.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">Update Status</a>
                            <a href="delete_request.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

<?php mysqli_close($conn); ?>
