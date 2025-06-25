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

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['userlevel'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'config.php';

// Handle booking deletion
if (isset($_GET['delete_id'])) {
    $bookingId = $_GET['delete_id'];
    $deleteQuery = "DELETE FROM booking WHERE bookingId = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $bookingId);
    
    if ($stmt->execute()) {
        $successMsg = "Booking deleted successfully!";
    } else {
        $errorMsg = "Error deleting booking: " . $conn->error;
    }
}

// Fetch all bookings with customer information
$query = "SELECT b.*, c.firstName, c.lastName, c.contactNumber, p.packageName 
          FROM booking b
          JOIN customer c ON b.customerId = c.customerId
          JOIN package p ON b.packageId = p.packageId
          ORDER BY b.timestamp DESC";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Booking Monitoring</title>
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
            background-color: #34485f;
        }
        
        .sidebar-menu li a.nav-link {
            color: #FFFFFF;
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
        
        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .status-connected {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-connecting {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        
        .table-responsive {
            margin-top: 20px;
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
            <li><a class="nav-link" href="adminhome.php">DASHBOARD</a></li>
            <li><a class="nav-link" href="admin_accounts.php">ACCOUNTS</a></li>
            <li><a class="nav-link" href="admin_packages.php">PACKAGES</a></li>
            <li><a class="nav-link" href="admin_vouchers.php">VOUCHERS</a></li>
            <li><a class="nav-link" href="admin_inventory.php">INVENTORY</a></li>
            <li><a class="nav-link" href="admin_reports.php">REPORTS</a></li>
            <li><a class="nav-link" href="admin_bookingApproval.php">BOOKING APPROVALS</a></li>
            <li><a class="nav-link" href="admin_bookingMonitoring.php"><span style="white-space: nowrap;">BOOKING MONITORING</span></a></li>
            <li><a class="nav-link" href="admin_agreementView.php">AGREEMENTS</a></li>
            <li><a class="nav-link" href="admin_feedbacks.php">FEEDBACKS</a></li>
            <li><a class="nav-link" href="admin_announcements.php">ANNOUNCEMENTS</a></li>
            <li><a class="nav-link" href="admin_resetpass.php">RESET PASSWORD</a></li>
            <li><span><a class="nav-link" href="logout.php">LOGOUT</a></span></li>
        </ul>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <h2>Booking Monitoring</h2>
        
        <?php if (isset($successMsg)): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="bookings-table">
                <thead>
                    <tr>                
                        <th>Connection Status</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Date Created</th>
                        <th>Event Dates</th>
                        <th>Location</th>
                        <th>Package</th>
                        <th>Price</th>
                        <th>Booking Status</th>
                        <th>Delete Booking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php 
                                        // Randomly assign connection status for demo purposes
                                        $status = ['Connected', 'Connecting', 'Connection error'][rand(0, 2)];
                                        $statusClass = strtolower(str_replace(' ', '-', $status));
                                        echo "<span class='status-$statusClass'>$status</span>";
                                    ?>
                                </td>
                                <td class="text-nowrap"><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                                <td><?php echo htmlspecialchars($row['contactNumber']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['timestamp'])); ?></td>
                                <td class="text-nowrap">
                                    <?php 
                                        echo date('M d, Y', strtotime($row['dateOfBooking'])) . ' to ' . 
                                             date('M d, Y', strtotime($row['dateOfReturn']));
                                    ?>
                                </td>
                                <td class="text-nowrap"><?php echo htmlspecialchars($row['eventLocation']); ?></td>
                                <td><?php echo htmlspecialchars($row['packageName']); ?></td>
                                <td>₱<?php echo number_format($row['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['bookingStatus']); ?></td>
                                <td>
                                    <a href="admin_bookingMonitoring.php?delete_id=<?php echo $row['bookingId']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this booking?');">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">No bookings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>