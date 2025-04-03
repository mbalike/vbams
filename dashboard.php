<?php
include('php/db.php');
include('php/auth.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../driver_requests2.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Get current admin name
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT name FROM admins WHERE id = $admin_id";
$admin_result = mysqli_query($conn, $admin_query);
$admin_name = mysqli_fetch_assoc($admin_result)['name'];

// Fetch service requests summary data
$daily_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE DATE(created_at) = CURDATE()"))['count'];
$weekly_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"))['count'];
$monthly_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"))['count'];

// Status breakdown for requests
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Pending'"))['count'];
$accepted_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Accepted'"))['count'];
$declined_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Declined'"))['count'];
$completed_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Completed'"))['count'];

// Fetch driver summary data
$total_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers"))['count'];
$available_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers WHERE availability_status = 'Available'"))['count'];
$busy_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers WHERE availability_status = 'Busy'"))['count'];
$offline_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers WHERE availability_status = 'Offline'"))['count'];

// Fetch recent service requests
$recent_requests_query = "SELECT r.*, d.name as driver_name 
                         FROM requests r 
                         LEFT JOIN drivers d ON r.assigned_driver_id = d.id 
                         ORDER BY r.created_at DESC LIMIT 5";
$recent_requests_result = mysqli_query($conn, $recent_requests_query);

// Fetch recent drivers activity
$recent_drivers_query = "SELECT * FROM drivers ORDER BY updated_at DESC LIMIT 5";
$recent_drivers_result = mysqli_query($conn, $recent_drivers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .badge {
            padding: 8px 12px;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 16px;
            margin: 0;
        }
        
        .user-role {
            color: #6c757d;
            font-size: 14px;
        }
        
        .date-time {
            margin-left: auto;
            text-align: right;
        }
        
        .current-date {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 16px;
            margin: 0;
        }
        
        .current-time {
            color: #6c757d;
            font-size: 14px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .activity-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            height: 100%;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.2s;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .activity-subtitle {
            color: #6c757d;
            font-size: 12px;
            margin: 0;
        }
        
        .activity-time {
            color: #6c757d;
            font-size: 12px;
            margin-top: 5px;
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
        <a href="drivers.php" class="<?= ($current_page == 'drivers.php') ? 'active' : '' ?>">
            <i class="fas fa-user-tie"></i> Drivers
        </a>
        <a href="reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a href="settings.php" class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="php/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="content" id="content">
        <div class="user-info mb-4">
            <div class="user-avatar">
                <?= substr($admin_name, 0, 1) ?>
            </div>
            <div class="user-details">
                <p class="user-name">Welcome, <?= $admin_name ?></p>
                <p class="user-role">Administrator</p>
            </div>
            <div class="date-time">
                <p class="current-date" id="current-date"></p>
                <p class="current-time" id="current-time"></p>
            </div>
        </div>
        
        <div class="dashboard-header">
            <h2>Dashboard Overview</h2>
        </div>
        
        <!-- Request Summary Section -->
        <h3 class="section-header">Service Requests Summary</h3>
        <div class="row card-container">
            <div class="col-md-3 mb-4">
                <div class="stat-card bg-primary text-white">
                    <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="card-title">Today's Requests</div>
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
        
        <!-- Drivers Summary Section -->
        <h3 class="section-header">Drivers Summary</h3>
        <div class="row card-container">
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #6c5ce7, #a29bfe);">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-title">Total Drivers</div>
                    <div class="card-value text-white"><?= $total_drivers ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #00b894, #55efc4);">
                    <div class="card-icon"><i class="fas fa-user-check"></i></div>
                    <div class="card-title">Available</div>
                    <div class="card-value text-white"><?= $available_drivers ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #ff7675, #fab1a0);">
                    <div class="card-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="card-title">Busy</div>
                    <div class="card-value text-white"><?= $busy_drivers ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #636e72, #b2bec3);">
                    <div class="card-icon"><i class="fas fa-user-slash"></i></div>
                    <div class="card-title">Offline</div>
                    <div class="card-value text-white"><?= $offline_drivers ?></div>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h4>Request Status Distribution</h4>
                    <canvas id="requestStatusChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h4>Drivers Availability</h4>
                    <canvas id="driverStatusChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="activity-card">
                    <h4>Recent Service Requests</h4>
                    <?php while($request = mysqli_fetch_assoc($recent_requests_result)): ?>
                        <div class="activity-item">
                            <p class="activity-title"><?= $request['name'] ?> - <?= $request['problem_description'] ?></p>
                            <p class="activity-subtitle">
                                <span class="badge 
                                    <?= ($request['status'] == 'Pending') ? 'bg-warning' : 
                                        (($request['status'] == 'Accepted') ? 'bg-info' : 
                                        (($request['status'] == 'Declined') ? 'bg-danger' : 'bg-success')) ?>">
                                    <?= $request['status'] ?>
                                </span>
                                <?= $request['driver_name'] ? 'Assigned to: ' . $request['driver_name'] : 'Not assigned' ?>
                            </p>
                            <p class="activity-time">
                                <i class="fas fa-clock me-1"></i> 
                                <?= date('M d, Y h:i A', strtotime($request['created_at'])) ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                    <div class="text-center mt-3">
                        <a href="service_requests.php" class="btn btn-sm btn-primary">View All Requests</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="activity-card">
                    <h4>Active Drivers</h4>
                    <?php while($driver = mysqli_fetch_assoc($recent_drivers_result)): ?>
                        <div class="activity-item">
                            <p class="activity-title"><?= $driver['name'] ?></p>
                            <p class="activity-subtitle">
                                <span class="badge 
                                    <?= ($driver['availability_status'] == 'Available') ? 'bg-success' : 
                                        (($driver['availability_status'] == 'Busy') ? 'bg-warning' : 'bg-secondary') ?>">
                                    <?= $driver['availability_status'] ?>
                                </span>
                                Phone: <?= $driver['phone'] ?>
                            </p>
                            <p class="activity-time">
                                <i class="fas fa-clock me-1"></i> 
                                Last active: <?= date('M d, Y h:i A', strtotime($driver['updated_at'])) ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                    <div class="text-center mt-3">
                        <a href="drivers.php" class="btn btn-sm btn-primary">View All Drivers</a>
                    </div>
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
            
            // Real-time date and time
            function updateDateTime() {
                const now = new Date();
                const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
                
                document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
                document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
            }
            
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // Request Status Chart
            var requestCtx = document.getElementById('requestStatusChart').getContext('2d');
            var requestStatusChart = new Chart(requestCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Accepted', 'Declined', 'Completed'],
                    datasets: [{
                        data: [<?= $pending_count ?>, <?= $accepted_count ?>, <?= $declined_count ?>, <?= $completed_count ?>],
                        backgroundColor: [
                            'rgba(255, 159, 64, 0.8)',  // Pending (orange)
                            'rgba(54, 162, 235, 0.8)',  // Accepted (blue)
                            'rgba(255, 99, 132, 0.8)',  // Declined (red)
                            'rgba(75, 192, 192, 0.8)'   // Completed (green)
                        ],
                        borderColor: [
                            'rgba(255, 159, 64, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {<?php
include('php/db.php');
include('php/auth.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$current_page = basename($_SERVER['PHP_SELF']);

// Get current admin name
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT name FROM admins WHERE id = $admin_id";
$admin_result = mysqli_query($conn, $admin_query);
$admin_name = mysqli_fetch_assoc($admin_result)['name'];

// Fetch service requests summary data
$daily_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE DATE(created_at) = CURDATE()"))['count'];
$weekly_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"))['count'];
$monthly_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"))['count'];

// Status breakdown for requests
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Pending'"))['count'];
$accepted_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Accepted'"))['count'];
$declined_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Declined'"))['count'];
$completed_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM requests WHERE status = 'Completed'"))['count'];

// Fetch driver summary data
$total_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers"))['count'];
$available_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers WHERE availability_status = 'Available'"))['count'];
$busy_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers WHERE availability_status = 'Busy'"))['count'];
$offline_drivers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM drivers WHERE availability_status = 'Offline'"))['count'];

// Fetch recent service requests
$recent_requests_query = "SELECT r.*, d.name as driver_name 
                         FROM requests r 
                         LEFT JOIN drivers d ON r.assigned_driver_id = d.id 
                         ORDER BY r.created_at DESC LIMIT 5";
$recent_requests_result = mysqli_query($conn, $recent_requests_query);

// Fetch recent drivers activity
$recent_drivers_query = "SELECT * FROM drivers ORDER BY last_activity DESC LIMIT 5";
$recent_drivers_result = mysqli_query($conn, $recent_drivers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .badge {
            padding: 8px 12px;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 16px;
            margin: 0;
        }
        
        .user-role {
            color: #6c757d;
            font-size: 14px;
        }
        
        .date-time {
            margin-left: auto;
            text-align: right;
        }
        
        .current-date {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 16px;
            margin: 0;
        }
        
        .current-time {
            color: #6c757d;
            font-size: 14px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .activity-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            height: 100%;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.2s;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .activity-subtitle {
            color: #6c757d;
            font-size: 12px;
            margin: 0;
        }
        
        .activity-time {
            color: #6c757d;
            font-size: 12px;
            margin-top: 5px;
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
        <a href="drivers.php" class="<?= ($current_page == 'drivers.php') ? 'active' : '' ?>">
            <i class="fas fa-user-tie"></i> Drivers
        </a>
        <a href="reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a href="settings.php" class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="php/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <div class="content" id="content">
        <div class="user-info mb-4">
            <div class="user-avatar">
                <?= substr($admin_name, 0, 1) ?>
            </div>
            <div class="user-details">
                <p class="user-name">Welcome, <?= $admin_name ?></p>
                <p class="user-role">Administrator</p>
            </div>
            <div class="date-time">
                <p class="current-date" id="current-date"></p>
                <p class="current-time" id="current-time"></p>
            </div>
        </div>
        
        <div class="dashboard-header">
            <h2>Dashboard Overview</h2>
        </div>
        
        <!-- Request Summary Section -->
        <h3 class="section-header">Service Requests Summary</h3>
        <div class="row card-container">
            <div class="col-md-3 mb-4">
                <div class="stat-card bg-primary text-white">
                    <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="card-title">Today's Requests</div>
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
        
        <!-- Drivers Summary Section -->
        <h3 class="section-header">Drivers Summary</h3>
        <div class="row card-container">
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #6c5ce7, #a29bfe);">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-title">Total Drivers</div>
                    <div class="card-value text-white"><?= $total_drivers ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #00b894, #55efc4);">
                    <div class="card-icon"><i class="fas fa-user-check"></i></div>
                    <div class="card-title">Available</div>
                    <div class="card-value text-white"><?= $available_drivers ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #ff7675, #fab1a0);">
                    <div class="card-icon"><i class="fas fa-user-clock"></i></div>
                    <div class="card-title">Busy</div>
                    <div class="card-value text-white"><?= $busy_drivers ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #636e72, #b2bec3);">
                    <div class="card-icon"><i class="fas fa-user-slash"></i></div>
                    <div class="card-title">Offline</div>
                    <div class="card-value text-white"><?= $offline_drivers ?></div>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h4>Request Status Distribution</h4>
                    <canvas id="requestStatusChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h4>Drivers Availability</h4>
                    <canvas id="driverStatusChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="activity-card">
                    <h4>Recent Service Requests</h4>
                    <?php while($request = mysqli_fetch_assoc($recent_requests_result)): ?>
                        <div class="activity-item">
                            <p class="activity-title"><?= $request['name'] ?> - <?= $request['problem_description'] ?></p>
                            <p class="activity-subtitle">
                                <span class="badge 
                                    <?= ($request['status'] == 'Pending') ? 'bg-warning' : 
                                        (($request['status'] == 'Accepted') ? 'bg-info' : 
                                        (($request['status'] == 'Declined') ? 'bg-danger' : 'bg-success')) ?>">
                                    <?= $request['status'] ?>
                                </span>
                                <?= $request['driver_name'] ? 'Assigned to: ' . $request['driver_name'] : 'Not assigned' ?>
                            </p>
                            <p class="activity-time">
                                <i class="fas fa-clock me-1"></i> 
                                <?= date('M d, Y h:i A', strtotime($request['created_at'])) ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                    <div class="text-center mt-3">
                        <a href="service_requests.php" class="btn btn-sm btn-primary">View All Requests</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="activity-card">
                    <h4>Active Drivers</h4>
                    <?php while($driver = mysqli_fetch_assoc($recent_drivers_result)): ?>
                        <div class="activity-item">
                            <p class="activity-title"><?= $driver['name'] ?></p>
                            <p class="activity-subtitle">
                                <span class="badge 
                                    <?= ($driver['availability_status'] == 'Available') ? 'bg-success' : 
                                        (($driver['availability_status'] == 'Busy') ? 'bg-warning' : 'bg-secondary') ?>">
                                    <?= $driver['availability_status'] ?>
                                </span>
                                Phone: <?= $driver['phone'] ?>
                            </p>
                            <p class="activity-time">
                                <i class="fas fa-clock me-1"></i> 
                                Last active: <?= date('M d, Y h:i A', strtotime($driver['last_activity'])) ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                    <div class="text-center mt-3">
                        <a href="drivers.php" class="btn btn-sm btn-primary">View All Drivers</a>
                    </div>
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
            
            // Real-time date and time
            function updateDateTime() {
                const now = new Date();
                const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
                
                document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
                document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
            }
            
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // Request Status Chart
            var requestCtx = document.getElementById('requestStatusChart').getContext('2d');
            var requestStatusChart = new Chart(requestCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Accepted', 'Declined', 'Completed'],
                    datasets: [{
                        data: [<?= $pending_count ?>, <?= $accepted_count ?>, <?= $declined_count ?>, <?= $completed_count ?>],
                        backgroundColor: [
                            'rgba(255, 159, 64, 0.8)',  // Pending (orange)
                            'rgba(54, 162, 235, 0.8)',  // Accepted (blue)
                            'rgba(255, 99, 132, 0.8)',  // Declined (red)
                            'rgba(75, 192, 192, 0.8)'   // Completed (green)
                        ],
                        borderColor: [
                            'rgba(255, 159, 64, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
            
            // Driver Status Chart
            var driverCtx = document.getElementById('driverStatusChart').getContext('2d');
            var driverStatusChart = new Chart(driverCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Busy', 'Offline'],
                    datasets: [{
                        data: [<?= $available_drivers ?>, <?= $busy_drivers ?>, <?= $offline_drivers ?>],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.8)',  // Available (green)
                            'rgba(255, 159, 64, 0.8)',  // Busy (orange)
                            'rgba(201, 203, 207, 0.8)'  // Offline (grey)
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(201, 203, 207, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {<?php
  include('php/db.php');
  include('php/auth.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        <a href="drivers.php">Drivers</a>
        <a href="#">Reports</a>
        <a href="#">Settings</a>
        <a href="php/logout.php">Logout</a>
    </div>
    
    <div class="content">
        <h2>Welcome, Admin</h2>
        <p>Select an option from the sidebar.</p>
    </div>
</body>
</html>

                            position: 'bottom',
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
            
            // Driver Status Chart
            var driverCtx = document.getElementById('driverStatusChart').getContext('2d');
            var driverStatusChart = new Chart(driverCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Busy', 'Offline'],
                    datasets: [{
                        data: [<?= $available_drivers ?>, <?= $busy_drivers ?>, <?= $offline_drivers ?>],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.8)',  // Available (green)
                            'rgba(255, 159, 64, 0.8)',  // Busy (orange)
                            'rgba(201, 203, 207, 0.8)'  // Offline (grey)
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(201, 203, 207, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {<?php
  include('php/db.php');
  include('php/auth.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        <a href="drivers.php">Drivers</a>
        <a href="#">Reports</a>
        <a href="#">Settings</a>
        <a href="php/logout.php">Logout</a>
    </div>
    
    <div class="content">
        <h2>Welcome, Admin</h2>
        <p>Select an option from the sidebar.</p>
    </div>
</body>
</html>

                            position: 'bottom',
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>