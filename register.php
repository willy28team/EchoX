<?php
session_start();
$usersFile = 'users.json';

// Load existing users
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username exists
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            // Username already taken
            echo "<script>alert('Username is already taken!'); window.history.back();</script>";
            exit();
        }
    }

    // Assign unique user ID
    $newUser = [
        "id" => count($users) + 1,
        "username" => $username,
        "password" => $password
    ];
    $users[] = $newUser;

    // Save users to file
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));

    // Registration successful
    echo "<script>alert('Registration Successful! Redirecting to login page...'); window.location.href = 'login.html';</script>";
    exit();
}
?>