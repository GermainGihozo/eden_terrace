<?php
$page_title = "Menu";
require_once 'includes/header.php';
require_once 'includes/db.php';

$db = getDB();

// Get selected category from URL
$selected_category = $_GET['category'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM menu_items WHERE is_available = TRUE";
$params = [];

if ($selected_category !== 'all') {
    $query .= " AND category = ?";
    $params[] = $selected_category;
}

if (!empty($search_query)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY 
    CASE category 
        WHEN 'appetizer' THEN 1
        WHEN 'main_course' THEN 2
        WHEN 'dessert' THEN 3
        WHEN 'beverage' THEN 4
        WHEN 'alcohol' THEN 5
        ELSE 6
    END, name";

// Execute query
try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $menu_items = $stmt->fetchAll();
    
    // Group items by category
    $items_by_category = [];
    foreach ($menu_items as $item) {
        $category = $item['category'];
        if (!isset($items_by_category[$category])) {
            $items_by_category[$category] = [];
        }
        $items_by_category[$category][] = $item;
    }
    
} catch (PDOException $e) {
    $menu_items = [];
    $items_by_category = [];
    $error = "Unable to load menu items.";
}

// Get all categories for filter
$categories = [
    'all' => 'All Items',
    'appetizer' => 'Appetizers',
    'main_course' => 'Main Courses',
    'dessert' => 'Desserts',
    'beverage' => 'Beverages',
    'alcohol' => 'Wine & Cocktails',
    'special' => 'Chef Specials'
];
?>

<!-- Menu Hero -->
<section class="menu-hero bg-dark text-white py-5">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4">Our Menu</h1>
                <p class="lead mb-0">Experience culinary excellence with our seasonal menu crafted by Chef Marco</p>
            </div>
        </div>
    </div>
</section>

<!-- Menu Navigation -->
<section class="py-4 bg-light sticky-top" style="top: 76px; z-index: 999;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap gap-2" id="menuCategories">
                    <?php foreach ($categories as $key => $label): ?>
                        <a href="?category=<?php echo $key; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                           class="btn btn-outline-primary <?php echo $selected_category === $key ? 'active' : ''; ?>"
                           data-category="<?php echo $key; ?>">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0">
                <form method="GET" class="d-flex">
                    <input type="hidden" name="category" value="<?php echo $selected_category; ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search menu items..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if (!empty($search_query)): ?>
                            <a href="?category=<?php echo $selected_category; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Menu Content -->
<section class="py-5">
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($menu_items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-egg display-1 text-muted"></i>
                <h3 class="mt-3">No Menu Items Found</h3>
                <p class="text-muted">
                    <?php if (!empty($search_query)): ?>
                        No items found for "<?php echo htmlspecialchars($search_query); ?>"
                    <?php else: ?>
                        Menu items coming soon
                    <?php endif; ?>
                </p>
                <a href="menu.php" class="btn btn-primary">View All Items</a>
            </div>
        <?php else: ?>
        
        <!-- Menu Items by Category -->
        <?php foreach ($items_by_category as $category => $items): ?>
            <div class="menu-category mb-5" id="category-<?php echo $category; ?>">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">
                        <i class="bi <?php echo getCategoryIcon($category); ?> text-primary me-2"></i>
                        <?php echo $categories[$category] ?? ucfirst(str_replace('_', ' ', $category)); ?>
                    </h2>
                    <span class="badge bg-primary rounded-pill"><?php echo count($items); ?> items</span>
                </div>
                
                <div class="row g-4">
                    <?php foreach ($items as $item): ?>
                    <div class="col-lg-6">
                        <div class="card menu-item-card border-0 shadow-sm h-100" 
                             data-item-id="<?php echo $item['id']; ?>"
                             data-category="<?php echo $item['category']; ?>">
                            <div class="row g-0 h-100">
                                <!-- Item Image -->
                                <div class="col-md-4">
                                    <div class="position-relative h-100">
                                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? getDefaultImage($item['category'])); ?>" 
                                             class="img-fluid rounded-start h-100 w-100" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             style="object-fit: cover; min-height: 200px;">
                                        <?php if ($item['category'] === 'special'): ?>
                                            <div class="position-absolute top-0 start-0 m-2">
                                                <span class="badge bg-danger">Chef's Special</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Item Details -->
                                <div class="col-md-8">
                                    <div class="card-body h-100 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <h5 class="text-primary mb-0">$<?php echo number_format($item['price'], 2); ?></h5>
                                        </div>
                                        
                                        <p class="card-text text-muted flex-grow-1">
                                            <?php echo htmlspecialchars($item['description'] ?? 'Delicious menu item'); ?>
                                        </p>
                                        
                                        <div class="mt-3">
                                            <?php if (!empty($item['allergies'])): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                                                        Contains: <?php echo htmlspecialchars($item['allergies']); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock me-1"></i>
                                                        Prep time: <?php echo $item['prep_time'] ?? '15-20'; ?> mins
                                                    </small>
                                                </div>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-primary add-to-cart"
                                                            data-item-id="<?php echo $item['id']; ?>"
                                                            data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                            data-item-price="<?php echo $item['price']; ?>">
                                                        <i class="bi bi-plus-circle me-1"></i> Add to Order
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary view-details ms-1"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#itemModal"
                                                            data-item-id="<?php echo $item['id']; ?>">
                                                        <i class="bi bi-info-circle"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Order Summary Sticky Bar -->
        <div class="sticky-bottom d-lg-none" style="bottom: 0; z-index: 1000;">
            <div class="bg-white border-top shadow-lg">
                <div class="container py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span id="cart-count" class="badge bg-primary rounded-pill">0</span>
                            <span class="ms-2">items in cart</span>
                        </div>
                        <div>
                            <span class="me-3">Total: <strong id="cart-total">$0.00</strong></span>
                            <a href="order-food.php" class="btn btn-primary">
                                <i class="bi bi-cart-check me-1"></i> Order Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</section>

<!-- Featured Specials -->
<?php if (isset($items_by_category['special']) && count($items_by_category['special']) > 0): ?>
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold mb-4">Chef's Specials</h2>
                <p class="lead mb-4">Experience our chef's latest creations, available for a limited time only.</p>
                <a href="?category=special" class="btn btn-light btn-lg">
                    View All Specials <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <?php foreach (array_slice($items_by_category['special'], 0, 2) as $special): ?>
                    <div class="col-6">
                        <div class="card bg-dark text-white border-0 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($special['image_url'] ?? getDefaultImage('special')); ?>" 
                                 class="card-img" alt="<?php echo htmlspecialchars($special['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-img-overlay d-flex flex-column justify-content-end" 
                                 style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($special['name']); ?></h5>
                                <p class="card-text mb-2">$<?php echo number_format($special['price'], 2); ?></p>
                                <button class="btn btn-sm btn-light add-to-cart align-self-start"
                                        data-item-id="<?php echo $special['id']; ?>"
                                        data-item-name="<?php echo htmlspecialchars($special['name']); ?>"
                                        data-item-price="<?php echo $special['price']; ?>">
                                    Add to Order
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Wine Pairing -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?fit=crop&w=600&h=400" 
                     alt="Wine Selection" class="img-fluid rounded-3 shadow">
            </div>
            <div class="col-lg-6 ps-lg-5 mt-5 mt-lg-0">
                <h2 class="display-5 fw-bold mb-4">Wine & Pairing</h2>
                <p class="lead text-muted mb-4">Our sommelier has carefully curated a selection of wines to complement our menu. Ask about our wine pairing recommendations.</p>
                
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center p-3 border rounded-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-droplet fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Local Wineries</h6>
                                <small class="text-muted">Regional selections</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center p-3 border rounded-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-globe fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">International</h6>
                                <small class="text-muted">Global collection</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="?category=alcohol" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-cup-straw me-2"></i> View Wine List
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Dietary Information -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-4">Dietary Information</h2>
                <p class="lead text-muted mb-5">We accommodate various dietary needs. Please inform us of any allergies or restrictions.</p>
                
                <div class="row g-4">
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                                <i class="bi bi-tree text-primary fs-2"></i>
                            </div>
                            <h6>Vegetarian</h6>
                            <small class="text-muted">Multiple options</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                                <i class="bi bi-cloud-sun text-primary fs-2"></i>
                            </div>
                            <h6>Vegan</h6>
                            <small class="text-muted">Available upon request</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                                <i class="bi bi-shield text-primary fs-2"></i>
                            </div>
                            <h6>Gluten-Free</h6>
                            <small class="text-muted">Clearly marked</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                                <i class="bi bi-droplet text-primary fs-2"></i>
                            </div>
                            <h6>Dairy-Free</h6>
                            <small class="text-muted">Available options</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-5">
                    <p class="text-muted">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Please inform your server of any allergies or dietary restrictions. 
                        While we take precautions, we cannot guarantee completely allergen-free preparation.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Item Details Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="itemModalBody">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Shopping Cart Sidebar (Desktop) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartSidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="bi bi-cart3 me-2"></i> Your Order
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div id="cartItems">
            <!-- Cart items will be loaded here -->
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-1 text-muted"></i>
                <p class="mt-3">Your cart is empty</p>
                <p class="text-muted small">Add items from the menu to get started</p>
            </div>
        </div>
        
        <div class="mt-auto border-top pt-3">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span id="cartSubtotal">$0.00</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Tax (8%):</span>
                <span id="cartTax">$0.00</span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span><strong>Total:</strong></span>
                <span id="cartTotal" class="fw-bold">$0.00</span>
            </div>
            
            <div class="d-grid gap-2">
                <a href="order-food.php" class="btn btn-primary">
                    <i class="bi bi-cart-check me-2"></i> Proceed to Order
                </a>
                <button class="btn btn-outline-danger" id="clearCart">
                    <i class="bi bi-trash me-2"></i> Clear Cart
                </button>
            </div>
            
            <div class="mt-3 text-center">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Prices include tax. Service charge may apply.
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Cart Floating Button (Desktop) -->
<div class="position-fixed bottom-3 end-3 z-3 d-none d-lg-block">
    <button class="btn btn-primary btn-lg rounded-pill shadow-lg" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#cartSidebar"
            style="padding: 1rem 1.5rem;">
        <i class="bi bi-cart3 me-2"></i>
        <span id="cartBadge" class="badge bg-danger rounded-pill">0</span>
    </button>
</div>

<?php
// Helper functions
function getCategoryIcon($category) {
    $icons = [
        'appetizer' => 'bi-egg-fried',
        'main_course' => 'bi-egg',
        'dessert' => 'bi-cake',
        'beverage' => 'bi-cup-straw',
        'alcohol' => 'bi-cup',
        'special' => 'bi-star',
        'all' => 'bi-list'
    ];
    return $icons[$category] ?? 'bi-egg';
}

function getDefaultImage($category) {
    $images = [
        'appetizer' => 'https://images.unsplash.com/photo-1563379091339-03246963d9d6?fit=crop&w=400&h=300',
        'main_course' => 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?fit=crop&w=400&h=300',
        'dessert' => 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?fit=crop&w=400&h=300',
        'beverage' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?fit=crop&w=400&h=300',
        'alcohol' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?fit=crop&w=400&h=300',
        'special' => 'https://images.unsplash.com/photo-1578474846511-04ba529f0b88?fit=crop&w=400&h=300'
    ];
    return $images[$category] ?? 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?fit=crop&w=400&h=300';
}
?>

<style>
.menu-hero {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?fit=crop&w=1920&h=600') center/cover no-repeat;
}

.menu-item-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.menu-item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

#menuCategories .btn.active {
    background-color: #0d6efd;
    color: white;
}

/* Smooth scrolling for category anchors */
html {
    scroll-behavior: smooth;
}

/* Cart badge animation */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.pulse {
    animation: pulse 0.3s ease-in-out;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart from localStorage
    let cart = JSON.parse(localStorage.getItem('eden_cart')) || [];
    
    // Update cart display
    function updateCartDisplay() {
        const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * 0.08;
        const total = subtotal + tax;
        
        // Update counters
        document.getElementById('cart-count').textContent = cartCount;
        document.getElementById('cart-badge').textContent = cartCount;
        document.getElementById('cartSubtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('cartTax').textContent = '$' + tax.toFixed(2);
        document.getElementById('cartTotal').textContent = '$' + total.toFixed(2);
        document.getElementById('cart-total').textContent = '$' + total.toFixed(2);
        
        // Update cart items list
        const cartItemsContainer = document.getElementById('cartItems');
        if (cartCount === 0) {
            cartItemsContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-cart-x display-1 text-muted"></i>
                    <p class="mt-3">Your cart is empty</p>
                    <p class="text-muted small">Add items from the menu to get started</p>
                </div>
            `;
        } else {
            let html = '';
            cart.forEach((item, index) => {
                html += `
                    <div class="cart-item mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${item.name}</h6>
                                <small class="text-muted">Rwf${item.price.toFixed(2)} each</small>
                            </div>
                            <div class="text-end">
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-sm btn-outline-secondary decrease-quantity" 
                                            data-index="${index}"
                                            ${item.quantity <= 1 ? 'disabled' : ''}>
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <span class="mx-2">${item.quantity}</span>
                                    <button class="btn btn-sm btn-outline-secondary increase-quantity" 
                                            data-index="${index}">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger ms-2 remove-item" 
                                            data-index="${index}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="mt-1">
                                    <small>$${(item.price * item.quantity).toFixed(2)}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            cartItemsContainer.innerHTML = html;
            
            // Add event listeners for cart controls
            document.querySelectorAll('.decrease-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    if (cart[index].quantity > 1) {
                        cart[index].quantity--;
                        saveCart();
                        updateCartDisplay();
                    }
                });
            });
            
            document.querySelectorAll('.increase-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    cart[index].quantity++;
                    saveCart();
                    updateCartDisplay();
                });
            });
            
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    cart.splice(index, 1);
                    saveCart();
                    updateCartDisplay();
                });
            });
        }
        
        // Add pulse animation to badge when count changes
        const badge = document.getElementById('cart-badge');
        if (badge) {
            badge.classList.add('pulse');
            setTimeout(() => badge.classList.remove('pulse'), 300);
        }
    }
    
    // Save cart to localStorage
    function saveCart() {
        localStorage.setItem('eden_cart', JSON.stringify(cart));
    }
    
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = parseInt(this.getAttribute('data-item-id'));
            const itemName = this.getAttribute('data-item-name');
            const itemPrice = parseFloat(this.getAttribute('data-item-price'));
            
            // Check if item already in cart
            const existingItem = cart.find(item => item.id === itemId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    quantity: 1
                });
            }
            
            saveCart();
            updateCartDisplay();
            
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 m-3';
            toast.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-header">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <strong class="me-auto">Added to cart</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${itemName} added to your order
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            
            // Auto-remove toast
            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
    });
    
    // Clear cart button
    document.getElementById('clearCart')?.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear your cart?')) {
            cart = [];
            saveCart();
            updateCartDisplay();
        }
    });
    
    // Item details modal
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            
            fetch(`api/get-menu-item.php?id=${itemId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('itemModalBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('itemModalBody').innerHTML = 
                        '<div class="alert alert-danger">Error loading item details.</div>';
                });
        });
    });
    
    // Category filter highlighting
    const categoryButtons = document.querySelectorAll('#menuCategories a');
    categoryButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Smooth scroll to category
    if (window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if (target) {
            setTimeout(() => {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
    
    // Initialize cart display
    updateCartDisplay();
    
    // Update badge in header if it exists
    const updateHeaderBadge = () => {
        const headerBadge = document.querySelector('.nav-cart-badge');
        if (headerBadge) {
            const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
            headerBadge.textContent = cartCount;
            headerBadge.style.display = cartCount > 0 ? 'inline-block' : 'none';
        }
    };
    
    // Update header badge initially and on cart changes
    updateHeaderBadge();
    const originalUpdateCartDisplay = updateCartDisplay;
    updateCartDisplay = function() {
        originalUpdateCartDisplay();
        updateHeaderBadge();
    };
});
</script>

<?php require_once 'includes/footer.php'; ?>