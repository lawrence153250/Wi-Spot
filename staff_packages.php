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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_package'])) {
    $packageName = $conn->real_escape_string($_POST['package_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $numberOfUsers = (int)$_POST['number_of_users'];
    $eventType = $conn->real_escape_string($_POST['event_type']);
    $eventAreaSize = (float)$_POST['event_area_size'];
    $expectedBandwidth = (float)$_POST['expected_bandwidth'];
    $selectedItems = $_POST['equipment_items'] ?? [];
    
    // Validate at least one item is selected
    if (empty($selectedItems)) {
        $error_message = "Please select at least one equipment item";
    } else {
        // Convert selected items array to comma-separated string
        $equipmentsIncluded = implode(',', $selectedItems);
        
        // Insert new package with all fields
        $stmt = $conn->prepare("INSERT INTO package (
            staffId, packageName, description, price, equipmentsIncluded, 
            status, numberOfUsers, eventType, eventAreaSize, expectedBandwidth
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "issdsisdd", 
            $staffId, 
            $packageName, 
            $description, 
            $price, 
            $equipmentsIncluded,
            $numberOfUsers,
            $eventType,
            $eventAreaSize,
            $expectedBandwidth
        );
        
        if ($stmt->execute()) {
            $success_message = "Package created successfully! Waiting for admin approval.";
        } else {
            $error_message = "Error creating package: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch available inventory items
$inventoryItems = [];
$inventoryQuery = $conn->query("SELECT itemId, itemName, itemType FROM inventory WHERE status = 'available'");
if ($inventoryQuery->num_rows > 0) {
    while ($row = $inventoryQuery->fetch_assoc()) {
        $inventoryItems[] = $row;
    }
}

// Fetch packages created by this staff with proper date fields
$packages = [];
$packageQuery = $conn->prepare("SELECT 
                                p.packageId, p.packageName, p.description, p.price, 
                                p.equipmentsIncluded, p.status, p.dateCreated, p.approvalDate,
                                p.numberOfUsers, p.eventType, p.eventAreaSize, p.expectedBandwidth,
                                GROUP_CONCAT(i.itemName SEPARATOR ', ') AS equipmentNames
                                FROM package p
                                LEFT JOIN inventory i ON FIND_IN_SET(i.itemId, p.equipmentsIncluded)
                                WHERE p.staffId = ?
                                GROUP BY p.packageId
                                ORDER BY 
                                  CASE p.status 
                                    WHEN 'pending' THEN 1
                                    WHEN 'available' THEN 2
                                    WHEN 'rejected' THEN 3
                                    ELSE 4
                                  END, 
                                p.dateCreated DESC");
$packageQuery->bind_param("i", $staffId);
$packageQuery->execute();
$result = $packageQuery->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $packages[] = $row;
    }
}
$packageQuery->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Packages</title>
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
        .packages-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .packages-table th, 
        .packages-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .packages-table th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
        }
        
        .packages-table tr:hover {
            background-color: #f9f9f9;
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
        
        .status-maintenance {
            background-color: #E2E3E5;
            color: #383D41;
        }
        
        .status-in_use {
            background-color: #CCE5FF;
            color: #004085;
        }
        
        .equipment-list {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }
        
        /* Form Styles */
        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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
            
            .packages-table {
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
            <li class="active"><a class="nav-link" href="staff_packages.php">PACKAGES</a></li>
            <li><a class="nav-link" href="staff_vouchers.php">VOUCHERS</a></li>
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
            <h2>PACKAGE MANAGEMENT</h2>
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
        
        <!-- Create Package Form -->
        <div class="form-container">
            <h4>Create New Package</h4>
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="package_name" class="form-label">Package Name</label>
                        <input type="text" class="form-control" id="package_name" name="package_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="price" class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="number_of_users" class="form-label">Number of Users</label>
                        <input type="number" class="form-control" id="number_of_users" name="number_of_users" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label for="event_type" class="form-label">Event Type</label>
                        <select class="form-select" id="event_type" name="event_type" required>
                            <option value="">Select type</option>
                            <option value="indoor">Indoor</option>
                            <option value="outdoor">Outdoor</option>
                            <option value="concert">Concert</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="event_area_size" class="form-label">Event Area Size (sqm)</label>
                        <input type="number" class="form-control" id="event_area_size" name="event_area_size" step="0.01" min="1" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="expected_bandwidth" class="form-label">Expected Bandwidth (Mbps)</label>
                        <input type="number" class="form-control" id="expected_bandwidth" name="expected_bandwidth" step="0.01" min="1" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Select Equipment Items</label>
                    <div class="equipment-list">
                        <?php if (!empty($inventoryItems)): ?>
                            <?php foreach ($inventoryItems as $item): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                        name="equipment_items[]" 
                                        value="<?php echo $item['itemId']; ?>" 
                                        id="item<?php echo $item['itemId']; ?>">
                                    <label class="form-check-label" for="item<?php echo $item['itemId']; ?>">
                                        <?php echo htmlspecialchars($item['itemName']); ?> 
                                        (<?php echo htmlspecialchars($item['itemType']); ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">No available equipment items found in inventory.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" name="create_package" class="btn btn-primary">Submit for Approval</button>
            </form>
        </div>
        
        <!-- Updated Packages Table -->
        <h4>My Packages</h4>
        <div class="table-responsive">
            <table class="packages-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Users</th>
                        <th>Type</th>
                        <th>Area (sqm)</th>
                        <th>Bandwidth</th>
                        <th>Equipment Included</th>
                        <th>Status</th>
                        <th>Date Created</th>
                        <th>Date Approved</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($packages)): ?>
                        <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($package['packageName']); ?></td>
                                <td><?php echo htmlspecialchars($package['description']); ?></td>
                                <td>₱<?php echo number_format($package['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($package['numberOfUsers']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($package['eventType'])); ?></td>
                                <td><?php echo number_format($package['eventAreaSize'], 2); ?></td>
                                <td><?php echo number_format($package['expectedBandwidth'], 2); ?> Mbps</td>
                                <td>
                                    <?php 
                                    if (!empty($package['equipmentNames'])) {
                                        echo htmlspecialchars($package['equipmentNames']);
                                    } else {
                                        $ids = explode(',', $package['equipmentsIncluded']);
                                        foreach ($ids as $id) {
                                            echo "Item #" . htmlspecialchars($id) . "<br>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($package['status']); ?>">
                                        <?php echo ucfirst($package['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date("M j, Y", strtotime($package['dateCreated'])); ?></td>
                                <td>
                                    <?php echo $package['approvalDate'] ? date("M j, Y", strtotime($package['approvalDate'])) : 'Pending'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center">No packages created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>