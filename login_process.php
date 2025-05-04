<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize user input
    $uid = htmlspecialchars(trim($_POST['uid'] ?? ''));
    $pass = htmlspecialchars(trim($_POST['pass'] ?? ''));

    $correct_uid = 'admin';
    $correct_pass = 'admin';

    if ($uid === $correct_uid && $pass === $correct_pass) {
        session_regenerate_id(true); 

        $_SESSION['uid'] = $uid;
        $_SESSION['loggedIn'] = true;

        echo "success";
    } else {
        sleep(1); 
        echo "error";
    }
} else {
    header("Location: index.php");
    exit();
}
?>
