<?php
// admin/dashboard.php
$page_title = "Admin Dashboard";
require_once 'includes/admin-header.php';

// Get database statistics with error handling
$user = $_SESSION['user_name'] ?? 'Admin';
$stats = [];
$recent_bookings = [];

try {
    $db = getDB();
    
    // Get real statistics
    // Total bookings
    $stmt = $db->query("SELECT COUNT(*) as total FROM bookings");
    $stats['total_bookings'] = $stmt->fetchColumn() ?: 0;
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetchColumn() ?: 0;
    
    // Total rooms
    $stmt = $db->query("SELECT COUNT(*) as total FROM rooms");
    $stats['total_rooms'] = $stmt->fetchColumn() ?: 0;
    
    // Today's bookings
    $stmt = $db->query("SELECT COUNT(*) as total FROM bookings WHERE DATE(created_at) = CURDATE()");
    $stats['today_bookings'] = $stmt->fetchColumn() ?: 0;
    
    // Pending bookings
    $stmt = $db->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
    $stats['pending_bookings'] = $stmt->fetchColumn() ?: 0;
    
    // Total revenue (confirmed bookings only)
    $stmt = $db->query("SELECT SUM(total_amount) as total FROM bookings WHERE status = 'confirmed'");
    $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // Today's revenue
    $stmt = $db->query("SELECT SUM(total_amount) as total FROM bookings WHERE DATE(created_at) = CURDATE() AND status = 'confirmed'");
    $stats['today_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // Occupied rooms
    $stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE is_available = FALSE");
    $stats['occupied_rooms'] = $stmt->fetchColumn() ?: 0;
    
    // Get recent bookings (last 5)
    $stmt = $db->query("
        SELECT b.*, 
               u.full_name as customer_name,
               u.email as customer_email
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll();
    
    // Get booking status distribution
    $stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM bookings 
        GROUP BY status
        ORDER BY count DESC
    ");
    $booking_status = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Unable to load dashboard data: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-2">Admin Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <strong><?php echo htmlspecialchars($user); ?></strong>. You have full administrative access.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="text-muted"><?php echo date('l, F j, Y'); ?></span>
            <div class="mt-1">
                <span class="badge bg-success">
                    <i class="bi bi-circle-fill me-1"></i>System Online
                </span>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Real Statistics Cards -->
<div class="row g-4 mb-4">
    <!-- Total Bookings -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Bookings</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_bookings']); ?></h3>
                        <small class="text-muted">
                            <i class="bi bi-calendar-check me-1"></i>
                            <?php echo number_format($stats['today_bookings']); ?> today
                        </small>
                    </div>
                    <div class="stat-icon text-primary">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo min(($stats['today_bookings']/max($stats['total_bookings'],1))*100, 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Revenue -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Revenue</h6>
                        <h3 class="mb-0">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <small class="text-muted">
                            <i class="bi bi-cash-coin me-1"></i>
                            $<?php echo number_format($stats['today_revenue'], 2); ?> today
                        </small>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" style="width: <?php echo min(($stats['today_revenue']/max($stats['total_revenue'],1))*100, 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Users -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Registered Users</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_users']); ?></h3>
                        <small class="text-muted">
                            <i class="bi bi-person me-1"></i>
                            <?php echo isset($_SESSION['user_id']) ? 'You' : '0'; ?> online
                        </small>
                    </div>
                    <div class="stat-icon text-warning">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" style="width: <?php echo min((1/max($stats['total_users'],1))*100, 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Room Occupancy -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Room Occupancy</h6>
                        <h3 class="mb-0"><?php echo $stats['occupied_rooms']; ?>/<?php echo $stats['total_rooms']; ?></h3>
                        <small class="text-muted">
                            <i class="bi bi-door-closed me-1"></i>
                            <?php echo $stats['total_rooms'] > 0 ? round(($stats['occupied_rooms']/$stats['total_rooms'])*100) : 0; ?>% occupied
                        </small>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="bi bi-house-door"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-info" style="width: <?php echo $stats['total_rooms'] > 0 ? ($stats['occupied_rooms']/$stats['total_rooms'])*100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Two Column Layout -->
<div class="row g-4">
    <!-- Left Column: Recent Activity -->
    <div class="col-xl-8">
        <!-- Recent Bookings -->
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>Recent Bookings
                </h6>
                <a href="manage-bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_bookings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-4 text-muted"></i>
                    <p class="mt-3">No bookings yet</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): 
                                $status_colors = [
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    'cancelled' => 'danger',
                                    'completed' => 'info',
                                    'paid' => 'primary'
                                ];
                                $color = $status_colors[$booking['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($booking['customer_name'] ?? $booking['guest_name'] ?? 'Guest'); ?></strong>
                                        <small class="d-block text-muted">
                                            <?php echo htmlspecialchars($booking['customer_email'] ?? $booking['guest_email'] ?? 'No email'); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $booking['booking_type'] == 'room' ? 'primary' : 'success'; ?>">
                                        <?php echo ucfirst($booking['booking_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                                    <small class="d-block text-muted"><?php echo date('h:i A', strtotime($booking['created_at'])); ?></small>
                                </td>
                                <td>
                                    <strong>$<?php echo number_format($booking['total_amount'] ?? 0, 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $color; ?> status-badge">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="manage-bookings.php?action=create" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center py-3">
                            <i class="bi bi-plus-circle fs-3 mb-2"></i>
                            <span>New Booking</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="manage-rooms.php?action=add" class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3">
                            <i class="bi bi-door-closed fs-3 mb-2"></i>
                            <span>Add Room</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="manage-menu.php?action=add" class="btn btn-outline-warning w-100 d-flex flex-column align-items-center py-3">
                            <i class="bi bi-plus-circle fs-3 mb-2"></i>
                            <span>Add Menu Item</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Stats & Info -->
    <div class="col-xl-4">
        <!-- Booking Status Chart -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-pie-chart me-2"></i>Booking Status
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($booking_status)): ?>
                <canvas id="bookingStatusChart" height="200"></canvas>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-graph-up text-muted display-5"></i>
                    <p class="mt-2 text-muted">No booking data available</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pending Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-clock me-2"></i>Pending Actions
                </h6>
                <span class="badge bg-danger"><?php echo $stats['pending_bookings']; ?></span>
            </div>
            <div class="card-body">
                <?php if ($stats['pending_bookings'] > 0): ?>
                <div class="alert alert-warning">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading">Attention Required</h6>
                            <p class="mb-0">You have <strong><?php echo $stats['pending_bookings']; ?></strong> bookings awaiting confirmation.</p>
                            <a href="manage-bookings.php?filter=pending" class="btn btn-sm btn-warning mt-2">Review Now</a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="bi bi-check-circle text-success display-5"></i>
                    <p class="mt-2">All caught up!</p>
                    <small class="text-muted">No pending actions at this time.</small>
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <h6 class="mb-3">System Overview</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Database:</span>
                        <span class="badge bg-success">Connected</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Server Time:</span>
                        <span><?php echo date('H:i:s'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>PHP Version:</span>
                        <span><?php echo phpversion(); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Admin Resources -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>Admin Resources
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action border-0">
                        <i class="bi bi-file-text me-2 text-primary"></i>
                        User Manual
                    </a>
                    <a href="#" class="list-group-item list-group-item-action border-0">
                        <i class="bi bi-question-circle me-2 text-success"></i>
                        Help & Support
                    </a>
                    <a href="#" class="list-group-item list-group-item-action border-0">
                        <i class="bi bi-gear me-2 text-warning"></i>
                        System Settings
                    </a>
                    <a href="../../logout.php" class="list-group-item list-group-item-action border-0 text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Info Footer -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-database me-1"></i>
                            Bookings: <?php echo $stats['total_bookings']; ?>
                        </small>
                    </div>
                    <div class="col-md-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-currency-dollar me-1"></i>
                            Revenue: $<?php echo number_format($stats['total_revenue'], 2); ?>
                        </small>
                    </div>
                    <div class="col-md-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-people me-1"></i>
                            Users: <?php echo $stats['total_users']; ?>
                        </small>
                    </div>
                    <div class="col-md-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-house-door me-1"></i>
                            Rooms: <?php echo $stats['total_rooms']; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    document.getElementById('sidebarCollapse').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Booking Status Chart
    <?php if (!empty($booking_status)): ?>
    const statusCtx = document.getElementById('bookingStatusChart').getContext('2d');
    const statusData = {
        labels: <?php echo json_encode(array_column($booking_status, 'status')); ?>.map(s => 
            s.charAt(0).toUpperCase() + s.slice(1)
        ),
        datasets: [{
            data: <?php echo json_encode(array_column($booking_status, 'count')); ?>,
            backgroundColor: [
                'rgba(241, 196, 15, 0.8)',   // Pending - Yellow
                'rgba(46, 204, 113, 0.8)',   // Confirmed - Green
                'rgba(231, 76, 60, 0.8)',    // Cancelled - Red
                'rgba(52, 152, 219, 0.8)',   // Completed - Blue
                'rgba(155, 89, 182, 0.8)'    // Paid - Purple
            ],
            borderColor: [
                'rgb(241, 196, 15)',
                'rgb(46, 204, 113)',
                'rgb(231, 76, 60)',
                'rgb(52, 152, 219)',
                'rgb(155, 89, 182)'
            ],
            borderWidth: 2
        }]
    };
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: statusData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true
                    }
                }
            },
            cutout: '65%'
        }
    });
    <?php endif; ?>
    
    // Auto-refresh dashboard every 60 seconds
    setTimeout(function() {
        window.location.reload();
    }, 60000);
    
    // Add hover effects to stat cards
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.stat-card {
    transition: all 0.3s ease;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
}

.progress-bar {
    border-radius: 10px;
}

.table td {
    vertical-align: middle;
}

.list-group-item {
    padding: 12px 0;
    border: none;
}

.list-group-item:hover {
    background-color: #f8f9fa;
    padding-left: 10px;
    transition: all 0.2s;
}

.alert {
    border: none;
    border-left: 4px solid;
}
</style>

<?php
require_once 'includes/admin-footer.php';
?>