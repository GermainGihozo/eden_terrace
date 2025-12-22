<?php
$page_title = "Rooms & Suites";
require_once 'includes/header.php';
require_once 'includes/config.php';
require_once 'includes/db.php';  // Add this line
require_once 'includes/auth.php';

$db = getDB();

// Get all rooms
try {
    // $stmt = $db->query("SELECT * FROM rooms ORDER BY price_per_night");
    $stmt = $db->query("SELECT * FROM rooms ORDER BY price_per_night");
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $rooms = [];
    $error = "Unable to load rooms at this time.";
}
?>

<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">Our Rooms & Suites</h1>
                <p class="lead mb-0">Experience luxury and comfort in our carefully designed accommodations</p>
            </div>
        </div>
    </div>
</section>

<!-- Room Filters -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-primary active" data-filter="all">All Rooms</button>
                    <button class="btn btn-outline-primary" data-filter="single">Single</button>
                    <button class="btn btn-outline-primary" data-filter="double">Double</button>
                    <button class="btn btn-outline-primary" data-filter="suite">Suite</button>
                    <button class="btn btn-outline-primary" data-filter="luxury">Luxury</button>
                </div>
            </div>
            <div class="col-lg-4">
                <select class="form-select" id="sortRooms">
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="capacity">Capacity</option>
                    <option value="name">Name A-Z</option>
                </select>
            </div>
        </div>
    </div>
</section>

<!-- Rooms Grid -->
<section class="py-5">
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($rooms)): ?>
            <div class="text-center py-5">
                <i class="bi bi-house-x display-1 text-muted"></i>
                <h3 class="mt-3">No Rooms Available</h3>
                <p class="text-muted">Please check back later or contact us for availability.</p>
            </div>
        <?php else: ?>
            <div class="row g-4" id="roomsContainer">
                <?php foreach ($rooms as $room): 
                    $image = !empty($room['image_url']) ? $room['image_url'] : 'https://via.placeholder.com/400x300/CCCCCC/666666?text=' . urlencode($room['name']);
                    $amenities = !empty($room['amenities']) ? explode(',', $room['amenities']) : ['WiFi', 'TV', 'AC'];
                ?>
                <div class="col-xl-4 col-lg-6" data-room-type="<?php echo strtolower($room['name']); ?>">
                    <div class="card room-card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                 class="card-img-top room-image" 
                                 alt="<?php echo htmlspecialchars($room['name']); ?>"
                                 style="height: 250px; object-fit: cover;">
                            <div class="position-absolute top-0 end-0 m-3">
                                <?php if ($room['is_available'] ?? true): ?>
                                    <span class="badge bg-success">Available</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Booked</span>
                                <?php endif; ?>
                            </div>
                            <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(transparent, rgba(0,0,0,0.7));">
                                <h5 class="text-white mb-0"><?php echo htmlspecialchars($room['name']); ?></h5>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-people text-muted me-2"></i>
                                        <small class="text-muted">Max <?php echo $room['capacity']; ?> guests</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-rulers text-muted me-2"></i>
                                        <small class="text-muted"><?php echo $room['size'] ?? '35'; ?> m²</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <h4 class="text-primary mb-0">$<?php echo number_format($room['price_per_night'], 2); ?></h4>
                                    <small class="text-muted">per night</small>
                                </div>
                            </div>
                            
                            <p class="card-text text-muted mb-4">
                                <?php echo htmlspecialchars(substr($room['description'] ?? 'Luxurious room with all amenities', 0, 120)); ?>...
                            </p>
                            
                            <div class="mb-4">
                                <h6 class="mb-2">Amenities:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach (array_slice($amenities, 0, 4) as $amenity): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-check-circle me-1"></i><?php echo trim($amenity); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (count($amenities) > 4): ?>
                                        <span class="badge bg-light text-dark border">+<?php echo count($amenities) - 4; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="book-room.php?room_id=<?php echo $room['id']; ?>" 
                                   class="btn btn-primary">
                                    <i class="bi bi-calendar-check me-2"></i>Book Now
                                </a>
                                <button class="btn btn-outline-primary view-details" 
                                        data-room-id="<?php echo $room['id']; ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#roomModal">
                                    <i class="bi bi-info-circle me-2"></i>View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Room Details Modal -->
<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomModalLabel">Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="roomModalBody">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Ready to Book Your Stay?</h2>
        <p class="lead mb-4">Experience luxury and comfort at Eden Terrace. Book directly for the best rates.</p>
        <a href="book-room.php" class="btn btn-light btn-lg px-5">
            <i class="bi bi-calendar-check me-2"></i>Book Your Room
        </a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Room filtering
    const filterButtons = document.querySelectorAll('[data-filter]');
    const rooms = document.querySelectorAll('.room-card').parentElement;
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter rooms
            document.querySelectorAll('[data-room-type]').forEach(room => {
                if (filter === 'all' || room.getAttribute('data-room-type').includes(filter)) {
                    room.style.display = 'block';
                } else {
                    room.style.display = 'none';
                }
            });
        });
    });
    
    // Room sorting
    document.getElementById('sortRooms').addEventListener('change', function() {
        const sortBy = this.value;
        const container = document.getElementById('roomsContainer');
        const roomCards = Array.from(container.children);
        
        roomCards.sort((a, b) => {
            const priceA = parseFloat(a.querySelector('.text-primary').textContent.replace('$', ''));
            const priceB = parseFloat(b.querySelector('.text-primary').textContent.replace('$', ''));
            const nameA = a.querySelector('h5').textContent;
            const nameB = b.querySelector('h5').textContent;
            
            switch(sortBy) {
                case 'price_low': return priceA - priceB;
                case 'price_high': return priceB - priceA;
                case 'name': return nameA.localeCompare(nameB);
                default: return 0;
            }
        });
        
        // Reorder DOM
        roomCards.forEach(card => container.appendChild(card));
    });
    
    // Room details modal
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const roomId = this.getAttribute('data-room-id');
            
            fetch(`api/get-room-details.php?id=${roomId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('roomModalBody').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('roomModalBody').innerHTML = 
                        '<div class="alert alert-danger">Error loading room details.</div>';
                });
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>