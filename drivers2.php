<?php
include('php/db.php');
include('php/auth.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch service requests from the database
$query = "SELECT * FROM drivers";
$result = mysqli_query($conn, $query);

// Fetch summary data
$daily_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE DATE(created_at) = CURDATE()"))['count'];
$weekly_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"))['count'];
$monthly_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"))['count'];

// Status breakdown
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Pending'"))['count'];
$accepted_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Accepted'"))['count'];
$declined_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Declined'"))['count'];
$completed_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Completed'"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Drivers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --info-color: #4361ee;
            --danger-color: #e63946;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            display: flex;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f6f9fc;
        }
        
        .sidebar {
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 15px;
            position: fixed;
            left: 0;
            transition: all 0.3s ease;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .sidebar.hidden {
            left: -280px;
        }
        
        .sidebar h4 {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            text-align: center;
        }
        
        .sidebar a {
            color: rgba(255, 255, 255, 0.9);
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .sidebar a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .content {
            margin-left: 280px;
            padding: 30px;
            transition: margin-left 0.3s;
            width: 100%;
        }
        
        .content.full-width {
            margin-left: 0;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .dashboard-header h2 {
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }
        
        .toggle-btn {
            background: var(--primary-color);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
        }
        
        .toggle-btn:hover {
            background: var(--secondary-color);
        }
        
        .card-container {
            margin-bottom: 30px;
        }
        
        .stat-card {
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            height: 100%;
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .stat-card .card-value {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }
        
        .stat-card .card-icon {
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .section-header {
            margin: 35px 0 20px;
            font-weight: 600;
            color: var(--dark-color);
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        .request-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .request-table thead {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }
        
        .request-table th {
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px;
            font-size: 14px;
        }
        
        .request-table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .request-table tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .badge {
            padding: 8px 12px;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .action-btn {
            padding: 6px 12px;
            margin-right: 5px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-assign {
            background-color: var(--accent-color);
            border: none;
            color: white;
        }
        
        .btn-update {
            background-color: var(--info-color);
            border: none;
            color: white;
        }
        
        .btn-delete {
            background-color: var(--danger-color);
            border: none;
            color: white;
        }
        
        /* Modal styles */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            border-top: none;
            padding: 15px 25px 25px;
        }
        
        .modal .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .modal .form-select,
        .modal .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.2s;
        }
        
        .modal .form-select:focus,
        .modal .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .modal .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .modal .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .modal .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .modal .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }
        
        .modal .btn-danger {
            background-color: var(--danger-color);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .modal .btn-danger:hover {
            background-color: #d32535;
            transform: translateY(-2px);
        }
        
        .btn-close {
            color: white;
            opacity: 0.8;
        }
        
        .btn-close:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <button class="toggle-btn" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="sidebar" id="sidebar">
        <h4>Service Admin</h4>
        <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="service_requests.php" class="<?= ($current_page == 'service_requests.php') ? 'active' : '' ?>">
            <i class="fas fa-clipboard-list"></i> Service Requests
        </a>
        <a href="drivers2.php" class="<?= ($current_page == 'drivers2.php') ? 'active' : '' ?>">
            <i class="fas fa-user-tie"></i> Drivers
        </a>
        
        <a href="php/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="content" id="content">
        <div class="dashboard-header">
            <h2>Drivers</h2>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDriverModal">
        Add Driver
    </button>
       
        
        <h3 class="section-header">Drivers List</h3>
        
        <div class="request-table">
            
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Issue</th>
                        <th>Status</th>
                        <th>Driver</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><strong><?= $row['id'] ?></strong></td>
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['phone'] ?></td>
                            <!-- Mshiu futa hii location afu badae ntairudisha cuz haipo kwenye database yenu -->
                            <td><?= $row['location'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td>
                                <?php
                                $statusClass = 'bg-secondary';
                                $statusIcon = '';
                                
                                if ($row['availability_status'] == 'Offline') {
                                    $statusClass = 'bg-secondary';
                                    $statusIcon = '<i class="fas fa- me-1"></i>';
                                } elseif ($row['availability_status'] == 'Available') {
                                    $statusClass = 'bg-success';
                                    $statusIcon = '<i class="fas fa- me-1"></i>';
                                } elseif ($row['availability_status'] == 'Busy') {
                                    $statusClass = 'bg-danger';
                                    $statusIcon = '<i class="fas fa- me-1"></i>';
                                }
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusIcon . $row['availability_status'] ?></span>
                            </td>
                            
                            <td>
                                
                            <button class="btn action-btn btn-update" data-bs-toggle="modal" data-bs-target="#updateDriver" 
                                data-id="<?= $row['id'] ?>"
                                data-name="<?= $row['name'] ?>"
                                data-phone="<?= $row['phone'] ?>"
                                data-email="<?= $row['email'] ?>"
                                data-location="<?= $row['location'] ?>"
                                data-status="<?= $row['availability_status'] ?>">
                                <i class="fas fa-edit"></i> Update
                            </button>
                                <button class="btn action-btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteDriver" data-id="<?= $row['id'] ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDriver" tabindex="-1" aria-labelledby="deleteRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDriver">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this driver?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="php/delete_driver.php">
                        <input type="hidden" name="id" id="delete_driver_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateDriver" tabindex="-1" aria-labelledby="updateDriver" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateDriver">Edit Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="php/edit_driver.php">
                        <input type="hidden" name="id" id="edit_driver_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="edit_driver_name" class="form-control">        
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" id="edit_driver_phone" class="form-control">        
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_driver_email" class="form-control">        
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="edit_driver_location" class="form-control">        
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Availability Status</label>
                            <select name="status" id="edit_driver_status" class="form-control">
                                <option value="Available">Available</option>
                                <option value="Offline">Offline</option>
                                <option value="Busy">Busy</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Edit Driver</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Driver Modal -->
<div class="modal fade" id="addDriverModal" tabindex="-1" aria-labelledby="addDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDriverModalLabel">Add New Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="php/add_driver.php">
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
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Driver</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Sidebar toggle functionality
            var toggleButton = document.getElementById("toggleSidebar");
            var sidebar = document.getElementById("sidebar");
            var content = document.getElementById("content");

            toggleButton.addEventListener("click", function () {
                sidebar.classList.toggle("hidden");
                content.classList.toggle("full-width");
            });
            
            
            // Modal functionality for Delete Request
            var deleteDriver = document.getElementById('deleteDriver');
            deleteDriver.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var driverId = button.getAttribute('data-id');
                document.getElementById('delete_driver_id').value = driverId;
            });
            
            // Modal functionality for edit Driver
            
            var editDriver = document.getElementById('updateDriver');
            editDriver.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                
                document.getElementById("edit_driver_id").value = button.getAttribute("data-id");
                document.getElementById("edit_driver_name").value = button.getAttribute("data-name");
                document.getElementById("edit_driver_phone").value = button.getAttribute("data-phone");
                document.getElementById("edit_driver_location").value = button.getAttribute("data-location");
                document.getElementById("edit_driver_email").value = button.getAttribute("data-email");
                document.getElementById("edit_driver_status").value = button.getAttribute("data-status");
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>