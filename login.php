<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $user = null;
    $userType = null;

    // Check customer table first
    $sql = "SELECT * FROM customer WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userType = 'customer';
    } 
    // If not found in customer table, check admin table
    else {
        $sql = "SELECT * FROM admin WHERE userName = '$username'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userType = 'admin';
        } 
        // If not found in admin table, check staff table
        else {
            $sql = "SELECT * FROM staff WHERE userName = '$username'";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $userType = 'staff';
            }
        }
    }

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $username;
        $_SESSION['userType'] = $userType;
        
        // Store additional user info based on type
        if ($userType === 'customer') {
            $_SESSION['userlevel'] = $user['userLevel'] ?? 'customer';
        } else {
            $_SESSION['userlevel'] = $userType; // 'admin' or 'staff'
        }

        // Redirect based on user type
        switch ($_SESSION['userlevel']) {
            case 'admin':
                header("Location: adminhome.php");
                break;
            case 'staff':
                header("Location: staff_dashboard.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();
    } else {
        $error_message = "Invalid username or password!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styleslogin.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" id="grad">
        <div class="nav-container">
            <a class="navbar-brand" href="index.php"><img src="logoo.png" class="logo"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse d-flex justify-content-between align-items-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="booking.php">BOOKING</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mapcoverage.php">MAP COVERAGE</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="aboutus.php">ABOUT US</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['username'])): ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php"><?php echo $_SESSION['username']; ?> <i class="bi bi-person-circle"></i></a>
                        </li>
                    </ul>
                <?php else: ?>
                    <div class="auth-buttons ms-auto">
                        <a class="btn btn-primary" href="login.php">LOGIN</a>
                        <a class="nav-link" href="register.php">SIGN UP</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
    <div class="row login-wrapper shadow rounded overflow-hidden">
        <div class="col-md-6 login-form p-5 bg-white">
            <h2 class="text-center mb-4">LOGIN FORM</h2>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group mb-4 position-relative">
    <label for="username" class="form-label">Username</label>
    <div class="input-icon">
        <i class='bx bxs-user'></i>
        <input type="text" id="username" name="username" class="form-control wi-input" required>
    </div>
</div>

<div class="form-group mb-4 position-relative">
    <label for="password" class="form-label">Password</label>
    <div class="input-icon">
        <i class='bx bxs-lock-alt'></i>
        <input type="password" id="password" name="password" class="form-control wi-input" required>
        <button type="button" class="toggle-password" onclick="togglePassword()">
            <i id="toggleIcon" class="bi bi-eye"></i>
        </button>
    </div>
</div>
                <div class="d-grid text-center mt-3">
                        <button type="submit" name="login" class="btn btn-primary">LOGIN</button>
                    </div>
                <div class="text-center mt-3">
                    <a href="forgotpassword.php" class="forgot-password-link">Forgot Password?</a>
                </div>
            </form>
            <div class="text-center mt-3">
                <p>Don't have an account? <a href="register.php" class="register-link">Register now</a></p>
            </div>
        </div>

        
        <div class="col-md-6 login-image position-relative text-white d-flex justify-content-center align-items-center">
            <div class="login-overlay-text text-center">
                <h2 class="fw-bold">Connect Smarter with Wi-Spot</h2>
                    <p class="lead">Reliable connectivity for businesses, events, and communities—anywhere you need it.</p>
                    <p>Sign in to manage your services and stay connected, all in one platform.</p>
            </div>
        </div>
    </div>
</div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
    </script>
    <div class="foot-container">
            <div class="foot-logo" style="text-align: center; margin-bottom: 1rem;">
            <img src="logofooter.png" alt="Wi-Spot Logo" style="width: 140px;">
        </div>
        <div class="foot-icons">
            <a href="https://www.youtube.com/" class="bi bi-youtube" target="_blank"></a>
            <a href="https://web.facebook.com/" class="bi bi-facebook" target="_blank"></a>
            <a href="https://www.instagram.com/" class="bi bi-instagram" target="_blank"></a>
            <a href="https://www.tiktok.com/" class="bi bi-tiktok" target="_blank"></a>
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
            <p>Wi-spot is available in English, French, German, Italian, Spanish, and more.</p><br>
            <p>&copy;2025 Wi-spot. All rights reserved. Wi-spot and related trademarks and logos are the property of Wi-spot. All other trademarks are the property of their respective owners.</p><br>
            <p>This webpage is for educational purposes only and no copyright infringement is intended.</p>
        </div>
        </div>
    <script src="scriptlogin.js"></script>
</body>
</html>