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

if (!isset($_SESSION['username'])) {
    echo '<div class="alert">You need to log in first. Redirecting to login page...</div>';
    header("Refresh: 3; url=login.php");
    exit();
}

$username = $_SESSION['username'];

$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user information safely using prepared statements
$stmt = $conn->prepare("SELECT * FROM customer WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $customerId = $user['customerId']; // Fetch customerId from the customer table
} else {
    die("User not found.");
}

$stmt->close();

// Fetch booking data for the logged-in customer
$sql = "SELECT 
            b.bookingId,
            b.timestamp AS date_booking_created,
            b.dateOfBooking AS date_of_start,
            b.dateOfReturn AS date_of_return,
            b.eventLocation AS event_location,
            p.packageName AS package_chosen,
            b.price AS total_price,
            b.bookingStatus AS booking_status,
            b.paymentStatus AS payment_status
        FROM booking b
        JOIN package p ON b.packageId = p.packageId
        WHERE b.customerId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Function to format a date string to "F j, Y" format
function formatDate($dateString) {
    return date("F j, Y", strtotime($dateString));
}

// Format the dates in the bookings array
foreach ($bookings as &$booking) {
    $booking['date_booking_created'] = formatDate($booking['date_booking_created']);
    $booking['date_of_start'] = formatDate($booking['date_of_start']);
    $booking['date_of_return'] = formatDate($booking['date_of_return']);
}
unset($booking); // Break the reference with the last element

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
                <div class="auth-buttons ms-auto">
                    <a class="btn btn-primary" href="login.php">LOGIN</a>
                    <a class="nav-link" href="register.php">SIGN UP</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
    <div class="container mt-4">
        <h2>My Transactions</h2>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date Booking Created</th>
                        <th>Date of Start</th>
                        <th>Date of Return</th>
                        <th>Event Location</th>
                        <th>Package Chosen</th>
                        <th>Total Price</th>
                        <th>Booking Status</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['date_booking_created']); ?></td>
                                <td><?php echo htmlspecialchars($booking['date_of_start']); ?></td>
                                <td><?php echo htmlspecialchars($booking['date_of_return']); ?></td>
                                <td><?php echo htmlspecialchars($booking['event_location']); ?></td>
                                <td><?php echo htmlspecialchars($booking['package_chosen']); ?></td>
                                <td>₱<?php echo number_format($booking['total_price'], 2); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['booking_status']); ?>
                                    <?php if ($booking['booking_status'] === 'Completed'): ?>
                                        <br>
                                        <a href="customer_feedback.php?bookingId=<?php echo $booking['bookingId']; ?>" class="btn btn-primary btn-sm mt-1">Give Feedback</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['payment_status']); ?>
                                    <br>
                                    <a href="xendit_payment.php?bookingId=<?php echo $booking['bookingId']; ?>" class="btn btn-success btn-sm mt-1">Proceed to Payment</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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