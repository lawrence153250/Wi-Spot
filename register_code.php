<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Sanitize inputs
    $username = trim(htmlspecialchars($_POST['username']));
    $email = trim(htmlspecialchars($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $firstname = trim(htmlspecialchars($_POST['firstname']));
    $lastname = trim(htmlspecialchars($_POST['lastname']));
    $birthday = $_POST['birthday'];
    $contactnumber = trim(htmlspecialchars($_POST['contactnumber']));
    $address = trim(htmlspecialchars($_POST['address']));
    $facebookProfile = trim(htmlspecialchars($_POST['facebookProfile']));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Validate password requirements
    if (strlen($password) < 8 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must be at least 8 characters with uppercase, lowercase, number, and special character";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Validate password match
    if ($password !== $confirm_password) {
        $error = "Password and Confirm Password do not match!";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Validate age (must be at least 18)
    $birthDate = new DateTime($birthday);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    if ($age < 18) {
        $error = "You must be at least 18 years old to register";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Validate Philippine contact number format (+63 followed by 10 digits)
    if (!preg_match('/^09\d{9}$/', $contactnumber)) {
        $error = "Invalid Philippine number format. Must start with 09 followed by 9 digits (11 digits total)";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Validate Facebook profile URL
    if (!preg_match('/^(https?:\/\/)?(www\.)?facebook\.com\/[a-zA-Z0-9._-]+\/?$/', $facebookProfile)) {
        $error = "Invalid Facebook profile URL";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Check if username or email already exists using prepared statement
    $stmt = $conn->prepare("SELECT * FROM customer WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Username or email already exists";
        header("Location: register.php?error=" . urlencode($error));
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user using prepared statement
    $stmt = $conn->prepare("INSERT INTO customer (firstname, lastname, username, password, email, birthday, contactnumber, address, facebookProfile) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $firstname, $lastname, $username, $hashed_password, $email, $birthday, $contactnumber, $address, $facebookProfile);

    if ($stmt->execute()) {
        header("Location: register.php?success=1");
    } else {
        $error = "Database error: " . $conn->error;
        header("Location: register.php?error=" . urlencode($error));
    }
    
    $stmt->close();
} else {
    header("Location: register.php");
}

$conn->close();
exit();
?>