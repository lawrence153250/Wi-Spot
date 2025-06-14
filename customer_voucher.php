<?php
session_start();

// Check if customer is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

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

// Database connection
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get customer ID from username in session
$customerId = null;
$stmt = $conn->prepare("SELECT customerId FROM customer WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if ($customer) {
    $customerId = $customer['customerId'];
} else {
    // Handle error - customer not found
    die("Customer not found");
}

// Fetch all available voucher batches (not expired and approved)
$currentDate = date('Y-m-d H:i:s');
$availableVouchersQuery = "
    SELECT vb.*, COUNT(vc.codeId) as remaining
    FROM voucher_batch vb
    LEFT JOIN voucher_code vc ON vb.batchId = vc.batchId AND vc.isGiven = FALSE
    WHERE vb.approvalStatus = 'approved' 
    AND vb.startDate <= '$currentDate' 
    AND vb.endDate >= '$currentDate'
    GROUP BY vb.batchId
";
$availableVouchersResult = $conn->query($availableVouchersQuery);
$availableVouchers = [];
if ($availableVouchersResult) {
    $availableVouchers = $availableVouchersResult->fetch_all(MYSQLI_ASSOC);
}

// Fetch vouchers specifically given to this customer
$customerVouchersQuery = "
    SELECT vc.*, vb.voucherName, vb.description, vb.discountRate, vb.endDate
    FROM voucher_code vc
    JOIN voucher_batch vb ON vc.batchId = vb.batchId
    WHERE vc.customerId = ? AND vc.isGiven = TRUE AND vc.isUsed = FALSE
    AND vb.endDate >= '$currentDate'
";
$stmt = $conn->prepare($customerVouchersQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$customerVouchers = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vouchers</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleslogin.css">
    <style>
        .voucher-section {
            padding: 2rem 0;
        }
        .voucher-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .voucher-card:hover {
            transform: translateY(-5px);
        }
        .voucher-card.yours {
            border-left: 5px solid #28a745;
        }
        .voucher-card.available {
            border-left: 5px solid #007bff;
        }
        .voucher-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .voucher-body {
            padding: 20px;
        }
        .discount-badge {
            font-size: 1.5rem;
            font-weight: bold;
            color: #dc3545;
        }
        .voucher-code {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
        }
        .section-title {
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
    </style>
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

<div class="container voucher-section">
    <h1 class="text-center mb-5">My Vouchers</h1>
    
    <!-- Customer's Personal Vouchers -->
    <div class="mb-5">
        <h2 class="section-title">Your Personal Vouchers</h2>
        <?php if (!empty($customerVouchers)): ?>
            <div class="row">
                <?php foreach ($customerVouchers as $voucher): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="voucher-card yours">
                            <div class="voucher-header">
                                <h5 class="mb-0"><?= htmlspecialchars($voucher['voucherName']) ?></h5>
                            </div>
                            <div class="voucher-body">
                                <div class="discount-badge mb-3"><?= htmlspecialchars($voucher['discountRate']) ?>% OFF</div>
                                <p class="text-muted"><?= htmlspecialchars($voucher['description']) ?></p>
                                <div class="mb-3">
                                    <span class="text-muted">Code:</span>
                                    <span class="voucher-code d-block mt-1"><?= htmlspecialchars($voucher['code']) ?></span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Expires: <?= date('M d, Y', strtotime($voucher['endDate'])) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                You don't have any personal vouchers yet.
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Available Vouchers -->
    <div class="mb-5">
        <h2 class="section-title">Available Vouchers</h2>
        <?php if (!empty($availableVouchers)): ?>
            <div class="row">
                <?php foreach ($availableVouchers as $voucher): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="voucher-card available">
                            <div class="voucher-header">
                                <h5 class="mb-0"><?= htmlspecialchars($voucher['voucherName']) ?></h5>
                            </div>
                            <div class="voucher-body">
                                <div class="discount-badge mb-3"><?= htmlspecialchars($voucher['discountRate']) ?>% OFF</div>
                                <p class="text-muted"><?= htmlspecialchars($voucher['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Valid until: <?= date('M d, Y', strtotime($voucher['endDate'])) ?></small>
                                    <?php if ($voucher['remaining'] > 0): ?>
                                        <span class="badge bg-primary"><?= $voucher['remaining'] ?> left</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                There are no available vouchers at the moment.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- footer -->
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