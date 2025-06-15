<?php 
session_start(); 
// Display validation errors
if (isset($_SESSION['errors'])) {
    echo '<div class="alert alert-danger">';
    foreach ($_SESSION['errors'] as $error) {
        echo htmlspecialchars($error) . '<br>';
    }
    echo '</div>';
    unset($_SESSION['errors']);
}

// Repopulate form fields if available
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="registerstyle.css">
    <style>
        .error-message {
            color: red;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .is-invalid {
            border-color: #dc3545;
        }
    </style>
</head>
<body style="background-color: #f0f3fa;"> <nav class="navbar navbar-expand-lg navbar-dark" id="grad">
    <div class="container">
        <a class="navbar-brand" href="index.php"><img src="logoo.png" class="logo"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="booking.php">BOOKING</a></li>
                <li class="nav-item"><a class="nav-link" href="mapcoverage.php">MAP COVERAGE</a></li>
                <li class="nav-item"><a class="nav-link" href="customer_voucher.php">VOUCHERS</a></li>
                <li class="nav-item"><a class="nav-link" href="aboutus.php">ABOUT US</a></li>
            </ul>

            <?php if (isset($_SESSION['username'])): ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><?= htmlspecialchars($_SESSION['username']) ?> <i class="bi bi-person-circle"></i></a>
                    </li>
                </ul>
            <?php else: ?>
                <div class="auth-buttons d-flex flex-column flex-lg-row ms-lg-auto gap-2 mt-2 mt-lg-0">
                    <a class="btn btn-primary" href="login.php">LOGIN</a>
                    <a class="nav-link" href="register.php">SIGN UP</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row login-wrapper shadow rounded overflow-hidden">

        <!-- LEFT SIDE -->
        <div class="col-md-6 login-image position-relative text-white d-flex justify-content-center align-items-center">
            <div class="login-overlay-text text-center p-4">
                <h2 class="fw-bold">Join Wi-Spot Today</h2>
                <p class="lead">Empowering businesses, events, and communities with trusted connectivity.</p>
            </div>
        </div>

 <!-- RIGHT SIDE -->
        <div class="col-md-6 login-form p-5 bg-white">
            <h2 class="text-center mb-4">SIGN UP</h2>

            <!-- SUCCESS OR ERROR MESSAGE -->
            <?php
                if (isset($_GET['success']) && $_GET['success'] == 1) {
                    echo '<div class="alert alert-success text-center">Registration Successful!</div>';
                } elseif (isset($_GET['error'])) {
                    echo '<div class="alert alert-danger text-center">' . htmlspecialchars($_GET['error']) . '</div>';
                }
                
                // Display validation errors
                if (isset($_SESSION['errors'])) {
                    echo '<div class="alert alert-danger">';
                    foreach ($_SESSION['errors'] as $error) {
                        echo htmlspecialchars($error) . '<br>';
                    }
                    echo '</div>';
                    unset($_SESSION['errors']);
                }
                
                // Get form data for repopulation
                $formData = $_SESSION['form_data'] ?? [];
                unset($_SESSION['form_data']);
            ?>

            <form method="POST" action="register_code.php" id="registrationForm" novalidate>
                <div class="form-group mb-3">
                    <label for="firstname">First name</label>
                    <input type="text" id="firstname" name="firstname" class="form-control wi-input" 
                           value="<?= htmlspecialchars($formData['firstname'] ?? '') ?>" required>
                    <div class="error-message" id="firstnameError"></div>
                </div>
                <div class="form-group mb-3">
                    <label for="lastname">Last name</label>
                    <input type="text" id="lastname" name="lastname" class="form-control wi-input" 
                           value="<?= htmlspecialchars($formData['lastname'] ?? '') ?>" required>
                    <div class="error-message" id="lastnameError"></div>
                </div>
                <div class="form-group mb-3">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control wi-input" 
                           value="<?= htmlspecialchars($formData['username'] ?? '') ?>" required>
                    <div class="error-message" id="usernameError"></div>
                </div>
                <div class="form-group mb-3">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control wi-input" 
                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                    <div class="error-message" id="emailError"></div>
                </div>
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control wi-input" required>
                    <div class="error-message" id="passwordError"></div>
                    <small class="form-text text-muted">Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.</small>
                </div>
                <div class="form-group mb-3">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control wi-input" required>
                    <div class="error-message" id="confirmPasswordError"></div>
                </div>
                <div class="form-group mb-3">
                    <label for="birthday">Birthday</label>
                    <input type="date" id="birthday" name="birthday" class="form-control wi-input" 
                           value="<?= htmlspecialchars($formData['birthday'] ?? '') ?>" required>
                    <div class="error-message" id="birthdayError"></div>
                </div>
                <div class="form-group mb-3">
                    <label for="contactnumber">Contact Number</label>
                    <input type="text" id="contactnumber" name="contactnumber" class="form-control wi-input" 
                           value="<?= htmlspecialchars($formData['contactnumber'] ?? '') ?>" required>
                    <div class="error-message" id="contactnumberError"></div>
                    <small class="form-text text-muted">Format: 09 followed by 9 digits (11 digits total, e.g., 09123456789)</small>
                </div>
                <div class="form-group mb-3">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" class="form-control wi-input" 
                           value="<?= htmlspecialchars($formData['address'] ?? '') ?>" required>
                    <div class="error-message" id="addressError"></div>
                </div>
                <div class="form-group mb-4">
                    <label for="facebookProfile">Facebook Profile Link</label>
                    <input type="text" id="facebookProfile" name="facebookProfile" class="form-control wi-input" 
                           value="<?= htmlspecialchars($formData['facebookProfile'] ?? '') ?>" required>
                    <div class="error-message" id="facebookProfileError"></div>
                </div>
                <div class="d-grid text-center">
                    <button type="submit" name="register" class="btn btn-primary" id="submitBtn">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- footer -->
<div class="foot-container">
    <div class="foot-logo" style="text-align: center; margin-bottom: 1rem;">
    <img src="logofooter.png" alt="Wi-Spot Logo" style="width: 140px;">
  </div>
  <div class="foot-icons">
    <a href="https://www.facebook.com/WiSpotServices" class="bi bi-facebook" target="_blank"></a>
  </div>

  <hr>

  <div class="foot-policy">
    <div class="policy-links">
      <a href="termsofservice.php" target="_blank">TERMS OF SERVICE</a>
      <a href="copyrightpolicy.php" target="_blank">COPYRIGHT POLICY</a>
      <a href="privacypolicy.php" target="_blank">PRIVACY POLICY</a>
      <a href="contactus.php" target="_blank">CONTACT US</a>
    </div>
  </div>

  <hr>

  <div class="foot_text">
    <br>
    <p>&copy;2025 Wi-spot. All rights reserved. Wi-spot and related trademarks and logos are the property of Wi-spot. All other trademarks are the property of their respective owners.</p><br>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    
    // Client-side validation
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
        // Let the form submit if validation passes
    });
    
    function validateForm() {
        let isValid = true;
        
        // Reset error messages
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        // Validate required fields
        const requiredFields = ['firstname', 'lastname', 'username', 'email', 'password', 'confirm_password', 'birthday', 'contactnumber', 'address', 'facebookProfile'];
        requiredFields.forEach(field => {
            const element = document.getElementById(field);
            if (!element.value.trim()) {
                document.getElementById(field + 'Error').textContent = 'This field is required';
                element.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        // Only proceed with other validations if required fields are filled
        if (!isValid) return false;
        
        // Validate email
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            document.getElementById('emailError').textContent = 'Please enter a valid email address.';
            document.getElementById('email').classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate password
        const password = document.getElementById('password').value;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        if (!passwordRegex.test(password)) {
            document.getElementById('passwordError').textContent = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.';
            document.getElementById('password').classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate password match
        const confirmPassword = document.getElementById('confirm_password').value;
        if (password !== confirmPassword) {
            document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
            document.getElementById('confirm_password').classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate birthday (must be at least 18 years old)
        const birthday = document.getElementById('birthday').value;
        if (birthday) {
            const birthDate = new Date(birthday);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (age < 18) {
                document.getElementById('birthdayError').textContent = 'You must be at least 18 years old to register.';
                document.getElementById('birthday').classList.add('is-invalid');
                isValid = false;
            }
        }
        
        // Validate contact number (Philippine format: +63 followed by 10 digits)
        const contactNumber = document.getElementById('contactnumber').value;
        const contactRegex = /^09\d{9}$/;
        if (!contactRegex.test(contactNumber)) {
            document.getElementById('contactnumberError').textContent = 'Please enter a valid Philippine number starting with 09 followed by 9 digits (11 digits total).';
            document.getElementById('contactnumber').classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate Facebook profile link
        const facebookProfile = document.getElementById('facebookProfile').value;
        const facebookRegex = /^(https?:\/\/)?(www\.)?facebook\.com\/[a-zA-Z0-9._-]+\/?$/;
        if (!facebookRegex.test(facebookProfile)) {
            document.getElementById('facebookProfileError').textContent = 'Please enter a valid Facebook profile URL.';
            document.getElementById('facebookProfile').classList.add('is-invalid');
            isValid = false;
        }
        
        return isValid;
    }
});
</script>
</body>
</html>