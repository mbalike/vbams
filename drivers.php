<?php
// Include database connection file
include('php/db.php');
$current_page = basename($_SERVER['PHP_SELF']); 

// Fetch drivers from the database
$query = "SELECT * FROM drivers";
$result = mysqli_query($conn, $query);

if (!$result) {
    die('Error fetching drivers: ' . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Drivers</title>
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
    <a href="drivers.php" class="<?= ($current_page == 'drivers.php') ? 'active' : '' ?>">Drivers</a>
    <a href="reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : '' ?>">Reports</a>
    <a href="settings.php" class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">Settings</a>
    <a href="php/logout.php">Logout</a>
</div>
    
    <div class="content">
        <h2>Drivers Management</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    
                    
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['phone'] ?></td>
                        <td><?= $row['email'] ?></td>
                        <td><?= $row['availability_status'] ?></td>
                        
                        <td>
                        <a href="edit_driver.php?id=<?php echo $row['id']; ?>" class="btn btn-warning">Edit</a>
                        <a href="delete_driver.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this driver?')">Delete</a>
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

