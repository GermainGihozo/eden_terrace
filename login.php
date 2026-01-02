<?php
// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Login";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/db.php';
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // DIRECT COMPARISON FOR TESTING (remove in production)
            if ($password === 'password123') {
                // Manual override for testing
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            }
            
            // Normal password verification
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = "Invalid password. Try 'password123'";
            }
        } else {
            $error = "User not found.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 text-center py-4">
                    <h2 class="fw-bold mb-0">Sign In</h2>
                </div>
                <div class="card-body p-4 p-sm-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="admin@edenterrace.com" required>
                        </div>
                                          
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   value="password123" required>
                            <small class="text-muted">Use 'password123' for test accounts</small>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-2">Test Accounts:</p>
                        <div class="small">
                            <div class="mb-1">
                                <strong>Admin:</strong> admin@edenterrace.com / password123
                                <br><span class="text-muted">(Goes to Admin Panel)</span>
                            </div>
                            <div>
                                <strong>Guest:</strong> guest@test.com / password123
                                <br><span class="text-muted">(Goes to User Dashboard)</span>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <p class="mb-0">Don't have an account? 
                            <a href="register.php" class="text-decoration-none">Sign up</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>