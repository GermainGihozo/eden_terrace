<?php
// admin/manage-users.php
$page_title = "Manage Users";
require_once 'includes/admin-header.php';

$db = getDB();
$message = "";

/* =========================
   DELETE USER
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id > 0) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $message = "User deleted successfully.";
    }
}

/* =========================
   ADD USER
========================= */
if (isset($_POST['add_user'])) {
    $stmt = $db->prepare("
        INSERT INTO users 
        (email, password, full_name, phone, address, role) 
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->execute([
        $_POST['email'],
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        $_POST['full_name'],
        $_POST['phone'],
        $_POST['address'],
        $_POST['role']
    ]);

    $message = "User added successfully.";
}

/* =========================
   UPDATE ROLE
========================= */
if (isset($_POST['update_role'])) {
    $stmt = $db->prepare("
        UPDATE users SET role=? WHERE id=?
    ");
    $stmt->execute([$_POST['role'], $_POST['user_id']]);
    $message = "User role updated.";
}

/* =========================
   FETCH USERS
========================= */
$users = $db->query("
    SELECT * FROM users 
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header mb-4">
    <h3>👤 Manage Users</h3>
    <p class="text-muted">Create, manage and control system users</p>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<!-- ADD USER -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">➕ Add New User</h6>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="col-md-4">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="col-md-4">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="phone" class="form-control" placeholder="Phone">
            </div>
            <div class="col-md-5">
                <input type="text" name="address" class="form-control" placeholder="Address">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="guest">Guest</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button name="add_user" class="btn btn-primary">
                    Add User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- USERS TABLE -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">📋 Users List</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td>
                            <form method="post" class="d-flex">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="role" class="form-select form-select-sm me-2">
                                    <option value="guest" <?= $u['role']=='guest'?'selected':'' ?>>Guest</option>
                                    <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                                </select>
                                <button name="update_role" class="btn btn-sm btn-outline-primary">
                                    Save
                                </button>
                            </form>
                        </td>
                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="?delete=<?= $u['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete this user?')">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
