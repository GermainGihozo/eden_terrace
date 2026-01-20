<?php
require_once '../includes/auth.php';
require_once 'includes/admin-header.php';

$db = getDB();
$message = "";

/* =========================
   DELETE TABLE
========================= */
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM tables WHERE id=?");
    $stmt->execute([(int)$_GET['delete']]);
    $message = "Table deleted successfully.";
}

/* =========================
   ADD TABLE
========================= */
if (isset($_POST['add_table'])) {
    $stmt = $db->prepare("
        INSERT INTO tables 
        (table_number, capacity, location, is_available)
        VALUES (?,?,?,1)
    ");
    $stmt->execute([
        $_POST['table_number'],
        $_POST['capacity'],
        $_POST['location']
    ]);
    $message = "Table added successfully.";
}

/* =========================
   TOGGLE AVAILABILITY
========================= */
if (isset($_GET['toggle'])) {
    $stmt = $db->prepare("
        UPDATE tables 
        SET is_available = IF(is_available=1,0,1)
        WHERE id=?
    ");
    $stmt->execute([(int)$_GET['toggle']]);
}

/* =========================
   FETCH TABLES
========================= */
$tables = $db->query("
    SELECT * FROM tables
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header mb-4">
    <h3>🍽 Manage Tables</h3>
    <p class="text-muted">Restaurant seating management</p>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<!-- ADD TABLE -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">➕ Add New Table</h6>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="table_number" class="form-control" placeholder="Table Number" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="capacity" class="form-control" placeholder="Capacity" min="1" required>
            </div>
            <div class="col-md-3">
                <select name="location" class="form-select" required>
                    <option value="main_hall">Main Hall</option>
                    <option value="patio">Patio</option>
                    <option value="private_room">Private Room</option>
                    <option value="bar">Bar</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button name="add_table" class="btn btn-primary">
                    Add Table
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABLE LIST -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">📋 Existing Tables</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Table No</th>
                    <th>Capacity</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tables as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><?= htmlspecialchars($t['table_number']) ?></td>
                    <td><?= $t['capacity'] ?></td>
                    <td><?= ucwords(str_replace('_',' ', $t['location'])) ?></td>
                    <td>
                        <span class="badge bg-<?= $t['is_available'] ? 'success' : 'danger' ?>">
                            <?= $t['is_available'] ? 'Available' : 'Unavailable' ?>
                        </span>
                    </td>
                    <td class="d-flex gap-1">
                        <a href="?toggle=<?= $t['id'] ?>" class="btn btn-sm btn-warning">
                            Toggle
                        </a>
                        <a href="?delete=<?= $t['id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this table?')">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
