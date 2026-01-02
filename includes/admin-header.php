<?php
// admin/includes/admin-header.php
$page_title = isset($page_title) ? $page_title : 'Admin Panel';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Start session and require admin access
startSession();
requireAdmin();

// Get admin stats for sidebar
$db = getDB();
$stats = [];

try {
    // Total bookings today
    $stmt = $db->query("SELECT COUNT(*) as total FROM bookings WHERE DATE(created_at) = CURDATE()");
    $stats['bookings_today'] = $stmt->fetchColumn();
    
    // Total rooms occupied
    $stmt = $db->query("SELECT COUNT(*) as occupied FROM rooms WHERE is_available = FALSE");
    $stats['rooms_occupied'] = $stmt->fetchColumn();
    
    // Total revenue today
    $stmt = $db->query("SELECT SUM(total_amount) as revenue FROM bookings WHERE DATE(created_at) = CURDATE() AND status = 'confirmed'");
    $stats['revenue_today'] = $stmt->fetchColumn() ?? 0;
    
    // Pending restaurant reservations
    $stmt = $db->query("SELECT COUNT(*) as pending FROM bookings WHERE booking_type = 'restaurant' AND status = 'pending'");
    $stats['pending_reservations'] = $stmt->fetchColumn();
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $stats = [
        'bookings_today' => 0, 
        'rooms_occupied' => 0, 
        'revenue_today' => 0, 
        'pending_reservations' => 0,
        'total_users' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Eden Terrace Admin</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --sidebar-width: 250px;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, #1a252f 100%);
            color: white;
            transition: all 0.3s;
            min-height: 100vh;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        #sidebar .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        #sidebar ul.components {
            padding: 20px 0;
        }
        
        #sidebar ul li a {
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            display: block;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        #sidebar ul li a:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left: 3px solid var(--secondary-color);
        }
        
        #sidebar ul li.active > a {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left: 3px solid var(--secondary-color);
            font-weight: 500;
        }
        
        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .stat-card {
            border-radius: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        #content {
            width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
            min-height: 100vh;
        }
        
        .navbar-admin {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .badge-admin {
            background: var(--accent-color);
            color: white;
            font-size: 0.7rem;
        }
        
        .table-admin th {
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-weight: 500;
            padding: 12px 15px;
        }
        
        .table-admin td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 20px;
        }
        
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
                position: fixed;
                z-index: 1050;
            }
            #content {
                width: 100%;
                margin-left: 0;
            }
            #sidebar.active {
                margin-left: 0;
            }
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.6rem;
            padding: 3px 6px;
        }
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .action-buttons .btn {
            border-radius: 20px;
            padding: 6px 15px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <h4 class="mb-0">
                    <i class="bi bi-buildings text-warning"></i> Eden Terrace
                    <small class="d-block text-muted mt-1" style="font-size: 0.8rem;">Administration Panel</small>
                </h4>
            </div>
            
            <ul class="list-unstyled components">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-bookings.php' ? 'active' : ''; ?>">
                    <a href="manage-bookings.php">
                        <i class="bi bi-calendar-check"></i> Bookings
                        <?php if ($stats['bookings_today'] > 0): ?>
                        <span class="badge bg-danger float-end mt-1"><?php echo $stats['bookings_today']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a href="#roomSubmenu" class="nav-link dropdown-toggle" data-bs-toggle="collapse" role="button">
                        <i class="bi bi-door-closed"></i> Rooms & Tables
                    </a>
                    <ul class="collapse list-unstyled ps-3" id="roomSubmenu">
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-rooms.php' ? 'active' : ''; ?>">
                            <a href="manage-rooms.php">
                                <i class="bi bi-chevron-right me-1"></i> Room Management
                            </a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-tables.php' ? 'active' : ''; ?>">
                            <a href="manage-tables.php">
                                <i class="bi bi-chevron-right me-1"></i> Table Management
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-menu.php' ? 'active' : ''; ?>">
                    <a href="manage-menu.php">
                        <i class="bi bi-menu-button"></i> Menu Items
                    </a>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-users.php' ? 'active' : ''; ?>">
                    <a href="manage-users.php">
                        <i class="bi bi-people"></i> Users
                        <span class="badge bg-info float-end mt-1"><?php echo $stats['total_users']; ?></span>
                    </a>
                </li>
                
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <a href="reports.php">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                </li>
                
                <li class="mt-4 pt-3 border-top border-secondary">
                    <a href="../logout.php" class="text-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer p-3 text-center border-top border-secondary">
                <div class="mb-2">
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 40px; height: 40px;">
                        <i class="bi bi-person text-white"></i>
                    </div>
                </div>
                <small class="text-muted d-block">
                    Logged in as:
                </small>
                <strong class="text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                <div class="mt-1">
                    <span class="badge bg-danger">Admin</span>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-admin px-4">
                <div class="container-fluid px-0">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="position-relative">
                                    <i class="bi bi-bell fs-5"></i>
                                    <?php if ($stats['pending_reservations'] > 0): ?>
                                    <span class="badge bg-danger notification-badge"><?php echo min($stats['pending_reservations'], 9); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li>
                                    <a class="dropdown-item" href="manage-bookings.php?filter=pending">
                                        <i class="bi bi-calendar-plus text-warning me-2"></i>
                                        <?php echo $stats['pending_reservations']; ?> pending reservations
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage-bookings.php?filter=today">
                                        <i class="bi bi-calendar-check text-primary me-2"></i>
                                        <?php echo $stats['bookings_today']; ?> bookings today
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div class="container-fluid p-4"></div>