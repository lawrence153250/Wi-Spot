<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
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

// Update database based on payment type
if ($paymentType === 'downpayment') {
    $newStatus = 'partially paid';
    $updateSql = "UPDATE booking SET paymentStatus = ?, amountPaid = ? WHERE bookingId = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sdi", $newStatus, $amountPaid, $bookingId);
} else {
    $newStatus = 'paid';
    $updateSql = "UPDATE booking SET paymentStatus = ?, amountPaid = price WHERE bookingId = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $newStatus, $bookingId);
}

if ($stmt->execute()) {
    // Payment successfully recorded
    unset($_SESSION['payment_info']);
    $message = "Payment successful! Status updated to: " . ucfirst($newStatus);
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
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3>Payment Successful</h3>
            </div>
            <div class="card-body">
                <p><?php echo htmlspecialchars($message); ?></p>
                <p>Booking ID: <?php echo htmlspecialchars($bookingId); ?></p>
                <p>Amount Paid: ₱<?php echo number_format($amountPaid, 2); ?></p>
                <a href="profile.php" class="btn btn-primary">Return to Profile</a>
            </div>
        </div>
    </div>
</body>
</html>