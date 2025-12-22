<?php
$page_title = "Forgot Password";
require_once 'includes/header.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // In a real application, you would:
        // 1. Check if email exists
        // 2. Generate reset token
        // 3. Send email with reset link
        // 4. Store token in database
        
        $success = "If an account exists with that email, you will receive password reset instructions.";
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">Forgot Password</h2>
                        <p class="text-muted">Enter your email to reset your password</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <div class="text-center">
                            <a href="login.php" class="btn btn-primary">Back to Login</a>
                        </div>
                    <?php else: ?>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="you@example.com" required>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">Back to Login</a>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>