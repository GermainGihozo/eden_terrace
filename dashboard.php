<?php
$page_title = "My Dashboard";
require_once 'includes/header.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Require login to access dashboard
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$user = getCurrentUser();

// Get user's bookings
try {
    // Get room bookings
    $stmt = $db->prepare("
        SELECT b.*, r.name as room_name, r.image_url as room_image
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.id
        WHERE b.user_id = ? 
        AND b.booking_type = 'room'
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $room_bookings = $stmt->fetchAll();
    
    // Get restaurant bookings
    $stmt = $db->prepare("
        SELECT b.*, t.table_number
        FROM bookings b
        LEFT JOIN tables t ON b.table_id = t.id
        WHERE b.user_id = ? 
        AND b.booking_type = 'restaurant'
        ORDER BY b.reservation_time DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $restaurant_bookings = $stmt->fetchAll();
    
    // Get total stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(total_amount) as total_spent
        FROM bookings 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!-- Dashboard Header -->
<section class="bg-primary text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h2 mb-0">Welcome back, <?php echo htmlspecialchars($user['name'] ?? 'Guest'); ?>!</h1>
                <p class="mb-0">Manage your bookings and account</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="book-room.php" class="btn btn-light">
                    <i class="bi bi-plus-circle me-2"></i>New Booking
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Dashboard Content -->
<section class="py-5">
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row mb-5">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">
                            <i class="bi bi-calendar-check display-6"></i>
                        </div>
                        <h3 class="card-title"><?php echo $stats['total_bookings'] ?? 0; ?></h3>
                        <p class="card-text text-muted">Total Bookings</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-warning mb-2">
                            <i class="bi bi-clock-history display-6"></i>
                        </div>
                        <h3 class="card-title"><?php echo $stats['pending_bookings'] ?? 0; ?></h3>
                        <p class="card-text text-muted">Pending</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-success mb-2">
                            <i class="bi bi-check-circle display-6"></i>
                        </div>
                        <h3 class="card-title"><?php echo $stats['confirmed_bookings'] ?? 0; ?></h3>
                        <p class="card-text text-muted">Confirmed</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-info mb-2">
                            <i class="bi bi-cash-coin display-6"></i>
                        </div>
                        <h3 class="card-title">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></h3>
                        <p class="card-text text-muted">Total Spent</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Room Bookings -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-bed text-primary me-2"></i>Recent Room Bookings
                            </h5>
                            <a href="my-bookings.php?type=room" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($room_bookings)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <p class="mt-3">No room bookings yet</p>
                                <a href="book-room.php" class="btn btn-primary">Book Your First Stay</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Room</th>
                                            <th>Dates</th>
                                            <th>Guests</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($room_bookings as $booking): 
                                            $nights = date_diff(
                                                new DateTime($booking['check_in']), 
                                                new DateTime($booking['check_out'])
                                            )->days;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo date('M d', strtotime($booking['check_in'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                                                <br><small class="text-muted"><?php echo $nights; ?> nights</small>
                                            </td>
                                            <td><?php echo $booking['num_guests']; ?> guests</td>
                                            <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'completed' => 'info',
                                                    'paid' => 'primary'
                                                ][$booking['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="booking-details.php?id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-danger cancel-booking" 
                                                            data-id="<?php echo $booking['id']; ?>">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Restaurant Bookings -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-egg-fried text-primary me-2"></i>Recent Restaurant Reservations
                            </h5>
                            <a href="my-bookings.php?type=restaurant" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($restaurant_bookings)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <p class="mt-3">No restaurant reservations yet</p>
                                <a href="book-table.php" class="btn btn-primary">Make a Reservation</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Table</th>
                                            <th>Date & Time</th>
                                            <th>Party Size</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($restaurant_bookings as $booking): ?>
                                        <tr>
                                            <td>Table <?php echo $booking['table_number']; ?></td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($booking['reservation_time'])); ?>
                                                <br><small class="text-muted">
                                                    <?php echo date('h:i A', strtotime($booking['reservation_time'])); ?>
                                                </small>
                                            </td>
                                            <td><?php echo $booking['party_size']; ?> people</td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'completed' => 'info'
                                                ][$booking['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="booking-details.php?id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-danger cancel-booking" 
                                                            data-id="<?php echo $booking['id']; ?>">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- User Profile Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-person-circle text-primary me-2"></i>My Profile
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="avatar-circle bg-primary text-white mx-auto mb-3">
                                <i class="bi bi-person fs-1"></i>
                            </div>
                            <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                        
                        <div class="list-group list-group-flush">
                            <a href="edit-profile.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-pencil me-2"></i>Edit Profile
                            </a>
                            <a href="change-password.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-key me-2"></i>Change Password
                            </a>
                            <?php if (isAdmin()): ?>
                                <a href="admin/" class="list-group-item list-group-item-action">
                                    <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning text-primary me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="book-room.php" class="btn btn-primary">
                                <i class="bi bi-bed me-2"></i>Book a Room
                            </a>
                            <a href="book-table.php" class="btn btn-outline-primary">
                                <i class="bi bi-egg-fried me-2"></i>Reserve a Table
                            </a>
                            <a href="order-food.php" class="btn btn-outline-primary">
                                <i class="bi bi-bell me-2"></i>Order Food
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Stays -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-date text-primary me-2"></i>Upcoming Stays
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stmt = $db->prepare("
                                SELECT b.check_in, b.check_out, r.name as room_name
                                FROM bookings b
                                LEFT JOIN rooms r ON b.room_id = r.id
                                WHERE b.user_id = ? 
                                AND b.booking_type = 'room'
                                AND b.status IN ('confirmed', 'pending')
                                AND b.check_in >= CURDATE()
                                ORDER BY b.check_in ASC
                                LIMIT 3
                            ");
                            $stmt->execute([$user_id]);
                            $upcoming_stays = $stmt->fetchAll();
                            
                            if (empty($upcoming_stays)) {
                                echo '<p class="text-muted text-center mb-0">No upcoming stays</p>';
                            } else {
                                foreach ($upcoming_stays as $stay) {
                                    echo '<div class="mb-3 pb-3 border-bottom">';
                                    echo '<h6 class="mb-1">' . htmlspecialchars($stay['room_name']) . '</h6>';
                                    echo '<small class="text-muted">';
                                    echo date('M d', strtotime($stay['check_in'])) . ' - ' . date('M d, Y', strtotime($stay['check_out']));
                                    echo '</small>';
                                    echo '</div>';
                                }
                            }
                        } catch (PDOException $e) {
                            echo '<p class="text-muted">Unable to load upcoming stays</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cancel Booking Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this booking?</p>
                <p class="text-muted small">Cancellations made within 48 hours may be subject to fees.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">Yes, Cancel Booking</button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cancel booking functionality
    const cancelButtons = document.querySelectorAll('.cancel-booking');
    const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
    let bookingIdToCancel = null;
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            bookingIdToCancel = this.getAttribute('data-id');
            cancelModal.show();
        });
    });
    
    // Confirm cancellation
    document.getElementById('confirmCancel').addEventListener('click', function() {
        if (bookingIdToCancel) {
            fetch('api/cancel-booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_id: bookingIdToCancel
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to cancel booking: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }
        cancelModal.hide();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>