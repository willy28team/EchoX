<?php
session_start();

$postsFile = 'posts.json';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $userId = $_SESSION['user_id'];
    $posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];
    
    $postId = $_POST['post_id'];
    
    // Filter out the post if it belongs to the logged-in user
    $updatedPosts = array_filter($posts, function ($post) use ($postId, $userId) {
        return !($post['id'] == $postId && $post['user_id'] == $userId);
    });
    
    // Reset array keys
    $updatedPosts = array_values($updatedPosts);
    
    file_put_contents($postsFile, json_encode($updatedPosts, JSON_PRETTY_PRINT));
}

header("Location: community.php");
exit();
