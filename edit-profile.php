<?php
$page_title = "Edit Profile";
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

// Get current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($full_name)) {
        $error = "Full name is required.";
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = ?, phone = ?, address = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $phone, $address, $user_id]);
            
            // Update session
            $_SESSION['user_name'] = $full_name;
            
            $success = "Profile updated successfully!";
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
            $user['address'] = $address;
            
        } catch (PDOException $e) {
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4">Edit Profile</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" 
                                          rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>