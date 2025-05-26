<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo '<div class="alert">You need to log in first. Redirecting to login page...</div>';
    header("Refresh: 3; url=login.php");
    exit();
}

$username = $_SESSION['username'];

$conn = new mysqli('localhost', 'root', '', 'capstonesample');

$sql = "SELECT * FROM customer WHERE username = '$username'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();


// Upload Profile Image Function
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profileImage'])) {
    $profileImage = $_FILES['profileImage']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($profileImage);
    if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $target_file)) {
        $sql = "UPDATE customer SET profileImage = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $target_file, $_SESSION['username']);
        $stmt->execute();
        $message = "Image uploaded successfully!";
        $messageType = "success";
        echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
    } else {
        $message = "Error Uploading Image!";
        $messageType = "error";
        echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
    }
}

// Update Profile function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $firstName = htmlspecialchars($_POST['firstName']);
    $lastName = htmlspecialchars($_POST['lastName']);
    $email = htmlspecialchars($_POST['email']);
    $birthday = htmlspecialchars($_POST['birthday']);
    $contactNumber = htmlspecialchars($_POST['contactNumber']);
    $address = htmlspecialchars($_POST['address']);
    $facebookProfile = htmlspecialchars($_POST['facebookProfile']);

    // Update query
    $sql = "UPDATE customer SET 
                firstName = ?, 
                lastName = ?, 
                email = ?, 
                birthday = ?, 
                contactNumber = ?, 
                address = ?, 
                facebookProfile = ? 
            WHERE username = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $firstName, $lastName, $email, $birthday, $contactNumber, $address, $facebookProfile, $username);

    if ($stmt->execute()) {
        $message = "Profile Updated successfully!";
        $messageType = "success";
        echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
    } else {
        $message = "Error Updating Profile";
        $messageType = "error";
        echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
    }
}

// Reset password function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    $current_password = htmlspecialchars($_POST['current_password']);
    $new_password = htmlspecialchars($_POST['new_password']);
    $confirm_new_password = htmlspecialchars($_POST['confirm_new_password']);

    $sql = "SELECT * FROM customer WHERE username = '$username'";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();

    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_new_password) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

            $sql = "UPDATE customer SET password = '$hashed_new_password' WHERE username = '$username'";
            if ($conn->query($sql) === TRUE) {
                $message = "Password has been reset successfully!";
                $messageType = "success";
                echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
            } else {
                $message = "Error updating password: " . $conn->error;
                $messageType = "error";
                echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
            }
        } else {
            $message = "New password and Confirm new password do not match!";
            $messageType = "error";
            echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
        }
    } else {
        $message = "Current password is incorrect!";
        $messageType = "error";
        echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
    }
}

//  Upload Id Function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['uploadId'])) {
    $idNumber = htmlspecialchars($_POST['idNumber']);

    // Check if file is uploaded
    if (isset($_FILES['validId']) && $_FILES['validId']['error'] == 0) {
        $fileTmpPath = $_FILES['validId']['tmp_name'];
        $fileType = $_FILES['validId']['type'];

        // Allowed file types
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($fileType, $allowedTypes)) {
            $message = "Invalid file type. Only JPG and PNG are allowed";
            $messageType = "error";
            echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
            exit();
        }

        // Read the image file as binary data
        $validId = file_get_contents($fileTmpPath);

        // Prepare SQL query using prepared statement
        $stmt = $conn->prepare("UPDATE customer SET validId = ?, idNumber = ? WHERE username = ?");
        $stmt->bind_param("bss", $null, $idNumber, $username);

        // Send binary dataW
        $stmt->send_long_data(0, $validId);

        if ($stmt->execute()) {
            $message = "ID uploaded successfully!";
            $messageType = "success";
            echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
            
        } else {
            $message = "Error uploading ID: " . $stmt->error . "";
            $messageType = "error";
            echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
        }

        $stmt->close();
    } else {
        $message = "Please upload a valid ID image.";
        $messageType = "error";
        echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
    }
} 

// Upload Proof of Billing Function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['uploadProofBilling'])) {
    // Check if file is uploaded
    if (isset($_FILES['ProofOfBilling']) && $_FILES['ProofOfBilling']['error'] == 0) {
        $fileTmpPath = $_FILES['ProofOfBilling']['tmp_name'];
        $fileType = $_FILES['ProofOfBilling']['type'];

        // Allowed file types
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($fileType, $allowedTypes)) {
            $message = "Invalid file type. Only JPG and PNG are allowed.";
            $messageType = "error";
            echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
            exit();
        }

        // Read the image file as binary data
        $proofOfBilling = file_get_contents($fileTmpPath);

        // Prepare SQL query using prepared statement
        $stmt = $conn->prepare("UPDATE customer SET proofOfBilling = ? WHERE username = ?");
        $stmt->bind_param("bs", $null, $username);

        // Send binary data
        $stmt->send_long_data(0, $proofOfBilling);

        if ($stmt->execute()) {
            $message = "Proof of Billing uploaded successfully!";
            $messageType = "success";
            echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
        } else {
            $message = "Error uploading Proof of Billing: " . $stmt->error . "";
            $messageType = "error";
            echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
        }

        $stmt->close();
    } else {
        $message = "Please upload a valid Proof of Billing image.";
        $messageType = "error";
        echo "<script>setTimeout(() => { window.location.href = 'profile.php'; }, 3000);</script>";
    }
}

function formatDate($dateString) {
    return date("F j, Y", strtotime($dateString));
}

// Format the user's birthday
$formattedBirthday = formatDate($user['birthday']);

// Retrieve stored ID and ID number
$sql = "SELECT validId, idNumber, proofOfBilling FROM customer WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($validId, $idNumber, $proofOfBilling);
$stmt->fetch();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="profilestyle.css">
</head>
<style>
    body {
        margin: 0;
        color: #2e323c;
        background: #f5f6fa;
        position: relative;
        height: 100%;

    }
    .account-settings .user-profile {
        margin: 0 0 1rem 0;
        text-align: center;
        
    }
    .account-settings .user-profile .user-avatar {
        margin: 0 0 1rem 0;
    }
    .account-settings .user-profile .user-avatar img {
        width: 200px;
        height: 200px;
        -webkit-border-radius: 100px;
        -moz-border-radius: 100px;
        border-radius: 100px;
        margin-right: 250px;
        margin-bottom: 20px;
    }
    .account-settings .user-profile h5.user-name {
        margin: 0 0 0.5rem 0;
    }
    .account-settings .user-profile h6.user-email {
        margin: 0;
        font-size: 0.8rem;
        font-weight: 400;
        color: #9fa8b9;
    }
    .account-settings .about {
        margin: 2rem 0 0 0;
        text-align: center;
    }
    .account-settings .about h5 {
        margin: 0 0 15px 0;
        color: #007ae1;
    }
    .account-settings .about p {
        font-size: 0.825rem;
    }
    .form-control {
        border: 1px solid #cfd1d8;
        -webkit-border-radius: 2px;
        -moz-border-radius: 2px;
        border-radius: 2px;
        font-size: .825rem;
        background: #ffffff;
        color: #2e323c;
    }

    .card {
        background: #ffffff;
        -webkit-border-radius: 5px;
        -moz-border-radius: 5px;
        border-radius: 5px;
        border: 0;
        margin-bottom: 1rem;
    }

    .reset-password-form {
        max-width: 600px;
        margin: auto;
    }
    .reset-password-form .form-group {
        margin-bottom: 1rem;
    }
    .reset-password-form button {
        width: 100%;
    }

    .user-info-section {
        border: 1px solid #cfd1d8;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
    background-color: #f5f6fa;   
 }
    .popup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        text-align: center;
    }
    .popup.success {
        border: 2px solid green;
        color: green;
    }
    .popup.error {
        border: 2px solid red;
        color: red;
    }
    .popup button {
        margin-top: 10px;
    }

</style>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark" id="grad">
        <div class="nav-container">
            <a class="navbar-brand" href="index.php"><img src="logo.png"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item active">
                        <a class="nav-link" href="index.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="booking.php">BOOKING</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mapcoverage.php">MAP COVERAGE</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="aboutus.php">ABOUT US</a>
                    </li>
                    <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php"><?php echo $_SESSION['username']; ?> <i class="bi bi-person-circle"></i></a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">LOGIN</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">SIGN UP</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="row gutters">
            <div class="col-xl-3 col-lg-3 col-md-12 col-sm-12 col-12">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="account-settings">
                            <div class="user-profile">
                                <div class="user-avatar">
                                    <h2>User Account</h2>
                                    <div class="profile-image">
                                        <?php
                                        $conn = new mysqli('localhost', 'root', '', 'capstonesample');
                                        $sql = "SELECT profileImage FROM customer WHERE username = ?";
                                        $stmt = $conn->prepare($sql);
                                        $stmt->bind_param("s", $_SESSION['username']);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        if ($result->num_rows > 0) {
                                            $row = $result->fetch_assoc();
                                            if (!empty($row['profileImage'])) {
                                                echo "<img src='{$row['profileImage']}' alt='Image' width='100'>";
                                                echo '<br><a class="bi bi-pencil text-altlight" data-bs-toggle="collapse" href="#uploadForm" role="button" aria-expanded="false" aria-controls="uploadForm"></a>';;
                                                
                                           }else { ?>
                                                <form method="post" enctype="multipart/form-data">
                                                <input type="file" name="profileImage" id="profileImage" required> <br>
                                                <div class="form-group">
                                                    <input type="submit" value="Upload Image">
                                                </div>
                                                </form>
                                            <?php
                                            }
                                        }
                                        $conn->close();
                                        ?>
                                        <div class="collapse mt-2" id="uploadForm">
                                            <div class="card-cont">
                                                <form method="post" enctype="multipart/form-data">
                                                    <input type="file" name="profileImage" id="profileImage" required> <br>
                                                    <div class="form-group">
                                                        <input type="submit" name="uploadProfile" value="Upload Image" class="btn btn-success">
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="form-group">    
                                        <a href="logout.php">Logout</a><br>
                                        <a href="viewtransactions.php">View Transactions</a><br>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#resetPass">Reset Password</a><br>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#resetProfile">Edit Profile Information</a>
                                    </div>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            <div class="col-xl-9 col-lg-9 col-md-12 col-sm-12 col-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4>User Information</h4>
                        <hr>
                        <div class="user-info-section">
                            <p><strong>Name: </strong> <?php echo $user['firstName'] .' '. $user['lastName']; ?></p>
                        </div>
                        <div class="user-info-section">
                            <p><strong>Birthday: </strong> <?php echo $formattedBirthday; ?></p>
                        </div>
                        <div class="user-info-section">
                            <p><strong>Address: </strong><?php echo $user['address']; ?></p>
                        </div>
                        <div class="user-info-section">
                            <p><strong>&nbsp; Email: </strong><?php echo $user['email'] ?></p>
                        </div>
                        <div class="user-info-section">
                            <p><strong>&nbsp; Contact: </strong><?php echo $user['contactNumber'] ?></p>
                        </div>
                        <div class="user-info-section">
                            <p><strong>&nbsp; Facebook Profile Link: </strong><?php echo $user['facebookProfile'] ?></p>
                        </div>
                        <div class="user-info-section">
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#viewIdModal">View Uploaded ID</button>
                        </div>
                        <div class="user-info-section">
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#viewproofModal">View Proof of Billing</button>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </div>
    <div class="background">
    <p style="background-image: url('img/Background.png');" >
    </p>
    </div>
    <!-- footer -->
    <div class="foot-container" id="grad">
            <div class="foot-icons">
                <a href="https://www.youtube.com/" class="bi bi-youtube text-altlight" target=”_blank”></a>
                <a href="https://web.facebook.com/" class="bi bi-facebook text-altlight" target=”_blank”></a>
                <a href="https://www.instagram.com/" class="bi bi-instagram text-altlight" target=”_blank”></a>
                <a href="https://www.tiktok.com/" class="bi bi-tiktok text-altlight" target=”_blank”></a>
            </div>
            <hr>
            <div class="foot-policy">
                <div class="row">
                    <div class="col-md-3">
                        <a class="foot-policy text-altlight" href="termsofservice.php" target="_blank">Terms of Service</a>
                    </div>
                    <div class="col-md-3">
                        <a class="foot-policy text-altlight" href="copyrightpolicy.php" target="_blank">Copyright Policy</a>
                    </div>
                    <div class="col-md-3">
                        <a class="foot-policy text-altlight" href="privacypolicy.php" target="_blank">Privacy Policy</a>
                    </div>
                    <div class="col-md-3">
                        <a class="foot-policy text-altlight" href="contactus.php" target=”_blank”>Contact Us</a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="foot_text text-altlight">
                <p>Wi-spot is available in English, French, German, Italian, Spanish, and more.</p><br>
                <p>
                    &copy;2025 Wi-spot. All rights reserved. Wi-spot and related trademarks and logos are the property of Wi-spot. All other trademarks are the property of their respective owners.
                </p><br>
                <p>
                    This webpage is for educational purposes only and no copyright infringement is intended.
                </p>
            </div>
        </div>
    
    
    </div>

    <!-- Reset password modal -->
    <div class="modal fade" id="resetPass" tabindex="-1" aria-labelledby="resetPassLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPassLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Enter Current Password:</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_new_password">Confirm New Password:</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="reset" class="btn btn-primary">Reset Password</button>
                            
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Profile Modal -->
    <div class="modal fade" id="resetProfile" tabindex="-1" aria-labelledby="resetProfileLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetProfileLabel">Update Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="firstName">First Name:</label>
                            <input type="text" id="firstName" name="firstName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name:</label>
                            <input type="text" id="lastName" name="lastName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="birthday">Birthday: </label>
                            <input type="date" id="birthday" name="birthday" class="form-control" value="<?php echo $user['birthday']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="contactNumber">Contact number:</label>
                            <input type="text" id="contactNumber" name="contactNumber" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address:</label>
                            <input type="text" id="address" name="address" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="facebookProfile">Facebook Profile link:</label>
                            <input type="text" id="facebookProfile" name="facebookProfile" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="update" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Upload ID modal -->
    <div class="modal fade" id="uploadIdModal" tabindex="-1" aria-labelledby="uploadIdModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadIdModalLabel">Upload Your ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- File Upload -->
                        <div class="mb-3">
                            <label for="validId" class="form-label">Upload ID Image (JPG/PNG)</label>
                            <input type="file" class="form-control" id="validId" name="validId" accept="image/*" required>
                        </div>

                        <!-- ID Number -->
                        <div class="mb-3">
                            <label for="idNumber" class="form-label">Enter ID Number</label>
                            <input type="text" class="form-control" id="idNumber" name="idNumber" required>
                        </div>

                        <button type="submit" class="btn btn-success" name="uploadId">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Uploaded ID modal -->
    <div class="modal fade" id="viewIdModal" tabindex="-1" aria-labelledby="viewIdModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewIdModalLabel">Your Uploaded ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <?php if (!empty($validId) && !empty($idNumber)): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($validId) ?>" alt="Uploaded ID" class="img-fluid mb-3" style="max-width: 300px;">
                        <p><strong>ID Number:</strong> <?= htmlspecialchars($idNumber) ?></p>
                    <?php else: ?>
                        <p>You don't have any ID uploaded yet!</p><br>
                        <p>Verify Your Account by uploading your ID:</p><br>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadIdModal">
                            Upload ID
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Proof of Billing Modal -->
    <div class="modal fade" id="uploadproofModal" tabindex="-1" aria-labelledby="uploadproofModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadproofModalLabel">Upload Your Proof of Billing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="ProofOfBilling" class="form-label">Upload Proof of Billing Image (JPG/PNG)</label>
                            <input type="file" class="form-control" id="ProofOfBilling" name="ProofOfBilling" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-success" name="uploadProofBilling">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Uploaded Proof of Billing Modal -->
    <div class="modal fade" id="viewproofModal" tabindex="-1" aria-labelledby="viewproofModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewproofModalLabel">Your Uploaded Proof of Billing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <?php if (!empty($proofOfBilling)): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($proofOfBilling) ?>" alt="Uploaded Proof of Billing" class="img-fluid mb-3" style="max-width: 300px;">
                    <?php else: ?>
                        <p>You haven't uploaded a Proof of Billing yet!</p><br>
                        <p>Please upload one for verification:</p><br>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadproofModal">
                            Upload Proof of Billing
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
     
    <?php if (!empty($message)) : ?>
        <div id="messagePopup" class="popup <?= $messageType ?>">
            <p><?= $message ?></p>
            <button onclick="closePopup()">OK</button>
        </div>

        <script>
            document.getElementById("messagePopup").style.display = "block";
            setTimeout(() => { document.getElementById("messagePopup").style.display = "none"; }, 3000);
        </script>
    <?php endif; ?>

    <script>
        function closePopup() {
            document.getElementById("messagePopup").style.display = "none";
        }
    </script>
</body>
</html>
