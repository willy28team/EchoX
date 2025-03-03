<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/jpg" href="cn.jpg">
    <title>Upload Post - Community Notes</title>
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
        #showBlacklistBtn {
            background-color: #6c757d;
            color: white;
            margin: 20px 0;
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
        .rating-info {
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .post-form {
            background: var(--card-background);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .create-post-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .post-textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            resize: vertical;
            font-family: inherit;
            font-size: 14px;
        }
        .file-input-container {
            position: relative;
        }
        .file-label {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--background-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .file-label:hover {
            background-color: #e9ecef;
        }
        .file-input {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }
        .post-submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .post-submit-btn:hover {
            background-color: #45a049;
        }
        .post {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .post-header strong {
            color: var(--primary-color);
            font-size: 1.1em;
        }
        .timestamp {
            color: #666;
            font-size: 0.85em;
        }
        .rating-info {
            color: #666;
            font-size: 0.9em;
            margin: 10px 0;
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
        #showBlacklistBtn {
            background: linear-gradient(45deg, rgb(24, 200, 1), rgb(2, 189, 30));
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(110, 255, 107, 0.3);
            transition: all 0.3s ease;
        }
        #showBlacklistBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(21, 255, 0, 0.4);
        }
        #blacklistList {
            list-style: none;
            padding: 0;
            margin: 15px 0;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            animation: slideDown 0.3s ease-out;
        }
        #blacklistList li {
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s;
        }
        #blacklistList li:hover {
            background-color: #fff0f0;
        }
        #blacklistList li:last-child {
            border-bottom: none;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .post-textarea {
        width: 100%; /* Full width or set a fixed width */
        max-width: 1700px; /* Optional: Limit maximum width */
        resize: none; /* Disable resizing */
        font-family: Arial, sans-serif; /* Optional: Set font */
    }

    #titleTextarea {
        height: 10px !important; /* Force smaller height for the title */
    }

    #contentTextarea {
        height: 200px !important; /* Force larger height for the content */
    }
    </style>
    <script src="js/jquery.min.js"></script>
</head>
<body>
<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
    <h3>Upload New Post</h3>
    <div class="post-form">
        <form action="post.php" method="post" enctype="multipart/form-data" class="create-post-form">
            <!-- Topic Dropdown Menu -->
            <div class="form-group">
                <label for="topic">Topic:</label>
                <select name="topic" id="topic" required class="form-control">
                    <option value="" disabled selected>Select a topic</option>
                    <option value="Politics">Politics</option>
                    <option value="Healthcare">Healthcare</option>
                    <option value="Technology">Technology</option>
                    <option value="Education">Education</option>
                    <option value="Environment">Environment</option>
                    <option value="Science">Science</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <!-- Title Textarea -->
            <textarea 
                id="titleTextarea"
                name="title" 
                placeholder="Write title post" 
                required 
                class="post-textarea"
                minlength="10"
                maxlength="150"
                style="width: 100%; resize: none;"
            ></textarea>

            <!-- Content Textarea -->
            <textarea 
                id="contentTextarea"
                name="content" 
                placeholder="Write content" 
                required 
                class="post-textarea"
                minlength="50"
                maxlength="750"
                style="width: 100%; resize: none;"
            ></textarea>

            <!-- File Input -->
            <div class="file-input-container">
                <label for="image-upload" class="file-label">
                    <i class="fas fa-image"></i> Attach an image
                </label>
                <input 
                    type="file" 
                    name="image" 
                    id="image-upload"
                    accept="image/*" 
                    class="file-input"
                >
            </div>

            <!-- Image Preview -->
            <div id="preview-container" style="text-align: center; margin-top: 10px;">
                <img id="preview" src="" alt="Image Preview" style="max-width: 100%; display: none;">
            </div>

            <!-- Submit Button -->
            <button type="submit" class="post-submit-btn" id="submitPost">Post</button>
        </form>
    </div>

    <!-- Navigation Buttons -->
    <div style="margin-top: 20px;">
        <button onclick="window.location.href='community.php'" style="background-color: var(--primary-color); color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Back to Community</button>
        <button id="logoutBtn">Logout</button>
    </div>
</div>
    
    <script>
        const badWords = ["fuck", "shit","bitch","asshole","damn", "bastard", "dick", "cock", "cunt", "motherfucker", "retard", "sigma","asU", "bajingan", "anjing","kampret",
            "goblok", "tolol", "idiot", "bego", "ndasmu", "memek", "kontol", "pepek", "memek", "jancok", "brengsek", "berengsek", "tahi", "tai", "setan", "bangsat"];

    function containsBadWord(text) {
    return badWords.some(word => text.toLowerCase().includes(word.toLowerCase()));
}

document.querySelector("form").addEventListener("submit", function(event) {
    let title = document.getElementById("titleTextarea").value.trim();
    let content = document.getElementById("contentTextarea").value.trim();

    if (containsBadWord(title) || containsBadWord(content)) {
        alert("Your post contains inappropriate words. Please remove them.");
        event.preventDefault(); // Prevent form submission
    }
});

        document.getElementById("logoutBtn").addEventListener("click", function() {
            fetch("logout.php")
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                window.location.href = "login.html";
            })
            .catch(error => console.error("Error:", error));
        });
        
        // Added JavaScript for Image Preview functionality
        document.getElementById("image-upload").addEventListener("change", function() {
            const preview = document.getElementById("preview");
            const file = this.files[0];
            if(file) {
                const reader = new FileReader();
                reader.addEventListener("load", function() {
                    preview.src = this.result;
                    preview.style.display = "block";
                });
                reader.readAsDataURL(file);
            } else {
                preview.src = "";
                preview.style.display = "none";
            }
        });
    </script>
</body>
</html>
