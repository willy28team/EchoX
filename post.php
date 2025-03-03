<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$postsFile = 'posts.json';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$content = $_POST['content'];
$title = $_POST['title'];
$topic = $_POST['topic'];
$media = '';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = htmlspecialchars($_POST['content']);
    $imagePath = '';

    // Handle File Upload
    if (!empty($_FILES['image']['name'])) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExt, $allowedTypes)) {
            $uniqueName = uniqid() . "." . $fileExt;
            $destination = $uploadDir . $uniqueName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $imagePath = $destination;
            } else {
                die("❌ Error uploading the image!");
            }
        } else {
            die("❌ Invalid file type! Only JPG, PNG, GIF allowed.");
        }
    }

    // Save post
    date_default_timezone_set('Asia/Singapore'); // Set to GMT+8
    $newPost = [
        "id" => count($posts) + 1,
        "user_id" => $user_id,
        "username" => $username,
        "content" => $content,
        "media" => $imagePath,
        "title" => $title,
        "topic" => $topic,
        "rating" => 0,
        "rating_count" => 0,
        "ratings" => (object) [], // ✅ Ensures JSON keeps it as {}
        "timestamp" => date("Y-m-d H:i:s"),
        "comments" => [] // ✅ Initialize as empty array
    ];
    $posts[] = $newPost;
    file_put_contents($postsFile, json_encode($posts, JSON_PRETTY_PRINT));

    header("Location: community.php");
    exit();
}

?>
