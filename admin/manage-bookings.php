<?php
// admin/manage-bookings.php
$page_title = "Manage Bookings";
require_once 'includes/admin-header.php';

$db = getDB();
$error = '';
$success = '';
$bookings = [];

// Handle actions
$action = $_GET['action'] ?? '';
$booking_id = $_GET['id'] ?? '';

// Handle confirmation/cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = $_POST['booking_id'] ?? '';
    
    if ($action === 'confirm' && $booking_id) {
        try {
            $stmt = $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$booking_id]);
            $success = "Booking #" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . " has been confirmed.";
        } catch (PDOException $e) {
            $error = "Error confirming booking: " . $e->getMessage();
        }
    } elseif ($action === 'cancel' && $booking_id) {
        try {
            $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$booking_id]);
            $success = "Booking #" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . " has been cancelled.";
        } catch (PDOException $e) {
            $error = "Error cancelling booking: " . $e->getMessage();
        }
    } elseif ($action === 'delete' && $booking_id) {
        try {
            $stmt = $db->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $success = "Booking #" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . " has been deleted.";
        } catch (PDOException $e) {
            $error = "Error deleting booking: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$booking_type = $_GET['type'] ?? '';

// Build query based on filters
$query = "SELECT b.*, 
                 u.full_name as user_name, 
                 u.email as user_email,
                 r.name as room_name,
                 t.table_number
          FROM bookings b
          LEFT JOIN users u ON b.user_id = u.id
          LEFT JOIN rooms r ON b.room_id = r.id
          LEFT JOIN tables t ON b.table_id = t.id
          WHERE 1=1";

$params = [];

// Apply filters
if ($filter === 'pending') {
    $query .= " AND b.status = 'pending'";
} elseif ($filter === 'confirmed') {
    $query .= " AND b.status = 'confirmed'";
} elseif ($filter === 'cancelled') {
    $query .= " AND b.status = 'cancelled'";
} elseif ($filter === 'today') {
    $query .= " AND DATE(b.created_at) = CURDATE()";
} elseif ($filter === 'upcoming') {
    $query .= " AND ((b.booking_type = 'room' AND b.check_in >= CURDATE()) OR 
                     (b.booking_type = 'restaurant' AND b.reservation_time >= NOW()))";
}

if ($booking_type) {
    $query .= " AND b.booking_type = ?";
    $params[] = $booking_type;
}

if ($date_from) {
    $query .= " AND DATE(b.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(b.created_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $query .= " AND (b.guest_name LIKE ? OR b.guest_email LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY b.created_at DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    // Get counts for filter badges
    $counts = [
        'all' => 0,
        'pending' => 0,
        'confirmed' => 0,
        'cancelled' => 0,
        'today' => 0,
        'upcoming' => 0
    ];
    
    $count_stmt = $db->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
    $status_counts = $count_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $counts['pending'] = $status_counts['pending'] ?? 0;
    $counts['confirmed'] = $status_counts['confirmed'] ?? 0;
    $counts['cancelled'] = $status_counts['cancelled'] ?? 0;
    
    $today_stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()");
    $counts['today'] = $today_stmt->fetchColumn();
    
    $upcoming_stmt = $db->query("
        SELECT COUNT(*) FROM bookings 
        WHERE ((booking_type = 'room' AND check_in >= CURDATE()) OR 
               (booking_type = 'restaurant' AND reservation_time >= NOW()))
    ");
    $counts['upcoming'] = $upcoming_stmt->fetchColumn();
    
    $all_stmt = $db->query("SELECT COUNT(*) FROM bookings");
    $counts['all'] = $all_stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = "Error loading bookings: " . $e->getMessage();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-2">Manage Bookings</h1>
            <p class="text-muted mb-0">View, confirm, cancel, and manage all hotel bookings and restaurant reservations.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Create Booking
            </a>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <!-- Quick Filters -->
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-2">
                    <a href="?filter=all" class="btn btn-sm btn-outline-primary <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        All Bookings <span class="badge bg-secondary ms-1"><?php echo $counts['all']; ?></span>
                    </a>
                    <a href="?filter=pending" class="btn btn-sm btn-outline-warning <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                        Pending <span class="badge bg-warning ms-1"><?php echo $counts['pending']; ?></span>
                    </a>
                    <a href="?filter=confirmed" class="btn btn-sm btn-outline-success <?php echo $filter === 'confirmed' ? 'active' : ''; ?>">
                        Confirmed <span class="badge bg-success ms-1"><?php echo $counts['confirmed']; ?></span>
                    </a>
                    <a href="?filter=cancelled" class="btn btn-sm btn-outline-danger <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">
                        Cancelled <span class="badge bg-danger ms-1"><?php echo $counts['cancelled']; ?></span>
                    </a>
                    <a href="?filter=today" class="btn btn-sm btn-outline-info <?php echo $filter === 'today' ? 'active' : ''; ?>">
                        Today <span class="badge bg-info ms-1"><?php echo $counts['today']; ?></span>
                    </a>
                    <a href="?filter=upcoming" class="btn btn-sm btn-outline-secondary <?php echo $filter === 'upcoming' ? 'active' : ''; ?>">
                        Upcoming <span class="badge bg-secondary ms-1"><?php echo $counts['upcoming']; ?></span>
                    </a>
                </div>
            </div>
            
            <!-- Type Filter -->
            <div class="col-md-4">
                <select class="form-select form-select-sm" id="typeFilter" onchange="window.location.href = this.value">
                    <option value="?<?php echo http_build_query(array_merge($_GET, ['type' => ''])); ?>">All Types</option>
                    <option value="?<?php echo http_build_query(array_merge($_GET, ['type' => 'room'])); ?>" <?php echo $booking_type === 'room' ? 'selected' : ''; ?>>Room Bookings</option>
                    <option value="?<?php echo http_build_query(array_merge($_GET, ['type' => 'restaurant'])); ?>" <?php echo $booking_type === 'restaurant' ? 'selected' : ''; ?>>Restaurant Reservations</option>
                    <option value="?<?php echo http_build_query(array_merge($_GET, ['type' => 'food_order'])); ?>" <?php echo $booking_type === 'food_order' ? 'selected' : ''; ?>>Food Orders</option>
                </select>
            </div>
        </div>
        
        <!-- Advanced Filters -->
        <div class="row g-3 mt-3">
            <div class="col-md-8">
                <form method="GET" class="row g-2">
                    <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                    <input type="hidden" name="type" value="<?php echo $booking_type; ?>">
                    
                    <div class="col-md-4">
                        <input type="date" class="form-control form-control-sm" name="date_from" 
                               value="<?php echo $date_from; ?>" placeholder="From Date">
                    </div>
                    <div class="col-md-4">
                        <input type="date" class="form-control form-control-sm" name="date_to" 
                               value="<?php echo $date_to; ?>" placeholder="To Date">
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by name or email">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if ($search || $date_from || $date_to): ?>
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Export & Actions -->
            <div class="col-md-4 text-end">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i>Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-file-earmark-excel me-2"></i>Excel</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-file-earmark-pdf me-2"></i>PDF</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-printer me-2"></i>Print</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bookings Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($bookings)): ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x display-4 text-muted"></i>
            <h4 class="mt-3">No Bookings Found</h4>
            <p class="text-muted">
                <?php if ($filter !== 'all'): ?>
                No <?php echo $filter; ?> bookings match your criteria.
                <?php else: ?>
                No bookings have been made yet.
                <?php endif; ?>
            </p>
            <a href="?" class="btn btn-primary">Clear Filters</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 data-table">
                <thead class="table-light">
                    <tr>
                        <th>Booking ID</th>
                        <th>Guest</th>
                        <th>Type</th>
                        <th>Details</th>
                        <th>Dates/Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): 
                        $status_colors = [
                            'pending' => 'warning',
                            'confirmed' => 'success',
                            'cancelled' => 'danger',
                            'completed' => 'info',
                            'paid' => 'primary'
                        ];
                        $color = $status_colors[$booking['status']] ?? 'secondary';
                        
                        // Format dates based on booking type
                        if ($booking['booking_type'] === 'room') {
                            $date_info = '
                                <div><strong>Check-in:</strong> ' . date('M d, Y', strtotime($booking['check_in'])) . '</div>
                                <div><strong>Check-out:</strong> ' . date('M d, Y', strtotime($booking['check_out'])) . '</div>
                                <small class="text-muted">' . $booking['num_guests'] . ' guest(s)</small>
                            ';
                            $details = $booking['room_name'] ?? 'Room Booking';
                        } elseif ($booking['booking_type'] === 'restaurant') {
                            $date_info = '
                                <div><strong>Date:</strong> ' . date('M d, Y', strtotime($booking['reservation_time'])) . '</div>
                                <div><strong>Time:</strong> ' . date('h:i A', strtotime($booking['reservation_time'])) . '</div>
                                <small class="text-muted">Table ' . ($booking['table_number'] ?? 'N/A') . '</small>
                            ';
                            $details = 'Table ' . ($booking['table_number'] ?? 'N/A') . ' • ' . $booking['party_size'] . ' people';
                        } else {
                            $date_info = '<div><strong>Order Time:</strong> ' . date('M d, Y h:i A', strtotime($booking['created_at'])) . '</div>';
                            $details = 'Food Order';
                        }
                    ?>
                    <tr>
                        <td>
                            <strong>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                            <br><small class="text-muted"><?php echo strtoupper($booking['booking_type']); ?></small>
                        </td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($booking['user_name'] ?? $booking['guest_name'] ?? 'Guest'); ?></strong>
                                <small class="d-block text-muted">
                                    <?php echo htmlspecialchars($booking['user_email'] ?? $booking['guest_email'] ?? 'No email'); ?>
                                </small>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($booking['guest_phone'] ?? ''); ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $booking['booking_type'] == 'room' ? 'primary' : ($booking['booking_type'] == 'restaurant' ? 'success' : 'warning'); ?>">
                                <?php echo ucfirst($booking['booking_type']); ?>
                            </span>
                        </td>
                        <td><?php echo $details; ?></td>
                        <td><?php echo $date_info; ?></td>
                        <td>
                            <strong>$<?php echo number_format($booking['total_amount'], 2); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                            <br><small class="text-muted"><?php echo date('h:i A', strtotime($booking['created_at'])); ?></small>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="?action=view&id=<?php echo $booking['id']; ?>">
                                            <i class="bi bi-eye me-2"></i>View Details
                                        </a>
                                    </li>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                    <li>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="confirm">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="dropdown-item" onclick="return confirm('Confirm this booking?')">
                                                <i class="bi bi-check-circle text-success me-2"></i>Confirm
                                            </button>
                                        </form>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] !== 'cancelled'): ?>
                                    <li>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="dropdown-item" onclick="return confirm('Cancel this booking?')">
                                                <i class="bi bi-x-circle text-danger me-2"></i>Cancel
                                            </button>
                                        </form>
                                    </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this booking permanently?')">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if (!empty($bookings)): ?>
    <div class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">
                    Showing <?php echo count($bookings); ?> of <?php echo $counts[$filter] ?? count($bookings); ?> bookings
                </small>
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Statistics Summary -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Bookings</h6>
                        <h3 class="mb-0"><?php echo $counts['all']; ?></h3>
                    </div>
                    <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Pending</h6>
                        <h3 class="mb-0"><?php echo $counts['pending']; ?></h3>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Confirmed</h6>
                        <h3 class="mb-0"><?php echo $counts['confirmed']; ?></h3>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Cancelled</h6>
                        <h3 class="mb-0"><?php echo $counts['cancelled']; ?></h3>
                    </div>
                    <i class="bi bi-x-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    $('.data-table').DataTable({
        "pageLength": 25,
        "order": [[7, "desc"]],
        "language": {
            "search": "Search within table:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });
    
    // Date range validation
    const dateFrom = document.querySelector('input[name="date_from"]');
    const dateTo = document.querySelector('input[name="date_to"]');
    
    if (dateFrom && dateTo) {
        dateFrom.addEventListener('change', function() {
            dateTo.min = this.value;
        });
        
        dateTo.addEventListener('change', function() {
            dateFrom.max = this.value;
        });
    }
    
    // Auto-submit date filters when both are selected
    if (dateFrom && dateTo) {
        dateTo.addEventListener('change', function() {
            if (dateFrom.value && this.value) {
                this.form.submit();
            }
        });
    }
    
    // Export functionality
    document.querySelectorAll('.export-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const format = this.getAttribute('data-format');
            alert('Exporting to ' + format + ' format...');
            // In production, this would trigger a server-side export
        });
    });
});
</script>

<?php
require_once 'includes/admin-footer.php';
?>