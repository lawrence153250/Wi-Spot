<?php session_start(); ?>
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
</head>
<body style="background-color: #f0f3fa;">
<nav class="navbar navbar-expand-lg navbar-dark" id="grad">
    <div class="nav-container">
        <a class="navbar-brand" href="index.php"><img src="logoo.png" class="logo"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse d-flex justify-content-between align-items-center" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="booking.php">BOOKING</a></li>
                <li class="nav-item"><a class="nav-link" href="mapcoverage.php">MAP COVERAGE</a></li>
                <li class="nav-item"><a class="nav-link" href="aboutus.php">ABOUT US</a></li>
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
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert alert-success text-center">Registration Successful!</div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <form method="POST" action="register_code.php">
                <div class="form-group mb-3">
                    <label for="firstname">First name</label>
                    <input type="text" id="firstname" name="firstname" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-3">
                    <label for="lastname">Last name</label>
                    <input type="text" id="lastname" name="lastname" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-3">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-3">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-3">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-3">
                    <label for="birthday">Birthday</label>
                    <input type="date" id="birthday" name="birthday" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-3">
                    <label for="contactnumber">Contact Number</label>
                    <input type="text" id="contactnumber" name="contactnumber" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-3">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" class="form-control wi-input" required>
                </div>
                <div class="form-group mb-4">
                    <label for="facebookProfile">Facebook Profile Link</label>
                    <input type="text" id="facebookProfile" name="facebookProfile" class="form-control wi-input" required>
                </div>
                <div class="d-grid text-center">
                    <button type="submit" name="register" class="btn btn-primary">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FOOTER -->
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
</div>
</body>
</html>
