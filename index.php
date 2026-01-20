<?php
$page_title = "Home";
require_once 'includes/header.php';
require_once 'includes/config.php';
require_once 'includes/db.php';  // Add this line
require_once 'includes/auth.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-dark text-white position-relative overflow-hidden">
    <div class="container py-5">
        <div class="row align-items-center min-vh-80">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Experience Luxury & Gourmet Dining</h1>
                <p class="lead mb-4"> hotel with award-winning restaurant in the heart of the Byumba city. Book your stay or table for an unforgettable experience.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="book-room.php" class="btn btn-primary btn-lg px-4 py-3">
                        <i class="bi bi-calendar-check me-2"></i> Book a Room
                    </a>
                    <a href="book-table.php" class="btn btn-outline-light btn-lg px-4 py-3">
                        <i class="bi bi-egg-fried me-2"></i> Reserve a Table
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="position-relative mt-5 mt-lg-0">
                    <img src="assets/images/hotel-lobby.jpg" alt="Hotel Lobby" class="img-fluid rounded-3 shadow-lg" style="max-height: 500px; width: 100%; object-fit: cover;">
                    <div class="position-absolute bottom-0 start-0 p-4">
                        <div class="bg-primary text-white p-3 rounded-2">
                            <h4 class="mb-0">Special Offer</h4>
                            <p class="mb-0">Book 3 nights, get 1 free!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                            <i class="bi bi-wifi fs-3"></i>
                        </div>
                        <h5 class="card-title">Free WiFi</h5>
                        <p class="card-text text-muted">High-speed internet throughout the property</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                            <i class="bi bi-water fs-3"></i>
                        </div>
                        <h5 class="card-title">Infinity Pool</h5>
                        <p class="card-text text-muted">Rooftop pool with panoramic city views</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                            <i class="bi bi-egg-fried fs-3"></i>
                        </div>
                        <h5 class="card-title">Fine Dining</h5>
                        <p class="card-text text-muted">Michelin-starred restaurant with seasonal menu</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                            <i class="bi bi-flower1 fs-3"></i>
                        </div>
                        <h5 class="card-title">Spa & Wellness</h5>
                        <p class="card-text text-muted">Full-service spa and state-of-the-art gym</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Rooms Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-3">Our Luxurious Rooms</h2>
                <p class="lead text-muted">Experience comfort and elegance in our carefully designed accommodations</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php
            $db = getDB();
            try {
                // Fix: Check if is_available column exists, if not, remove it from query
                $stmt = $db->query("SELECT * FROM rooms LIMIT 3");
                $rooms = $stmt->fetchAll();
                
                if (empty($rooms)) {
                    echo '<div class="col-12 text-center"><p class="text-muted">No rooms available at the moment.</p></div>';
                } else {
                    foreach ($rooms as $room):
                        // Use default image if not set
                        $image = !empty($room['image_url']) ? $room['image_url'] : 'https://via.placeholder.com/400x300/CCCCCC/666666?text=Room+Image';
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="card room-card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="position-relative">
                        <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>" style="height: 250px; object-fit: cover;">
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-success">Available</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($room['name']); ?></h5>
                            <h5 class="text-primary mb-0">$<?php echo number_format($room['price_per_night'], 2); ?><small class="text-muted fs-6">/night</small></h5>
                        </div>
                        <p class="card-text text-muted mb-3"><?php echo htmlspecialchars(substr($room['description'], 0, 100) . '...'); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-people me-1 text-muted"></i>
                                <small class="text-muted">Up to <?php echo $room['capacity']; ?> guests</small>
                            </div>
                            <a href="book-room.php?room=<?php echo $room['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-calendar-check me-1"></i> Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                    endforeach;
                }
            } catch (PDOException $e) {
                echo '<div class="col-12 text-center text-danger"><p>Error loading rooms: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
            }
            ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="rooms.php" class="btn btn-outline-primary btn-lg">
                View All Rooms <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Restaurant Section -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <img src="assets/images/restaurant.jpg" alt="Restaurant" class="img-fluid rounded-3 shadow" style="height: 400px; width: 100%; object-fit: cover;">
            </div>
            <div class="col-lg-6 ps-lg-5 mt-5 mt-lg-0">
                <h2 class="display-5 fw-bold mb-4">Gourmet Restaurant</h2>
                <p class="lead text-muted mb-4">Experience culinary excellence with our seasonal menu crafted by Chef Marco. Using locally sourced ingredients for an unforgettable dining experience.</p>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-3 border rounded-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-cup-straw fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Craft Cocktails</h6>
                                <small class="text-muted">Signature drinks</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-3 border rounded-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="bi bi-flower2 fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Garden Fresh</h6>
                                <small class="text-muted">Organic produce</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="menu.php" class="btn btn-primary btn-lg me-3">
                    <i class="bi bi-menu-button me-2"></i> View Menu
                </a>
                <a href="book-table.php" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-egg-fried me-2"></i> Book a Table
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-5 fw-bold mb-3">Guest Reviews</h2>
                <p class="lead text-muted">What our guests say about their experience</p>
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
                        <p class="card-text">"The room was absolutely beautiful with stunning city views. The restaurant exceeded all expectations!"</p>
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
                        <p class="card-text">"Perfect venue for our anniversary dinner. The service was impeccable and food was divine."</p>
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
                        <p class="card-text">"The infinity pool at sunset is magical. Will definitely return for another relaxing weekend getaway."</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
