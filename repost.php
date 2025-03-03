<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$postsFile = 'posts.json';
$posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];

if (isset($_POST['post_id'])) {
    $postId = $_POST['post_id'];
    $originalPost = null;

    // Cari post yang akan di-repost
    foreach ($posts as $post) {
        if ($post['id'] == $postId) {
            $originalPost = $post;
            break;
        }
    }

    if ($originalPost) {
        // Buat post baru berdasarkan post yang di-repost
        $repost = [
            'id' => uniqid(), // Generate unique ID for the repost
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'title' => $originalPost['title'],
            'content' => $originalPost['content'],
            'media' => $originalPost['media'],
            'topic' => $originalPost['topic'],
            'rating' => 0,
            'rating_count' => 0,
            'timestamp' => date("Y-m-d H:i:s"),
            'reposted_from' => $originalPost['user_id'], // Menyimpan ID user yang asli
            'reposted_from_username' => $originalPost['username'] // Menyimpan username yang asli
        ];

        // Tambahkan repost ke dalam array posts
        $posts[] = $repost;

        // Simpan kembali ke file JSON
        file_put_contents($postsFile, json_encode($posts, JSON_PRETTY_PRINT));

        header("Location: community.php");
        exit();
    } else {
        echo "Post not found.";
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}
?>