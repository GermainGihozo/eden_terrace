<?php
$page_title = "Change Password";
require_once 'includes/header.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters.";
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            $success = "Password changed successfully!";
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4">Change Password</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required>
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>