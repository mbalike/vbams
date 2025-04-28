<?php
include('php/db.php');
include('php/auth.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch service requests from the database
$query = "SELECT * FROM requests";
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
    <title>Admin Dashboard - Service Requests</title>
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
        <a href="settings.php" class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">
            <i class="fas fa-gear"></i> Settings
        </a>
       
        <a href="php/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="content" id="content">
        <div class="dashboard-header">
            <h2>Service Requests</h2>
        </div>
        
        <div class="row card-container">
            <div class="col-md-3 mb-4">
                <div class="stat-card bg-primary text-white">
                    <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="card-title">Today</div>
                    <div class="card-value"><?= $daily_count ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card bg-warning text-white">
                    <div class="card-icon"><i class="fas fa-calendar-week"></i></div>
                    <div class="card-title">This Week</div>
                    <div class="card-value"><?= $weekly_count ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card bg-success text-white">
                    <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="card-title">This Month</div>
                    <div class="card-value"><?= $monthly_count ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #3a0ca3, #4361ee);">
                    <div class="card-icon"><i class="fas fa-tasks"></i></div>
                    <div class="card-title">Total Active</div>
                    <div class="card-value text-white"><?= $pending_count + $accepted_count ?></div>
                </div>
            </div>
        </div>

        <div class="row card-container">
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #f72585, #ff9e00);">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <div class="card-title">Pending</div>
                    <div class="card-value text-white"><?= $pending_count ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #4cc9f0, #4361ee);">
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="card-title">Accepted</div>
                    <div class="card-value text-white"><?= $accepted_count ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #e63946, #ff9e00);">
                    <div class="card-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="card-title">Declined</div>
                    <div class="card-value text-white"><?= $declined_count ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #2ec4b6, #3a86ff);">
                    <div class="card-icon"><i class="fas fa-flag-checkered"></i></div>
                    <div class="card-title">Completed</div>
                    <div class="card-value text-white"><?= $completed_count ?></div>
                </div>
            </div>
        </div>
        
        <h3 class="section-header">Service Requests List</h3>
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
                            <td><?= $row['location'] ?></td>
                            <td><?= $row['problem_description'] ?></td>
                            <td>
                                <?php
                                $statusClass = 'bg-secondary';
                                $statusIcon = '';
                                
                                if ($row['status'] == 'Pending') {
                                    $statusClass = 'bg-warning';
                                    $statusIcon = '<i class="fas fa-clock me-1"></i>';
                                } elseif ($row['status'] == 'Accepted') {
                                    $statusClass = 'bg-info';
                                    $statusIcon = '<i class="fas fa-check-circle me-1"></i>';
                                } elseif ($row['status'] == 'Declined') {
                                    $statusClass = 'bg-danger';
                                    $statusIcon = '<i class="fas fa-times-circle me-1"></i>';
                                } elseif ($row['status'] == 'Completed') {
                                    $statusClass = 'bg-success';
                                    $statusIcon = '<i class="fas fa-flag-checkered me-1"></i>';
                                }
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusIcon . $row['status'] ?></span>
                            </td>
                            <td>
                                <?= ($row['assigned_driver_id']) ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM drivers WHERE id = " . $row['assigned_driver_id']))['name'] : '<span class="badge bg-secondary">Not Assigned</span>' ?>
                            </td>
                            <td>
                                <button class="btn action-btn btn-assign" data-bs-toggle="modal" data-bs-target="#assignDriverModal" data-id="<?= $row['id'] ?>">
                                    <i class="fas fa-user-plus"></i> Assign
                                </button>
                                <button class="btn action-btn btn-update" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-id="<?= $row['id'] ?>">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <button class="btn action-btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteRequestModal" data-id="<?= $row['id'] ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteRequestModal" tabindex="-1" aria-labelledby="deleteRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRequestModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this request?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</p>
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
        document.addEventListener("DOMContentLoaded", function () {
            // Sidebar toggle functionality
            var toggleButton = document.getElementById("toggleSidebar");
            var sidebar = document.getElementById("sidebar");
            var content = document.getElementById("content");

            toggleButton.addEventListener("click", function () {
                sidebar.classList.toggle("hidden");
                content.classList.toggle("full-width");
            });
            
            // Modal functionality for Assign Driver
            var assignDriverModal = document.getElementById('assignDriverModal');
            assignDriverModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var requestId = button.getAttribute('data-id');
                document.getElementById('modal_request_id').value = requestId;
            });
            
            // Modal functionality for Delete Request
            var deleteRequestModal = document.getElementById('deleteRequestModal');
            deleteRequestModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var requestId = button.getAttribute('data-id');
                document.getElementById('delete_request_id').value = requestId;
            });
            
            // Modal functionality for Update Status
            var updateStatusModal = document.getElementById('updateStatusModal');
            updateStatusModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var requestId = button.getAttribute('data-id');
                document.getElementById('update_request_id').value = requestId;
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>