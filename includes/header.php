<?php
require_once 'config.php';
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title ?? 'Luxury Stay & Dining'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold fs-3" href="index.php">
                <i class="bi bi-building text-primary me-2"></i>
                <span class="text-dark">Eden Terrace</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-door me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rooms.php"><i class="bi bi-bed me-1"></i> Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="restaurant.php"><i class="bi bi-egg-fried me-1"></i> Restaurant</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php"><i class="bi bi-menu-button me-1"></i> Menu</a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="bookDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar-plus me-1"></i> Book
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="book-room.php"><i class="bi bi-bed me-2"></i> Book Room</a></li>
                            <li><a class="dropdown-item" href="book-table.php"><i class="bi bi-table me-2"></i> Book Table</a></li>
                            <li><a class="dropdown-item" href="order-food.php"><i class="bi bi-bell me-2"></i> Order Food</a></li>
                        </ul>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="bi bi-person-circle me-1"></i> My Account</a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/index.php"><i class="bi bi-gear me-1"></i> Admin</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a href="register.php" class="btn btn-primary btn-sm">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>