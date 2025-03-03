<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Deletion</title>
    <script src="js/jquery.min.js"></script>
    <script>
        function showAlert(message, reload = true ){
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
        $commentIndex = (int)$_POST['comment_index'];
        $userId = $_SESSION['user_id'];

        foreach ($posts as &$post) {
            if ($post['id'] === $postId && isset($post['comments'][$commentIndex])) {
                if ($post['comments'][$commentIndex]['user_id'] === $userId) {
                    array_splice($post['comments'], $commentIndex, 1);
                    file_put_contents($postsFile, json_encode($posts, JSON_PRETTY_PRINT));
                    echo "<script>showAlert('Comment deleted successfully!', true);</script>";
                    exit();
                } else {
                    echo "<script>showAlert('You can only delete your own comments.');</script>";
                    exit();
                }
            }
        }

        echo "<script>showAlert('Error 204. Comment is lost :(');</script>";
    }
    ?>
</body>
</html>