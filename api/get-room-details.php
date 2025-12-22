<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: text/html');

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Room ID is required.</div>';
    exit;
}

$room_id = intval($_GET['id']);
$db = getDB();

try {
    $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    
    if (!$room) {
        echo '<div class="alert alert-danger">Room not found.</div>';
        exit;
    }
    
    $image = !empty($room['image_url']) ? $room['image_url'] : 'https://via.placeholder.com/800x500';
    $amenities = !empty($room['amenities']) ? explode(',', $room['amenities']) : ['WiFi', 'TV', 'Air Conditioning', 'Mini Bar', 'Safe'];
    ?>
    
    <div class="row">
        <div class="col-md-6">
            <img src="<?php echo htmlspecialchars($image); ?>" 
                 alt="<?php echo htmlspecialchars($room['name']); ?>" 
                 class="img-fluid rounded mb-3">
            <div class="d-grid">
                <a href="book-room.php?room_id=<?php echo $room['id']; ?>" 
                   class="btn btn-primary btn-lg">
                    <i class="bi bi-calendar-check me-2"></i>Book This Room
                </a>
            </div>
        </div>
        <div class="col-md-6">
            <h3 class="mb-3"><?php echo htmlspecialchars($room['name']); ?></h3>
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h4 class="text-primary">$<?php echo number_format($room['price_per_night'], 2); ?> <small class="text-muted">/night</small></h4>
                </div>
                <div>
                    <span class="badge bg-success fs-6">Available</span>
                </div>
            </div>
            
            <p class="text-muted mb-4"><?php echo htmlspecialchars($room['description']); ?></p>
            
            <div class="row mb-4">
                <div class="col-6">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-people fs-5 text-primary me-2"></i>
                        <div>
                            <small class="text-muted d-block">Capacity</small>
                            <strong>Up to <?php echo $room['capacity']; ?> guests</strong>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-rulers fs-5 text-primary me-2"></i>
                        <div>
                            <small class="text-muted d-block">Size</small>
                            <strong><?php echo $room['size'] ?? '35'; ?> m²</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <h5 class="mb-3">Amenities</h5>
            <div class="row">
                <?php foreach ($amenities as $amenity): ?>
                <div class="col-6 mb-2">
                    <i class="bi bi-check-circle text-success me-2"></i>
                    <?php echo trim($amenity); ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4">
                <h5 class="mb-3">Room Features</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check me-2 text-success"></i>King-size bed</li>
                    <li class="mb-2"><i class="bi bi-check me-2 text-success"></i>En-suite bathroom</li>
                    <li class="mb-2"><i class="bi bi-check me-2 text-success"></i>City view</li>
                    <li class="mb-2"><i class="bi bi-check me-2 text-success"></i>Daily housekeeping</li>
                    <li class="mb-2"><i class="bi bi-check me-2 text-success"></i>24-hour room service</li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error loading room details.</div>';
}
?>