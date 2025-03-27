<?php
session_start();
include('php/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    header("Location: ../login.php");
    exit();
}

$driver_id = $_SESSION['user_id'];

// Fetch only requests assigned to this driver
$query = "SELECT * FROM requests WHERE assigned_driver_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $driver_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Service Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
            flex: 1;
            padding: 20px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Driver Panel</h3>
        <a href="driver_requests.php">My Requests</a>
        <a href="profile.php">Profile</a>
        <a href="php/logout.php">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h2>Your Assigned Service Requests</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Issue</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
$index = 1;
while($row = mysqli_fetch_assoc($result)):
    if ($index < 10) {
        $index_str = '0' . $index;
    } else {
        $index_str = (string) $index;
    }
?>
    <tr>
        <td><?= $index_str ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['phone']) ?></td>
        <td><?= htmlspecialchars($row['location']) ?></td>
        <td><?= htmlspecialchars($row['problem_description']) ?></td>
        <td><strong><?= htmlspecialchars($row['status']) ?></strong></td>
        <td>
            <form method="POST" action="php/driver_requests_status.php">
                <input type="hidden" name="request_id" value="<?= htmlspecialchars($row['id']) ?>">
                <?php if ($row['status'] == 'Pending'): ?>
                    <button type="submit" name="status" value="Accepted" class="btn btn-success btn-sm">Accept</button>
                    <button type="submit" name="status" value="Declined" class="btn btn-danger btn-sm">Decline</button>
                <?php elseif ($row['status'] == 'Accepted'): ?>
                    <button type="submit" name="status" value="Completed" class="btn btn-primary btn-sm">Complete</button>
                <?php endif; ?>
            </form>
        </td>
    </tr>
<?php
$index++;
endwhile;
?>
            </tbody>
        </table>
    </div>

</body>
</html>

<?php
// Close database connection
mysqli_close($conn);
?>
