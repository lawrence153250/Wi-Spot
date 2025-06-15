<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'capstonesample');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

    // Validate inputs
    $errors = [];
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($firstname)) $errors[] = "First name is required";
    if (empty($lastname)) $errors[] = "Last name is required";
    if (empty($birthday)) $errors[] = "Birthday is required";
    if (empty($contactnumber)) $errors[] = "Contact number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($facebookProfile)) $errors[] = "Facebook profile is required";
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (strlen($password) < 8 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must be at least 8 characters with uppercase, lowercase, number, and special character";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Password and Confirm Password do not match!";
    }

    if (!empty($birthday)) {
        $birthDate = new DateTime($birthday);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        if ($age < 18) {
            $errors[] = "You must be at least 18 years old to register";
        }
    }

    if (!preg_match('/^09\d{9}$/', $contactnumber)) {
        $errors[] = "Invalid Philippine number format. Must start with 09 followed by 9 digits (11 digits total)";
    }

    if (!preg_match('/^(https?:\/\/)?(www\.)?facebook\.com\/[a-zA-Z0-9._-]+\/?$/', $facebookProfile)) {
        $errors[] = "Invalid Facebook profile URL";
    }

    // Only check database if no validation errors
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM customer WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customer (firstname, lastname, username, password, email, birthday, contactnumber, address, facebookProfile) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $firstname, $lastname, $username, $hashed_password, $email, $birthday, $contactnumber, $address, $facebookProfile);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header("Location: login.php?success=1");
                exit();
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Save form data to repopulate
        header("Location: register.php");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}

$conn->close();
?>