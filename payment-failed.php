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

$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get parameters from URL
$bookingId = isset($_GET['bookingId']) ? intval($_GET['bookingId']) : 0;

// Verify booking ID
if ($bookingId <= 0) {
    die("Invalid booking ID");
}

// Fetch booking details to show user
$stmt = $conn->prepare("SELECT b.*, p.packageName FROM booking b 
                       JOIN package p ON b.packageId = p.packageId 
                       WHERE b.bookingId = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found");
}

$booking = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Determine if this was a downpayment or full payment attempt
$paymentType = isset($_GET['paymentType']) ? $_GET['paymentType'] : 'unknown';
$attemptedAmount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .payment-failed-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .alert-icon {
            font-size: 72px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .booking-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .btn-retry {
            background-color: #dc3545;
            color: white;
        }
        .btn-retry:hover {
            background-color: #bb2d3b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-failed-container text-center">
            <div class="alert-icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <h2 class="text-danger mb-4">Payment Failed</h2>
            <p class="lead">We couldn't process your payment. Please try again or contact support if the problem persists.</p>
            
            <div class="booking-details text-start">
                <h5>Booking Details:</h5>
                <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($bookingId); ?></p>
                <p><strong>Package:</strong> <?php echo htmlspecialchars($booking['packageName']); ?></p>
                <p><strong>Total Price:</strong> ₱<?php echo number_format($booking['price'], 2); ?></p>
                <p><strong>Payment Attempt:</strong> 
                    <?php 
                    if ($paymentType === 'downpayment') {
                        echo "Downpayment (₱" . number_format($attemptedAmount, 2) . ")";
                    } elseif ($paymentType === 'fullpayment') {
                        echo "Full Payment (₱" . number_format($attemptedAmount, 2) . ")";
                    } else {
                        echo "Payment attempt";
                    }
                    ?>
                </p>
                <p><strong>Current Status:</strong> <?php echo htmlspecialchars(ucfirst($booking['paymentStatus'] ?? 'pending')); ?></p>
            </div>
            
            <div class="d-grid gap-2 d-md-block mt-4">
                <?php if ($paymentType === 'downpayment' || $paymentType === 'fullpayment'): ?>
                    <a href="xendit_payment.php?bookingId=<?php echo $bookingId; ?>" class="btn btn-retry btn-lg me-md-2">
                        <i class="bi bi-arrow-repeat"></i> Try Again
                    </a>
                <?php endif; ?>
                <a href="profile.php" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-person"></i> Back to Profile
                </a>
                <a href="contactus.php" class="btn btn-outline-primary btn-lg ms-md-2">
                    <i class="bi bi-headset"></i> Contact Support
                </a>
            </div>
            
            <div class="mt-4 text-muted">
                <small>If you believe this is an error, please contact our support team with your booking ID.</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>