<?php
// Start the session
session_start();
include 'chatbot-widget.html';
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
$paymentBalance = $booking['paymentBalance'];
$paymentStatus = $booking['paymentStatus'];

// Initialize variables
$amountToPay = 0;
$paymentType = '';

// Handle form submission for payment choice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paymentChoice'])) {
    $paymentChoice = $_POST['paymentChoice'];
    
    if ($paymentChoice === 'full') {
        $amountToPay = $paymentBalance;
        $paymentType = 'fullpayment';
    } elseif ($paymentChoice === 'half') {
        $amountToPay = ceil($paymentBalance / 2);
        $paymentType = 'partialpayment';
    }
    
    // Store in session for the next step
    $_SESSION['payment_amount'] = $amountToPay;
    $_SESSION['payment_type'] = $paymentType;
}

// If we're processing the payment details form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $amount = $_POST['amount'];
    $paymentType = $_POST['paymentType'];

    // Replace with your actual Xendit API key
    $xenditSecretKey = 'xnd_development_Cbav1oJEw2TZJjRg4y3Cu2PmJyeJ6VIo4psrih1UaieTgLlmsZ3XUbwBS5WGAXa';

    // Create invoice data
    $data = [
        'external_id' => 'invoice-' . time() . '-' . $bookingId,
        'payer_email' => $email,
        'description' => 'Wi-Spot Service Payment (' . ($paymentType === 'fullpayment' ? 'Full Payment' : 'Partial Payment') . ')',
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
        $error = 'Failed to create invoice: ' . (isset($responseData['message']) ? $responseData['message'] : 'Unknown error');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wi-Spot Payment</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
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
        .payment-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .payment-option {
            flex: 1;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }
        .payment-option.selected {
            border-color: #1E88E5;
            background-color: #f0f7ff;
        }
        .payment-option input[type="radio"] {
            display: none;
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
    <div class="payment-container">
        <h2 class="text-center mb-4">Wi-Spot Payment</h2>
        
        <div class="booking-details">
            <h4>Booking Details</h4>
            <p><strong>Package:</strong> <?php echo htmlspecialchars($booking['packageName']); ?></p>
            <p><strong>Total Price:</strong> ₱<?php echo number_format($totalPrice, 2); ?></p>
            <p><strong>Current Balance:</strong> ₱<?php echo number_format($paymentBalance, 2); ?></p>
            <p><strong>Payment Status:</strong> <?php echo htmlspecialchars(ucfirst($paymentStatus ?? 'pending')); ?></p>
        </div>
        
        <?php if (!isset($_SESSION['payment_amount'])): ?>
            <form method="post">
                <h4>Select Payment Option</h4>
                <div class="payment-options">
                    <label class="payment-option <?php echo ($paymentBalance == $totalPrice) ? 'selected' : ''; ?>">
                        <input type="radio" name="paymentChoice" value="full" <?php echo ($paymentBalance == $totalPrice) ? 'checked' : ''; ?> required>
                        Pay Full Balance (₱<?php echo number_format($paymentBalance, 2); ?>)
                    </label>
                    <label class="payment-option <?php echo ($paymentBalance != $totalPrice) ? 'selected' : ''; ?>">
                        <input type="radio" name="paymentChoice" value="half" <?php echo ($paymentBalance != $totalPrice) ? 'checked' : ''; ?>>
                        Pay 50% (₱<?php echo number_format(ceil($paymentBalance / 2), 2); ?>)
                    </label>
                </div>
                <button type="submit" class="btn-pay">Continue to Payment</button>
            </form>
        <?php else: ?>
            <div class="payment-info">
                <h4>Payment Information</h4>
                <p>You're making a <?php echo ($_SESSION['payment_type'] === 'fullpayment' ? 'full' : 'partial'); ?> payment.</p>
                
                <div class="payment-amount">
                    Amount to Pay: ₱<?php echo number_format($_SESSION['payment_amount'], 2); ?>
                </div>
            </div>
            
            <form method="post">
                <input type="hidden" name="amount" value="<?php echo $_SESSION['payment_amount']; ?>">
                <input type="hidden" name="paymentType" value="<?php echo $_SESSION['payment_type']; ?>">
                
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
        <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add interactive selection for payment options
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input').checked = true;
            });
        });
    </script>
</body>
</html>