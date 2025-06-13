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

// Database connection
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if bookingId is provided
if (!isset($_GET['bookingId']) || !is_numeric($_GET['bookingId'])) {
    die("Invalid booking ID");
}

$bookingId = intval($_GET['bookingId']);

// Get customerId from database based on session username
$customerId = null;
if (isset($_SESSION['username'])) {
    $username = $conn->real_escape_string($_SESSION['username']);
    $customerQuery = $conn->query("SELECT customerId FROM customer WHERE username = '$username'");
    
    if ($customerQuery && $customerQuery->num_rows > 0) {
        $customerData = $customerQuery->fetch_assoc();
        $customerId = $customerData['customerId'];
    } else {
        die("Customer not found");
    }
} else {
    die("You must be logged in to submit feedback");
}

// Check if the booking exists and belongs to the customer
$bookingCheck = $conn->prepare("SELECT * FROM booking WHERE bookingId = ? AND customerId = ?");
$bookingCheck->bind_param("ii", $bookingId, $customerId);
$bookingCheck->execute();
$bookingResult = $bookingCheck->get_result();

if ($bookingResult->num_rows === 0) {
    die("Booking not found or doesn't belong to you");
}

$booking = $bookingResult->fetch_assoc();

// Check if feedback already exists for this booking
$feedbackCheck = $conn->prepare("SELECT * FROM feedback WHERE bookingId = ?");
$feedbackCheck->bind_param("i", $bookingId);
$feedbackCheck->execute();
$feedbackResult = $feedbackCheck->get_result();

if ($feedbackResult->num_rows > 0) {
    die("You have already submitted feedback for this booking");
}

$voucherCode = null;
$successMessage = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $ratings = [
        'internet_speed' => intval($_POST['internet_speed'] ?? 0),
        'reliability' => intval($_POST['reliability'] ?? 0),
        'signal_strength' => intval($_POST['signal_strength'] ?? 0),
        'customer_service' => intval($_POST['customer_service'] ?? 0),
        'installation_service' => intval($_POST['installation_service'] ?? 0),
        'equipment_quality' => intval($_POST['equipment_quality'] ?? 0),
        'overall_rating' => intval($_POST['overall_rating'] ?? 0)
    ];
    
    // Validate all ratings are between 1-5 (except overall_rating which can be 0-5)
    $valid = true;
    foreach ($ratings as $key => $value) {
        if ($key === 'overall_rating') {
            if ($value < 0 || $value > 5) {
                $valid = false;
                $error = "Overall rating must be between 0 and 5";
                break;
            }
        } else {
            if ($value < 1 || $value > 5) {
                $valid = false;
                $error = "All ratings must be between 1 and 5";
                break;
            }
        }
    }
    
    if ($valid) {
        $comment = $conn->real_escape_string(trim($_POST['comment'] ?? ''));
        $sentiment = $conn->real_escape_string(trim($_POST['sentiment'] ?? ''));
        
        // Handle file upload
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/feedback/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('feedback_') . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                $photo = $uploadPath;
            }
        }
        
        // Insert feedback into database
        $stmt = $conn->prepare("INSERT INTO feedback (
            customerId, bookingId, internet_speed, reliability, signal_strength, 
            customer_service, installation_service, equipment_quality, 
            overall_rating, photo, comment, sentiment
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("iiiiiiiiisss", 
            $customerId, $bookingId, 
            $ratings['internet_speed'], $ratings['reliability'], $ratings['signal_strength'],
            $ratings['customer_service'], $ratings['installation_service'], $ratings['equipment_quality'],
            $ratings['overall_rating'], $photo, $comment, $sentiment
        );
        
        if ($stmt->execute()) {
            // Get a voucher code that hasn't been given yet
            $voucherQuery = $conn->query("SELECT * FROM voucher_code WHERE isGiven = FALSE LIMIT 1");
            if ($voucherQuery && $voucherQuery->num_rows > 0) {
                $voucher = $voucherQuery->fetch_assoc();
                $voucherCode = $voucher['code'];
                
                // Mark voucher as given and associate with customer and booking
                $updateStmt = $conn->prepare("UPDATE voucher_code SET 
                    isGiven = TRUE, 
                    customerId = ?, 
                    bookingId = ? 
                    WHERE codeId = ?");
                $updateStmt->bind_param("iii", $customerId, $bookingId, $voucher['codeId']);
                $updateStmt->execute();
                
                $successMessage = "Thank you for your feedback! Here's your reward:";
            } else {
                $successMessage = "Thank you for your feedback!";
            }
        } else {
            $error = "Failed to submit feedback. Please try again. Error: " . $conn->error;
        }
    }
}
?>

<!-- REST OF YOUR HTML CODE REMAINS THE SAME -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Feedback - Wi-Spot</title>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .rating-options {
      display: flex;
      gap: 15px;
      margin-top: 0.5rem;
    }
    .rating-options label {
      display: flex;
      flex-direction: column;
      align-items: center;
      cursor: pointer;
    }
    .rating-options input[type="radio"] {
      margin-top: 5px;
    }
    #previewImage {
      max-width: 200px;
      max-height: 200px;
      margin-top: 10px;
      display: none;
    }
    .form-control:focus, .form-select:focus {
      border-color: #0d6efd;
      box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .voucher-code {
      font-size: 1.5rem;
      font-weight: bold;
      color: #0d6efd;
      background-color: #f8f9fa;
      padding: 10px 20px;
      border-radius: 5px;
      border: 2px dashed #0d6efd;
      margin: 20px 0;
    }
  </style>
</head>
<body>
  <!-- Navigation -->
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
          <li class="nav-item"><a class="nav-link" href="aboutus.php">ABOUT US</a></li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="profile.php"><?php echo htmlspecialchars($_SESSION['username']); ?> <i class="bi bi-person-circle"></i></a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <?php if (isset($error)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
          <div class="alert alert-success text-center">
            <h4><?php echo htmlspecialchars($successMessage); ?></h4>
            <?php if ($voucherCode): ?>
              <div class="voucher-code"><?php echo htmlspecialchars($voucherCode); ?></div>
              <p>Use this code during your next booking for a special discount!</p>
            <?php endif; ?>
            <a href="profile.php" class="btn btn-primary">Back to Profile</a>
          </div>
        <?php else: ?>
          <div class="card shadow">
            <div class="card-header bg-primary text-white">
              <h3 class="mb-0">Feedback Form</h3>
              <p class="mb-0">Please rate your experience with Wi-Spot</p>
            </div>
            <div class="card-body">
              <form method="POST" enctype="multipart/form-data" id="feedbackForm">
                <input type="hidden" name="bookingId" value="<?php echo htmlspecialchars($bookingId); ?>">
                
                <h5 class="mb-4">Service Ratings</h5>
                
                <!-- Internet Speed -->
                <div class="mb-4">
                  <label class="form-label">Internet Speed</label>
                  <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label>
                        <?php echo $i; ?>
                        <input type="radio" name="internet_speed" value="<?php echo $i; ?>" required>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <!-- Reliability -->
                <div class="mb-4">
                  <label class="form-label">Reliability</label>
                  <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label>
                        <?php echo $i; ?>
                        <input type="radio" name="reliability" value="<?php echo $i; ?>" required>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <!-- Signal Strength -->
                <div class="mb-4">
                  <label class="form-label">Signal Strength</label>
                  <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label>
                        <?php echo $i; ?>
                        <input type="radio" name="signal_strength" value="<?php echo $i; ?>" required>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <!-- Customer Service -->
                <div class="mb-4">
                  <label class="form-label">Customer Service</label>
                  <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label>
                        <?php echo $i; ?>
                        <input type="radio" name="customer_service" value="<?php echo $i; ?>" required>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <!-- Installation Service -->
                <div class="mb-4">
                  <label class="form-label">Installation Service</label>
                  <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label>
                        <?php echo $i; ?>
                        <input type="radio" name="installation_service" value="<?php echo $i; ?>" required>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <!-- Equipment Quality -->
                <div class="mb-4">
                  <label class="form-label">Equipment Quality</label>
                  <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label>
                        <?php echo $i; ?>
                        <input type="radio" name="equipment_quality" value="<?php echo $i; ?>" required>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <!-- Overall Rating -->
                <div class="mb-4">
                  <label class="form-label">Overall Rating</label>
                  <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <label>
                        <?php echo $i; ?>
                        <input type="radio" name="overall_rating" value="<?php echo $i; ?>" required>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>
                
                <!-- Comment -->
                <div class="mb-4">
                  <label for="comment" class="form-label">Your Comments</label>
                  <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                </div>
                
                <!-- Sentiment -->
                <div class="mb-4">
                  <label for="sentiment" class="form-label">How would you describe your experience?</label>
                  <select class="form-select" id="sentiment" name="sentiment" required>
                    <option value="" selected disabled>Select an option</option>
                    <option value="Excellent">Excellent</option>
                    <option value="Good">Good</option>
                    <option value="Average">Average</option>
                    <option value="Poor">Poor</option>
                    <option value="Terrible">Terrible</option>
                  </select>
                </div>
                
                <!-- Photo Upload -->
                <div class="mb-4">
                  <label for="photo" class="form-label">Upload Photo (Optional)</label>
                  <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
                  <img id="previewImage" src="#" alt="Preview" class="img-thumbnail">
                </div>
                
                <div class="d-grid gap-2">
                  <button type="submit" class="btn btn-primary btn-lg">Submit Feedback</button>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Footer -->
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

  <script>
    // Image preview
    document.getElementById('photo').addEventListener('change', function(e) {
      const preview = document.getElementById('previewImage');
      const file = e.target.files[0];
      
      if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
      } else {
        preview.style.display = 'none';
      }
    });
    
    // Form validation
    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
      let isValid = true;
      
      // Check all radio buttons are selected
      document.querySelectorAll('input[type="radio"][required]').forEach(radioGroup => {
        const groupName = radioGroup.name;
        const checked = document.querySelector(`input[name="${groupName}"]:checked`);
        if (!checked) {
          isValid = false;
          const label = document.querySelector(`label[for="${groupName}"]`) || 
                       document.querySelector(`label:has(input[name="${groupName}"])`);
          alert(`Please select a rating for ${label?.textContent?.trim() || groupName}`);
        }
      });
      
      if (!isValid) {
        e.preventDefault();
      }
    });
  </script>
</body>
</html>