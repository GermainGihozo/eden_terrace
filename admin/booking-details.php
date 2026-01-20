<?php
// admin/booking-details.php
$page_title = "Booking Details";
require_once 'includes/admin-header.php';

$db = getDB();
$error = '';
$booking = null;

$booking_id = $_GET['id'] ?? '';

if (!$booking_id) {
    header('Location: manage-bookings.php');
    exit();
}

try {
    $stmt = $db->prepare("
        SELECT b.*, 
               u.full_name as user_name, 
               u.email as user_email,
               u.phone as user_phone,
               r.name as room_name,
               r.price_per_night as room_price,
               r.capacity as room_capacity,
               t.table_number,
               t.capacity as table_capacity
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN tables t ON b.table_id = t.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $error = "Booking not found.";
    }
} catch (PDOException $e) {
    $error = "Error loading booking details: " . $e->getMessage();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? '';
    $notes = $_POST['admin_notes'] ?? '';
    
    if ($new_status && in_array($new_status, ['pending', 'confirmed', 'cancelled', 'completed', 'paid'])) {
        try {
            $stmt = $db->prepare("
                UPDATE bookings 
                SET status = ?, admin_notes = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $notes, $booking_id]);
            
            // Refresh booking data
            $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();
            
            $success = "Booking status updated to " . ucfirst($new_status) . ".";
        } catch (PDOException $e) {
            $error = "Error updating status: " . $e->getMessage();
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-2">Booking Details</h1>
            <p class="text-muted mb-0">
                Booking #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?>
                <?php if ($booking): ?>
                - <?php echo htmlspecialchars($booking['guest_name'] ?? $booking['user_name'] ?? 'Guest'); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="manage-bookings.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Bookings
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

<?php if (isset($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!$booking): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-calendar-x display-4 text-muted"></i>
        <h4 class="mt-3">Booking Not Found</h4>
        <p class="text-muted">The booking you're looking for doesn't exist or has been deleted.</p>
        <a href="manage-bookings.php" class="btn btn-primary">Back to Bookings</a>
    </div>
</div>
<?php else: ?>
<div class="row g-4">
    <!-- Left Column: Booking Information -->
    <div class="col-lg-8">
        <!-- Booking Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Booking Information</h6>
                <?php
                $status_colors = [
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'cancelled' => 'danger',
                    'completed' => 'info',
                    'paid' => 'primary'
                ];
                $color = $status_colors[$booking['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($booking['status']); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Booking ID:</th>
                                <td>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td>
                                    <span class="badge bg-<?php echo $booking['booking_type'] == 'room' ? 'primary' : ($booking['booking_type'] == 'restaurant' ? 'success' : 'warning'); ?>">
                                        <?php echo ucfirst($booking['booking_type']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Guest Name:</th>
                                <td><?php echo htmlspecialchars($booking['guest_name'] ?? $booking['user_name'] ?? 'Guest'); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($booking['guest_email'] ?? $booking['user_email'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($booking['guest_phone'] ?? $booking['user_phone'] ?? 'N/A'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Created:</th>
                                <td><?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?php echo $booking['updated_at'] ? date('M d, Y h:i A', strtotime($booking['updated_at'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Total Amount:</th>
                                <td><strong>$<?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Reference:</th>
                                <td>
                                    <?php
                                    $prefix = '';
                                    if ($booking['booking_type'] == 'room') $prefix = 'ET-';
                                    elseif ($booking['booking_type'] == 'restaurant') $prefix = 'RT-';
                                    elseif ($booking['booking_type'] == 'food_order') $prefix = 'FO-';
                                    echo $prefix . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Booking Type Specific Details -->
                <div class="mt-4">
                    <h6 class="mb-3">Booking Details</h6>
                    <?php if ($booking['booking_type'] === 'room'): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">Room Information</h6>
                                    <p class="mb-1"><strong>Room:</strong> <?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?></p>
                                    <p class="mb-1"><strong>Price per night:</strong> $<?php echo number_format($booking['room_price'] ?? 0, 2); ?></p>
                                    <p class="mb-1"><strong>Capacity:</strong> <?php echo $booking['room_capacity'] ?? 0; ?> guests</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">Stay Information</h6>
                                    <p class="mb-1"><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
                                    <p class="mb-1"><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
                                    <p class="mb-1"><strong>Nights:</strong> 
                                        <?php
                                        $nights = 0;
                                        if ($booking['check_in'] && $booking['check_out']) {
                                            $nights = (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / (60 * 60 * 24);
                                        }
                                        echo $nights;
                                        ?>
                                    </p>
                                    <p class="mb-1"><strong>Guests:</strong> <?php echo $booking['num_guests'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($booking['booking_type'] === 'restaurant'): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">Reservation Details</h6>
                                    <p class="mb-1"><strong>Table:</strong> <?php echo $booking['table_number'] ?? 'N/A'; ?></p>
                                    <p class="mb-1"><strong>Table Capacity:</strong> <?php echo $booking['table_capacity'] ?? 0; ?> people</p>
                                    <p class="mb-1"><strong>Party Size:</strong> <?php echo $booking['party_size'] ?? 0; ?> people</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">Date & Time</h6>
                                    <p class="mb-1"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['reservation_time'])); ?></p>
                                    <p class="mb-1"><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['reservation_time'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($booking['booking_type'] === 'food_order'): ?>
                    <div class="card border">
                        <div class="card-body">
                            <h6 class="card-title">Order Details</h6>
                            <?php 
                            $menu_items = json_decode($booking['menu_items'] ?? '[]', true);
                            if ($menu_items && is_array($menu_items)):
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($menu_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name'] ?? 'Item'); ?></td>
                                            <td><?php echo $item['quantity'] ?? 0; ?></td>
                                            <td>$<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                                            <td>$<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Food Total:</strong></td>
                                            <td>$<?php echo number_format($booking['food_total'] ?? 0, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Tax (8%):</strong></td>
                                            <td>$<?php echo number_format(($booking['food_total'] ?? 0) * 0.08, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><strong>$<?php echo number_format($booking['total_amount'] ?? 0, 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted mb-0">No menu items found for this order.</p>
                            <?php endif; ?>
                            
                            <?php if ($booking['delivery_room']): ?>
                            <p class="mb-0 mt-3"><strong>Delivery to:</strong> Room <?php echo $booking['delivery_room']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Special Requests -->
                <?php if (!empty($booking['special_requests'])): ?>
                <div class="mt-4">
                    <h6 class="mb-2">Special Requests</h6>
                    <div class="card border">
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Admin Notes -->
                <div class="mt-4">
                    <h6 class="mb-2">Admin Notes</h6>
                    <form method="POST">
                        <textarea class="form-control" name="admin_notes" rows="3" 
                                  placeholder="Add notes about this booking..."><?php echo htmlspecialchars($booking['admin_notes'] ?? ''); ?></textarea>
                        <div class="mt-3">
                            <button type="submit" name="update_notes" class="btn btn-outline-primary btn-sm">
                                Save Notes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Actions & History -->
    <div class="col-lg-4">
        <!-- Status Update -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <select class="form-select" name="status">
                            <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="paid" <?php echo $booking['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="update_status" class="btn btn-primary">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($booking['status'] === 'pending'): ?>
                    <form method="POST" class="d-grid">
                        <input type="hidden" name="action" value="confirm">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Confirm this booking?')">
                            <i class="bi bi-check-circle me-2"></i>Confirm Booking
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] !== 'cancelled'): ?>
                    <form method="POST" class="d-grid">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel this booking?')">
                            <i class="bi bi-x-circle me-2"></i>Cancel Booking
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <a href="javascript:window.print()" class="btn btn-outline-secondary">
                        <i class="bi bi-printer me-2"></i>Print Details
                    </a>
                    
                    <form method="POST" class="d-grid">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this booking permanently? This action cannot be undone.')">
                            <i class="bi bi-trash me-2"></i>Delete Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Booking Timeline -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Booking Timeline</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Booking Created</h6>
                            <p class="text-muted mb-0"><?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($booking['updated_at'] && $booking['updated_at'] != $booking['created_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Last Updated</h6>
                            <p class="text-muted mb-0"><?php echo date('M d, Y h:i A', strtotime($booking['updated_at'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($booking['booking_type'] === 'room'): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Scheduled Check-in</h6>
                            <p class="text-muted mb-0"><?php echo date('M d, Y', strtotime($booking['check_in'])); ?> at 3:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Scheduled Check-out</h6>
                            <p class="text-muted mb-0"><?php echo date('M d, Y', strtotime($booking['check_out'])); ?> at 11:00 AM</p>
                        </div>
                    </div>
                    <?php elseif ($booking['booking_type'] === 'restaurant'): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Reservation Time</h6>
                            <p class="text-muted mb-0"><?php echo date('M d, Y h:i A', strtotime($booking['reservation_time'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    padding-left: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Print functionality
    document.querySelector('.print-btn').addEventListener('click', function() {
        window.print();
    });
    
    // Status update confirmation
    document.querySelector('button[name="update_status"]').addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to update the booking status?')) {
            e.preventDefault();
        }
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
            }
        });
    });
});
</script>
<?php endif; ?>

<?php
require_once 'includes/admin-footer.php';
?>