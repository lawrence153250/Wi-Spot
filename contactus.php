<?php
session_start();
require_once 'config.php'; // Include your database configuration

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = $conn->real_escape_string(htmlspecialchars($_POST['name']));
    $email = $conn->real_escape_string(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $subject = $conn->real_escape_string(htmlspecialchars($_POST['subject']));
    $message = $conn->real_escape_string(htmlspecialchars($_POST['message']));

    // Insert into database
    $sql = "INSERT INTO contacts (name, email, subject, message) 
            VALUES ('$name', '$email', '$subject', '$message')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['contact_success'] = "Thank you for your message! We'll get back to you soon.";
    } else {
        $_SESSION['contact_error'] = "Sorry, there was an error submitting your form: " . $conn->error;
    }
    
    // Redirect to prevent form resubmission
    header("Location: contactus.php");
    exit();
}

// Include chatbot if exists
if (file_exists('chatbot-widget.html')) {
    include 'chatbot-widget.html';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Wi-spot</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .contact-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .contact-header h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .contact-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .contact-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .contact-method {
            flex: 1 1 300px;
            text-align: center;
            padding: 20px;
            margin: 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .contact-method:hover {
            background: #e9ecef;
            transform: translateY(-5px);
        }
        
        .contact-method i {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .contact-form .form-group {
            margin-bottom: 20px;
        }
        
        .contact-form label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            display: block;
        }
        
        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .contact-form textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .submit-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: block;
            margin: 0 auto;
        }
        
        .submit-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .map-container {
            margin-top: 40px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Navbar and footer styles from your original code */
        #grad {
            background: linear-gradient(to right, #1a2980, #26d0ce);
        }
        
        .logo {
            height: 40px;
        }
        
        .foot-container {
            padding: 30px 0;
            color: white;
        }
        
        .foot-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        
        .foot-icons a {
            color: white;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" id="grad">
    <div class="container">
        <a class="navbar-brand" href="index.php"><img src="logoo.png" class="logo"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="booking.php">BOOKING</a></li>
                <li class="nav-item"><a class="nav-link" href="mapcoverage.php">MAP COVERAGE</a></li>
                <li class="nav-item"><a class="nav-link" href="customer_voucher.php">VOUCHERS</a></li>
                <li class="nav-item"><a class="nav-link" href="aboutus.php">ABOUT US</a></li>
            </ul>
            <?php if (isset($_SESSION['username'])): ?>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><?= htmlspecialchars($_SESSION['username']) ?> <i class="bi bi-person-circle"></i></a>
                    </li>
                </ul>
            <?php else: ?>
                <div class="d-flex">
                    <a class="btn btn-primary me-2" href="login.php">LOGIN</a>
                    <a class="btn btn-outline-light" href="register.php">SIGN UP</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="contact-container">
    <div class="contact-header">
        <h2>Contact Us</h2>
        <p>Have questions or feedback? We'd love to hear from you!</p>
    </div>
    
    <?php if (isset($_SESSION['contact_success'])): ?>
        <div class="success-message">
            <?= $_SESSION['contact_success']; ?>
            <?php unset($_SESSION['contact_success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['contact_error'])): ?>
        <div class="error-message">
            <?= $_SESSION['contact_error']; ?>
            <?php unset($_SESSION['contact_error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="contact-info">
        <div class="contact-method">
            <i class="bi bi-telephone-fill"></i>
            <h4>Call Us</h4>
            <p>+1 (555) 123-4567</p>
            <p>Mon-Fri: 9am-6pm</p>
        </div>
        
        <div class="contact-method">
            <i class="bi bi-envelope-fill"></i>
            <h4>Email Us</h4>
            <p>support@wi-spot.com</p>
            <p>Response within 24 hours</p>
        </div>
        
        <div class="contact-method">
            <i class="bi bi-geo-alt-fill"></i>
            <h4>Visit Us</h4>
            <p>123 Tech Street</p>
            <p>San Francisco, CA 94107</p>
        </div>
    </div>
    
    <form action="contactus.php" method="POST" class="contact-form">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" required>
        </div>
        
        <div class="form-group">
            <label for="message">Your Message</label>
            <textarea id="message" name="message" required></textarea>
        </div>
        
        <button type="submit" class="submit-btn">Send Message</button>
    </form>

</div>

<div class="foot-container" id="grad">
    <div class="container">
        <div class="foot-icons">
            <a href="https://www.youtube.com/" class="text-light" target="_blank"><i class="bi bi-youtube"></i></a>
            <a href="https://web.facebook.com/" class="text-light" target="_blank"><i class="bi bi-facebook"></i></a>
            <a href="https://www.instagram.com/" class="text-light" target="_blank"><i class="bi bi-instagram"></i></a>
            <a href="https://www.tiktok.com/" class="text-light" target="_blank"><i class="bi bi-tiktok"></i></a>
        </div>
        <hr>
        <div class="row text-center">
            <div class="col-md-3 mb-2">
                <a class="text-light text-decoration-none" href="termsofservice.php">Terms of Service</a>
            </div>
            <div class="col-md-3 mb-2">
                <a class="text-light text-decoration-none" href="copyrightpolicy.php">Copyright Policy</a>
            </div>
            <div class="col-md-3 mb-2">
                <a class="text-light text-decoration-none" href="privacypolicy.php">Privacy Policy</a>
            </div>
            <div class="col-md-3 mb-2">
                <a class="text-light text-decoration-none" href="contactus.php">Contact Us</a>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p class="mb-1">Wi-spot is available in English, French, German, Italian, Spanish, and more.</p>
            <p class="mb-1">
                &copy;2025 Wi-spot. All rights reserved. Wi-spot and related trademarks and logos are the property of Wi-spot.
            </p>
            <p class="mb-0">
                This webpage is for educational purposes only and no copyright infringement is intended.
            </p>
        </div>
    </div>
</div>

</body>
</html>
<?php
// Close database connection
$conn->close();
?>