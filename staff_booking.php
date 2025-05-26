<?php
session_start();

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

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        $bookingId = $_POST['booking_id'];
        $action = $_POST['action'];
        
        // Update booking status
        $stmt = $conn->prepare("UPDATE booking SET bookingStatus = ? WHERE bookingId = ?");
        $status = ($action === 'accept') ? 'accepted' : 'declined';
        $stmt->bind_param("si", $status, $bookingId);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to avoid form resubmission
        header("Location: staff_booking.php");
        exit();
    }
}

// Fetch all bookings with customer information
$sql = "SELECT 
            b.bookingId,
            b.timestamp AS date_booking_created,
            b.dateOfBooking AS date_of_start,
            b.dateOfReturn AS date_of_return,
            b.eventLocation AS event_location,
            p.packageName AS package_chosen,
            b.price AS total_price,
            b.bookingStatus AS booking_status,
            b.paymentStatus AS payment_status,
            c.username AS customer_username,
            c.firstName AS customer_firstname,
            c.lastName AS customer_lastname,
            c.contactNumber AS customer_contact
        FROM booking b
        JOIN package p ON b.packageId = p.packageId
        JOIN customer c ON b.customerId = c.customerId
        ORDER BY b.timestamp DESC";
$result = $conn->query($sql);

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Booking Management</title>
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
        
        .status-accepted {
            color: #2ecc71;
            font-weight: 600;
        }
        
        .status-declined {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
        
        /* Modal styles */
        .modal-item-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .item-checkbox {
            margin-right: 10px;
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
            <li class="active"><a class="nav-link" href="staff_booking.php">BOOKINGS</a></li>
            <li><a class="nav-link" href="staff_accounts.php">ACCOUNTS</a></li>
            <li><a class="nav-link" href="staff_packages.php">PACKAGES</a></li>
            <li><a class="nav-link" href="staff_vouchers.php">VOUCHERS</a></li>
            <li><a class="nav-link" href="staff_inventory.php">INVENTORY</a></li>
            <li><a class="nav-link" href="staff_reports.php">REPORTS</a></li>
            <li><a class="nav-link" href="staff_feedbacks.php">FEEDBACKS</a></li>
            <li><a class="nav-link" href="staff_announcements.php">ANNOUNCEMENTS</a></li>
            <li><span><a class="nav-link" href="logout.php">LOGOUT</a></span></li>
        </ul>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="page-header">
            <h2>BOOKING MANAGEMENT</h2>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="bi bi-person-circle"></i>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Date Created</th>
                        <th>Event Dates</th>
                        <th>Location</th>
                        <th>Package</th>
                        <th>Price</th>
                        <th>Booking Status</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['bookingId']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['customer_firstname'] . ' ' . $booking['customer_lastname']); ?><br>
                                    <small><?php echo htmlspecialchars($booking['customer_username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['customer_contact']); ?></td>
                                <td><?php echo htmlspecialchars($booking['date_booking_created']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['date_of_start']); ?> to<br>
                                    <?php echo htmlspecialchars($booking['date_of_return']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($booking['event_location']); ?></td>
                                <td><?php echo htmlspecialchars($booking['package_chosen']); ?></td>
                                <td>₱<?php echo number_format($booking['total_price'], 2); ?></td>
                                <td class="<?php echo 'status-' . strtolower($booking['booking_status']); ?>">
                                    <?php echo htmlspecialchars($booking['booking_status']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($booking['payment_status']); ?></td>
                                <td>
                                    <?php if (strtolower($booking['booking_status']) == 'pending'): ?>
                                        <div class="action-buttons">
                                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#acceptModal<?php echo $booking['bookingId']; ?>">
                                                Accept
                                            </button>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['bookingId']; ?>">
                                                <input type="hidden" name="action" value="decline">
                                                <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            
                            <!-- Accept Booking Modal -->
                            <div class="modal fade" id="acceptModal<?php echo $booking['bookingId']; ?>" tabindex="-1" aria-labelledby="acceptModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="post">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['bookingId']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="acceptModalLabel">Accept Booking #<?php echo $booking['bookingId']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h6>Booking Details:</h6>
                                                <p>
                                                    <strong>Customer:</strong> <?php echo htmlspecialchars($booking['customer_firstname'] . ' ' . $booking['customer_lastname']); ?><br>
                                                    <strong>Current Package:</strong> <?php echo htmlspecialchars($booking['package_chosen']); ?><br>
                                                    <strong>Event Dates:</strong> <?php echo htmlspecialchars($booking['date_of_start']); ?> to <?php echo htmlspecialchars($booking['date_of_return']); ?><br>
                                                    <strong>Location:</strong> <?php echo htmlspecialchars($booking['event_location']); ?>
                                                </p>
                                                
                                                <hr>
                                                
                                                <h6>Package Options:</h6>
                                                <p>Select the package for this booking:</p>
                                                
                                                <div class="modal-item-list">
                                                    <?php
                                                    // Reconnect to database for package query
                                                    $conn = new mysqli('localhost', 'root', '', 'capstonesample');
                                                    if ($conn->connect_error) {
                                                        die("Connection failed: " . $conn->connect_error);
                                                    }
                                                    
                                                    // Fetch all packages
                                                    $package_sql = "SELECT packageId, packageName, status FROM package ORDER BY packageName";
                                                    $package_result = $conn->query($package_sql);
                                                    
                                                    if ($package_result->num_rows > 0) {
                                                        while ($package = $package_result->fetch_assoc()) {
                                                            $status_class = ($package['status'] == 'available') ? 'text-success' : 'text-danger';
                                                            $disabled = ($package['status'] != 'available') ? 'disabled' : '';
                                                            echo '<div class="form-check mb-2">';
                                                            echo '<input class="form-check-input package-radio" type="radio" name="selected_package" value="'.$package['packageId'].'" id="package'.$package['packageId'].'"';
                                                            // Preselect the current package
                                                            if ($package['packageName'] == $booking['package_chosen']) {
                                                                echo ' checked';
                                                            }
                                                            echo ' '.$disabled.'>';
                                                            echo '<label class="form-check-label" for="package'.$package['packageId'].'">';
                                                            echo htmlspecialchars($package['packageName']);
                                                            echo ' <span class="'.$status_class.'">('.$package['status'].')</span>';
                                                            echo '</label>';
                                                            echo '</div>';
                                                        }
                                                    } else {
                                                        echo '<p>No packages found in the system.</p>';
                                                    }
                                                    $conn->close();
                                                    ?>
                                                </div>
                                                
                                                <div class="alert alert-info mt-3">
                                                    <i class="bi bi-info-circle"></i> Only packages with "available" status can be selected. 
                                                    If no suitable package is available, please decline the booking and advise the customer.
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <label for="staffNotes" class="form-label">Staff Notes:</label>
                                                    <textarea class="form-control" id="staffNotes" name="staff_notes" rows="3" 
                                                            placeholder="Add any notes about package selection or special instructions"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Confirm Acceptance</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center">No bookings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>