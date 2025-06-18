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

// Get booking ID from URL parameter
$bookingId = isset($_GET['bookingId']) ? intval($_GET['bookingId']) : 0;

if ($bookingId <= 0) {
    die("Invalid booking ID");
}

// Fetch booking details from database
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

$totalPrice = $booking['price'];
$paymentStatus = $booking['paymentStatus'];
$paymentType = 'downpayment'; // Default to downpayment

// Determine payment type and amount
if ($paymentStatus === 'Unpaid' || $paymentStatus === null) {
    $amountToPay = ceil($totalPrice / 2); // Downpayment is half, rounded up
    $paymentType = 'downpayment';
} elseif ($paymentStatus === 'partially paid') {
    $amountToPay = $totalPrice - $booking['amountPaid']; // Remaining balance
    $paymentType = 'fullpayment';
} else {
    die("This booking is already fully paid");
}

// Replace with your actual Xendit API key
$xenditSecretKey = 'xnd_development_Cbav1oJEw2TZJjRg4y3Cu2PmJyeJ6VIo4psrih1UaieTgLlmsZ3XUbwBS5WGAXa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $amount = $_POST['amount'];
    $paymentType = $_POST['paymentType'];

    // Create invoice data
    $data = [
        'external_id' => 'invoice-' . time() . '-' . $bookingId,
        'payer_email' => $email,
        'description' => 'Wi-Spot Service Payment (' . ($paymentType === 'downpayment' ? 'Downpayment' : 'Full Payment') . ')',
        'amount' => (int)$amount,
        'currency' => 'PHP',
        'customer' => [
            'given_names' => $name,
            'email' => $email
        ],
        'success_redirect_url' => 'C:\xampp\htdocs\Wi-Spot\payment-success.php?bookingId=' . $bookingId . '&paymentType=' . $paymentType . '&amount=' . $amount,
        'failure_redirect_url' => 'C:\xampp\htdocs\Wi-Spot\payment-failed.php?bookingId=' . $bookingId
    ];

    // Call Xendit API
    $ch = curl_init('https://api.xendit.co/v2/invoices');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $xenditSecretKey . ":");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $responseData = json_decode($response, true);

    if (isset($responseData['invoice_url'])) {
        // Store payment information in session for verification later
        $_SESSION['payment_info'] = [
            'bookingId' => $bookingId,
            'amount' => $amount,
            'paymentType' => $paymentType,
            'invoice_id' => $responseData['id']
        ];
        
        header('Location: ' . $responseData['invoice_url']);
        exit();
    } else {
        $error = 'Failed to create invoice: ' . (isset($responseData['message'])) ? $responseData['message'] : 'Unknown error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wi-Spot Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            padding: 40px;
        }
        .payment-container {
            max-width: 600px;
            margin: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .booking-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .payment-amount {
            font-size: 24px;
            font-weight: bold;
            color: #1E88E5;
            margin: 15px 0;
        }
        .btn-pay {
            background: #1E88E5;
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            margin-top: 20px;
            cursor: pointer;
        }
        .btn-pay:hover {
            background: #1565C0;
        }
        .error {
            color: red;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h2 class="text-center mb-4">Wi-Spot Payment</h2>
        
        <div class="booking-details">
            <h4>Booking Details</h4>
            <p><strong>Package:</strong> <?php echo htmlspecialchars($booking['packageName']); ?></p>
            <p><strong>Total Price:</strong> ₱<?php echo number_format($totalPrice, 2); ?></p>
            <p><strong>Current Payment Status:</strong> <?php echo htmlspecialchars(ucfirst($paymentStatus ?? 'pending')); ?></p>
        </div>
        
        <div class="payment-info">
            <h4>Payment Information</h4>
            <?php if ($paymentType === 'downpayment'): ?>
                <p>You're making a downpayment (50% of total price).</p>
            <?php else: ?>
                <p>You're paying the remaining balance.</p>
            <?php endif; ?>
            
            <div class="payment-amount">
                Amount to Pay: ₱<?php echo number_format($amountToPay, 2); ?>
            </div>
        </div>
        
        <form method="post">
            <input type="hidden" name="amount" value="<?php echo $amountToPay; ?>">
            <input type="hidden" name="paymentType" value="<?php echo $paymentType; ?>">
            
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <button type="submit" class="btn-pay">Pay Now with Xendit</button>
            
            <?php if (isset($error)): ?>
                <div class="error mt-3"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>