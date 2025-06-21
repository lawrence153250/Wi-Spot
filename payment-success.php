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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
</body>
</html>