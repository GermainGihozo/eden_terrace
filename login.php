<?php
// Start session FIRST
session_start();

$page_title = "Login";
require_once 'includes/header.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
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
                
                header('Location: dashboard.php');
                exit();
            }
            
            // Normal password verification
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                header('Location: dashboard.php');
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
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="text-center mb-4">Login</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="guest@test.com" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" value="password123" required>
                            <small class="text-muted">Use 'password123' for test accounts</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Test Accounts:</p>
                        <p>guest@test.com / password123</p>
                        <p>admin@edenterrace.com / password123</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>