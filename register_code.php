<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);
    $confirm_password = htmlspecialchars($_POST['confirm_password']);
    $firstname = htmlspecialchars($_POST['firstname']);
    $lastname = htmlspecialchars($_POST['lastname']);
    $birthday = htmlspecialchars($_POST['birthday']);
    $contactnumber = htmlspecialchars($_POST['contactnumber']);
    $address = htmlspecialchars($_POST['address']);
    $facebookProfile = htmlspecialchars($_POST['facebookProfile']);

    if ($password === $confirm_password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO customer (firstname, lastname, username, password, email, birthday, contactnumber, address, facebookProfile) 
                VALUES ('$firstname', '$lastname', '$username', '$hashed_password', '$email', '$birthday', '$contactnumber', '$address', '$facebookProfile')";

        if ($conn->query($sql) === TRUE) {
            header("Location: register.php?success=1");
        } else {
            $error = "Database error: " . $conn->error;
            header("Location: register.php?error=" . urlencode($error));
        }
    } else {
        $error = "Password and Confirm Password do not match!";
        header("Location: register.php?error=" . urlencode($error));
    }
} else {
    header("Location: register.php");
}

$conn->close();
exit();
?>
