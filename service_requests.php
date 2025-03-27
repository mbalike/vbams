<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$current_page = basename($_SERVER['PHP_SELF']); 

include('php/auth.php');
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
                            <div class="d-flex gap-2">
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#assignDriverModal" data-id="<?= $row['id'] ?>">Assign Driver</button>
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-id="<?= $row['id'] ?>">Update Status</button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteRequestModal" data-id="<?= $row['id'] ?>">Delete</button>

                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <!-- Assign Driver Modal -->
    <div class="modal fade" id="assignDriverModal" tabindex="-1" aria-labelledby="assignDriverModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignDriverModalLabel">Assign Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="php/assign_driver.php">
                        <input type="hidden" name="request_id" id="modal_request_id">
                        <div class="mb-3">
                            <label class="form-label">Select Driver</label>
                            <select name="driver_id" class="form-select" required>
                                <option value="">-- Select Driver --</option>
                                <?php 
                                $drivers_query = "SELECT * FROM drivers";
                                $drivers_result = mysqli_query($conn, $drivers_query);
                                while ($driver = mysqli_fetch_assoc($drivers_result)): ?>
                                    <option value="<?= $driver['id'] ?>"><?= $driver['name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Assign Driver</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var assignDriverModal = document.getElementById('assignDriverModal');
        assignDriverModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var requestId = button.getAttribute('data-id');
            document.getElementById('modal_request_id').value = requestId;
        });
    </script>
    <!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteRequestModal" tabindex="-1" aria-labelledby="deleteRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRequestModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this request?
            </div>
            <div class="modal-footer">
                <form method="POST" action="php/delete_request.php">
                    <input type="hidden" name="request_id" id="delete_request_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    var deleteRequestModal = document.getElementById('deleteRequestModal');
    deleteRequestModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var requestId = button.getAttribute('data-id');
        document.getElementById('delete_request_id').value = requestId;
    });
</script>
<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Request Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="php/update_status.php">
                    <input type="hidden" name="request_id" id="update_request_id">
                    <div class="mb-3">
                        <label class="form-label">Select Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Pending">Pending</option>
                            <option value="Accepted">Accepted</option>
                            <option value="Declined">Declined</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    var updateStatusModal = document.getElementById('updateStatusModal');
    updateStatusModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var requestId = button.getAttribute('data-id');
        document.getElementById('update_request_id').value = requestId;
    });
</script>


</body>
</html>

<?php
// Close database connection
 mysqli_close($conn);
?>
