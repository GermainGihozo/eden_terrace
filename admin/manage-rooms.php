<?php
// admin/manage-rooms.php
$page_title = "Manage Rooms";
require_once 'includes/admin-header.php';

$db = getDB();
$error = '';
$success = '';

// Handle actions
$action = $_GET['action'] ?? '';
$room_id = $_GET['id'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_id = $_POST['room_id'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price_per_night = floatval($_POST['price_per_night'] ?? 0);
        $capacity = intval($_POST['capacity'] ?? 1);
        $size = trim($_POST['size'] ?? '');
        $amenities = trim($_POST['amenities'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Validation
        if (empty($name) || $price_per_night <= 0 || $capacity < 1) {
            $error = "Please fill in all required fields with valid values.";
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $db->prepare("
                        INSERT INTO rooms (name, description, price_per_night, capacity, size, amenities, image_url, is_available)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $price_per_night, $capacity, $size, $amenities, $image_url, $is_available]);
                    $success = "Room '$name' has been added successfully.";
                } else {
                    $stmt = $db->prepare("
                        UPDATE rooms 
                        SET name = ?, description = ?, price_per_night = ?, capacity = ?, 
                            size = ?, amenities = ?, image_url = ?, is_available = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $price_per_night, $capacity, $size, $amenities, $image_url, $is_available, $room_id]);
                    $success = "Room '$name' has been updated successfully.";
                }
            } catch (PDOException $e) {
                $error = "Error saving room: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete' && $room_id) {
        try {
            // Check if room has bookings
            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status NOT IN ('cancelled', 'completed')");
            $stmt->execute([$room_id]);
            $has_bookings = $stmt->fetchColumn();
            
            if ($has_bookings) {
                $error = "Cannot delete room with active or upcoming bookings. Cancel the bookings first.";
            } else {
                $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->execute([$room_id]);
                $success = "Room has been deleted successfully.";
            }
        } catch (PDOException $e) {
            $error = "Error deleting room: " . $e->getMessage();
        }
    } elseif ($action === 'toggle_availability' && $room_id) {
        try {
            $stmt = $db->prepare("UPDATE rooms SET is_available = NOT is_available WHERE id = ?");
            $stmt->execute([$room_id]);
            $success = "Room availability updated.";
        } catch (PDOException $e) {
            $error = "Error updating room availability: " . $e->getMessage();
        }
    }
}

// Get rooms data
$rooms = [];
$room_stats = [];

try {
    $stmt = $db->query("
        SELECT r.*, 
               COUNT(b.id) as booking_count,
               SUM(CASE WHEN b.status NOT IN ('cancelled', 'completed') THEN 1 ELSE 0 END) as active_bookings
        FROM rooms r
        LEFT JOIN bookings b ON r.id = b.room_id AND b.booking_type = 'room'
        GROUP BY r.id
        ORDER BY r.price_per_night
    ");
    $rooms = $stmt->fetchAll();
    
    // Get room statistics
    $stats_stmt = $db->query("
        SELECT 
            COUNT(*) as total_rooms,
            SUM(CASE WHEN is_available = TRUE THEN 1 ELSE 0 END) as available_rooms,
            AVG(price_per_night) as avg_price,
            SUM(capacity) as total_capacity
        FROM rooms
    ");
    $room_stats = $stats_stmt->fetch();
    
} catch (PDOException $e) {
    $error = "Error loading rooms: " . $e->getMessage();
}

// Get room for editing
$edit_room = null;
if ($action === 'edit' && $room_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $edit_room = $stmt->fetch();
        
        if (!$edit_room) {
            $error = "Room not found.";
        }
    } catch (PDOException $e) {
        $error = "Error loading room: " . $e->getMessage();
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-2">Manage Rooms</h1>
            <p class="text-muted mb-0">Add, edit, and manage hotel rooms and suites.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <?php if ($action !== 'add' && $action !== 'edit'): ?>
            <a href="?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add New Room
            </a>
            <?php endif; ?>
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

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Room Form -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">
            <i class="bi bi-door-closed me-2"></i>
            <?php echo $action === 'add' ? 'Add New Room' : 'Edit Room'; ?>
        </h6>
    </div>
    <div class="card-body">
        <form method="POST" id="roomForm">
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <?php if ($action === 'edit'): ?>
            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
            <?php endif; ?>
            
            <div class="row g-3">
                <!-- Basic Information -->
                <div class="col-md-8">
                    <div class="card border mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Room Name *</label>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?php echo htmlspecialchars($edit_room['name'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Price per Night ($) *</label>
                                    <input type="number" class="form-control" name="price_per_night" 
                                           value="<?php echo $edit_room['price_per_night'] ?? ''; ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Capacity (Guests) *</label>
                                    <select class="form-select" name="capacity" required>
                                        <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                            <?php echo ($edit_room['capacity'] ?? 1) == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> guest<?php echo $i > 1 ? 's' : ''; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Room Size</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="size" 
                                               value="<?php echo htmlspecialchars($edit_room['size'] ?? ''); ?>" 
                                               placeholder="e.g., 35">
                                        <span class="input-group-text">m²</span>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($edit_room['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Amenities -->
                    <div class="card border">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Amenities</h6>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Amenities (comma-separated)</label>
                            <textarea class="form-control" name="amenities" rows="3" 
                                      placeholder="e.g., WiFi, TV, Air Conditioning, Mini Bar, Safe"><?php echo htmlspecialchars($edit_room['amenities'] ?? ''); ?></textarea>
                            <small class="text-muted">Separate amenities with commas</small>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar: Image & Settings -->
                <div class="col-md-4">
                    <!-- Image URL -->
                    <div class="card border mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Room Image</h6>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Image URL</label>
                            <input type="url" class="form-control" name="image_url" 
                                   value="<?php echo htmlspecialchars($edit_room['image_url'] ?? ''); ?>" 
                                   placeholder="https://example.com/room-image.jpg">
                            
                            <?php if (!empty($edit_room['image_url'])): ?>
                            <div class="mt-3">
                                <img src="<?php echo htmlspecialchars($edit_room['image_url']); ?>" 
                                     alt="Room Preview" class="img-fluid rounded" 
                                     style="max-height: 150px; width: auto;">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Availability -->
                    <div class="card border mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Availability</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_available" 
                                       id="is_available" value="1" 
                                       <?php echo ($edit_room['is_available'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_available">
                                    Available for Booking
                                </label>
                            </div>
                            <small class="text-muted">When disabled, room won't appear in booking options</small>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="card border">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>
                                    <?php echo $action === 'add' ? 'Add Room' : 'Update Room'; ?>
                                </button>
                                <a href="manage-rooms.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Room Statistics -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Rooms</h6>
                        <h3 class="mb-0"><?php echo $room_stats['total_rooms'] ?? 0; ?></h3>
                    </div>
                    <i class="bi bi-door-closed fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Available</h6>
                        <h3 class="mb-0"><?php echo $room_stats['available_rooms'] ?? 0; ?></h3>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Avg. Price</h6>
                        <h3 class="mb-0">$<?php echo number_format($room_stats['avg_price'] ?? 0, 2); ?></h3>
                    </div>
                    <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Capacity</h6>
                        <h3 class="mb-0"><?php echo $room_stats['total_capacity'] ?? 0; ?></h3>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rooms Grid -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-door-closed me-2"></i>All Rooms
        </h6>
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" class="form-control" placeholder="Search rooms..." id="searchRooms">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (empty($rooms)): ?>
        <div class="text-center py-5">
            <i class="bi bi-door-closed display-4 text-muted"></i>
            <h4 class="mt-3">No Rooms Found</h4>
            <p class="text-muted">No rooms have been added yet. Add your first room to get started.</p>
            <a href="?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add First Room
            </a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($rooms as $room): ?>
            <div class="col-xl-4 col-md-6">
                <div class="card room-card border-0 shadow-sm h-100">
                    <div class="position-relative">
                        <img src="<?php echo htmlspecialchars($room['image_url'] ?: 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?fit=crop&w=400&h=250'); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-<?php echo $room['is_available'] ? 'success' : 'danger'; ?>">
                                <?php echo $room['is_available'] ? 'Available' : 'Booked'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($room['name']); ?></h5>
                            <h5 class="text-primary mb-0">$<?php echo number_format($room['price_per_night'], 2); ?></h5>
                        </div>
                        
                        <p class="card-text text-muted mb-3">
                            <?php echo htmlspecialchars(substr($room['description'] ?? 'No description', 0, 80)); ?>...
                        </p>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-people text-muted me-2"></i>
                                    <small class="text-muted"><?php echo $room['capacity']; ?> guests</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-rulers text-muted me-2"></i>
                                    <small class="text-muted"><?php echo $room['size'] ?? 'N/A'; ?> m²</small>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($room['amenities']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Amenities:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <?php 
                                $amenities = explode(',', $room['amenities']);
                                foreach (array_slice($amenities, 0, 3) as $amenity):
                                ?>
                                <span class="badge bg-light text-dark border"><?php echo trim($amenity); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($amenities) > 3): ?>
                                <span class="badge bg-light text-dark border">+<?php echo count($amenities) - 3; ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    <?php echo $room['booking_count']; ?> bookings
                                </small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="?action=edit&id=<?php echo $room['id']; ?>">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_availability">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-toggle-<?php echo $room['is_available'] ? 'off' : 'on'; ?> me-2"></i>
                                                <?php echo $room['is_available'] ? 'Mark as Booked' : 'Mark as Available'; ?>
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this room? This action cannot be undone.')">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Table View (Alternative) -->
    <div class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">
                    Showing <?php echo count($rooms); ?> rooms
                </small>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="viewToggle">
                <label class="form-check-label" for="viewToggle">
                    Grid View
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Most Expensive</small>
                        <strong class="d-block">
                            <?php 
                            $most_expensive = array_reduce($rooms, function($carry, $item) {
                                return (!$carry || $item['price_per_night'] > $carry['price_per_night']) ? $item : $carry;
                            });
                            echo htmlspecialchars($most_expensive['name'] ?? 'N/A');
                            ?>
                        </strong>
                        <small class="text-muted">$<?php echo number_format($most_expensive['price_per_night'] ?? 0, 2); ?>/night</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Most Booked</small>
                        <strong class="d-block">
                            <?php 
                            $most_booked = array_reduce($rooms, function($carry, $item) {
                                return (!$carry || $item['booking_count'] > $carry['booking_count']) ? $item : $carry;
                            });
                            echo htmlspecialchars($most_booked['name'] ?? 'N/A');
                            ?>
                        </strong>
                        <small class="text-muted"><?php echo $most_booked['booking_count'] ?? 0; ?> bookings</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Highest Capacity</small>
                        <strong class="d-block">
                            <?php 
                            $highest_capacity = array_reduce($rooms, function($carry, $item) {
                                return (!$carry || $item['capacity'] > $carry['capacity']) ? $item : $carry;
                            });
                            echo htmlspecialchars($highest_capacity['name'] ?? 'N/A');
                            ?>
                        </strong>
                        <small class="text-muted"><?php echo $highest_capacity['capacity'] ?? 0; ?> guests</small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Currently Available</small>
                        <strong class="d-block text-success"><?php echo $room_stats['available_rooms'] ?? 0; ?></strong>
                        <small class="text-muted">out of <?php echo $room_stats['total_rooms'] ?? 0; ?> rooms</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.room-card {
    transition: transform 0.3s, box-shadow 0.3s;
}

.room-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchRooms');
    const roomCards = document.querySelectorAll('.room-card');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            roomCards.forEach(card => {
                const roomName = card.querySelector('.card-title').textContent.toLowerCase();
                const roomDesc = card.querySelector('.card-text').textContent.toLowerCase();
                
                if (roomName.includes(searchTerm) || roomDesc.includes(searchTerm)) {
                    card.parentElement.style.display = 'block';
                } else {
                    card.parentElement.style.display = 'none';
                }
            });
        });
    }
    
    // View toggle
    const viewToggle = document.getElementById('viewToggle');
    const roomsContainer = document.querySelector('.row.g-4');
    
    if (viewToggle) {
        viewToggle.addEventListener('change', function() {
            if (this.checked) {
                roomsContainer.classList.remove('row-cols-1');
                roomsContainer.classList.add('row-cols-xl-4', 'row-cols-md-2', 'row-cols-1');
            } else {
                // Switch to table view (simplified - in production would reload with table view)
                alert('Table view would be implemented here. Currently showing grid view.');
            }
        });
    }
    
    // Form validation for room forms
    const roomForm = document.getElementById('roomForm');
    if (roomForm) {
        roomForm.addEventListener('submit', function(e) {
            const price = this.querySelector('input[name="price_per_night"]').value;
            if (parseFloat(price) <= 0) {
                e.preventDefault();
                alert('Price per night must be greater than 0.');
            }
        });
    }
});
</script>
<?php endif; ?>

<?php
require_once 'includes/admin-footer.php';
?>