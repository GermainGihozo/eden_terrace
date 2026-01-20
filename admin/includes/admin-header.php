<?php
// admin/includes/admin-header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   AUTH CHECK
========================= */
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

/* =========================
   PAGE TITLE
========================= */
$page_title = $page_title ?? 'Admin Panel';

/* =========================
   DB CONNECTION
========================= */
require_once __DIR__ . '/../../includes/db.php';

$stats = [
    'total_bookings'      => 0,
    'pending_bookings'    => 0,
    'today_bookings'      => 0,
    'total_users'         => 0,
    'total_rooms'         => 0,
    'occupied_rooms'      => 0,
    'total_revenue'       => 0,
    'today_revenue'       => 0
];

try {
    $db = getDB();

    // Total bookings
    $stats['total_bookings'] = $db
        ->query("SELECT COUNT(*) FROM eden_terrace_bookings")
        ->fetchColumn();

    // Pending bookings
    $stats['pending_bookings'] = $db
        ->query("SELECT COUNT(*) FROM eden_terrace_bookings WHERE status = 'pending'")
        ->fetchColumn();

    // Today's bookings
    $stats['today_bookings'] = $db
        ->query("SELECT COUNT(*) FROM eden_terrace_bookings WHERE DATE(created_at) = CURDATE()")
        ->fetchColumn();

    // Users
    $stats['total_users'] = $db
        ->query("SELECT COUNT(*) FROM eden_terrace_users")
        ->fetchColumn();

    // Rooms
    $stats['total_rooms'] = $db
        ->query("SELECT COUNT(*) FROM eden_terrace_rooms")
        ->fetchColumn();

    // Occupied rooms
    $stats['occupied_rooms'] = $db
        ->query("SELECT COUNT(*) FROM eden_terrace_rooms WHERE is_available = 0")
        ->fetchColumn();

    // Total revenue
    $stats['total_revenue'] = $db
        ->query("SELECT COALESCE(SUM(total_amount),0) FROM eden_terrace_bookings WHERE status IN ('paid','completed')")
        ->fetchColumn();

    // Today's revenue
    $stats['today_revenue'] = $db
        ->query("
            SELECT COALESCE(SUM(total_amount),0)
            FROM eden_terrace_bookings
            WHERE status IN ('paid','completed')
            AND DATE(created_at) = CURDATE()
        ")
        ->fetchColumn();

} catch (PDOException $e) {
    // silent fail
}

/* =========================
   ACTIVE PAGE
========================= */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?> | Eden Terrace Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 250px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f5f7fb;
            min-height: 100vh;
        }
        
        /* ========== SIDEBAR ========== */
        #sidebar {
            width: var(--sidebar-width);
            background: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .sidebar-header small {
            opacity: 0.8;
        }
        
        #sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        #sidebar .nav-link:hover {
            background: #f8f9fa;
            color: #667eea;
            border-left-color: #667eea;
        }
        
        #sidebar .nav-link.active {
            background: #f0f4ff;
            color: #667eea;
            border-left-color: #667eea;
            font-weight: 500;
        }
        
        #sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        #sidebar .badge {
            margin-left: auto;
        }
        
        .logout-link .nav-link {
            color: #e74c3c;
        }
        
        /* ========== CONTENT AREA ========== */
        #main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        /* ========== TOP NAVBAR ========== */
        .top-navbar {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-navbar h1 {
            font-size: 1.5rem;
            margin: 0;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* ========== MOBILE TOGGLE BUTTON ========== */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #2c3e50;
            padding: 5px 10px;
            cursor: pointer;
        }
        
        /* ========== RESPONSIVE BREAKPOINTS ========== */
        
        /* Large tablets and small laptops */
        @media (max-width: 1199.98px) {
            .top-navbar {
                padding: 15px 20px;
            }
            
            #main-content {
                padding: 15px;
            }
        }
        
        /* Tablets */
        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%);
            }
            
            #sidebar.show {
                transform: translateX(0);
            }
            
            #main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            /* Overlay when sidebar is open */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 99;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        /* Mobile phones */
        @media (max-width: 767.98px) {
            .top-navbar {
                padding: 12px 15px;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .top-navbar > div {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            #main-content {
                padding: 10px;
            }
            
            /* Make cards full width on mobile */
            .col-md-6, .col-lg-4, .col-xl-3 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        
        /* Small mobile phones */
        @media (max-width: 575.98px) {
            .top-navbar h1 {
                font-size: 1.2rem;
            }
            
            .user-info span {
                display: none;
            }
            
            /* Touch-friendly elements */
            .btn, button, .nav-link {
                min-height: 44px;
            }
            
            .table-responsive {
                border: 1px solid #dee2e6;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                margin-bottom: 10px;
            }
        }
        
        /* ========== STAT CARDS ========== */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            height: 100%;
        }
        
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.2;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Loading animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>
<body>

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ================= SIDEBAR ================= -->
<aside id="sidebar">
    <div class="sidebar-header">
        <h4>Eden Terrace</h4>
        <small>Admin Panel</small>
    </div>
    
    <nav class="nav flex-column mt-3">
        <a href="dashboard.php" class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>
        
        <a href="manage-bookings.php" class="nav-link <?= $current_page=='manage-bookings.php'?'active':'' ?>">
            <i class="bi bi-calendar-check"></i>
            Bookings
            <?php if ($stats['pending_bookings'] > 0): ?>
                <span class="badge bg-danger rounded-pill ms-auto"><?= $stats['pending_bookings'] ?></span>
            <?php endif; ?>
        </a>
        
        <a href="manage-rooms.php" class="nav-link <?= $current_page=='manage-rooms.php'?'active':'' ?>">
            <i class="bi bi-door-closed"></i>
            Rooms
        </a>
        
        <a href="manage-tables.php" class="nav-link <?= $current_page=='manage-tables.php'?'active':'' ?>">
            <i class="bi bi-grid"></i>
            Tables
        </a>
        
        <a href="manage-menu.php" class="nav-link <?= $current_page=='manage-menu.php'?'active':'' ?>">
            <i class="bi bi-menu-button"></i>
            Menu Items
        </a>
        
        <a href="manage-users.php" class="nav-link <?= $current_page=='manage-users.php'?'active':'' ?>">
            <i class="bi bi-people"></i>
            Users
        </a>
        
        <div class="mt-4 pt-3 border-top">
            <a href="logout.php" class="nav-link logout-link">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>
    </nav>
</aside>

<!-- ================= MAIN CONTENT ================= -->
<div id="main-content" class="fade-in">
    
    <!-- Top Navigation -->
    <div class="top-navbar">
        <div>
            <button class="mobile-toggle" id="mobileToggle">
                <i class="bi bi-list"></i>
            </button>
            <h1><?= htmlspecialchars($page_title) ?></h1>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
            </div>
            <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
        </div>
    </div>
    
    <!-- Main Content Will Go Here -->
    <div class="container-fluid px-0">