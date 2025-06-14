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

// 1. FIRST GET THE STAFF ID PROPERLY
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_equipment'])) {
    $itemName = $conn->real_escape_string($_POST['item_name']);
    $itemType = $conn->real_escape_string($_POST['item_type']);
    $quantity = (int)$_POST['quantity'];
    $description = $conn->real_escape_string($_POST['description']);
    
    // 2. FIXED INSERT STATEMENT WITH PROPER COLUMNS
    $stmt = $conn->prepare("INSERT INTO inventory 
                          (itemName, itemType, quantity, description, staffId, status, dateAdded) 
                          VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("ssisi", $itemName, $itemType, $quantity, $description, $staffId);
    
    if ($stmt->execute()) {
        $success_message = "Equipment added successfully! Waiting for admin approval.";
    } else {
        $error_message = "Error adding equipment: " . $conn->error;
    }
    $stmt->close();
}

// 3. FIXED FETCH QUERY TO INCLUDE ALL NEEDED COLUMNS
$query = $conn->prepare("SELECT 
                        itemId, itemName, itemType, quantity, 
                        status, description, dateAdded 
                        FROM inventory 
                        WHERE staffId = ? 
                        ORDER BY 
                          CASE status 
                            WHEN 'pending' THEN 1
                            WHEN 'available' THEN 2
                            WHEN 'rejected' THEN 3
                            ELSE 4
                          END, 
                        dateAdded DESC");
$query->bind_param("i", $staffId);
$query->execute();
$result = $query->get_result();

$inventoryItems = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $inventoryItems[] = $row;
    }
}
$query->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Inventory</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            background-color:rgb(255, 255, 255);
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
        
        .account-section {
            margin-bottom: 40px;
        }
        
        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-available {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .equipment-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
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
            <li><a class="nav-link" href="staff_vouchers.php">VOUCHERS</a></li>
            <li class="active"><a class="nav-link" href="staff_inventory.php">INVENTORY</a></li>
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
            <h2>INVENTORY MANAGEMENT</h2>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="bi bi-person-circle"></i>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Add Equipment Form -->
        <div class="form-container">
            <h4>Add New Equipment</h4>
            <form method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="item_name" class="form-label">Equipment Name</label>
                        <input type="text" class="form-control" id="item_name" name="item_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="item_type" class="form-label">Equipment Type</label>
                        <select class="form-select" id="item_type" name="item_type" required>
                            <option value="">Select Type</option>
                            <option value="WiFi Router">WiFi Router</option>
                            <option value="WiFi Extender">WiFi Extender</option>
                            <option value="Ethernet Cable">Ethernet Cable</option>
                            <option value="Network Switch">Network Switch</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <button type="submit" name="add_equipment" class="btn btn-primary">Submit for Approval</button>
            </form>
        </div>
        
        <!-- Inventory List -->
        <h4>My Equipment Submissions</h4>
        <div class="table-responsive">
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inventoryItems)): ?>
                        <?php foreach ($inventoryItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['itemId']); ?></td>
                                <td><?php echo htmlspecialchars($item['itemName']); ?></td>
                                <td><?php echo htmlspecialchars($item['itemType']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date("M j, Y", strtotime($item['dateAdded'])); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No equipment submissions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
