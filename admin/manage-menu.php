<?php
require_once '../includes/auth.php';
require_once 'includes/admin-header.php';

$db = getDB();

// Add menu item
if (isset($_POST['add_item'])) {
    $stmt = $db->prepare("
        INSERT INTO menu_items 
        (name, description, price, category, prep_time, ingredients, allergies, is_spicy, is_available)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['description'],
        $_POST['price'],
        $_POST['category'],
        $_POST['prep_time'],
        $_POST['ingredients'],
        $_POST['allergies'],
        isset($_POST['is_spicy']) ? 1 : 0
    ]);
}

// Toggle availability
if (isset($_GET['toggle'])) {
    $stmt = $db->prepare("UPDATE menu_items SET is_available = NOT is_available WHERE id = ?");
    $stmt->execute([$_GET['toggle']]);
}

// Fetch menu
$menu = $db->query("SELECT * FROM menu_items ORDER BY created_at DESC")->fetchAll();
?>

<div class="page-header">
    <h3>Manage Menu Items</h3>
</div>

<div class="card mb-4">
    <div class="card-header">Add Menu Item</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <input name="name" class="form-control" placeholder="Item Name" required>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option>appetizer</option>
                    <option>main_course</option>
                    <option>dessert</option>
                    <option>beverage</option>
                    <option>alcohol</option>
                    <option>special</option>
                </select>
            </div>
            <div class="col-md-3">
                <input name="prep_time" class="form-control" placeholder="Prep Time (e.g 20 mins)">
            </div>
            <div class="col-12">
                <textarea name="description" class="form-control" placeholder="Description"></textarea>
            </div>
            <div class="col-6">
                <input name="ingredients" class="form-control" placeholder="Ingredients">
            </div>
            <div class="col-6">
                <input name="allergies" class="form-control" placeholder="Allergies">
            </div>
            <div class="col-md-2">
                <div class="form-check mt-2">
                    <input type="checkbox" name="is_spicy" class="form-check-input">
                    <label class="form-check-label">Spicy</label>
                </div>
            </div>
            <div class="col-md-4">
                <button name="add_item" class="btn btn-success w-100">Add Item</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Menu Items</div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Spicy</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menu as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><?= ucfirst($m['category']) ?></td>
                    <td>$<?= number_format($m['price'],2) ?></td>
                    <td><?= $m['is_spicy'] ? '🌶️' : '—' ?></td>
                    <td>
                        <span class="badge bg-<?= $m['is_available'] ? 'success' : 'danger' ?>">
                            <?= $m['is_available'] ? 'Available' : 'Hidden' ?>
                        </span>
                    </td>
                    <td>
                        <a href="?toggle=<?= $m['id'] ?>" class="btn btn-sm btn-warning">Toggle</a>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
