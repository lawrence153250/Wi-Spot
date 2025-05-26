<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
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
    
    <h1>About Us</h1>
    <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Aliquid consequatur hic deleniti eveniet quam? Veritatis ut commodi rerum ipsam atque incidunt obcaecati libero ab provident minima? Temporibus vero commodi voluptatibus.</p>
    <div class="foot-container" id="grad">
        <div class="foot-icons">
            <a href="https://www.youtube.com/" class="bi bi-youtube text-altlight" target=”_blank”></a>
            <a href="https://web.facebook.com/" class="bi bi-facebook text-altlight" target=”_blank”></a>
            <a href="https://www.instagram.com/" class="bi bi-instagram text-altlight" target=”_blank”></a>
            <a href="https://www.tiktok.com/" class="bi bi-tiktok text-altlight" target=”_blank”></a>
        </div>
        <hr>
        <div class="foot-policy">
            <div class="row">
                <div class="col-md-3">
                    <a class="foot-policy text-altlight" href="termsofservice.php" target="_blank">Terms of Service</a>
                </div>
                <div class="col-md-3">
                    <a class="foot-policy text-altlight" href="copyrightpolicy.php" target="_blank">Copyright Policy</a>
                </div>
                <div class="col-md-3">
                    <a class="foot-policy text-altlight" href="privacypolicy.php" target="_blank">Privacy Policy</a>
                </div>
                <div class="col-md-3">
                    <a class="foot-policy text-altlight" href="contactus.php" target=”_blank”>Contact Us</a>
                </div>
            </div>
        </div>
        <hr>
        <div class="foot_text text-altlight">
            <p>Wi-spot is available in English, French, German, Italian, Spanish, and more.</p><br>
            <p>
                &copy;2025 Wi-spot. All rights reserved. Wi-spot and related trademarks and logos are the property of Wi-spot. All other trademarks are the property of their respective owners.
            </p><br>
            <p>
                This webpage is for educational purposes only and no copyright infringement is intended.
            </p>
        </div>
    </div>
</body>
</html>
