<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Processing</title>
    <script src="js/jquery.min.js"></script>
    <script>
        function showAlert(message, reload = true) {
            alert(message);
            if (reload) {
                window.location.href = document.referrer || 'community.php';
            } else {
                window.history.back();
            }
        }
    </script>
</head>
<body>
    <?php
    if (!isset($_SESSION['user_id'])) {
        echo "<script>showAlert('Unauthorized access.');</script>";
        exit();
    }

    $postsFile = 'posts.json';
    $posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postId = (int)$_POST['post_id'];
        $content = trim($_POST['content']);
        $userId = $_SESSION['user_id'];
        $username = $_SESSION['username'];

        if (empty($content)) {
            echo "<script>showAlert('Comment cannot be empty.');</script>";
            exit();
        }

        foreach ($posts as &$post) {
            if ($post['id'] === $postId) {
                if (!isset($post['comments'])) {
                    $post['comments'] = [];
                }

                date_default_timezone_set('Asia/Singapore'); // Set to GMT+8

                $newComment = [
                    "user_id" => $userId,
                    "username" => $username,
                    "content" => $content,
                    "timestamp" => date("Y-m-d H:i:s")
                ];

                $post['comments'][] = $newComment;
                file_put_contents($postsFile, json_encode($posts, JSON_PRETTY_PRINT));

                echo "<script>showAlert('Comment added successfully!', true);</script>";
                exit();
            }
        }

        echo "<script>showAlert('Post not found.');</script>";
    }
    ?>
</body>
</html>