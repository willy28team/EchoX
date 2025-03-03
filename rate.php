<?php
session_start();

// Determine if the request is AJAX (submitted via fetch)
$isAjax = false;
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $isAjax = true;
}

// Helper function to output the response accordingly
function outputResponse($response, $isAjax) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Show the message in an alert and redirect back to community.php
        echo "<script>alert('{$response['message']}'); window.location.href='community.php';</script>";
    }
    exit();
}

if (!isset($_SESSION['user_id'])) {
    outputResponse(["status" => "error", "message" => "Unauthorized access."], $isAjax);
}

$postsFile = 'posts.json';
$posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)$_POST['post_id'];
    $rating = (int)$_POST['rating'];
    $userId = $_SESSION['user_id'];

    if ($rating < 1 || $rating > 5) {
        outputResponse(["status" => "error", "message" => "Invalid rating."], $isAjax);
    }

    foreach ($posts as &$post) {
        if ($post['id'] === $postId) {
            // Ensure ratings array exists
            if (!isset($post['ratings'])) {
                $post['ratings'] = [];
            }

            // Check if user already rated
            if (isset($post['ratings'][$userId])) {
                outputResponse(["status" => "error", "message" => "You have already rated this post."], $isAjax);
            }

            // Store user's rating
            $post['ratings'][$userId] = $rating;

            // Calculate new average rating
            $totalRatings = array_values($post['ratings']);
            $post['rating'] = array_sum($totalRatings) / count($totalRatings);
            $post['rating_count'] = count($totalRatings);

            file_put_contents($postsFile, json_encode($posts, JSON_PRETTY_PRINT));
            outputResponse([
                "status" => "success",
                "message" => "Rating submitted successfully!",
                "new_rating" => $post['rating']
            ], $isAjax);
        }
    }

    outputResponse(["status" => "error", "message" => "Post not found."], $isAjax);
}
?>
