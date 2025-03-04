<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Fix: Wrap the function definition to avoid redeclaration errors and simplify condition
if (!function_exists('getUserRating')) {
    function getUserRating($userId, $allPosts) {
        $sum = 0;
        $count = 0;
        foreach ($allPosts as $p) {
            if ($p['user_id'] == $userId && $p['rating'] > 0) {
                $sum += $p['rating'];
                $count++;
            }
        }
        return $count > 0 ? round($sum / $count, 2) : 0;
    }
}

$postsFile = 'posts.json';
$relationsFile = 'relations.json';

$posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];
$searchpost = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];
$relations = file_exists($relationsFile) ? json_decode(file_get_contents($relationsFile), true) : [];

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$blacklist = isset($relations[$user_id]['blacklist']) ? $relations[$user_id]['blacklist'] : [];
$whitelist = isset($relations[$user_id]['whitelist']) ? $relations[$user_id]['whitelist'] : [];
// NEW: Load the content-based blacklist array (post IDs the user wants hidden)
$content_blacklist = isset($relations[$user_id]['content_blacklist']) ? $relations[$user_id]['content_blacklist'] : [];

// NEW: Get the sort filter from GET parameters (default to 'newest')
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// SEARCH BAR FILTER 
if (isset($_POST['search']) && !empty($_POST['search'])) {
    $searchText = trim($_POST['search']);
    $searchWords = explode(' ', strtolower($searchText));

    $filteredPosts = array_filter($posts, function ($post) use ($searchWords) {
        $title = strtolower($post['title']);
        $content = strtolower($post['content']);
        $combinedText = $title . ' ' . $content;

        // Check if all search words appear in order
        $position = 0;
        foreach ($searchWords as $word) {
            $newPosition = strpos($combinedText, $word, $position);
            if ($newPosition === false) {
                return false;
            }
            $position = $newPosition + strlen($word);
        }
        return true;
    });

    $posts = $filteredPosts;
}

if (isset($_GET['sort_topics']) && $_GET['sort_topics'] !== 'all') {
    $selectedTopic = $_GET['sort_topics'];

    // Filter posts based on the selected topic
    $posts = array_filter($posts, function ($post) use ($selectedTopic) {
        return isset($post['topic']) && $post['topic'] === $selectedTopic;
    });
}

// Now apply sorting to the filtered posts
if ($sort === 'newest') {
    usort($posts, function ($a, $b) {
        return strtotime($b['timestamp'] ?? '0') <=> strtotime($a['timestamp'] ?? '0');
    });
} elseif ($sort === 'highest') {
    usort($posts, function ($a, $b) {
        return $b['rating'] <=> $a['rating'];
    });
} elseif ($sort === 'lowest') {
    usort($posts, function ($a, $b) {
        return $a['rating'] <=> $b['rating'];
    });
}

// NEW: Reorder posts to place whitelisted posts at the top based on user's preference
if (!empty($whitelist)) {
    $whitelistedPosts = [];
    $otherPosts = [];
    foreach ($posts as $post) {
        if (in_array($post['user_id'], $whitelist)) {
            $whitelistedPosts[] = $post;
        } else {
            $otherPosts[] = $post;
        }
    }
    // Merge arrays so that whitelisted posts appear first, preserving each group's sorted order
    $posts = array_merge($whitelistedPosts, $otherPosts);
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/jpg" href="cn.jpg">
    <title>Community Notes</title>
    <style>
        .rules-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            display: none; /* Hidden by default */
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.5s ease-in-out;
        }

        .rules-container {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.8);
            border-radius: 0;
            font-family: Arial, Helvetica, sans-serif;
            position: relative;
            animation: slideIn 0.5s ease-in-out;
        }

        .rules-content {
            text-align: center;
            color: white;
            max-width: 800px;
            padding: 20px;
        }

        .rules-content h1 {
            font-size: 40px;
            margin-bottom: 20px;
        }

        .rules-content p {
            font-size: 20px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .rules-content button {
            width: 200px;
            height: 50px;
            border-radius: 25px;
            background-color: rgb(51, 118, 211);
            color: white;
            border: 0;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        .rules-content button:hover {
            background-color: #4a8bf9;
        }

        /* Keyframes for animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Keyframes for fade-out animation */
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Apply fade-out animation when hiding the overlay */
        .rules-overlay.hidden {
            animation: fadeOut 0.5s ease-in-out forwards; /* "forwards" keeps the final state */
        }

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
            position:relative;
            bottom:9px;
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
            /* Enable smooth scroll for browsers that support it */
            scroll-behavior: smooth;
        }
        /* NEW: Outer container for the posts and trending topics with a 3:1 ratio */
        .outer-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .left-col {
            flex: 3;
        }
        .right-col {
            flex: 1;
            /* Adjusted to extend further right */
            margin-right: 0;
        }
        /* NEW: Trending container remains the same but with a smaller font */
         
        .container {
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.97), rgba(245, 245, 245, 0.97));
    border-radius: 20px;
    padding: 30px;
    backdrop-filter: blur(12px);
    box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.25);
    max-width: 900px;
    margin: 0 auto; /* Keep centered */
    position: relative; /* Enable positioning */
    left: 60px; /* Shift 20px to the right */
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Trending container with a subtle gradient and hover effect */
.trending-container {
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(240, 240, 240, 0.95));
    border-radius: 18px;
    padding: 25px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 36px 0 rgba(31, 38, 135, 0.3);
    max-width: 100%;
    margin: 20px auto;
    font-size: 1em;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative; /* Enable positioning */
    right: 160px;
}

.trending-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 44px 0 rgba(31, 38, 135, 0.35);
}

/* Most Liked Posts container with a slightly different gradient and hover effect */
.most-liked-container {
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(235, 235, 235, 0.95));
    border-radius: 18px;
    padding: 25px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 36px 0 rgba(31, 38, 135, 0.3);
    max-width: 100%;
    margin: 20px auto;
    font-size: 1em;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative; /* Enable positioning */
    right: 160px;
}

.most-liked-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 44px 0 rgba(31, 38, 135, 0.35);
}

/* Delicate spacing between containers */
.trending-container + .most-liked-container {
    margin-top: 30px;
}

.container + .trending-container {
    margin-top: 30px;
}
        h2, h3 {
            color: var(--text-color);
            margin: 20px 0;
            text-align: center;
        }
        /* NEW: Increase the font size for welcome and community posts in the left column */
        .left-col h2 {
            font-size: 2.5em;
        }
        .left-col h3 {
            font-size: 2em;
        }
        .post {
            background: var(--card-background);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* NEW: Darker style for blacklisted posts */
        .post.blacklisted-user,
        .post.blacklisted-content {
            background: #e9e9e9;
        }
        .post.blacklisted-user p,
        .post.blacklisted-content p {
            color: #555;
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .post-header .user-info {
            display: flex;
            align-items: center;
        }
        .post-header .username {
            color: var(--primary-color);
            font-size: 1.1em;
            margin-right: 5px;
        }


        .user-rating {
            color: darkorange;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .timestamp {
            text-align: right;
            margin-left: auto;
            color: #666;
            font-size: 0.85em;
        }
        .rating-info {
            color: darkorange;
            font-size: 0.9em;
            margin: 10px 0;
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
        /* Dark Button Style for Blacklist Buttons */
        .dark-button {
            background-color: #343a40;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 20px 0;
        }
        .dark-button:hover {
            background-color: #23272b;
        }
        #blacklistList {
            list-style: none;
            background: var(--card-background);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 10px 0;
        }
        #blacklistList li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        /* Comments Section Styles */
        .comments-section {
            margin-top: 20px;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }
        .toggle-comments {
            background-color: #f0f0f0;
            color: #333;
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .toggle-comments:hover {
            background-color: #e0e0e0;
        }
        .comments {
            margin-top: 15px;
        }
        .comment {
            background-color: #f8f9fa;
            border-left: 3px solid var(--primary-color);
            margin: 10px 0;
            padding: 12px;
            border-radius: 0 4px 4px 0;
        }
        .comment strong {
            color: var(--primary-color);
            font-size: 0.95em;
        }
        .comment p {
            margin: 8px 0;
            font-size: 0.95em;
        }
        .comment small {
            color: #666;
            font-size: 0.85em;
            display: block;
            margin-top: 5px;
        }
        .comment form {
            margin-top: 8px;
        }
        .comment button[type="submit"] {
            background-color: var(--danger-color);
            color: white;
            padding: 4px 8px;
            font-size: 0.85em;
        }
        .comments form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin: 10px 0;
            min-height: 60px;
            font-family: inherit;
        }
        .comments form button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
        }
        .comments form button[type="submit"]:hover {
            background-color: #45a049;
        }
        .actions button:hover {
            opacity: 0.9;
        }
        /* Navigation button for new post */
        .nav-button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
        }

        .search-container {
            display: flex;
            max-width: 600px;
            margin: 20px auto;
            gap: 0;
        }

        .searchText {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-right: none;
            border-radius: 25px 0 0 25px;
            font-size: 16px;
            resize: none;
            outline: none;
            transition: border-color 0.3s ease;
            overflow: hidden; /* Hide scrollbars */
            height: 40px;
        }

        .searchText:focus {
            border-color: var(--primary-color);
        }

        .searchButton {
            padding: 12px 25px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 25px 25px 0;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .searchButton:hover {
            background-color: #45a049;
        }

        /* Responsive adjustment for smaller screens */
        @media (max-width: 600px) {
            .searchText {
                font-size: 14px;
                padding: 10px;
            }
            .outer-container {
                flex-direction: column;
            }
        }
        /* NEW: Styles for the Most Liked Posts blocks */
        .most-liked-post {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            background: var(--card-background);
        }
        .most-liked-post-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .most-liked-title {
            font-weight: bold;
        }
        .most-liked-rating {
            color: darkorange;
            font-size: 0.9em;
        }
    </style>
    <script src="js/jquery.min.js"></script>
</head>
<body>
    
    <div class="outer-container">
        <div class="left-col">
            <div class="container">
                <h2>Welcome to EchoX, <?php echo htmlspecialchars($username); ?>!</h2>

                <div class="rules-overlay" id="rulesOverlay">
        <div class="rules-container">
            <div class="rules-content">
                <h1>Rules and Regulations</h1>
                <style>
                     * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .rules-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

       

        .rules-content {
            border: 2px solid #333;
            padding: 10px;
            border-radius: 8px;
            background:rgb(242, 239, 239);
            width: 80%;
            color: black;
        }

        button {
            margin-top: 15px;
            padding: 10px 15px;
            border: none;
            background: #007BFF;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            position: relative;
            top: -10px
        }

        button:hover {
            background: #0056b3;
        }
                </style>
                <p>
                    1. Be respectful to other users.<br>
                    2. Do not post offensive or harmful content.<br>
                    3. Follow community guidelines at all times.<br>
                    4. Report any inappropriate behavior.<br>
                    5. Have fun and contribute positively!
                </p>
                <button id="understandBtn">I Understand</button>
            </div>
        </div>
    </div>

                <!-- Navigation Buttons -->
                <div style="margin-bottom: 20px;">
                    <!-- NEW: "New Post" button directs to upload.php -->
                    <button onclick="window.location.href='upload.php'" class="nav-button">New Post</button>
                    <button id="logoutBtn">Logout</button>
                </div>

                <!-- Existing Blacklist Buttons -->
                <button id="showBlacklistBtn" class="dark-button">Show Blacklisted Users</button>
                <ul id="blacklistList" style="display: none;"></ul>
                <button onclick="window.location.href='blacklisted_contents.php'" class="dark-button">Show Blacklisted Content</button>
                
                <!-- NEW: Filter Form -->
                <form method="GET" action="community.php" style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 30px;">
                        <!-- Left-aligned Filter Posts -->
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label for="sortPosts">Filter Posts:</label>
                            <select name="sort_posts" id="sortPosts">
                                <option value="newest" <?php if($sort == 'newest') echo 'selected'; ?>>Newest Content</option>
                                <option value="highest" <?php if($sort == 'highest') echo 'selected'; ?>>Highest-Rated Content</option>
                                <option value="lowest" <?php if($sort == 'lowest') echo 'selected'; ?>>Lowest-Rated Content</option>
                            </select>
                        </div>

                        <!-- Right-aligned Filter Topics -->
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label for="sortTopics">Filter Topics:</label>
                            <select name="sort_topics" id="sortTopics">
                                <option value="Politics" <?php if($sort == 'Politics') echo 'selected'; ?>>Politics</option>
                                <option value="Healthcare" <?php if($sort == 'Healthcare') echo 'selected'; ?>>Healthcare</option>
                                <option value="Technology" <?php if($sort == 'Technology') echo 'selected'; ?>>Technology</option>
                                <option value="Education" <?php if($sort == 'Education') echo 'selected'; ?>>Education</option>
                                <option value="Environment" <?php if($sort == 'Environment') echo 'selected'; ?>>Environment</option>
                                <option value="Science" <?php if($sort == 'Science') echo 'selected'; ?>>Science</option>
                                <option value="Other" <?php if($sort == 'Other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit">Apply</button>
                    </div>
                </form>

                <h3>Echommunity Posts</h3>
                <form action="community.php" method="post" enctype="multipart/form-data" class="create-post-form">
                    <div class="search-container">
                        <textarea 
                            id="searchText"
                            name="search" 
                            placeholder="Search by keyword..." 
                            class="searchText"
                            maxlength="40"
                            style="flex: 1; padding: 10px; border: 2px solid #e9ecef; border-radius: 6px; resize: none; height: 40px;"
                        ></textarea>
                        <button 
            type="submit" 
            class="searchButton" 
            id="searchButton"
            style="height: 40px; padding: 0 15px; border: 2px solid #e9ecef; border-radius: 6px; background-color: #4CAF50; color: white; cursor: pointer; top: -17px;"
        >
            Search
        </button>
                    </div>
                </form>

                <script>
                    document.getElementById("searchText").addEventListener("keydown", function(event) {
                        if (event.key === "Enter") {
                            event.preventDefault(); // Prevent newline
                            document.getElementById("searchButton").click(); // Trigger the search button click
                        }
                    });
                </script>

                <br>
                <?php if (empty($posts)): ?>
                    <div style="display: flex; justify-content: center; align-items: center;">
                        No post(s) were found!
                    </div>
                    <br>
                <?php endif; ?>
                <?php foreach ($posts as $post): 
                    // Determine if the post's user or content is blacklisted
                    $isBlacklistedUser = in_array($post['user_id'], $blacklist);
                    $isBlacklistedContent = in_array($post['id'], $content_blacklist);

                    // For the "newest" filter, skip blacklisted posts
                    if ($sort === 'newest' && ($isBlacklistedUser || $isBlacklistedContent)) {
                        continue;
                    }

                    // For the "highest" or "lowest" filters, show all posts but mark those that are blacklisted
                    $extraClass = "";
                    $blacklistNote = "";
                    if ($sort !== 'newest') {
                        if ($isBlacklistedUser) {
                            $extraClass .= " blacklisted-user";
                            $blacklistNote .= "<p style='color: #555; font-size: 0.9em;'>You have blacklisted this user.</p>";
                        }
                        if ($isBlacklistedContent) {
                            $extraClass .= " blacklisted-content";
                            $blacklistNote .= "<p style='color: #555; font-size: 0.9em;'>You have blacklisted this content.</p>";
                        }
                    }
                ?>
                    <div class="post<?php echo $extraClass; ?>" id="post-<?php echo $post['id']; ?>">
                        <?php
                            if (!empty($blacklistNote)) {
                                echo $blacklistNote;
                            }
                        ?>
                        <div class="post-header">
                            <div class="user-info">
                                <strong class="username"><?php echo htmlspecialchars($post['username']); ?></strong> | <span class="user-rating">
                                    <?php 
                                        $rating = getUserRating($post['user_id'], $searchpost); 
                                        echo ($rating == 0) ? 'Not Rated' : "User rating: $rating/5";
                                    ?>
                                </span>
                            </div>
                            <small class="timestamp">Posted on: <?php echo date("F j, Y, g:i A", strtotime($post['timestamp'] ?? '')); ?></small>
                        </div>
                        <p style="line-height: 2;"><b style="font-size:22px;"><?php echo nl2br(htmlspecialchars($post['title'])); ?></b></p>
                        <br>
                        <p style="word-wrap: break-word; overflow-wrap: break-word;">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </p>
                        <?php if ($post['media']): ?>
                            <?php
                            $file_type= strtolower(pathinfo($post['media'], PATHINFO_EXTENSION));

                            if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <div style="display: flex; justify-content: center; align-items: center;"></div>
                            <div style="display: flex; justify-content: center; align-items: center;">

                                <img 
                                    src="<?php echo htmlspecialchars($post['media']); ?>" 
                                    alt="Post media" 
                                    style="max-width: 100%; width: 300px; height: auto;">
                            </div>
                            <?php elseif ($file_type === 'mp4'): ?>
                    <div style="display: flex; justify-content: center; align-items: center;">
                        <video controls style="max-width: 100%; width: 300px; height: auto;">
                            <source src="<?php echo htmlspecialchars($post['media']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        </div>
                        <?php elseif ($file_type === 'mp3'): ?>
                    <div style="display: flex; justify-content: center; align-items: center;">
                        <audio controls>
                            <source src="<?php echo htmlspecialchars($post['media']); ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                <?php elseif (in_array($file_type, ['pdf', 'doc', 'docx'])): ?>
                    <div style="display: flex; justify-content: center; align-items: center;">
                        <a href="<?php echo htmlspecialchars($post['media']); ?>" target="_blank">Download Document</a>
                    </div>
                <?php endif; ?>
        <?php endif; ?>
        <br>
                        
                        <div class="topic-info">
                             Topic: <?php echo $post['topic']; ?>
                        </div>

                        <div class="rating-info">
                             Rating: <?php echo number_format($post['rating'], 2); ?> (<?php echo $post['rating_count']; ?> votes)
                        </div>

                        <div class="actions">
                            <?php if ($post['user_id'] === $user_id): ?>
                                <form action="delete_post.php" method="post" style="display:inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this post?')" style="background-color: red">Delete Post</button>
                                </form>
                            <?php else: ?>
        
                                <form action="rate.php" method="post" style="display:inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <select name="rating" class="rating-dropdown" required>
                                        <option value="" disabled selected>Give rating</option>
                                        <option value="1">1 - Poor</option>
                                        <option value="2">2 - Fair</option>
                                        <option value="3">3 - Good</option>
                                        <option value="4">4 - Very Good</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                    <button type="submit" style="background-color: rgb(5, 127, 111);">Rate</button>
                                </form>

                                 <!-- Repost/Share Button -->
                                 <form action="repost.php" method="post" style="display:inline;">
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            <button type="submit" style="background-color: rgb(7, 38, 161);">Repost/Share</button>
            </form>

                                <form action="relation.php" method="post" style="display:inline;">
                                    <input type="hidden" name="target_user_id" value="<?php echo $post['user_id']; ?>">
                                    <?php if (in_array($post['user_id'], $whitelist)): ?>
                                        <button type="submit" name="action" value="remove_whitelist" style="background-color: rgb(5, 53, 46);">Remove from Whitelist</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="whitelist" style="background-color: rgb(132, 200, 168);">Whitelist</button>
                                    <?php endif; ?>
                                    <?php if (in_array($post['user_id'], $blacklist)): ?>
                                        <button type="submit" name="action" value="remove_blacklist" style="background-color: rgb(7, 117, 101);">Remove user blacklist</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="blacklist" style="background-color: rgb(37, 78, 72);">Blacklist User</button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>

                            <?php if ($post['user_id'] !== $user_id): ?>
                                <form action="relation.php" method="post" style="display:inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <?php if (in_array($post['id'], $content_blacklist)): ?>
                                        <button type="submit" name="action" value="remove_content_blacklist" style="background-color: rgb(7, 117, 101);">Remove Content Blacklist</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="blacklist_content" style="background-color: rgb(37, 78, 72);">Blacklist Content</button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>

                            <!-- Comments Section -->
                            <div class="comments-section">
                                <button class="toggle-comments" onclick="toggleComments(this)">Show Comments</button>
                                <div class="comments" style="display: none;">
                                    <br>
                                    <h4>Comments</h4>
                                    <br>
                                    <?php if (!empty($post['comments'])): ?>
                                        <?php foreach ($post['comments'] as $comment): ?>
                                            <div class="comment">
                                                <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                                <small>Posted on: <?php echo date("F j, Y, g:i A", strtotime($comment['timestamp'])); ?></small>
                                                <?php if ($comment['user_id'] === $_SESSION['user_id']): ?>
                                                    <form action="delete_comment.php" method="post" style="display:inline;">
                                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                        <input type="hidden" name="comment_index" value="<?php echo array_search($comment, $post['comments']); ?>">
                                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this comment?')" style="background-color: red">Delete Comment</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No comments yet.</p>
                                    <?php endif; ?>
                                    <form action="comment.php" method="post">
                                        <br>
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <textarea name="content" required placeholder="Write a comment..." rows="2"></textarea>
                                        <button type="submit">Post Comment</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="right-col">
            <div class="trending-container">
                <h2>Trending Topics</h2>
                <?php
                    // Calculate trending topics based on the total posts in $searchpost
                    $topicCount = [];
                    foreach ($searchpost as $postItem) {
                        $topic = $postItem['topic'];
                        if (!empty($topic)) {
                            if (!isset($topicCount[$topic])) {
                                $topicCount[$topic] = 0;
                            }
                            $topicCount[$topic]++;
                        }
                    }
                    arsort($topicCount);
                    $trendingTopics = array_slice($topicCount, 0, 5, true);
                    $i = 1;
                ?>
                <?php if (empty($trendingTopics)): ?>
                    <p>No trending topic(s) yet!</p>
                <?php else: ?>
                    <?php foreach ($trendingTopics as $topic => $count): ?>
                        <p>#<?php echo $i; ?> <?php echo htmlspecialchars($topic); ?></p>
                        <?php $i++; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            

            <!-- NEW: Most Liked Posts Block with block design -->
            <div class="most-liked-container">
                <h2>Most Liked Posts</h2>
                <?php
                    // Filter out posts with a rating of 0 before sorting and slicing
                    $mostLikedPosts = array_filter($searchpost, function($post) {
                        return $post['rating'] > 0;
                    });
                    usort($mostLikedPosts, function($a, $b) {
                        return $b['rating'] <=> $a['rating'];
                    });
                    $mostLikedPosts = array_slice($mostLikedPosts, 0, 3);
                ?>
                <?php if (empty($mostLikedPosts)): ?>
                    <p>No most liked post(s) yet!</p>
                <?php else: ?>
                    <?php foreach ($mostLikedPosts as $likedPost): ?>
                        <div class="most-liked-post">
                            <a href="#post-<?php echo $likedPost['id']; ?>" class="most-liked-link" style="text-decoration: none; color: inherit;">
                                <div class="most-liked-post-content">
                                    <span class="most-liked-title"><?php echo htmlspecialchars($likedPost['title']); ?></span>
                                    <span class="most-liked-rating">Rating: <?php echo number_format($likedPost['rating'], 2); ?></span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
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

        document.getElementById("showBlacklistBtn").addEventListener("click", function() {
            let blacklistList = document.getElementById("blacklistList");

            if (blacklistList.style.display === "none") {
                fetch("relation.php")
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        blacklistList.innerHTML = "";
                        if (data.blacklisted_users.length === 0) {
                            blacklistList.innerHTML = "<li>No blacklisted users.</li>";
                        } else {
                            data.blacklisted_users.forEach(user => {
                                let listItem = document.createElement("li");
                                listItem.textContent = user.username;

                                let removeBtn = document.createElement("button");
                                removeBtn.textContent = "Remove";
                                removeBtn.onclick = function() {
                                    removeBlacklist(user.id);
                                };

                                listItem.appendChild(removeBtn);
                                blacklistList.appendChild(listItem);
                            });
                        }
                        blacklistList.style.display = "block";
                    }
                })
                .catch(error => console.error("Error fetching blacklist:", error));
            } else {
                blacklistList.style.display = "none";
            }
        });

        function removeBlacklist(userId) {
            fetch("relation.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "target_user_id=" + userId + "&action=blacklist"
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                location.reload();
            })
            .catch(error => console.error("Error removing user from blacklist:", error));
        }

        // Define toggleComments function once for all posts
        function toggleComments(button) {
            let commentsDiv = button.nextElementSibling;
            if (commentsDiv.style.display === "none" || commentsDiv.style.display === "") {
                commentsDiv.style.display = "block";
                button.textContent = "Hide Comments";
            } else {
                commentsDiv.style.display = "none";
                button.textContent = "Show Comments";
            }
        }

        // NEW: Smooth scroll for Most Liked Posts links
        document.querySelectorAll('.most-liked-link').forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        const userId = "<?php echo $_SESSION['user_id']; ?>";

    // Check if the user has already acknowledged the rules
    if (!localStorage.getItem(`rulesAcknowledged_${userId}`)) {
        // Show the overlay if the rules haven't been acknowledged
        const rulesOverlay = document.getElementById("rulesOverlay");
        rulesOverlay.style.display = "flex"; // Ensure the overlay is visible

        // Handle the "I Understand" button click
        document.getElementById("understandBtn").addEventListener("click", function() {
            // Add the "hidden" class to trigger the fade-out animation
            rulesOverlay.classList.add("hidden");

            // Wait for the animation to complete before hiding the overlay
            rulesOverlay.addEventListener("animationend", () => {
                rulesOverlay.style.display = "none"; // Hide the overlay after animation

                // Set the flag in local storage to indicate the rules have been acknowledged
                localStorage.setItem(`rulesAcknowledged_${userId}`, "true");
            }, { once: true }); // Ensure the event listener is removed after firing
        });
    } else {
        // If the rules have already been acknowledged, hide the overlay
        const rulesOverlay = document.getElementById("rulesOverlay");
        rulesOverlay.style.display = "none";
    }
    </script>
</body>
</html>
