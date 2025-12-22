<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: text/html');

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Item ID is required.</div>';
    exit;
}

$item_id = intval($_GET['id']);
$db = getDB();

try {
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        echo '<div class="alert alert-danger">Menu item not found.</div>';
        exit;
    }
    
    $image = !empty($item['image_url']) ? $item['image_url'] : 
             'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?fit=crop&w-800&h=600';
    
    $category_names = [
        'appetizer' => 'Appetizer',
        'main_course' => 'Main Course',
        'dessert' => 'Dessert',
        'beverage' => 'Beverage',
        'alcohol' => 'Wine & Cocktails',
        'special' => 'Chef Special'
    ];
    ?>
    
    <div class="row">
        <div class="col-md-6">
            <img src="<?php echo htmlspecialchars($image); ?>" 
                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                 class="img-fluid rounded mb-3">
        </div>
        <div class="col-md-6">
            <h3 class="mb-3"><?php echo htmlspecialchars($item['name']); ?></h3>
            
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <span class="badge bg-primary"><?php echo $category_names[$item['category']] ?? ucfirst($item['category']); ?></span>
                    <?php if ($item['category'] === 'special'): ?>
                        <span class="badge bg-danger ms-2">Limited Time</span>
                    <?php endif; ?>
                </div>
                <h4 class="text-primary mb-0">$<?php echo number_format($item['price'], 2); ?></h4>
            </div>
            
            <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
            
            <?php if (!empty($item['ingredients'])): ?>
            <div class="mb-4">
                <h6>Ingredients:</h6>
                <p class="text-muted"><?php echo htmlspecialchars($item['ingredients']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($item['allergies'])): ?>
            <div class="mb-4">
                <h6>Allergens:</h6>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Contains: <?php echo htmlspecialchars($item['allergies']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mb-4">
                <h6>Preparation:</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="bi bi-clock text-primary me-2"></i>
                        Prep time: <?php echo $item['prep_time'] ?? '15-20'; ?> minutes
                    </li>
                    <?php if ($item['is_spicy'] ?? false): ?>
                    <li class="mb-2">
                        <i class="bi bi-thermometer-high text-danger me-2"></i>
                        Spicy level: Medium
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="d-grid gap-2">
                <button class="btn btn-primary btn-lg add-to-cart-modal"
                        data-item-id="<?php echo $item['id']; ?>"
                        data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                        data-item-price="<?php echo $item['price']; ?>">
                    <i class="bi bi-plus-circle me-2"></i> Add to Order
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
    
    <?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error loading item details.</div>';
}
?>