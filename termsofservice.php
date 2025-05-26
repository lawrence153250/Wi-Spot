<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
      
        body {
            background-color: #E6F2F4;
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            color: #ffffff; 
        }

        .navbar-brand img {
            max-height: 50px; 
        }

       
        .container {
            width: 50%;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff; 
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            border-radius: 20px;
        }

        h1, h2 {
            text-align: left;
            color: #F6D110;
            font-family: 'Garet', sans-serif;
            margin-bottom: 20px;
        }

        p {
            font-family: Arial, sans-serif;
            color: #333;
            font-size: 16px;
            line-height: 1.6;
        }

   
        .foot-container {
            background-color: #E6F2F4; 
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
        }

        .foot-icons a {
            color: #333;
            font-size: 24px;
            margin-right: 10px;
        }

        .foot-policy .foot-policy {
            color: #333;
        }

        .foot_text {
            color: #333; 
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" id="grad">
    <div class="nav-container">
        <a class="navbar-brand" href="index.php"><img src="logo.jpg"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item active">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="booking.php">Booking</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mapcoverage.php">Map Coverage</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="aboutus.php">About Us</a>
                </li>
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><?php echo $_SESSION['username']; ?> <i class="bi bi-person-circle"></i></a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Log In</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Sign Up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
    <h1>Terms of Service</h1>
    <div>
        <h2><b>Introduction</b></h2>
        <p>Welcome to Wi-Spot Services! These terms of service outline the rules and regulations for the use of Wi-Spot Services's Website.</p>
    </div>
    
    <div>
        <h2><b>License</b></h2>
        <p>Unless otherwise stated, Wi-Spot Services and/or its licensors own the intellectual property rights for all material on Wi-Spot Services. All intellectual property rights are reserved. You may view and/or print pages from https://www.Wi-Spot Services.com for your own personal use subject to restrictions set in these terms of service.</p>
    </div>
    
    <div>
        <h2><b>User Comments</b></h2>
        <p>Certain parts of this website offer the opportunity for users to post and exchange opinions, information, material and data ('Comments') in areas of the website. Wi-Spot Services does not screen, edit, publish or review Comments prior to their appearance on the website and Comments do not reflect the views or opinions of Wi-Spot Services, its agents or affiliates. Comments reflect the view and opinion of the person who posts such view or opinion. To the extent permitted by applicable laws Wi-Spot Services shall not be responsible or liable for the Comments or for any loss cost, liability, damages or expenses caused and or suffered as a result of any use of and/or posting of and/or appearance of the Comments on this website.</p>
    </div>
    
    <div>
        <h2><b>Contact Us</b></h2>
        <p>For any questions, concern or clarification please contact us through our email [wispot.servicesph@gmail.com] or through our phone number +639123456789.</p>
    </div>
</div>
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
