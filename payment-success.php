<?php
// Start the session
session_start();

// Set session timeout to 15 minutes (900 seconds)
$inactive = 900; 

// Check if timeout variable is set
if (isset($_SESSION['timeout'])) {
    // Calculate the session's lifetime
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        // Logout and redirect to login page
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
}

// Update timeout with current time
$_SESSION['timeout'] = time();

require_once 'config.php';

// Verify payment was successful
if (!isset($_GET['bookingId']) || !isset($_GET['paymentType']) || !isset($_GET['amount'])) {
    die("Invalid request parameters");
}

$bookingId = intval($_GET['bookingId']);
$paymentType = $_GET['paymentType'];
$amountPaid = floatval($_GET['amount']);

// Verify the payment with session data
if (!isset($_SESSION['payment_info']) || $_SESSION['payment_info']['bookingId'] != $bookingId) {
    die("Payment verification failed");
}

// First, get the current payment balance and price
$stmt = $conn->prepare("SELECT paymentBalance, price FROM booking WHERE bookingId = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$stmt->bind_result($currentBalance, $totalPrice);
$stmt->fetch();
$stmt->close();

// Calculate new balance
$newBalance = $currentBalance - $amountPaid;

// Determine new payment status
if ($newBalance <= 0) {
    $newStatus = 'Paid';
    $newBalance = 0; // Ensure balance doesn't go negative
} else {
    $newStatus = 'Partially Paid';
}

// Update database (only using paymentBalance now)
$updateSql = "UPDATE booking SET 
              paymentStatus = ?, 
              paymentBalance = ?
              WHERE bookingId = ?";

$stmt = $conn->prepare($updateSql);
$stmt->bind_param("sdi", $newStatus, $newBalance, $bookingId);

if ($stmt->execute()) {
    // Payment successfully recorded
    unset($_SESSION['payment_info']);
    $message = "Payment successful! Status updated to: " . $newStatus;
    
    // If balance was fully paid, update booking status to confirmed if it was pending
    if ($newStatus === 'Paid') {
        $conn->query("UPDATE booking SET bookingStatus = 'Confirmed' 
                     WHERE bookingId = $bookingId AND bookingStatus = 'Pending'");
    }
} else {
    $message = "Payment received but database update failed. Please contact support.";
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Success</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .card {
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            font-weight: bold;
        }
        .payment-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .progress {
            height: 20px;
            margin-bottom: 20px;
        }
        .progress-bar {
            background-color: #28a745;
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
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3>Payment Successful</h3>
            </div>
            <div class="card-body">
                <div class="payment-details">
                    <p><?php echo htmlspecialchars($message); ?></p>
                    <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($bookingId); ?></p>
                    <p><strong>Amount Paid:</strong> ₱<?php echo number_format($amountPaid, 2); ?></p>
                    <p><strong>Remaining Balance:</strong> ₱<?php echo number_format($newBalance, 2); ?></p>
                    
                    <!-- Payment progress bar -->
                    <?php $paidPercentage = (($totalPrice - $newBalance) / $totalPrice) * 100; ?>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?php echo $paidPercentage; ?>%" 
                             aria-valuenow="<?php echo $paidPercentage; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?php echo round($paidPercentage); ?>% Paid
                        </div>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <a href="profile.php" class="btn btn-primary">Return to Profile</a>
                    <?php if ($newBalance > 0): ?>
                        <a href="payment.php?bookingId=<?php echo $bookingId; ?>" class="btn btn-outline-primary">
                            Pay Remaining Balance (₱<?php echo number_format($newBalance, 2); ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
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
</body>
</html>