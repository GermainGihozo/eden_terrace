<?php
$page_title = "Restaurant";
require_once 'includes/header.php';
require_once 'includes/db.php';

$db = getDB();

// Get restaurant hours and info
$restaurant_info = [
    'name' => 'The Terrace Restaurant',
    'description' => 'Award-winning fine dining with panoramic city views',
    'chef' => 'Chef Marco Rossi',
    'cuisine' => 'Modern European with local ingredients',
    'dress_code' => 'Smart Casual',
    'hours' => [
        'Monday - Thursday' => '6:00 PM - 10:00 PM',
        'Friday - Saturday' => '5:30 PM - 11:00 PM',
        'Sunday' => '5:00 PM - 9:00 PM',
        'Brunch (Sunday)' => '11:00 AM - 3:00 PM'
    ]
];

// Get featured menu items
try {
    $stmt = $db->query("
        SELECT * FROM menu_items 
        WHERE is_available = TRUE 
        AND category IN ('main_course', 'special')
        ORDER BY price DESC 
        LIMIT 6
    ");
    $featured_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_items = [];
}
?>

<!-- Restaurant Hero -->
<section class="restaurant-hero bg-dark text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-80">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">The Terrace Restaurant</h1>
                <p class="lead mb-4">Experience culinary excellence with panoramic city views. Award-winning cuisine by Chef Marco Rossi.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="book-table.php" class="btn btn-primary btn-lg px-4 py-3">
                        <i class="bi bi-calendar-check me-2"></i> Reserve a Table
                    </a>
                    <a href="menu.php" class="btn btn-outline-light btn-lg px-4 py-3">
                        <i class="bi bi-menu-button me-2"></i> View Menu
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="position-relative mt-5 mt-lg-0">
                    <div class="row g-3">
                        <div class="col-6">
                            <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?fit=crop&w=400&h=300" 
                                 alt="Restaurant Interior" class="img-fluid rounded-3 shadow">
                        </div>
                        <div class="col-6">
                            <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?fit=crop&w=400&h=300" 
                                 alt="Fine Dining" class="img-fluid rounded-3 shadow mt-4">
                        </div>
                    </div>
                    <div class="position-absolute bottom-0 start-0 p-4">
                        <div class="bg-primary text-white p-3 rounded-2 shadow">
                            <h4 class="mb-0">⭐ Michelin Guide 2024</h4>
                            <p class="mb-0">Awarded One Michelin Star</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Restaurant -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?fit=crop&w=600&h=400" 
                     alt="Chef Marco" class="img-fluid rounded-3 shadow">
            </div>
            <div class="col-lg-6 ps-lg-5 mt-5 mt-lg-0">
                <h2 class="display-5 fw-bold mb-4">Culinary Excellence</h2>
                <p class="lead text-muted mb-4">Led by Chef Marco Rossi, our restaurant offers a modern interpretation of European cuisine using locally sourced, seasonal ingredients.</p>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-3 border rounded-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-star fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Michelin Star</h6>
                                <small class="text-muted">Awarded 2024</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-3 border rounded-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-award fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Wine Spectator</h6>
                                <small class="text-muted">Award of Excellence</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="#chef" class="btn btn-outline-primary">Meet Our Chef</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Dishes -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-3">Signature Dishes</h2>
                <p class="lead text-muted">Experience our chef's most celebrated creations</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php if (empty($featured_items)): ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Featured dishes coming soon.</p>
                </div>
            <?php else: ?>
                <?php foreach ($featured_items as $item): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card dish-card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?fit=crop&w=400&h=300'); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 style="height: 250px; object-fit: cover;">
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $item['category'])); ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <h5 class="text-primary mb-0">$<?php echo number_format($item['price'], 2); ?></h5>
                            </div>
                            <p class="card-text text-muted"><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 100)); ?>...</p>
                            <div class="mt-3">
                                <a href="order-food.php?item=<?php echo $item['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> Add to Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="menu.php" class="btn btn-primary btn-lg">
                View Full Menu <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Chef Section -->
<section class="py-5" id="chef">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold mb-4">Meet Chef Marco Rossi</h2>
                <p class="lead text-muted mb-4">With over 20 years of experience in Michelin-starred kitchens across Europe, Chef Marco brings his passion for seasonal, locally sourced ingredients to every dish.</p>
                
                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-trophy text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">3 Michelin Stars</h6>
                                <small class="text-muted">Throughout career</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-globe text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">5 Countries</h6>
                                <small class="text-muted">International experience</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1583394293214-28ded15ee548?fit=crop&w=600&h=400" 
                     alt="Chef Marco Rossi" class="img-fluid rounded-3 shadow mt-5 mt-lg-0">
            </div>
        </div>
    </div>
</section>

<!-- Hours & Info -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-5 mb-lg-0">
                <h3 class="mb-4">Hours & Information</h3>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php foreach ($restaurant_info['hours'] as $day => $time): ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span><?php echo $day; ?></span>
                            <strong><?php echo $time; ?></strong>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-4">
                            <h6>Additional Information:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Dress Code: <?php echo $restaurant_info['dress_code']; ?></li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Valet Parking Available</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Private Dining Rooms</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Wheelchair Accessible</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <h3 class="mb-4">Make a Reservation</h3>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-4">
                                <div class="display-1 text-primary mb-3">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <h4 class="card-title">Online Reservation</h4>
                                <p class="card-text text-muted">Book your table instantly with our online reservation system.</p>
                                <a href="book-table.php" class="btn btn-primary mt-3">Book Now</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-4">
                                <div class="display-1 text-primary mb-3">
                                    <i class="bi bi-telephone"></i>
                                </div>
                                <h4 class="card-title">Phone Reservation</h4>
                                <p class="card-text text-muted">Prefer to speak with someone? Call our reservations team.</p>
                                <div class="mt-3">
                                    <h5 class="text-primary">(123) 456-7890</h5>
                                    <small class="text-muted">Mon-Sun, 9AM-10PM</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Special Events & Private Dining</h5>
                                <p class="card-text">Planning a special celebration or corporate event? Our private dining rooms accommodate groups from 10 to 100 guests.</p>
                                <a href="contact.php" class="btn btn-outline-primary">Inquire About Events</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-3">Guest Reviews</h2>
                <p class="lead text-muted">What our guests say about their dining experience</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                                <i class="bi bi-person-circle fs-3 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Sarah Johnson</h6>
                                <div class="text-warning">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                        <p class="card-text">"The wagyu steak was absolutely divine. Service was impeccable and the city view from our table was breathtaking."</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                                <i class="bi bi-person-circle fs-3 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Michael Chen</h6>
                                <div class="text-warning">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                </div>
                            </div>
                        </div>
                        <p class="card-text">"Perfect venue for our anniversary dinner. The sommelier's wine pairing was exceptional. Will definitely return."</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                                <i class="bi bi-person-circle fs-3 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Emma Wilson</h6>
                                <div class="text-warning">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                        <p class="card-text">"The tasting menu was a culinary journey. Each course was better than the last. Worth every penny!"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.restaurant-hero {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?fit=crop&w=1920&h=1080') center/cover no-repeat;
    min-height: 80vh;
    display: flex;
    align-items: center;
}

.dish-card {
    transition: transform 0.3s ease;
}

.dish-card:hover {
    transform: translateY(-10px);
}
</style>

<?php require_once 'includes/footer.php'; ?>