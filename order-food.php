<?php
$page_title = "Order Food";
require_once 'includes/header.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

$db = getDB();
$error = '';
$success = '';

// Get cart from localStorage via POST or initialize empty
$cart = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $cart = json_decode($_POST['cart_data'], true) ?? [];
} else {
    // For display purposes, we'll use JavaScript to populate the cart
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $guest_name = trim($_POST['guest_name'] ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');
    $guest_phone = trim($_POST['guest_phone'] ?? '');
    $delivery_room = trim($_POST['delivery_room'] ?? '');
    $delivery_time = $_POST['delivery_time'] ?? '';
    $special_instructions = trim($_POST['special_instructions'] ?? '');
    $cart_data = json_decode($_POST['cart_data'] ?? '[]', true);
    
    // Validate
    $errors = [];
    
    if (empty($guest_name)) $errors[] = "Name is required.";
    if (empty($guest_email)) $errors[] = "Email is required.";
    if (empty($guest_phone)) $errors[] = "Phone is required.";
    if (empty($delivery_time)) $errors[] = "Delivery time is required.";
    if (empty($cart_data)) $errors[] = "Your cart is empty.";
    
    if (empty($errors)) {
        // Handle user (logged in or guest)
        if (isLoggedIn()) {
            $user_id = $_SESSION['user_id'];
            $guest_email = $_SESSION['user_email'];
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
        
        // Calculate total
        $food_total = 0;
        $menu_items = [];
        foreach ($cart_data as $item) {
            $food_total += $item['price'] * $item['quantity'];
            $menu_items[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity']
            ];
        }
        
        // Add 8% tax
        $tax = $food_total * 0.08;
        $total_amount = $food_total + $tax;
        
        // Create booking
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO bookings 
                (user_id, booking_type, menu_items, food_total, total_amount, 
                 delivery_room, special_requests, status, guest_name, guest_email, guest_phone)
                VALUES (?, 'food_order', ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                json_encode($menu_items),
                $food_total,
                $total_amount,
                $delivery_room ?: NULL,
                $special_instructions . (empty($delivery_time) ? '' : "\nDelivery Time: " . $delivery_time),
                $guest_name,
                $guest_email,
                $guest_phone
            ]);
            
            $booking_id = $db->lastInsertId();
            
            $db->commit();
            
            // Clear cart
            $success = "
                <div class='text-center'>
                    <div class='mb-4'>
                        <i class='bi bi-check-circle-fill text-success display-1'></i>
                    </div>
                    <h3 class='mb-3'>Order Placed Successfully! 🎉</h3>
                    
                    <div class='card border-success mb-4'>
                        <div class='card-body text-start'>
                            <h5 class='card-title'>Order Details</h5>
                            <div class='row'>
                                <div class='col-6'>
                                    <p class='mb-2'><strong>Order #:</strong></p>
                                    <p class='mb-2'><strong>Total Items:</strong></p>
                                    <p class='mb-2'><strong>Food Total:</strong></p>
                                    <p class='mb-2'><strong>Tax (8%):</strong></p>
                                    <p class='mb-0'><strong>Total Amount:</strong></p>
                                </div>
                                <div class='col-6'>
                                    <p class='mb-2'>FO-" . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . "</p>
                                    <p class='mb-2'>" . array_sum(array_column($cart_data, 'quantity')) . " items</p>
                                    <p class='mb-2'>$" . number_format($food_total, 2) . "</p>
                                    <p class='mb-2'>$" . number_format($tax, 2) . "</p>
                                    <p class='mb-0'><strong>$" . number_format($total_amount, 2) . "</strong></p>
                                </div>
                            </div>
                            " . (!empty($delivery_room) ? "<p class='mt-3'><strong>Delivery to:</strong> Room $delivery_room</p>" : "") . "
                            " . (!empty($delivery_time) ? "<p><strong>Delivery Time:</strong> " . date('g:i A', strtotime($delivery_time)) . "</p>" : "") . "
                        </div>
                    </div>
                    
                    <p class='mb-4'>Your order has been received and will be prepared shortly. Estimated delivery time: 30-45 minutes.</p>
                    
                    <div class='d-grid gap-2 col-lg-6 mx-auto'>
                        " . (isLoggedIn() ? 
                            '<a href="dashboard.php" class="btn btn-outline-primary">View My Orders</a>' : 
                            '<a href="register.php" class="btn btn-outline-primary">Create Account</a>'
                        ) . "
                        <a href='order-food.php' class='btn btn-primary'>Place Another Order</a>
                    </div>
                </div>
            ";
            
            // Clear cart in localStorage via JavaScript
            echo '<script>localStorage.removeItem("eden_cart");</script>';
            
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Failed to place order: " . $e->getMessage();
        }
    } else {
        $error = '<ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul>';
    }
}

// Get available rooms for guests staying at hotel
$available_rooms = [];
try {
    $stmt = $db->query("
        SELECT room_number 
        FROM rooms 
        WHERE is_available = FALSE 
        ORDER BY room_number
    ");
    $available_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignore error, room selection is optional
}
?>

<!-- Order Hero -->
<section class="bg-dark text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">Order Food</h1>
                <p class="lead mb-0">Enjoy delicious meals from our restaurant delivered to your room</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="bg-primary p-3 rounded-3 d-inline-block">
                    <h5 class="mb-0">Room Service</h5>
                    <small>Available 6AM - 1AM</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Order Form -->
<section class="py-5">
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
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
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4">Your Order</h3>
                        
                        <div id="orderItemsContainer">
                            <!-- Cart items will be loaded here by JavaScript -->
                            <div class="text-center py-5">
                                <i class="bi bi-cart-x display-1 text-muted"></i>
                                <p class="mt-3">Your cart is empty</p>
                                <p class="text-muted small mb-4">Add items from the menu to get started</p>
                                <a href="menu.php" class="btn btn-primary">
                                    <i class="bi bi-menu-button me-2"></i> Browse Menu
                                </a>
                            </div>
                        </div>
                        
                        <div class="border-top pt-4 mt-4">
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-2">Subtotal:</p>
                                    <p class="mb-2">Tax (8%):</p>
                                    <p class="mb-0"><strong>Total:</strong></p>
                                </div>
                                <div class="col-6 text-end">
                                    <p class="mb-2" id="orderSubtotal">$0.00</p>
                                    <p class="mb-2" id="orderTax">$0.00</p>
                                    <p class="mb-0"><strong id="orderTotal">$0.00</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4">Delivery Information</h3>
                        
                        <form method="POST" id="orderForm" onsubmit="return prepareOrderForm()">
                            <input type="hidden" name="cart_data" id="cartData">
                            <input type="hidden" name="place_order" value="1">
                            
                            <!-- Guest Information -->
                            <div class="mb-3">
                                <label for="guest_name" class="form-label">Name *</label>
                                <input type="text" class="form-control" id="guest_name" name="guest_name" 
                                       value="<?php echo htmlspecialchars($_POST['guest_name'] ?? ($_SESSION['user_name'] ?? '')); ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="guest_email" name="guest_email" 
                                       value="<?php echo htmlspecialchars($_POST['guest_email'] ?? ($_SESSION['user_email'] ?? '')); ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="guest_phone" name="guest_phone" 
                                       value="<?php echo htmlspecialchars($_POST['guest_phone'] ?? ''); ?>" 
                                       pattern="[0-9\s\-\(\)]+" required>
                            </div>
                            
                            <!-- Delivery Details -->
                            <div class="mb-3">
                                <label for="delivery_room" class="form-label">Room Number (Optional)</label>
                                <input type="text" class="form-control" id="delivery_room" name="delivery_room" 
                                       list="roomSuggestions"
                                       value="<?php echo htmlspecialchars($_POST['delivery_room'] ?? ''); ?>"
                                       placeholder="e.g., 101">
                                <datalist id="roomSuggestions">
                                    <?php foreach ($available_rooms as $room): ?>
                                        <option value="<?php echo $room; ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="text-muted">Leave blank for restaurant pickup</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery_time" class="form-label">Preferred Delivery Time *</label>
                                <select class="form-select" id="delivery_time" name="delivery_time" required>
                                    <option value="">Select Time</option>
                                    <option value="asap">ASAP (30-45 minutes)</option>
                                    <optgroup label="Specific Time">
                                        <?php
                                        // Generate time slots for next 4 hours
                                        $current_hour = date('G');
                                        $start_hour = max(6, $current_hour); // Start at 6AM or current hour
                                        
                                        for ($hour = $start_hour; $hour <= min(25, $start_hour + 4); $hour++) {
                                            for ($minute = 0; $minute < 60; $minute += 30) {
                                                $time = sprintf('%02d:%02d', $hour % 24, $minute);
                                                $display_hour = $hour % 24;
                                                $am_pm = $display_hour >= 12 ? 'PM' : 'AM';
                                                $display_hour = $display_hour % 12;
                                                $display_hour = $display_hour ? $display_hour : 12;
                                                $display_time = sprintf('%d:%02d %s', $display_hour, $minute, $am_pm);
                                                
                                                echo "<option value='$time'>$display_time</option>";
                                            }
                                        }
                                        ?>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="special_instructions" class="form-label">Special Instructions</label>
                                <textarea class="form-control" id="special_instructions" name="special_instructions" 
                                          rows="3" placeholder="Dietary restrictions, allergies, delivery instructions, etc."><?php echo htmlspecialchars($_POST['special_instructions'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a> *
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3" id="submitOrder" disabled>
                                <i class="bi bi-check-circle me-2"></i> Place Order
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Help Card -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-info-circle me-2"></i>Need Help?</h5>
                        <p class="text-muted">Contact our room service:</p>
                        <p><i class="bi bi-telephone me-2"></i> Ext. 1234</p>
                        <p><i class="bi bi-clock me-2"></i> 6:00 AM - 1:00 AM</p>
                        <p class="text-muted small mt-3">Minimum order: $25.00<br>Delivery fee: $5.00 for rooms</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</section>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room Service Terms</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Delivery Times</h6>
                <p>Standard delivery: 30-45 minutes. Specific time requests are not guaranteed but we do our best.</p>
                
                <h6>Cancellation</h6>
                <p>Orders can be cancelled within 10 minutes of placement. After preparation begins, cancellation may not be possible.</p>
                
                <h6>Payment</h6>
                <p>Charged to room account or credit card on file. Please have ID ready for delivery.</p>
                
                <h6>Allergies</h6>
                <p>Please inform us of any allergies. While we take precautions, we cannot guarantee allergen-free preparation.</p>
                
                <h6>Service Charge</h6>
                <p>18% service charge and 8% tax will be added to all orders.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.order-item {
    transition: background-color 0.2s;
}

.order-item:hover {
    background-color: #f8f9fa;
}

.sticky-top {
    position: sticky;
    z-index: 1020;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load cart from localStorage
    let cart = JSON.parse(localStorage.getItem('eden_cart')) || [];
    
    // Update order summary
    function updateOrderSummary() {
        const orderItemsContainer = document.getElementById('orderItemsContainer');
        const submitButton = document.getElementById('submitOrder');
        
        if (cart.length === 0) {
            orderItemsContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-cart-x display-1 text-muted"></i>
                    <p class="mt-3">Your cart is empty</p>
                    <p class="text-muted small mb-4">Add items from the menu to get started</p>
                    <a href="menu.php" class="btn btn-primary">
                        <i class="bi bi-menu-button me-2"></i> Browse Menu
                    </a>
                </div>
            `;
            submitButton.disabled = true;
            updateTotals(0);
            return;
        }
        
        // Calculate totals
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.08;
        const total = subtotal + tax;
        
        // Update totals display
        updateTotals(subtotal);
        
        // Build order items HTML
        let html = `
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            html += `
                <tr class="order-item" data-index="${index}">
                    <td>
                        <strong>${item.name}</strong>
                    </td>
                    <td class="text-end">$${item.price.toFixed(2)}</td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary decrease-qty" 
                                    data-index="${index}"
                                    ${item.quantity <= 1 ? 'disabled' : ''}>
                                <i class="bi bi-dash"></i>
                            </button>
                            <span class="mx-2">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary increase-qty" 
                                    data-index="${index}">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-end">$${itemTotal.toFixed(2)}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-danger remove-item" data-index="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="menu.php" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-2"></i> Add More Items
                </a>
            </div>
        `;
        
        orderItemsContainer.innerHTML = html;
        submitButton.disabled = false;
        
        // Add event listeners
        document.querySelectorAll('.decrease-qty').forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                if (cart[index].quantity > 1) {
                    cart[index].quantity--;
                    saveCart();
                    updateOrderSummary();
                }
            });
        });
        
        document.querySelectorAll('.increase-qty').forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                cart[index].quantity++;
                saveCart();
                updateOrderSummary();
            });
        });
        
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                if (confirm(`Remove ${cart[index].name} from your order?`)) {
                    cart.splice(index, 1);
                    saveCart();
                    updateOrderSummary();
                }
            });
        });
    }
    
    function updateTotals(subtotal) {
        const tax = subtotal * 0.08;
        const total = subtotal + tax;
        
        document.getElementById('orderSubtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('orderTax').textContent = '$' + tax.toFixed(2);
        document.getElementById('orderTotal').textContent = '$' + total.toFixed(2);
    }
    
    function saveCart() {
        localStorage.setItem('eden_cart', JSON.stringify(cart));
        // Update header badge
        const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
        const headerBadge = document.querySelector('.nav-cart-badge');
        if (headerBadge) {
            headerBadge.textContent = cartCount;
            headerBadge.style.display = cartCount > 0 ? 'inline-block' : 'none';
        }
    }
    
    // Prepare form submission
    window.prepareOrderForm = function() {
        if (cart.length === 0) {
            alert('Your cart is empty. Please add items from the menu.');
            return false;
        }
        
        document.getElementById('cartData').value = JSON.stringify(cart);
        return true;
    };
    
    // Initialize
    updateOrderSummary();
    
    // Phone number formatting
    const phoneInput = document.getElementById('guest_phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    }
    
    // Form validation
    const form = document.getElementById('orderForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Auto-fill for logged-in users
    <?php if (isLoggedIn()): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('guest_name').value = '<?php echo addslashes($_SESSION['user_name'] ?? ''); ?>';
        document.getElementById('guest_email').value = '<?php echo addslashes($_SESSION['user_email'] ?? ''); ?>';
    });
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>