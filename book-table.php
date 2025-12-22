<?php
$page_title = "Book a Table";
require_once 'includes/header.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

$db = getDB();
$error = '';
$success = '';

// Get available tables
try {
    $stmt = $db->query("
        SELECT t.*, 
               COUNT(b.id) as booked_count
        FROM tables t
        LEFT JOIN bookings b ON t.id = b.table_id 
            AND b.booking_type = 'restaurant'
            AND b.status NOT IN ('cancelled', 'completed')
            AND b.reservation_time >= NOW()
            AND b.reservation_time < DATE_ADD(NOW(), INTERVAL 2 HOUR)
        WHERE t.is_available = TRUE
        GROUP BY t.id
        ORDER BY t.capacity
    ");
    $tables = $stmt->fetchAll();
} catch (PDOException $e) {
    $tables = [];
    $error = "Unable to load table availability.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_name = trim($_POST['guest_name'] ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');
    $guest_phone = trim($_POST['guest_phone'] ?? '');
    $reservation_date = $_POST['reservation_date'] ?? '';
    $reservation_time = $_POST['reservation_time'] ?? '';
    $party_size = $_POST['party_size'] ?? '';
    $table_id = $_POST['table_id'] ?? '';
    $special_requests = trim($_POST['special_requests'] ?? '');
    
    // Validate
    $errors = [];
    
    if (empty($guest_name)) $errors[] = "Name is required.";
    if (empty($guest_email)) $errors[] = "Email is required.";
    if (empty($guest_phone)) $errors[] = "Phone is required.";
    if (empty($reservation_date)) $errors[] = "Date is required.";
    if (empty($reservation_time)) $errors[] = "Time is required.";
    if (empty($party_size)) $errors[] = "Party size is required.";
    if (empty($table_id)) $errors[] = "Please select a table.";
    
    if (empty($errors)) {
        // Combine date and time
        $reservation_datetime = $reservation_date . ' ' . $reservation_time . ':00';
        
        // Check if table is available
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM bookings 
                WHERE table_id = ? 
                AND booking_type = 'restaurant'
                AND status NOT IN ('cancelled', 'completed')
                AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, ?)) < 120
            ");
            $stmt->execute([$table_id, $reservation_datetime]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Selected table is not available at that time. Please choose another time or table.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error checking availability.";
        }
    }
    
    if (empty($errors)) {
        // Handle user (logged in or guest)
        if (isLoggedIn()) {
            $user_id = $_SESSION['user_id'];
        } else {
            // Check if guest email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$guest_email]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                $user_id = $existing_user['id'];
            } else {
                // Create guest user
                $temp_password = password_hash(uniqid(), PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (email, password, full_name, phone, role) 
                    VALUES (?, ?, ?, ?, 'guest')
                ");
                $stmt->execute([$guest_email, $temp_password, $guest_name, $guest_phone]);
                $user_id = $db->lastInsertId();
            }
        }
        
        // Create booking
        try {
            $stmt = $db->prepare("
                INSERT INTO bookings 
                (user_id, booking_type, table_id, reservation_time, party_size, 
                 special_requests, status, guest_name, guest_email, guest_phone)
                VALUES (?, 'restaurant', ?, ?, ?, ?, 'pending', ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $table_id,
                $reservation_datetime,
                $party_size,
                $special_requests,
                $guest_name,
                $guest_email,
                $guest_phone
            ]);
            
            $booking_id = $db->lastInsertId();
            
            // Get table info for confirmation
            $stmt = $db->prepare("SELECT table_number FROM tables WHERE id = ?");
            $stmt->execute([$table_id]);
            $table = $stmt->fetch();
            
            $success = "
                <div class='text-center'>
                    <div class='mb-4'>
                        <i class='bi bi-check-circle-fill text-success display-1'></i>
                    </div>
                    <h3 class='mb-3'>Reservation Confirmed! 🎉</h3>
                    
                    <div class='card border-success mb-4'>
                        <div class='card-body text-start'>
                            <h5 class='card-title'>Reservation Details</h5>
                            <div class='row'>
                                <div class='col-6'>
                                    <p class='mb-2'><strong>Reference:</strong></p>
                                    <p class='mb-2'><strong>Table:</strong></p>
                                    <p class='mb-2'><strong>Date & Time:</strong></p>
                                    <p class='mb-2'><strong>Party Size:</strong></p>
                                    <p class='mb-0'><strong>Status:</strong></p>
                                </div>
                                <div class='col-6'>
                                    <p class='mb-2'>RT-" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "</p>
                                    <p class='mb-2'>Table " . $table['table_number'] . "</p>
                                    <p class='mb-2'>" . date('F j, Y', strtotime($reservation_date)) . " at " . date('g:i A', strtotime($reservation_time)) . "</p>
                                    <p class='mb-2'>{$party_size} " . ($party_size == 1 ? 'person' : 'people') . "</p>
                                    <p class='mb-0'><span class='badge bg-warning'>Pending Confirmation</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <p class='mb-4'>A confirmation email has been sent to <strong>{$guest_email}</strong>.</p>
                    
                    <div class='d-grid gap-2 col-lg-6 mx-auto'>
                        " . (isLoggedIn() ? 
                            '<a href="dashboard.php" class="btn btn-outline-primary">View My Bookings</a>' : 
                            '<a href="register.php" class="btn btn-outline-primary">Create Account</a>'
                        ) . "
                        <a href='book-table.php' class='btn btn-primary'>Make Another Reservation</a>
                    </div>
                </div>
            ";
            
            // Clear form for guest bookings
            if (!isLoggedIn()) {
                $_POST = [];
            }
            
        } catch (PDOException $e) {
            $error = "Failed to create reservation: " . $e->getMessage();
        }
    } else {
        $error = '<ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul>';
    }
}
?>

<!-- Reservation Hero -->
<section class="bg-dark text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">Reserve Your Table</h1>
                <p class="lead mb-0">Experience fine dining at The Terrace Restaurant</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="bg-primary p-3 rounded-3 d-inline-block">
                    <h5 class="mb-0">⭐ Michelin Star</h5>
                    <small>Award-winning cuisine</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reservation Form -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4">Make a Reservation</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Please fix the following:</strong>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success border-0 bg-light">
                                <?php echo $success; ?>
                            </div>
                        <?php else: ?>
                        
                        <form method="POST" id="reservationForm" class="needs-validation" novalidate>
                            <!-- Guest Information -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-person-circle me-2"></i>Guest Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="guest_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="guest_name" name="guest_name" 
                                               value="<?php echo htmlspecialchars($_POST['guest_name'] ?? ($_SESSION['user_name'] ?? '')); ?>" 
                                               required>
                                        <div class="invalid-feedback">Please enter your name.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="guest_email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="guest_email" name="guest_email" 
                                               value="<?php echo htmlspecialchars($_POST['guest_email'] ?? ($_SESSION['user_email'] ?? '')); ?>" 
                                               required>
                                        <div class="invalid-feedback">Please enter a valid email.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="guest_phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="guest_phone" name="guest_phone" 
                                               value="<?php echo htmlspecialchars($_POST['guest_phone'] ?? ''); ?>" 
                                               pattern="[0-9\s\-\(\)]+" required>
                                        <div class="invalid-feedback">Please enter your phone number.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Reservation Details -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-calendar-week me-2"></i>Reservation Details</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="reservation_date" class="form-label">Date *</label>
                                        <input type="date" class="form-control" id="reservation_date" name="reservation_date" 
                                               value="<?php echo $_POST['reservation_date'] ?? ''; ?>" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">Please select a date.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="reservation_time" class="form-label">Time *</label>
                                        <select class="form-select" id="reservation_time" name="reservation_time" required>
                                            <option value="">Select Time</option>
                                            <?php
                                            // Generate time slots (5:00 PM to 10:00 PM)
                                            for ($hour = 17; $hour <= 22; $hour++) {
                                                for ($minute = 0; $minute < 60; $minute += 30) {
                                                    $time = sprintf('%02d:%02d', $hour, $minute);
                                                    $display_time = date('g:i A', strtotime($time));
                                                    $selected = ($_POST['reservation_time'] ?? '') === $time ? 'selected' : '';
                                                    echo "<option value='{$time}' {$selected}>{$display_time}</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a time.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="party_size" class="form-label">Party Size *</label>
                                        <select class="form-select" id="party_size" name="party_size" required>
                                            <option value="">Number of Guests</option>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?php echo $i; ?>" 
                                                    <?php echo (isset($_POST['party_size']) && $_POST['party_size'] == $i) ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?> <?php echo $i == 1 ? 'person' : 'people'; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select party size.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Table Selection -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-table me-2"></i>Table Selection</h5>
                                <div class="row g-3" id="tablesContainer">
                                    <?php if (empty($tables)): ?>
                                        <div class="col-12">
                                            <div class="alert alert-warning">
                                                No tables available for online booking. Please call (123) 456-7890.
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($tables as $table): 
                                            $is_available = $table['booked_count'] == 0;
                                            $table_class = $is_available ? 'border-primary' : 'border-secondary';
                                        ?>
                                        <div class="col-md-6">
                                            <div class="card table-card <?php echo $table_class; ?> h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h5 class="card-title mb-0">Table <?php echo $table['table_number']; ?></h5>
                                                        <span class="badge bg-<?php echo $is_available ? 'success' : 'secondary'; ?>">
                                                            <?php echo $is_available ? 'Available' : 'Booked'; ?>
                                                        </span>
                                                    </div>
                                                    <p class="card-text">
                                                        <i class="bi bi-people me-1"></i> 
                                                        Capacity: <?php echo $table['capacity']; ?> people<br>
                                                        <i class="bi bi-geo-alt me-1"></i> 
                                                        <?php echo ucfirst(str_replace('_', ' ', $table['location'])); ?>
                                                    </p>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                               name="table_id" 
                                                               id="table_<?php echo $table['id']; ?>" 
                                                               value="<?php echo $table['id']; ?>"
                                                               <?php echo !$is_available ? 'disabled' : ''; ?>
                                                               <?php echo (isset($_POST['table_id']) && $_POST['table_id'] == $table['id']) ? 'checked' : ''; ?>
                                                               required>
                                                        <label class="form-check-label" for="table_<?php echo $table['id']; ?>">
                                                            Select this table
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Special Requests -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-chat-dots me-2"></i>Special Requests</h5>
                                <textarea class="form-control" id="special_requests" name="special_requests" 
                                          rows="3" placeholder="Dietary restrictions, allergies, celebration, window seat preference, etc."><?php echo htmlspecialchars($_POST['special_requests'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Terms & Submit -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">cancellation policy</a> *
                                </label>
                                <div class="invalid-feedback">You must agree to the terms.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                                <i class="bi bi-check-circle me-2"></i>Confirm Reservation
                            </button>
                        </form>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4 mt-4 mt-lg-0">
                <!-- Restaurant Info -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">The Terrace Restaurant</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-clock me-2"></i><strong>Hours:</strong></li>
                            <li class="mb-1 ps-4">Mon-Thu: 6:00 PM - 10:00 PM</li>
                            <li class="mb-1 ps-4">Fri-Sat: 5:30 PM - 11:00 PM</li>
                            <li class="mb-3 ps-4">Sun: 5:00 PM - 9:00 PM</li>
                            <li class="mb-2"><i class="bi bi-telephone me-2"></i><strong>Phone:</strong> (123) 456-7890</li>
                            <li class="mb-2"><i class="bi bi-person me-2"></i><strong>Dress Code:</strong> Smart Casual</li>
                            <li><i class="bi bi-info-circle me-2"></i><strong>Note:</strong> 2-hour dining time</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Tips -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Reservation Tips</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Book at least 48 hours in advance</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Notify us of dietary restrictions</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Arrive 15 minutes early</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Cancel at least 24 hours before</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Large groups: call for availability</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Menu Preview -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Menu Preview</h5>
                        <div class="mb-3">
                            <h6 class="mb-1">Wagyu Beef Steak</h6>
                            <small class="text-muted">Premium grade with truffle mashed potatoes</small>
                            <div class="text-end">$68.00</div>
                        </div>
                        <div class="mb-3">
                            <h6 class="mb-1">Grilled Salmon</h6>
                            <small class="text-muted">Atlantic salmon with lemon butter sauce</small>
                            <div class="text-end">$32.00</div>
                        </div>
                        <div class="mb-3">
                            <h6 class="mb-1">Chocolate Soufflé</h6>
                            <small class="text-muted">With vanilla bean ice cream</small>
                            <div class="text-end">$18.00</div>
                        </div>
                        <a href="menu.php" class="btn btn-outline-primary w-100">View Full Menu</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancellation Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Standard Reservations</h6>
                <p>Free cancellation up to 24 hours before reservation time. Late cancellations may incur a $25 per person fee.</p>
                
                <h6>Large Groups (6+ people)</h6>
                <p>48-hour cancellation notice required. Late cancellations may incur a $50 per person fee.</p>
                
                <h6>No-shows</h6>
                <p>No-shows will be charged $50 per person and may result in future booking restrictions.</p>
                
                <h6>Running Late</h6>
                <p>Please call if running more than 15 minutes late. Tables may be released after 30 minutes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.table-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.table-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.table-card.border-primary {
    border-width: 2px;
}

.table-card input[type="radio"]:checked + label {
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reservationDate = document.getElementById('reservation_date');
    const reservationTime = document.getElementById('reservation_time');
    const partySize = document.getElementById('party_size');
    const tablesContainer = document.getElementById('tablesContainer');
    
    // Set minimum date (today)
    if (reservationDate) {
        const today = new Date().toISOString().split('T')[0];
        reservationDate.min = today;
        
        // Set default to tomorrow if empty
        if (!reservationDate.value) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            reservationDate.value = tomorrow.toISOString().split('T')[0];
        }
    }
    
    // Filter tables based on party size
    function filterTables() {
        const selectedSize = parseInt(partySize.value) || 0;
        const tableCards = tablesContainer.querySelectorAll('.table-card');
        
        tableCards.forEach(card => {
            const capacity = parseInt(card.querySelector('.card-text').textContent.match(/Capacity: (\d+)/)[1]);
            
            if (selectedSize > 0 && selectedSize > capacity) {
                card.style.opacity = '0.5';
                card.style.pointerEvents = 'none';
                card.querySelector('input[type="radio"]').disabled = true;
            } else {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                // Only re-enable if table is available
                const badge = card.querySelector('.badge');
                if (badge && badge.textContent === 'Available') {
                    card.querySelector('input[type="radio"]').disabled = false;
                }
            }
        });
    }
    
    if (partySize) {
        partySize.addEventListener('change', filterTables);
        filterTables(); // Initial filter
    }
    
    // Form validation
    const form = document.getElementById('reservationForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Auto-select first available table
    const availableTables = tablesContainer.querySelectorAll('input[type="radio"]:not(:disabled)');
    if (availableTables.length > 0 && !document.querySelector('input[type="radio"]:checked')) {
        availableTables[0].checked = true;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>