<?php
$page_title = "Register";
require_once 'includes/header.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'includes/db.php';

$error = '';
$success = '';
$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agree_terms = isset($_POST['agree_terms']);
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    } elseif (strlen($full_name) < 2) {
        $errors[] = "Full name must be at least 2 characters.";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (!$agree_terms) {
        $errors[] = "You must agree to the terms and conditions.";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email address is already registered.";
            }
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $db->prepare("
                INSERT INTO users (email, password, full_name, phone, role) 
                VALUES (?, ?, ?, ?, 'guest')
            ");
            
            $stmt->execute([$email, $hashed_password, $full_name, $phone]);
            $user_id = $db->lastInsertId();
            
            // Auto-login after registration
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_role'] = 'guest';
            
            $success = "
                <div class='text-center'>
                    <div class='mb-4'>
                        <i class='bi bi-check-circle-fill text-success display-1'></i>
                    </div>
                    <h3 class='mb-3'>Registration Successful! 🎉</h3>
                    <p class='lead mb-4'>Welcome to Eden Terrace, " . htmlspecialchars($full_name) . "!</p>
                    
                    <div class='alert alert-info'>
                        <h5>Your Account Details:</h5>
                        <p class='mb-1'><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                        <p class='mb-1'><strong>Name:</strong> " . htmlspecialchars($full_name) . "</p>
                        <p class='mb-0'><strong>Account Type:</strong> Guest Account</p>
                    </div>
                    
                    <div class='d-grid gap-2 col-lg-8 mx-auto'>
                        <a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a>
                        <a href='index.php' class='btn btn-outline-primary'>Back to Home</a>
                    </div>
                </div>
            ";
            
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
        }
    }
    
    if (!empty($errors)) {
        $error = '<ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul>';
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">Create Account</h2>
                        <p class="text-muted">Join Eden Terrace for exclusive benefits and easy booking</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Please fix the following:</strong>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success border-0 bg-light">
                            <?php echo $success; ?>
                        </div>
                    <?php else: ?>
                    
                    <form method="POST" id="registerForm" class="needs-validation" novalidate>
                        <!-- Personal Information -->
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-person-circle me-2"></i>Personal Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                           placeholder="John Smith" required>
                                    <div class="invalid-feedback">Please enter your full name.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="you@example.com" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                           placeholder="(123) 456-7890" required>
                                    <div class="invalid-feedback">Please enter your phone number.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="bi bi-shield-lock me-2"></i>Password</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Enter password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <small>Must be at least 8 characters with uppercase, lowercase, and number.</small>
                                    </div>
                                    <div class="invalid-feedback">Please enter a valid password.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" placeholder="Confirm password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Passwords must match.</div>
                                </div>
                            </div>
                            
                            <!-- Password strength indicator -->
                            <div class="mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="passwordStrengthText">Password strength</small>
                            </div>
                        </div>
                        
                        <!-- Terms & Conditions -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                                <label class="form-check-label" for="agree_terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> 
                                    and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a> *
                                </label>
                                <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                            </div>
                        </div>
                        
                        <!-- Submit -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus me-2"></i>Create Account
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none fw-semibold">Sign in here</a>
                            </p>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Account Registration</h6>
                <p>You must provide accurate and complete information when creating an account.</p>
                
                <h6>Booking Terms</h6>
                <p>All bookings are subject to availability and our cancellation policy.</p>
                
                <h6>Account Security</h6>
                <p>You are responsible for maintaining the confidentiality of your account credentials.</p>
                
                <h6>Privacy</h6>
                <p>We collect and use your information as described in our Privacy Policy.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Privacy Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Information Collection</h6>
                <p>We collect personal information necessary for booking and account management.</p>
                
                <h6>Data Usage</h6>
                <p>Your information is used to process bookings, provide customer support, and improve our services.</p>
                
                <h6>Data Protection</h6>
                <p>We implement security measures to protect your personal information.</p>
                
                <h6>Third Parties</h6>
                <p>We do not sell your personal information to third parties.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }
    
    if (toggleConfirmPassword && confirmPasswordInput) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }
    
    // Password strength indicator
    const passwordStrengthBar = document.getElementById('passwordStrength');
    const passwordStrengthText = document.getElementById('passwordStrengthText');
    
    if (passwordInput && passwordStrengthBar && passwordStrengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let text = 'Weak';
            let color = 'danger';
            
            // Length check
            if (password.length >= 8) strength += 25;
            
            // Contains lowercase
            if (/[a-z]/.test(password)) strength += 25;
            
            // Contains uppercase
            if (/[A-Z]/.test(password)) strength += 25;
            
            // Contains number
            if (/[0-9]/.test(password)) strength += 25;
            
            // Set progress bar
            passwordStrengthBar.style.width = strength + '%';
            
            // Set text and color
            if (strength >= 75) {
                text = 'Strong';
                color = 'success';
            } else if (strength >= 50) {
                text = 'Good';
                color = 'warning';
            } else if (strength >= 25) {
                text = 'Fair';
                color = 'info';
            }
            
            passwordStrengthBar.className = 'progress-bar bg-' + color;
            passwordStrengthText.textContent = 'Password strength: ' + text;
        });
    }
    
    // Password confirmation validation
    const confirmPassword = document.getElementById('confirm_password');
    if (passwordInput && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (passwordInput.value !== this.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Phone number formatting
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    }
    
    // Form validation
    const form = document.getElementById('registerForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>