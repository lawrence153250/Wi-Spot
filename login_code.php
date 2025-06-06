<?php
$conn = new mysqli('localhost', 'root', '', 'capstonesample');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $user = null;
    $userType = null;

    $tables = [
        'customer' => 'username',
        'admin' => 'userName',
        'staff' => 'userName'
    ];

    foreach ($tables as $table => $column) {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE $column = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userType = $table;
            break;
        }
    }

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $username;
        $_SESSION['userType'] = $userType;
        $_SESSION['userlevel'] = ($userType === 'customer') ? ($user['userLevel'] ?? 'customer') : $userType;

        switch ($_SESSION['userlevel']) {
            case 'admin':
                header("Location: adminhome.php");
                break;
            case 'staff':
                header("Location: staff_dashboard.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();
    } else {
        $error_message = "Invalid username or password!";
    }
}

$conn->close();
