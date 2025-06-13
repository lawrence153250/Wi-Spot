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

// Process response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_response'])) {
    $feedbackId = intval($_POST['feedback_id']);
    $response = $conn->real_escape_string(trim($_POST['response']));
    
    // Update feedback with response and change status
    $updateStmt = $conn->prepare("UPDATE feedback SET response = ?, responseStatus = 'responded', responseDate = NOW() WHERE feedbackId = ?");
    $updateStmt->bind_param("si", $response, $feedbackId);
    
    if ($updateStmt->execute()) {
        $successMessage = "Response submitted successfully!";
    } else {
        $error = "Failed to submit response. Please try again.";
    }
}

// Get all feedback with customer information
$feedbackQuery = "
    SELECT f.*, c.username, c.email, b.packageId 
    FROM feedback f
    JOIN customer c ON f.customerId = c.customerId
    JOIN booking b ON f.bookingId = b.bookingId
    ORDER BY f.timestamp DESC
";
$feedbackResult = $conn->query($feedbackQuery);

// Calculate average ratings
$avgRatingQuery = "
    SELECT 
        AVG(internet_speed) as avg_speed,
        AVG(reliability) as avg_reliability,
        AVG(signal_strength) as avg_signal,
        AVG(customer_service) as avg_service,
        AVG(installation_service) as avg_installation,
        AVG(equipment_quality) as avg_equipment,
        AVG(overall_rating) as avg_overall
    FROM feedback
";
$avgRatings = $conn->query($avgRatingQuery)->fetch_assoc();

// Count feedback by sentiment
$sentimentCountQuery = "
    SELECT sentiment, COUNT(*) as count 
    FROM feedback 
    GROUP BY sentiment
    ORDER BY count DESC
";
$sentimentCounts = $conn->query($sentimentCountQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management</title>
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
        
        /* Feedback Table Styles */
        .feedback-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .feedback-table th, 
        .feedback-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .feedback-table th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
        }
        
        .feedback-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .rating-cell {
            text-align: center;
            font-weight: bold;
        }
        
        .rating-1 { color: #e74c3c; }
        .rating-2 { color: #e67e22; }
        .rating-3 { color: #f1c40f; }
        .rating-4 { color: #2ecc71; }
        .rating-5 { color: #27ae60; }
        
        .sentiment-excellent { color: #27ae60; font-weight: bold; }
        .sentiment-good { color: #2ecc71; font-weight: bold; }
        .sentiment-average { color: #f1c40f; font-weight: bold; }
        .sentiment-poor { color: #e67e22; font-weight: bold; }
        .sentiment-terrible { color: #e74c3c; font-weight: bold; }
        
        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card h5 {
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
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
            
            .feedback-table {
                display: block;
                overflow-x: auto;
            }
        }
        .response-modal textarea {
            min-height: 150px;
        }
        
        .existing-response {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation (Same as before) -->
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
            <li><a class="nav-link" href="staff_resetpass.php">RESET PASSWORD</a></li>
            <li><span><a class="nav-link" href="logout.php">LOGOUT</a></span></li>
        </ul>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="page-header">
            <h2>FEEDBACK MANAGEMENT</h2>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="bi bi-person-circle"></i>
            </div>
        </div>
        
        <?php if(isset($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Feedback Statistics -->
        <div class="stats-grid">
            <div class="stats-card">
                <h5>Average Ratings</h5>
                <div>Internet Speed: <span class="rating-<?= round($avgRatings['avg_speed']) ?>"><?= number_format($avgRatings['avg_speed'], 1) ?></span></div>
                <div>Reliability: <span class="rating-<?= round($avgRatings['avg_reliability']) ?>"><?= number_format($avgRatings['avg_reliability'], 1) ?></span></div>
                <div>Signal Strength: <span class="rating-<?= round($avgRatings['avg_signal']) ?>"><?= number_format($avgRatings['avg_signal'], 1) ?></span></div>
                <div>Customer Service: <span class="rating-<?= round($avgRatings['avg_service']) ?>"><?= number_format($avgRatings['avg_service'], 1) ?></span></div>
                <div>Installation: <span class="rating-<?= round($avgRatings['avg_installation']) ?>"><?= number_format($avgRatings['avg_installation'], 1) ?></span></div>
                <div>Equipment: <span class="rating-<?= round($avgRatings['avg_equipment']) ?>"><?= number_format($avgRatings['avg_equipment'], 1) ?></span></div>
                <div>Overall: <span class="rating-<?= round($avgRatings['avg_overall']) ?>"><?= number_format($avgRatings['avg_overall'], 1) ?></span></div>
            </div>
            
            <div class="stats-card">
                <h5>Feedback Sentiment</h5>
                <?php while($sentiment = $sentimentCounts->fetch_assoc()): ?>
                    <div>
                        <?= htmlspecialchars($sentiment['sentiment']) ?>: 
                        <span class="sentiment-<?= strtolower($sentiment['sentiment']) ?>">
                            <?= $sentiment['count'] ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="stats-card">
                <h5>Total Feedback</h5>
                <div><?= $feedbackResult->num_rows ?> feedback entries</div>
                <h5 class="mt-3">Response Status</h5>
                <?php 
                    $responseStatusQuery = "SELECT responseStatus, COUNT(*) as count FROM feedback GROUP BY responseStatus";
                    $responseStatus = $conn->query($responseStatusQuery);
                    while($status = $responseStatus->fetch_assoc()):
                ?>
                    <div><?= ucfirst($status['responseStatus']) ?>: <?= $status['count'] ?></div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Feedback Table -->
        <div class="table-responsive">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Package</th>
                        <th>Ratings</th>
                        <th>Sentiment</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($feedback = $feedbackResult->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($feedback['username']) ?></strong><br>
                            <small><?= htmlspecialchars($feedback['email']) ?></small>
                        </td>
                        <td>Package #<?= $feedback['packageId'] ?></td>
                        <td>
                            <div>Speed: <span class="rating-<?= $feedback['internet_speed'] ?>"><?= $feedback['internet_speed'] ?></span></div>
                            <div>Reliability: <span class="rating-<?= $feedback['reliability'] ?>"><?= $feedback['reliability'] ?></span></div>
                            <div>Signal: <span class="rating-<?= $feedback['signal_strength'] ?>"><?= $feedback['signal_strength'] ?></span></div>
                            <div>Overall: <span class="rating-<?= $feedback['overall_rating'] ?>"><?= $feedback['overall_rating'] ?></span></div>
                        </td>
                        <td class="sentiment-<?= strtolower($feedback['sentiment']) ?>">
                            <?= htmlspecialchars($feedback['sentiment']) ?>
                        </td>
                        <td>
                            <?= nl2br(htmlspecialchars(substr($feedback['comment'], 0, 100))) ?>
                            <?php if(strlen($feedback['comment']) > 100) echo '...'; ?>
                        </td>
                        <td><?= date('M d, Y h:i A', strtotime($feedback['timestamp'])) ?></td>
                        <td>
                            <?php if($feedback['responseStatus'] === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-success">Responded</span>
                                <small class="d-block"><?= date('M d, Y', strtotime($feedback['responseDate'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#responseModal" 
                                data-feedback-id="<?= $feedback['feedbackId'] ?>"
                                data-existing-response="<?= htmlspecialchars($feedback['response'] ?? '') ?>">
                                <i class="bi bi-reply"></i> Respond
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Response Modal -->
    <div class="modal fade response-modal" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalLabel">Respond to Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="staff_feedbacks.php">
                    <div class="modal-body">
                        <input type="hidden" name="feedback_id" id="modalFeedbackId">
                        
                        <div class="mb-3">
                            <label for="response" class="form-label">Your Response</label>
                            <textarea class="form-control" id="response" name="response" required></textarea>
                        </div>
                        
                        <div id="existingResponseContainer" style="display:none;">
                            <h6>Existing Response:</h6>
                            <div class="existing-response" id="existingResponseText"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit_response" class="btn btn-primary">Submit Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize modal with feedback data
        document.addEventListener('DOMContentLoaded', function() {
            var responseModal = document.getElementById('responseModal');
            responseModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var feedbackId = button.getAttribute('data-feedback-id');
                var existingResponse = button.getAttribute('data-existing-response');
                
                var modal = this;
                modal.querySelector('#modalFeedbackId').value = feedbackId;
                
                // Clear previous response
                modal.querySelector('#response').value = '';
                
                // Show/hide existing response
                var existingContainer = modal.querySelector('#existingResponseContainer');
                var existingText = modal.querySelector('#existingResponseText');
                
                if (existingResponse && existingResponse.trim() !== '') {
                    existingText.textContent = existingResponse;
                    existingContainer.style.display = 'block';
                } else {
                    existingContainer.style.display = 'none';
                }
            });
            
            // Clear modal when closed
            responseModal.addEventListener('hidden.bs.modal', function() {
                this.querySelector('#response').value = '';
                this.querySelector('#existingResponseContainer').style.display = 'none';
            });
        });
    </script>
</body>
</html>