<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['userlevel'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'capstonesample');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current admin ID
$username = $_SESSION['username'];
$adminQuery = $conn->prepare("SELECT adminId FROM admin WHERE username = ?");
$adminQuery->bind_param("s", $username);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();

if ($adminResult->num_rows === 0) {
    die("Error: Admin account not found for username: " . htmlspecialchars($username));
}

$adminData = $adminResult->fetch_assoc();
$adminId = $adminData['adminId'];
$adminQuery->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        // Create new announcement
        $title = $_POST['title'];
        $category = $_POST['category'];
        $description = $_POST['description'];
        $isPriority = isset($_POST['isPriority']) ? 1 : 0;
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];

        $stmt = $conn->prepare("INSERT INTO announcement (adminId, title, category, description, isPriority, startDate, endDate) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiss", $adminId, $title, $category, $description, $isPriority, $startDate, $endDate);
        $stmt->execute();
        $stmt->close();
        
        // Refresh to show new announcement
        header("Location: admin_announcements.php");
        exit();
    } elseif (isset($_POST['update'])) {
        // Update existing announcement
        $id = $_POST['id'];
        $title = $_POST['title'];
        $category = $_POST['category'];
        $description = $_POST['description'];
        $isPriority = isset($_POST['isPriority']) ? 1 : 0;
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];

        $stmt = $conn->prepare("UPDATE announcement SET title=?, category=?, description=?, isPriority=?, startDate=?, endDate=? WHERE announcementId=?");
        $stmt->bind_param("sssissi", $title, $category, $description, $isPriority, $startDate, $endDate, $id);
        $stmt->execute();
        $stmt->close();
        
        // Refresh to show updated announcement
        header("Location: admin_announcements.php");
        exit();
    }
} elseif (isset($_GET['delete'])) {
    // Delete announcement
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM announcement WHERE announcementId=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Refresh after deletion
    header("Location: admin_announcements.php");
    exit();
}

// Fetch all announcements
$announcements = $conn->query("SELECT * FROM announcement ORDER BY date DESC");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Packages</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
        
        .account-section {
            margin-bottom: 40px;
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
        
        .status-in_use{
            background-color: #E2E3E5;
            color:rgb(173, 65, 18);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .equipment-list {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            <a class="navbar-brand" href="adminhome.php"><img src="logo.png"></a>
        </div>
        <ul class="sidebar-menu">
            <li><a class="nav-link" href="admin_accounts.php">ACCOUNTS</a></li>
            <li class="active"><a class="nav-link" href="admin_packages.php">PACKAGES</a></li>
            <li><a class="nav-link" href="admin_vouchers.php">VOUCHERS</a></li>
            <li><a class="nav-link" href="admin_inventory.php">INVENTORY</a></li>
            <li><a class="nav-link" href="admin_reports.php">REPORTS</a></li>
            <li><a class="nav-link" href="admin_feedbacks.php">FEEDBACKS</a></li>
            <li><a class="nav-link" href="admin_announcements.php">ANNOUNCEMENTS</a></li>
            <li><span><a class="nav-link" href="logout.php">LOGOUT</a></span></li>
        </ul>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="page-header">
            <h2>Package Management</h2>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="bi bi-person-circle"></i>
            </div>
        </div>
        
          <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Announcement <?php echo htmlspecialchars($_GET['success']); ?> successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Error: <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
         <!-- Packages Table -->
        <div class="table-responsive">
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>Package Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Equipment Included</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Date Created</th>
                        <th>Approved By</th>
                        <th>Date Approved</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($packages)): ?>
                        <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($package['packageName']); ?></td>
                                <td><?php echo htmlspecialchars($package['description']); ?></td>
                                <td>₱<?php echo number_format($package['price'], 2); ?></td>
                                <td class="equipment-list" title="<?php echo htmlspecialchars($package['equipmentsIncluded']); ?>">
                                    <?php echo htmlspecialchars($package['equipmentsIncluded']); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($package['status']); ?>">
                                        <?php echo ucfirst($package['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($package['staff_username'] ?? 'System'); ?></td>
                                <td><?php echo date("M j, Y", strtotime($package['dateCreated'])); ?></td>
                                <td><?php echo htmlspecialchars($package['admin_username'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php echo $package['approvalDate'] ? date("M j, Y", strtotime($package['approvalDate'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php if ($package['status'] === 'pending'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="package_id" value="<?php echo $package['packageId']; ?>">
                                            <input type="hidden" name="new_status" value="approved">
                                            <button type="submit" name="update_status" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="package_id" value="<?php echo $package['packageId']; ?>">
                                            <input type="hidden" name="new_status" value="declined">
                                            <button type="submit" name="update_status" class="btn btn-danger btn-sm">Decline</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="package_id" value="<?php echo $package['packageId']; ?>">
                                            <select name="new_status" class="form-select form-select-sm d-inline" style="width: auto;">
                                                <option value="available" <?php echo $package['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                                <option value="in_use" <?php echo $package['status'] === 'in_use' ? 'selected' : ''; ?>>In Use</option>
                                                <option value="maintenance" <?php echo $package['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                <option value="rejected" <?php echo $package['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center">No packages found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</body>
</html>