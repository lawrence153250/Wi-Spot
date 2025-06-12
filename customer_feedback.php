<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// // Check if user is logged in
// if (!isset($_SESSION['customerId'])) {
//     header("Location: login.php?redirect=feedback");
//     exit();
// }



// // Validate CSRF token
// if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
//     die("Invalid CSRF token");
// }

// Get the latest completed booking for this customer
$stmt = $conn->prepare("SELECT bookingId FROM booking WHERE customerId = ? AND status = 'completed' ORDER BY bookingDate DESC LIMIT 1");
$stmt->bind_param("i", $_SESSION['customerId']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: booking.php?feedback=no_booking");
    exit();
}

$booking = $result->fetch_assoc();
$bookingId = $booking['bookingId'];
$stmt->close();

// Process form data
$requiredFields = [
    'internet_speed', 'reliability', 'signal_strength', 
    'customer_service', 'installation_service', 'equipment_quality',
    'overall_rating', 'comments'
];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        die("Missing required field: $field");
    }
}

// Validate ratings
$ratings = [
    'internet_speed' => (int)$_POST['internet_speed'],
    'reliability' => (int)$_POST['reliability'],
    'signal_strength' => (int)$_POST['signal_strength'],
    'customer_service' => (int)$_POST['customer_service'],
    'installation_service' => (int)$_POST['installation_service'],
    'equipment_quality' => (int)$_POST['equipment_quality'],
    'overall_rating' => (int)$_POST['overall_rating']
];

foreach ($ratings as $key => $value) {
    if ($value < 1 || ($key === 'overall_rating' ? $value > 5 : $value > 5)) {
        die("Invalid rating value for $key");
    }
}

// Process file upload
$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
        die("Invalid file type. Only JPG, PNG, and GIF are allowed.");
    }
    
    if ($_FILES['photo']['size'] > $maxSize) {
        die("File size exceeds 2MB limit.");
    }
    
    $uploadDir = 'uploads/feedback/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('feedback_') . '.' . $extension;
    $destination = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
        $photoPath = $destination;
    }
}

// Basic sentiment analysis based on ratings and comments
$averageRating = array_sum($ratings) / count($ratings);
$comment = strtolower(trim($_POST['comments']));

$positiveWords = ['good', 'great', 'excellent', 'happy', 'satisfied', 'awesome', 'perfect', 'love', 'liked'];
$negativeWords = ['bad', 'poor', 'terrible', 'unhappy', 'dissatisfied', 'awful', 'hate', 'dislike', 'worst'];

$sentiment = 'neutral';
if ($averageRating >= 4) {
    $sentiment = 'positive';
} elseif ($averageRating <= 2) {
    $sentiment = 'negative';
}

foreach ($positiveWords as $word) {
    if (strpos($comment, $word) !== false) {
        $sentiment = 'positive';
        break;
    }
}

foreach ($negativeWords as $word) {
    if (strpos($comment, $word) !== false) {
        $sentiment = 'negative';
        break;
    }
}

// Insert feedback into database
$stmt = $conn->prepare("INSERT INTO feedback (
    customerId, bookingId, internet_speed, reliability, signal_strength,
    customer_service, installation_service, equipment_quality, overall_rating,
    photo, comment, sentiment
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "iiiiiiiiisss",
    $_SESSION['customerId'],
    $bookingId,
    $ratings['internet_speed'],
    $ratings['reliability'],
    $ratings['signal_strength'],
    $ratings['customer_service'],
    $ratings['installation_service'],
    $ratings['equipment_quality'],
    $ratings['overall_rating'],
    $photoPath,
    $_POST['comments'],
    $sentiment
);

if ($stmt->execute()) {
    // Success - redirect with success message
    header("Location: feedback.php?success=1");
} else {
    // Error handling
    error_log("Feedback submission failed: " . $stmt->error);
    header("Location: feedback.php?error=1");
}


// Check if user is logged in and has an active booking
if (!isset($_SESSION['username']) || !isset($_SESSION['customerId'])) {
    header("Location: login.php?redirect=feedback");
    exit();
}

// Connect to database to verify booking
require_once 'db_connect.php';
$bookingExists = false;
if (isset($_SESSION['customerId'])) {
    $stmt = $conn->prepare("SELECT bookingId FROM booking WHERE customerId = ? AND status = 'completed'");
    $stmt->bind_param("i", $_SESSION['customerId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookingExists = $result->num_rows > 0;
    $stmt->close();
}

if (!$bookingExists) {
    header("Location: booking.php?feedback=no_booking");
    exit();
}

$stmt->close();
$conn->close();
exit();
?>


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
    .star-rating i {
      color: #ccc;
      font-size: 24px;
      cursor: pointer;
      transition: color 0.2s;
    }
    .star-rating i.checked, .star-rating i:hover {
      color: gold;
    }
    .star-rating i:hover ~ i:not(.checked) {
      color: #ccc;
    }
    .rating-category {
      margin-bottom: 1.5rem;
    }
    .rating-category label {
      font-weight: 500;
      margin-bottom: 0.5rem;
      display: block;
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

  <!-- Feedback Form -->
  <div class="container mt-5 mb-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <?php if (isset($_GET['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            Thank you for your feedback! We appreciate your time.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h2 class="mb-0 text-center">We value your feedback!</h2>
          </div>
          <div class="card-body">
            <form action="submit_feedback.php" method="POST" enctype="multipart/form-data" id="feedbackForm">
              <div class="mb-4">
                <label for="photo" class="form-label">Upload a photo (optional)</label>
                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                <small class="text-muted">Max file size: 2MB (JPEG, PNG only)</small>
                <img id="previewImage" class="img-thumbnail" alt="Preview">
              </div>

              <div class="mb-4">
                <h5 class="mb-3">Rate your experience with:</h5>
                
                <div class="rating-category">
                  <label>Internet Speed</label>
                  <select class="form-select" name="internet_speed" required>
                    <option value="" disabled selected>Select rating</option>
                    <option value="1">1 - Poor</option>
                    <option value="2">2 - Below Average</option>
                    <option value="3">3 - Average</option>
                    <option value="4">4 - Good</option>
                    <option value="5">5 - Excellent</option>
                  </select>
                </div>

                <div class="rating-category">
                  <label>Service Reliability</label>
                  <select class="form-select" name="reliability" required>
                    <option value="" disabled selected>Select rating</option>
                    <option value="1">1 - Unreliable</option>
                    <option value="2">2 - Occasionally unreliable</option>
                    <option value="3">3 - Somewhat reliable</option>
                    <option value="4">4 - Reliable</option>
                    <option value="5">5 - Very reliable</option>
                  </select>
                </div>

                <div class="rating-category">
                  <label>Signal Strength</label>
                  <select class="form-select" name="signal_strength" required>
                    <option value="" disabled selected>Select rating</option>
                    <option value="1">1 - Weak</option>
                    <option value="2">2 - Fair</option>
                    <option value="3">3 - Moderate</option>
                    <option value="4">4 - Strong</option>
                    <option value="5">5 - Excellent</option>
                  </select>
                </div>

                <div class="rating-category">
                  <label>Customer Service</label>
                  <select class="form-select" name="customer_service" required>
                    <option value="" disabled selected>Select rating</option>
                    <option value="1">1 - Poor</option>
                    <option value="2">2 - Needs improvement</option>
                    <option value="3">3 - Satisfactory</option>
                    <option value="4">4 - Good</option>
                    <option value="5">5 - Excellent</option>
                  </select>
                </div>

                <div class="rating-category">
                  <label>Installation Service</label>
                  <select class="form-select" name="installation_service" required>
                    <option value="" disabled selected>Select rating</option>
                    <option value="1">1 - Poor</option>
                    <option value="2">2 - Below expectations</option>
                    <option value="3">3 - Met expectations</option>
                    <option value="4">4 - Exceeded expectations</option>
                    <option value="5">5 - Outstanding</option>
                  </select>
                </div>

                <div class="rating-category">
                  <label>Equipment Quality</label>
                  <select class="form-select" name="equipment_quality" required>
                    <option value="" disabled selected>Select rating</option>
                    <option value="1">1 - Poor</option>
                    <option value="2">2 - Adequate</option>
                    <option value="3">3 - Good</option>
                    <option value="4">4 - Very good</option>
                    <option value="5">5 - Excellent</option>
                  </select>
                </div>
              </div>

              <div class="mb-4">
                <label for="comments" class="form-label">Additional Comments / Suggestions</label>
                <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Please share any additional thoughts about your experience..."></textarea>
              </div>

              <div class="mb-4">
                <label class="form-label">Overall Experience:</label><br>
                <div class="star-rating mb-2" id="starRating">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star-fill" data-value="<?= $i ?>"></i>
                  <?php endfor; ?>
                </div>
                <div class="d-flex justify-content-between">
                  <small class="text-muted">Poor</small>
                  <small class="text-muted">Excellent</small>
                </div>
                <input type="hidden" name="overall_rating" id="overall_rating" value="0" required>
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="bi bi-send-fill me-2"></i> Submit Feedback
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="foot-container">
    <!-- ... (keep your existing footer code) ... -->
  </div>

  <script>
    // Enhanced star rating with hover effect
    const stars = document.querySelectorAll('.star-rating i');
    const ratingInput = document.getElementById('overall_rating');

    stars.forEach(star => {
      star.addEventListener('click', () => {
        const value = parseInt(star.getAttribute('data-value'));
        ratingInput.value = value;
        stars.forEach(s => s.classList.remove('checked'));
        for (let i = 0; i < value; i++) {
          stars[i].classList.add('checked');
        }
      });
      
      star.addEventListener('mouseover', () => {
        const value = parseInt(star.getAttribute('data-value'));
        stars.forEach((s, index) => {
          if (index < value) {
            s.style.color = 'gold';
          } else {
            s.style.color = '#ccc';
          }
        });
      });
      
      star.addEventListener('mouseout', () => {
        const currentRating = parseInt(ratingInput.value);
        stars.forEach((s, index) => {
          if (index < currentRating) {
            s.style.color = 'gold';
          } else {
            s.style.color = '#ccc';
          }
        });
      });
    });

    // Image preview functionality
    document.getElementById('photo').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
          const preview = document.getElementById('previewImage');
          preview.src = event.target.result;
          preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
      }
    });

    // Form validation
    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
      if (parseInt(ratingInput.value) === 0) {
        e.preventDefault();
        alert('Please provide an overall rating by clicking the stars');
      }
    });
  </script>
</body>
</html>