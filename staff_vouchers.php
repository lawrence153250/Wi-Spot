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
// Check if user is logged in and is staff
if (!isset($_SESSION['username']) || $_SESSION['userlevel'] !== 'staff') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current staff ID
$username = $_SESSION['username'];
$staffQuery = $conn->prepare("SELECT staffId FROM staff WHERE username = ?");
$staffQuery->bind_param("s", $username);
$staffQuery->execute();
$staffResult = $staffQuery->get_result();

if ($staffResult->num_rows === 0) {
    die("Error: Staff account not found for username: " . htmlspecialchars($username));
}

$staffData = $staffResult->fetch_assoc();
$staffId = $staffData['staffId'];
$staffQuery->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucherType = $_POST['voucherType'];
    $voucherName = $_POST['voucherName'];
    $description = $_POST['description'];
    $discountRate = $_POST['discountRate'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $quantity = $_POST['quantity'];

    // Insert voucher batch
    $stmt = $conn->prepare("INSERT INTO voucher_batch (staffId, voucherType, voucherName, description, discountRate, startDate, endDate, quantity) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdssi", $staffId, $voucherType, $voucherName, $description, $discountRate, $startDate, $endDate, $quantity);
    
    if ($stmt->execute()) {
        $batchId = $stmt->insert_id;
        
        // Generate voucher codes
        $codes = generateVoucherCodes($quantity);
        
        // Insert voucher codes
        $insertCode = $conn->prepare("INSERT INTO voucher_code (batchId, code) VALUES (?, ?)");
        foreach ($codes as $code) {
            $insertCode->bind_param("is", $batchId, $code);
            $insertCode->execute();
        }
        
        $_SESSION['success_message'] = "Voucher batch created successfully with $quantity codes!";
        header("Location: staff_vouchers.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error creating voucher batch: " . $conn->error;
    }
}

// Function to generate random alphanumeric codes
function generateVoucherCodes($quantity) {
    $codes = [];
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    for ($i = 0; $i < $quantity; $i++) {
        $code = '';
        for ($j = 0; $j < 12; $j++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        $codes[] = $code;
    }
    
    return $codes;
}

// Fetch existing vouchers
$vouchers = [];
$result = $conn->query("SELECT * FROM voucher_batch ORDER BY createdAt DESC");
if ($result) {
    $vouchers = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Vouchers</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            padding: 20px 0;
            position: fixed;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            font-size: 2vh;
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .sidebar-menu li:hover {
            background-color: #34495e;
        }
        
        .sidebar-menu li.active {
            background-color: #3498db;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-header h2 {
            color: #2c3e50;
            font-size: 24px;
        }
        
        /* Table Styles */
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .bookings-table th, 
        .bookings-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .bookings-table th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
        }
        
        .bookings-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: 600;
        }
        .voucher-form {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .voucher-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .voucher-status {
            font-weight: bold;
        }
        
        .status-pending {
            color: #f39c12;
        }
        
        .status-approved {
            color: #2ecc71;
        }
        
        .status-declined {
            color: #e74c3c;
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header h1, 
            .sidebar-menu li span {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            
            .bookings-table {
                display: block;
                overflow-x: auto;
            }
        }
        
    </style>
</head>
<body>
<!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a class="navbar-brand" href="staff_dashboard.php"><img src="logo.png"></a>
        </div>
        <ul class="sidebar-menu">
            <li><a class="nav-link" href="staff_dashboard.php">DASHBOARD</a></li>
            <li><a class="nav-link" href="staff_booking.php">BOOKINGS</a></li>
            <li><a class="nav-link" href="staff_accounts.php">ACCOUNTS</a></li>
            <li><a class="nav-link" href="staff_packages.php">PACKAGES</a></li>
            <li class="active"><a class="nav-link" href="staff_vouchers.php">VOUCHERS</a></li>
            <li><a class="nav-link" href="staff_inventory.php">INVENTORY</a></li>
            <li><a class="nav-link" href="staff_reports.php">REPORTS</a></li>
            <li><a class="nav-link" href="staff_feedbacks.php">FEEDBACKS</a></li>
            <li><a class="nav-link" href="staff_announcements.php">ANNOUNCEMENTS</a></li>
            <li><a class="nav-link" href="staff_resetpass.php">RESET PASSWORD</a></li>
            <li><span><a class="nav-link" href="logout.php">LOGOUT</a></span></li>
        </ul>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="page-header">
            <h2>VOUCHER MANAGEMENT</h2>
            <div class="user-info"> 
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="bi bi-person-circle"></i>
            </div>
        </div>
        
        <!-- Display messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <!-- Create Voucher Form -->
        <div class="voucher-form">
            <h4>Create New Voucher Batch</h4>
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="voucherType" class="form-label">Voucher Type</label>
                        <select class="form-select" id="voucherType" name="voucherType" required>
                            <option value="">Select type</option>
                            <option value="Birthday">Birthday</option>
                            <option value="Referral">Referral</option>
                            <option value="Returning Customer">Returning Customer</option>
                            <option value="Limited-Time">Limited-Time</option>
                            <option value="First Rental">First Rental</option>
                            <option value="Seasonal">Seasonal</option>
                            <option value="VIP">VIP</option>
                            <option value="Bundle">Bundle</option>
                            <option value="Flash Sale">Flash Sale</option>
                            <option value="Corporate Discount">Corporate Discount</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="voucherName" class="form-label">Voucher Name</label>
                        <input type="text" class="form-control" id="voucherName" name="voucherName" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="discountRate" class="form-label">Discount Rate (%)</label>
                        <input type="number" class="form-control" id="discountRate" name="discountRate" min="1" max="100" required>
                    </div>
                    <div class="col-md-4">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="datetime-local" class="form-control" id="startDate" name="startDate" required>
                    </div>
                    <div class="col-md-4">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="datetime-local" class="form-control" id="endDate" name="endDate" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="quantity" class="form-label">Number of Vouchers to Generate</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="1000" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Vouchers</button>
            </form>
        </div>
        
        <!-- Existing Vouchers -->
        <h4 class="mt-4">Existing Voucher Batches</h4>
        <?php if (empty($vouchers)): ?>
            <div class="alert alert-info">No voucher batches found.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($vouchers as $voucher): ?>
                    <div class="col-md-6">
                        <div class="voucher-card">
                            <div class="d-flex justify-content-between">
                                <h5><?php echo htmlspecialchars($voucher['voucherName']); ?></h5>
                                <span class="voucher-status status-<?php echo strtolower($voucher['approvalStatus']); ?>">
                                    <?php echo htmlspecialchars($voucher['approvalStatus']); ?>
                                </span>
                            </div>
                            <p class="text-muted"><?php echo htmlspecialchars($voucher['voucherType']); ?></p>
                            <p><?php echo htmlspecialchars($voucher['description']); ?></p>
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-primary"><?php echo $voucher['discountRate']; ?>% OFF</span>
                                <small class="text-muted">
                                    Valid: <?php echo date('M d, Y', strtotime($voucher['startDate'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($voucher['endDate'])); ?>
                                </small>
                            </div>
                            <div class="mt-2">
                                <small>Created: <?php echo date('M d, Y H:i', strtotime($voucher['createdAt'])); ?></small>
                                <a href="voucher_codes.php?batchId=<?php echo $voucher['batchId']; ?>" class="btn btn-sm btn-outline-primary float-end">View Codes</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>