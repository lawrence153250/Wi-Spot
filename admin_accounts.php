<?php
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

// Database 
require_once 'config.php';

// Handle account status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $account_type = $_POST['account_type'];
    $account_id = $_POST['account_id'];
    $new_status = $_POST['new_status'];
    
    // Determine the table and ID field based on account type
    $table = '';
    $id_field = '';
    
    switch ($account_type) {
        case 'admin':
            $table = 'admin';
            $id_field = 'adminId';
            break;
        case 'staff':
            $table = 'staff';
            $id_field = 'staffId';
            break;
        case 'customer':
            $table = 'customer';
            $id_field = 'customerId';
            break;
    }
    
    if ($table && $id_field) {
        $sql = "UPDATE $table SET accountStatus = ? WHERE $id_field = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $account_id);
        $stmt->execute();
        $stmt->close();
        
        // Refresh the page to show updated status
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'username';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Handle search
$search_username = isset($_GET['search']) ? $_GET['search'] : '';
$search_result = null;

if (!empty($search_username)) {
    // Search across all account types
    $sql = "(SELECT 'admin' as type, adminId as id, email, fullName, registerDate, userName, accountStatus FROM admin WHERE userName LIKE ?)
            UNION
            (SELECT 'staff' as type, staffId as id, email, fullName, registerDate, userName, accountStatus FROM staff WHERE userName LIKE ?)
            UNION
            (SELECT 'customer' as type, customerId as id, email, CONCAT(firstName, ' ', lastName) as fullName, registerDate, userName, accountStatus FROM customer WHERE userName LIKE ?)";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search_username%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $search_result = $stmt->get_result();
    $stmt->close();
}

// Fetch all admins with sorting
$admins = [];
$sql = "SELECT adminId, email, fullName, registerDate, userName, accountStatus FROM admin";
if ($sort === 'username') {
    $sql .= " ORDER BY userName $order";
} elseif ($sort === 'accountStatus') {
    $sql .= " ORDER BY accountStatus $order";
} elseif ($sort === 'fullName') {
    $sql .= " ORDER BY fullName $order";
}
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Fetch all customers with sorting
$customers = [];
$sql = "SELECT customerId, email, firstName, lastName, contactNumber, address, birthday, registerDate, userName, accountStatus FROM customer";
if ($sort === 'username') {
    $sql .= " ORDER BY userName $order";
} elseif ($sort === 'accountStatus') {
    $sql .= " ORDER BY accountStatus $order";
} elseif ($sort === 'fullName') {
    $sql .= " ORDER BY CONCAT(firstName, ' ', lastName) $order";
}
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Fetch all staff with sorting
$staff = [];
$sql = "SELECT staffId, email, fullName, position, registerDate, userName, accountStatus FROM staff";
if ($sort === 'username') {
    $sql .= " ORDER BY userName $order";
} elseif ($sort === 'accountStatus') {
    $sql .= " ORDER BY accountStatus $order";
} elseif ($sort === 'fullName') {
    $sql .= " ORDER BY fullName $order";
}
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Sidebar Styles */
        .sidebar-menu li a.nav-link {
        color: #FFFFFF;
        }

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
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .account-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .add-account-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .add-account-btn:hover {
            background-color: #2980b9;
            color: white;
        }
        
        /* Status Badge Styles */
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-locked {
            color: #dc3545;
            font-weight: 600;
        }
        
        .status-blocked {
            color: #6c757d;
            font-weight: 600;
        }
        
        /* Search and Sort Styles */
        .search-sort-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .sort-dropdown .dropdown-toggle {
            background-color: #3498db;
            border: none;
        }
        
        .search-form {
            display: flex;
            gap: 5px;
        }
        
        /* Status dropdown button styles */
        .status-dropdown .btn {
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
            
            .search-sort-container {
                flex-direction: column;
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
            <li><a class="nav-link" href="adminhome.php">DASHBOARD</a></li>
            <li class="active"><a class="nav-link" href="admin_accounts.php">ACCOUNTS</a></li>
            <li><a class="nav-link" href="admin_packages.php">PACKAGES</a></li>
            <li><a class="nav-link" href="admin_vouchers.php">VOUCHERS</a></li>
            <li><a class="nav-link" href="admin_inventory.php">INVENTORY</a></li>
            <li><a class="nav-link" href="admin_reports.php">REPORTS</a></li>
            <li><a class="nav-link" href="admin_feedbacks.php">FEEDBACKS</a></li>
            <li><a class="nav-link" href="admin_announcements.php">ANNOUNCEMENTS</a></li>
            <li><a class="nav-link" href="admin_resetpass.php">RESET PASSWORD</a></li>
            <li><span><a class="nav-link" href="logout.php">LOGOUT</a></span></li>
        </ul>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="page-header">
            <h2>ACCOUNT MANAGEMENT</h2>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="bi bi-person-circle"></i>
            </div>
        </div>

        <div class="search-sort-container">
            <!-- Sort Dropdown -->
            <div class="dropdown sort-dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-sort-alpha-down"></i> Sort By
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                    <li><a class="dropdown-item" href="?sort=username&order=asc">Username (A-Z)</a></li>
                    <li><a class="dropdown-item" href="?sort=username&order=desc">Username (Z-A)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?sort=accountStatus&order=asc">Account Status (A-Z)</a></li>
                    <li><a class="dropdown-item" href="?sort=accountStatus&order=desc">Account Status (Z-A)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?sort=fullName&order=asc">Full Name (A-Z)</a></li>
                    <li><a class="dropdown-item" href="?sort=fullName&order=desc">Full Name (Z-A)</a></li>
                </ul>
            </div>
            
            <!-- Search Form -->
            <form class="search-form" method="GET" action="">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search by username" value="<?php echo htmlspecialchars($search_username); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>
        </div>

        <a href="create_account.php" class="add-account-btn">
            <i class="bi bi-plus-circle"></i> Add New Account
        </a><br><br>

        <?php if (!empty($search_username)) : ?>
            <!-- Search Results Modal -->
            <div class="modal fade" id="searchResultsModal" tabindex="-1" aria-labelledby="searchResultsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="searchResultsModalLabel">Search Results for "<?php echo htmlspecialchars($search_username); ?>"</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if ($search_result && $search_result->num_rows > 0) : ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Account Type</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Full Name</th>
                                                <th>Account Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $search_result->fetch_assoc()) : ?>
                                                <tr>
                                                    <td><?php echo ucfirst(htmlspecialchars($row['type'])); ?></td>
                                                    <td><?php echo htmlspecialchars($row['userName']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['fullName']); ?></td>
                                                    <td class="<?php echo 'status-' . strtolower($row['accountStatus']); ?>">
                                                        <?php echo htmlspecialchars($row['accountStatus']); ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else : ?>
                                <div class="alert alert-info">No accounts found matching "<?php echo htmlspecialchars($search_username); ?>"</div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                // Show the modal automatically when search results are present
                document.addEventListener('DOMContentLoaded', function() {
                    var searchModal = new bootstrap.Modal(document.getElementById('searchResultsModal'));
                    searchModal.show();
                });
            </script>
        <?php endif; ?>

        <!-- Admin Accounts Section -->
        <div class="account-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Admin Accounts</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Account Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($admins)): ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['userName']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['fullName']); ?></td>
                                    <td class="<?php echo 'status-' . strtolower($admin['accountStatus']); ?>">
                                        <?php echo htmlspecialchars($admin['accountStatus']); ?>
                                    </td>
                                    <td>
                                        <div class="dropdown status-dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="adminStatusDropdown<?php echo $admin['adminId']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Change Status
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="adminStatusDropdown<?php echo $admin['adminId']; ?>">
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="admin">
                                                        <input type="hidden" name="account_id" value="<?php echo $admin['adminId']; ?>">
                                                        <input type="hidden" name="new_status" value="Active">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Active</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="admin">
                                                        <input type="hidden" name="account_id" value="<?php echo $admin['adminId']; ?>">
                                                        <input type="hidden" name="new_status" value="Locked">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Locked</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="admin">
                                                        <input type="hidden" name="account_id" value="<?php echo $admin['adminId']; ?>">
                                                        <input type="hidden" name="new_status" value="Blocked">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Blocked</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No admin accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Staff Accounts Section -->
        <div class="account-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Staff Accounts</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Position</th>
                            <th>Account Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($staff)): ?>
                            <?php foreach ($staff as $staffMember): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($staffMember['userName']); ?></td>
                                    <td><?php echo htmlspecialchars($staffMember['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staffMember['fullName']); ?></td>
                                    <td><?php echo htmlspecialchars($staffMember['position']); ?></td>
                                    <td class="<?php echo 'status-' . strtolower($staffMember['accountStatus']); ?>">
                                        <?php echo htmlspecialchars($staffMember['accountStatus']); ?>
                                    </td>
                                    <td>
                                        <div class="dropdown status-dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="staffStatusDropdown<?php echo $staffMember['staffId']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Change Status
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="staffStatusDropdown<?php echo $staffMember['staffId']; ?>">
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="staff">
                                                        <input type="hidden" name="account_id" value="<?php echo $staffMember['staffId']; ?>">
                                                        <input type="hidden" name="new_status" value="Active">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Active</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="staff">
                                                        <input type="hidden" name="account_id" value="<?php echo $staffMember['staffId']; ?>">
                                                        <input type="hidden" name="new_status" value="Locked">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Locked</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="staff">
                                                        <input type="hidden" name="account_id" value="<?php echo $staffMember['staffId']; ?>">
                                                        <input type="hidden" name="new_status" value="Blocked">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Blocked</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No staff accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Accounts Section -->
        <div class="account-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Customer Accounts</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Contact Number</th>
                            <th>Address</th>
                            <th>Birthday</th>
                            <th>Account Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($customers)): ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['userName']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['firstName'] . ' ' . $customer['lastName']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['contactNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['birthday']); ?></td>
                                    <td class="<?php echo 'status-' . strtolower($customer['accountStatus']); ?>">
                                        <?php echo htmlspecialchars($customer['accountStatus']); ?>
                                    </td>
                                    <td>
                                        <div class="dropdown status-dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="customerStatusDropdown<?php echo $customer['customerId']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Change Status
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="customerStatusDropdown<?php echo $customer['customerId']; ?>">
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="customer">
                                                        <input type="hidden" name="account_id" value="<?php echo $customer['customerId']; ?>">
                                                        <input type="hidden" name="new_status" value="Active">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Active</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="customer">
                                                        <input type="hidden" name="account_id" value="<?php echo $customer['customerId']; ?>">
                                                        <input type="hidden" name="new_status" value="Locked">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Locked</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="account_type" value="customer">
                                                        <input type="hidden" name="account_id" value="<?php echo $customer['customerId']; ?>">
                                                        <input type="hidden" name="new_status" value="Blocked">
                                                        <button type="submit" name="update_status" class="dropdown-item">Set to Blocked</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No customer accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>