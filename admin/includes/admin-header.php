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
    // silent fail – dashboard still loads
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background:#f4f6f9; }
        #sidebar {
            width:260px;
            background:#2c3e50;
            min-height:100vh;
            color:#fff;
            position:fixed;
        }
        #sidebar a {
            color:#ddd;
            text-decoration:none;
            display:block;
            padding:12px 20px;
        }
        #sidebar a:hover,
        #sidebar .active > a {
            background:#34495e;
            color:#fff;
        }
        #content {
            margin-left:260px;
            padding:20px;
        }
        .sidebar-header {
            padding:20px;
            background:#1a252f;
        }
    </style>
</head>
<body>

<div class="d-flex">

<!-- ================= SIDEBAR ================= -->
<nav id="sidebar">
    <div class="sidebar-header text-center">
        <h5 class="mb-0">Eden Terrace</h5>
        <small class="text-muted">Admin Panel</small>
    </div>

    <ul class="list-unstyled mt-3">
        <li class="<?= $current_page=='dashboard.php'?'active':'' ?>">
            <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        </li>

        <li class="<?= $current_page=='manage-bookings.php'?'active':'' ?>">
            <a href="manage-bookings.php">
                <i class="bi bi-calendar-check me-2"></i>Bookings
                <?php if ($stats['pending_bookings'] > 0): ?>
                    <span class="badge bg-danger float-end"><?= $stats['pending_bookings'] ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="<?= $current_page=='manage-rooms.php'?'active':'' ?>">
            <a href="manage-rooms.php">
                <i class="bi bi-door-closed me-2"></i>Rooms
            </a>
        </li>

        <li class="<?= $current_page=='manage-tables.php'?'active':'' ?>">
            <a href="manage-tables.php">
                <i class="bi bi-grid me-2"></i>Tables
            </a>
        </li>

        <li class="<?= $current_page=='manage-menu.php'?'active':'' ?>">
            <a href="manage-menu.php">
                <i class="bi bi-menu-button me-2"></i>Menu Items
            </a>
        </li>

        <li class="<?= $current_page=='manage-users.php'?'active':'' ?>">
            <a href="manage-users.php">
                <i class="bi bi-people me-2"></i>Users
            </a>
        </li>

        <li class="mt-4">
            <a href="logout.php" class="text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </li>
    </ul>
</nav>

<!-- ================= CONTENT ================= -->
<div id="content">
    <nav class="navbar bg-white shadow-sm rounded mb-4 px-3">
        <span class="navbar-brand fw-bold"><?= htmlspecialchars($page_title) ?></span>
        <span class="text-muted">
            Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
        </span>
    </nav>
