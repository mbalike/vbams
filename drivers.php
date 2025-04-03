<?php
// Include database connection file
include('php/db.php');
include('php/auth.php');
$current_page = basename($_SERVER['PHP_SELF']); 

// Fetch drivers from the database
$query = "SELECT * FROM drivers";
$result = mysqli_query($conn, $query);
include('php/auth.php');
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
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Drivers Management</h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDriverModal">
        Add Driver
    </button>
</div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['phone'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['availability_status'] ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editDriverModal" 
                            data-id="<?= $row['id'] ?>" data-name="<?= $row['name'] ?>" data-phone="<?= $row['phone'] ?>"
                            data-email="<?= $row['email'] ?>" data-status="<?= $row['availability_status'] ?>">Edit</button>
                        
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteDriverModal"
                            data-id="<?= $row['id'] ?>">Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit Driver Modal -->
<div class="modal fade" id="editDriverModal" tabindex="-1" aria-labelledby="editDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDriverModalLabel">Edit Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="php/edit_driver.php">
                    <input type="hidden" name="id" id="edit_driver_id">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="edit_driver_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="edit_driver_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_driver_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Availability Status</label>
                        <select name="status" id="edit_driver_status" class="form-select">
                            <option value="Available">Available</option>
                            <option value="Busy">Busy</option>
                            <option value="Offline">Offline</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Driver</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Driver Modal -->
<div class="modal fade" id="deleteDriverModal" tabindex="-1" aria-labelledby="deleteDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDriverModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this driver?</p>
                <form method="POST" action="php/delete_driver.php">
                    <input type="hidden" name="id" id="delete_driver_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var editDriverModal = document.getElementById("editDriverModal");Update Status
    editDriverModal.addEventListener("show.bs.modal", function (event) {
        var button = event.relatedTarget;
        document.getElementById("edit_driver_id").value = button.getAttribute("data-id");
        document.getElementById("edit_driver_name").value = button.getAttribute("data-name");
        document.getElementById("edit_driver_phone").value = button.getAttribute("data-phone");
        document.getElementById("edit_driver_email").value = button.getAttribute("data-email");
        document.getElementById("edit_driver_status").value = button.getAttribute("data-status");
    });

    var deleteDriverModal = document.getElementById("deleteDriverModal");
    deleteDriverModal.addEventListener("show.bs.modal", function (event) {
        var button = event.relatedTarget;
        document.getElementById("delete_driver_id").value = button.getAttribute("data-id");
    });
});
</script>
<!-- Add Driver Modal -->
<div class="modal fade" id="addDriverModal" tabindex="-1" aria-labelledby="addDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDriverModalLabel">Add New Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="php/add_driver.php">Update Status
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Driver</button>
                </form>
            </div>
        </div>
    </div>
</div>


</body>
</html>

<?php
mysqli_close($conn);
?>
