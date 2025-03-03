<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$postsFile = 'posts.json';
$relationsFile = 'relations.json';

$posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];
$relations = file_exists($relationsFile) ? json_decode(file_get_contents($relationsFile), true) : [];

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Load the per‑content blacklist (post IDs)
$content_blacklist = isset($relations[$user_id]['content_blacklist']) ? $relations[$user_id]['content_blacklist'] : [];
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/jpg" href="cn.jpg">
    <title>Blacklisted Contents</title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --danger-color: #dc3545;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-color: #333333;
            --border-color: #dee2e6;
        }
        .rating-dropdown {
            width: 150px;
            padding: 8px;
            border: 2px solid rgb(0, 0, 0);
            opacity: 0.5;
            border-radius: 5px;
            background-color: #fff;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease-in-out;
        }
        .rating-dropdown:hover {
            border-color: #0056b3;
        }
        .rating-dropdown:focus {
            border-color: #0056b3;
            box-shadow: 0px 0px 5px rgba(0, 91, 187, 0.5);
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            max-width: 800px;
            margin: 0 auto;
        }
        h2, h3 {
            color: var(--text-color);
            margin: 20px 0;
            text-align: center;
        }
        .post-form {
            background: var(--card-background);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: 10px;
            resize: vertical;
            min-height: 100px;
        }
        .post {
            background: var(--card-background);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post strong {
            color: var(--primary-color);
            font-size: 1.1em;
        }
        .post img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            margin: 0 5px;
        }
        button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        input[type="file"] {
            margin: 10px 0;
        }
        input[type="number"] {
            padding: 6px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 60px;
        }
        #logoutBtn {
            background-color: var(--danger-color);
            color: white;
            position: fixed;
            top: 20px;
            right: 20px;
        }
    </style>
    <script src="js/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <h2>Blacklisted Contents for <?php echo htmlspecialchars($username); ?></h2>

        <!-- Navigation Buttons -->
        <div style="margin-bottom: 20px;">
            <button onclick="window.location.href='community.php'" style="background-color: var(--primary-color); color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Back to Community</button>
            <button id="logoutBtn">Logout</button>
        </div>

        <h3>Blacklisted Posts</h3>
        <?php
            $found = false;
            foreach ($posts as $post):
                if (in_array($post['id'], $content_blacklist)):
                    $found = true;
        ?>
            <div class="post">
                <div class="post-header">
                    <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                    <small class="timestamp">Posted on: <?php echo date("F j, Y, g:i A", strtotime($post['timestamp'] ?? '')); ?></small>
                </div>
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php if ($post['media']): ?>
                    <img src="<?php echo htmlspecialchars($post['media']); ?>" alt="Post media">
                <?php endif; ?>
                <div class="rating-info">
                    Rating: <?php echo number_format($post['rating'], 2); ?> (<?php echo $post['rating_count']; ?> votes)
                </div>
                <div class="actions">
                    <!-- Button to remove this post from the content blacklist -->
                    <form action="relation.php" method="post" style="display:inline;">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" name="action" value="remove_content_blacklist">Remove from Blacklist</button>
                    </form>
                </div>
            </div>
        <?php
                endif;
            endforeach;
            if (!$found):
        ?>
            <p>No posts have been blacklisted.</p>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById("logoutBtn").addEventListener("click", function() {
            fetch("logout.php")
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                window.location.href = "login.html";
            })
            .catch(error => console.error("Error:", error));
        });
    </script>
</body>
</html>
