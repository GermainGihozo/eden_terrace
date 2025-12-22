<?php
$page_title = "Book a Room";
require_once 'includes/header.php';
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/db.php';  // Add this line
// require_once 'includes/auth.php';

$db = getDB();
$room_id = $_GET['room_id'] ?? null;
$room = null;
$error = '';
$success = '';

// Get room details if room_id is provided
if ($room_id) {
    $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    
    if (!$room) {
        $error = "Room not found.";
    }
}

// Get all rooms for selection
$stmt = $db->query("SELECT id, name, price_per_night FROM rooms WHERE is_available = TRUE ORDER BY name");
$all_rooms = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data with null coalescing
    $guest_name = trim($_POST['guest_name'] ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');
    $guest_phone = trim($_POST['guest_phone'] ?? '');
    $room_id = $_POST['room_id'] ?? '';
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $num_guests = $_POST['num_guests'] ?? '';
    $special_requests = trim($_POST['special_requests'] ?? '');
    
    // Validate required fields
    if (empty($guest_name) || empty($guest_email) || empty($guest_phone) || 
        empty($room_id) || empty($check_in) || empty($check_out) || empty($num_guests)) {
        $error = "Please fill in all required fields.";
    } else {
        // Validate dates
        try {
            $check_in_date = new DateTime($check_in);
            $check_out_date = new DateTime($check_out);
            $today = new DateTime();
            $today->setTime(0, 0, 0); // Reset time to compare dates only
            
            if ($check_in_date < $today) {
                $error = "Check-in date cannot be in the past.";
            } elseif ($check_out_date <= $check_in_date) {
                $error = "Check-out date must be after check-in date.";
            } elseif (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address.";
            } else {
                // Calculate nights and price
                $nights = $check_in_date->diff($check_out_date)->days;
                
                // Get room price and capacity
                $stmt = $db->prepare("SELECT price_per_night, capacity FROM rooms WHERE id = ?");
                $stmt->execute([$room_id]);
                $room_data = $stmt->fetch();
                
                if (!$room_data) {
                    $error = "Selected room not found.";
                } elseif ($num_guests > $room_data['capacity']) {
                    $error = "Number of guests exceeds room capacity (max: {$room_data['capacity']}).";
                } else {
                    $room_price = $room_data['price_per_night'];
                    $total_amount = $room_price * $nights;
                    
                    // Check room availability
                    $stmt = $db->prepare("
                        SELECT COUNT(*) 
                        FROM bookings 
                        WHERE room_id = ? 
                        AND booking_type = 'room'
                        AND status NOT IN ('cancelled', 'completed')
                        AND (
                            (check_in < ? AND check_out > ?) OR
                            (check_in >= ? AND check_in < ?) OR
                            (check_out > ? AND check_out <= ?)
                        )
                    ");
                    $stmt->execute([
                        $room_id,
                        $check_out,
                        $check_in,
                        $check_in,
                        $check_out,
                        $check_in,
                        $check_out
                    ]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Room is not available for the selected dates. Please try different dates.";
                    } else {
                        // Start transaction for data consistency
                        $db->beginTransaction();
                        
                        try {
                            // Handle user (logged in or create guest account)
                            if (isLoggedIn()) {
                                $user_id = $_SESSION['user_id'];
                                $guest_email = $_SESSION['user_email'] ?? $guest_email; // Use logged-in user's email
                            } else {
                                // Check if guest email already exists
                                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                                $stmt->execute([$guest_email]);
                                $existing_user = $stmt->fetch();
                                
                                if ($existing_user) {
                                    $user_id = $existing_user['id'];
                                } else {
                                    // Create a guest user account
                                    $stmt = $db->prepare("
                                        INSERT INTO users (email, password, full_name, phone, role) 
                                        VALUES (?, ?, ?, ?, 'guest')
                                    ");
                                    
                                    // Generate a secure temporary password
                                    $temp_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                                    
                                    $stmt->execute([
                                        $guest_email,
                                        $temp_password,
                                        $guest_name,
                                        $guest_phone
                                    ]);
                                    
                                    $user_id = $db->lastInsertId();
                                }
                            }
                            
                            // Insert booking
                            $stmt = $db->prepare("
                                INSERT INTO bookings 
                                (user_id, booking_type, room_id, check_in, check_out, num_guests, 
                                 room_total, total_amount, special_requests, status, guest_name, guest_email, guest_phone)
                                VALUES (?, 'room', ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
                            ");
                            
                            $stmt->execute([
                                $user_id,
                                $room_id,
                                $check_in,
                                $check_out,
                                $num_guests,
                                $total_amount,
                                $total_amount,
                                $special_requests,
                                $guest_name,
                                $guest_email,
                                $guest_phone
                            ]);
                            
                            $booking_id = $db->lastInsertId();
                            
                            // Update room availability if needed
                            $stmt = $db->prepare("
                                UPDATE rooms 
                                SET is_available = FALSE 
                                WHERE id = ? 
                                AND NOT EXISTS (
                                    SELECT 1 FROM bookings 
                                    WHERE room_id = rooms.id 
                                    AND status NOT IN ('cancelled', 'completed')
                                    AND check_in < ? 
                                    AND check_out > ?
                                )
                            ");
                            $stmt->execute([$room_id, $check_out, $check_in]);
                            
                            // Commit transaction
                            $db->commit();
                            
                            // Get room name for confirmation message
                            $stmt = $db->prepare("SELECT name FROM rooms WHERE id = ?");
                            $stmt->execute([$room_id]);
                            $room_name = $stmt->fetchColumn() ?? 'Selected Room';
                            
                            $success = "
                                <div class='text-center'>
                                    <div class='mb-4'>
                                        <i class='bi bi-check-circle-fill text-success display-1'></i>
                                    </div>
                                    <h3 class='mb-3'>Booking Confirmed! 🎉</h3>
                                    <p class='lead mb-4'>Your room has been successfully booked.</p>
                                    
                                    <div class='card border-success mb-4'>
                                        <div class='card-body text-start'>
                                            <h5 class='card-title'>Booking Details</h5>
                                            <div class='row'>
                                                <div class='col-6'>
                                                    <p class='mb-2'><strong>Reference:</strong></p>
                                                    <p class='mb-2'><strong>Room:</strong></p>
                                                    <p class='mb-2'><strong>Dates:</strong></p>
                                                    <p class='mb-2'><strong>Guests:</strong></p>
                                                    <p class='mb-0'><strong>Total:</strong></p>
                                                </div>
                                                <div class='col-6'>
                                                    <p class='mb-2'>ET-" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "</p>
                                                    <p class='mb-2'>{$room_name}</p>
                                                    <p class='mb-2'>{$check_in} to {$check_out}</p>
                                                    <p class='mb-2'>{$num_guests} guest" . ($num_guests > 1 ? 's' : '') . "</p>
                                                    <p class='mb-0'>$" . number_format($total_amount, 2) . "</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class='mb-4'>A confirmation email has been sent to <strong>{$guest_email}</strong>.</p>
                                    
                                    <div class='d-grid gap-2 col-lg-6 mx-auto'>
                                        " . (isLoggedIn() ? 
                                            '<a href="dashboard.php" class="btn btn-outline-primary">View My Bookings</a>' : 
                                            '<a href="register.php" class="btn btn-outline-primary">Create Full Account</a>'
                                        ) . "
                                        <a href='book-room.php' class='btn btn-primary'>Make Another Booking</a>
                                    </div>
                                </div>
                            ";
                            
                            // Clear form for guest bookings
                            if (!isLoggedIn()) {
                                $_POST = [];
                            }
                            
                        } catch (PDOException $e) {
                            // Rollback transaction on error
                            $db->rollBack();
                            $error = "Booking failed. Please try again or contact support.";
                            // Log the error for debugging
                            error_log("Booking error: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error = "Invalid date format. Please check your dates.";
        }
    }
}
?>

<!-- Booking Hero -->
<section class="bg-dark text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">Book Your Stay</h1>
                <p class="lead mb-0">Secure your luxury accommodation at Eden Terrace</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="bg-primary p-3 rounded-3 d-inline-block">
                    <h5 class="mb-0">Best Rate Guarantee</h5>
                    <small>Book directly for lowest prices</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Booking Form -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4">Room Booking Details</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success border-0 bg-light">
                                <?php echo $success; ?>
                            </div>
                        <?php else: ?>
                        
                        <form method="POST" id="bookingForm" class="needs-validation" novalidate>
                            <!-- Guest Information -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-person-circle me-2"></i>Guest Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="guest_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="guest_name" name="guest_name" 
                                               value="<?php echo htmlspecialchars($_POST['guest_name'] ?? ($_SESSION['user_name'] ?? '')); ?>" 
                                               required>
                                        <div class="invalid-feedback">Please enter your full name.</div>
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
                                        <small class="text-muted">Format: (123) 456-7890</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Room Selection -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-door-closed me-2"></i>Room Selection</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="room_id" class="form-label">Select Room *</label>
                                        <select class="form-select" id="room_id" name="room_id" required>
                                            <option value="">Choose a room...</option>
                                            <?php foreach ($all_rooms as $r): 
                                                $selected = ($room_id && $r['id'] == $room_id) ? 'selected' : 
                                                           (isset($_POST['room_id']) && $_POST['room_id'] == $r['id'] ? 'selected' : '');
                                            ?>
                                            <option value="<?php echo $r['id']; ?>" 
                                                    data-price="<?php echo $r['price_per_night']; ?>"
                                                    <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($r['name']); ?> - $<?php echo number_format($r['price_per_night'], 2); ?>/night
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a room.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="num_guests" class="form-label">Number of Guests *</label>
                                        <select class="form-select" id="num_guests" name="num_guests" required>
                                            <option value="">Select...</option>
                                            <?php for ($i = 1; $i <= 6; $i++): 
                                                $selected = isset($_POST['num_guests']) && $_POST['num_guests'] == $i ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $i; ?> guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select number of guests.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dates -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-calendar-week me-2"></i>Dates</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="check_in" class="form-label">Check-in Date *</label>
                                        <input type="date" class="form-control" id="check_in" name="check_in" 
                                               value="<?php echo $_POST['check_in'] ?? ''; ?>" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">Please select check-in date.</div>
                                        <small class="text-muted">Check-in time: 3:00 PM</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="check_out" class="form-label">Check-out Date *</label>
                                        <input type="date" class="form-control" id="check_out" name="check_out" 
                                               value="<?php echo $_POST['check_out'] ?? ''; ?>" required>
                                        <div class="invalid-feedback">Please select check-out date.</div>
                                        <small class="text-muted">Check-out time: 11:00 AM</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Special Requests -->
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-chat-dots me-2"></i>Special Requests</h5>
                                <textarea class="form-control" id="special_requests" name="special_requests" 
                                          rows="3" placeholder="Any special requirements, early check-in, dietary needs, etc."><?php echo htmlspecialchars($_POST['special_requests'] ?? ''); ?></textarea>
                                <small class="text-muted">Optional. We'll do our best to accommodate your requests.</small>
                            </div>
                            
                            <!-- Price Summary -->
                            <div class="mb-4 p-3 bg-light rounded-3">
                                <h5 class="mb-3">Price Summary</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1">Room rate per night:</p>
                                        <p class="mb-1">Number of nights:</p>
                                        <hr class="my-2">
                                        <p class="mb-1"><strong>Total amount:</strong></p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p class="mb-1" id="ratePerNight">$0.00</p>
                                        <p class="mb-1" id="numberOfNights">0 nights</p>
                                        <hr class="my-2">
                                        <h4 class="text-primary mb-0" id="totalAmount">$0.00</h4>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">* Taxes and fees will be calculated at check-in</small>
                                </div>
                            </div>
                            
                            <!-- Terms & Submit -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a> *
                                </label>
                                <div class="invalid-feedback">You must agree to the terms.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                                <i class="bi bi-lock me-2"></i>Book Now
                            </button>
                            
                            <?php if (!isLoggedIn()): ?>
                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    By booking, you'll create a guest account. 
                                    <a href="register.php">Register</a> for full account features.
                                </small>
                            </div>
                            <?php endif; ?>
                        </form>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4 mt-4 mt-lg-0">
                <!-- Selected Room Info -->
                <?php if (isset($room) && !empty($room['name'])): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <img src="<?php echo htmlspecialchars($room['image_url'] ?? 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?fit=crop&w=400&h=300'); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>"
                         style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars(substr($room['description'] ?? 'Luxury accommodation with all amenities', 0, 100)); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="text-primary mb-0">$<?php echo number_format($room['price_per_night'] ?? 0, 2); ?><small class="text-muted fs-6">/night</small></h4>
                            <?php if ($room['is_available'] ?? true): ?>
                                <span class="badge bg-success">Available</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Booked</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Benefits -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-stars me-2"></i>Why Book With Us?</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Best price guarantee</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>No booking fees</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Free cancellation (48h)</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>24/7 customer support</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Direct hotel contact</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Help -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-question-circle me-2"></i>Need Help?</h5>
                        <p class="text-muted">Contact our reservations team:</p>
                        <p><i class="bi bi-telephone me-2"></i> (123) 456-7890</p>
                        <p><i class="bi bi-envelope me-2"></i> reservations@edenterrace.com</p>
                        <p class="text-muted mt-3">Office hours: 8:00 AM - 10:00 PM daily</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Terms & Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Cancellation Policy</h6>
                <p>Free cancellation up to 48 hours before check-in. Late cancellations may incur a fee of one night's stay.</p>
                
                <h6>Payment</h6>
                <p>Credit card required to guarantee booking. Payment charged at check-in.</p>
                
                <h6>Check-in/Check-out</h6>
                <p>Check-in: 3:00 PM | Check-out: 11:00 AM. Early check-in and late check-out subject to availability.</p>
                
                <h6>Children & Extra Beds</h6>
                <p>Children under 12 stay free. Extra beds available upon request for additional charge.</p>
                
                <h6>Pets</h6>
                <p>Pets not allowed, with exception of service animals.</p>
                
                <h6>Smoking Policy</h6>
                <p>All rooms are non-smoking. Smoking allowed in designated areas only.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_id');
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    const ratePerNight = document.getElementById('ratePerNight');
    const numberOfNights = document.getElementById('numberOfNights');
    const totalAmount = document.getElementById('totalAmount');
    
    // Set minimum check-out date
    checkIn.addEventListener('change', function() {
        if (this.value) {
            const minDate = new Date(this.value);
            minDate.setDate(minDate.getDate() + 1);
            checkOut.min = minDate.toISOString().split('T')[0];
            
            if (checkOut.value && new Date(checkOut.value) <= new Date(this.value)) {
                checkOut.value = '';
            }
        }
        calculateTotal();
    });
    
    checkOut.addEventListener('change', calculateTotal);
    roomSelect.addEventListener('change', calculateTotal);
    
    function calculateTotal() {
        const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
        const roomPrice = selectedRoom ? parseFloat(selectedRoom.getAttribute('data-price') || 0) : 0;
        
        if (checkIn.value && checkOut.value && roomPrice > 0) {
            const start = new Date(checkIn.value);
            const end = new Date(checkOut.value);
            const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            
            if (nights > 0) {
                ratePerNight.textContent = '$' + roomPrice.toFixed(2);
                numberOfNights.textContent = nights + ' night' + (nights !== 1 ? 's' : '');
                totalAmount.textContent = '$' + (roomPrice * nights).toFixed(2);
                return;
            }
        }
        
        ratePerNight.textContent = '$0.00';
        numberOfNights.textContent = '0 nights';
        totalAmount.textContent = '$0.00';
    }
    
    // Form validation
    const form = document.getElementById('bookingForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Initialize calculation if room is pre-selected
    if (roomSelect.value) {
        calculateTotal();
    }
    
    // Phone number formatting
    const phoneInput = document.getElementById('guest_phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    }
    
    // Auto-fill check-out date (3 days after check-in)
    checkIn.addEventListener('change', function() {
        if (this.value && !checkOut.value) {
            const defaultDate = new Date(this.value);
            defaultDate.setDate(defaultDate.getDate() + 3);
            checkOut.value = defaultDate.toISOString().split('T')[0];
            calculateTotal();
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>