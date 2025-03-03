<?php
session_start();

// Force AJAX mode so that all responses are JSON
$isAjax = true;

function outputResponse($response) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    outputResponse(["status" => "error", "message" => "Unauthorized access."]);
}

$relationsFile = 'relations.json';
$usersFile = 'users.json';

// Load relations and users
$relations = file_exists($relationsFile) ? json_decode(file_get_contents($relationsFile), true) : [];
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First, handle per‑content blacklist actions
    if (isset($_POST['action']) && ($_POST['action'] === 'blacklist_content' || $_POST['action'] === 'remove_content_blacklist')) {
        $action = $_POST['action'];
        if (!isset($_POST['post_id'])) {
            outputResponse(["status" => "error", "message" => "Missing post id."]);
        }
        $postId = (int)$_POST['post_id'];
        
        // Initialize the content_blacklist array if not present
        if (!isset($relations[$userId]['content_blacklist'])) {
            $relations[$userId]['content_blacklist'] = [];
        }
        
        if ($action === 'blacklist_content') {
            if (!in_array($postId, $relations[$userId]['content_blacklist'], true)) {
                $relations[$userId]['content_blacklist'][] = $postId;
            }
            file_put_contents($relationsFile, json_encode($relations, JSON_PRETTY_PRINT));
            outputResponse(["status" => "success", "message" => "Post added to content blacklist."]);
        } else { // remove_content_blacklist
            if (in_array($postId, $relations[$userId]['content_blacklist'], true)) {
                $relations[$userId]['content_blacklist'] = array_values(array_diff($relations[$userId]['content_blacklist'], [$postId]));
            }
            file_put_contents($relationsFile, json_encode($relations, JSON_PRETTY_PRINT));
            outputResponse(["status" => "success", "message" => "Post removed from content blacklist."]);
        }
        exit();
    }
    
    // Otherwise, handle user‑level actions
    if (!isset($_POST['target_user_id'])) {
        outputResponse(["status" => "error", "message" => "Missing target user id."]);
    }
    $targetUserId = (int)$_POST['target_user_id'];
    $action = $_POST['action'];

    // Normalize action to ensure it's recognized
    if ($action === 'remove_whitelist') {
        $action = 'whitelist'; // Convert it to a valid action
    }
    
    // NEW: Handle remove_blacklist action separately
    if ($action === 'remove_blacklist') {
        if (!isset($relations[$userId])) {
            $relations[$userId] = ['blacklist' => [], 'whitelist' => []];
        }
        if (in_array($targetUserId, $relations[$userId]['blacklist'], true)) {
            $relations[$userId]['blacklist'] = array_values(array_diff($relations[$userId]['blacklist'], [$targetUserId]));
            file_put_contents($relationsFile, json_encode($relations, JSON_PRETTY_PRINT));
            outputResponse(["status" => "success", "message" => "User removed from blacklist."]);
        } else {
            outputResponse(["status" => "error", "message" => "User not found in blacklist."]);
        }
        exit();
    }

    if (!in_array($action, ['blacklist', 'whitelist'])) {
        outputResponse(["status" => "error", "message" => "Invalid action received: $action"]);
    }

    if ($userId === $targetUserId) {
        outputResponse(["status" => "error", "message" => "You cannot blacklist or whitelist yourself."]);
    }

    if (!isset($relations[$userId])) {
        $relations[$userId] = ['blacklist' => [], 'whitelist' => []];
    }

    if ($action === 'blacklist') {
        if (in_array($targetUserId, $relations[$userId]['blacklist'], true)) {
            $relations[$userId]['blacklist'] = array_values(array_diff($relations[$userId]['blacklist'], [$targetUserId]));
            file_put_contents($relationsFile, json_encode($relations, JSON_PRETTY_PRINT));
            outputResponse(["status" => "success", "message" => "User removed from blacklist."]);
        } else {
            $relations[$userId]['blacklist'][] = $targetUserId;
            $relations[$userId]['whitelist'] = array_values(array_diff($relations[$userId]['whitelist'], [$targetUserId]));
            file_put_contents($relationsFile, json_encode($relations, JSON_PRETTY_PRINT));
            outputResponse(["status" => "success", "message" => "User added to blacklist."]);
        }
    } elseif ($action === 'whitelist') {
        if (in_array($targetUserId, $relations[$userId]['whitelist'], true)) {
            $relations[$userId]['whitelist'] = array_values(array_diff($relations[$userId]['whitelist'], [$targetUserId]));
            file_put_contents($relationsFile, json_encode($relations, JSON_PRETTY_PRINT));
            outputResponse(["status" => "success", "message" => "User removed from whitelist."]);
        } else {
            $relations[$userId]['whitelist'][] = $targetUserId;
            $relations[$userId]['blacklist'] = array_values(array_diff($relations[$userId]['blacklist'], [$targetUserId]));
            file_put_contents($relationsFile, json_encode($relations, JSON_PRETTY_PRINT));
            outputResponse(["status" => "success", "message" => "User added to whitelist."]);
        }
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $blacklistedUsers = $relations[$userId]['blacklist'] ?? [];
    $whitelistedUsers = $relations[$userId]['whitelist'] ?? [];

    $blacklistNames = [];
    $whitelistNames = [];

    foreach ($users as $user) {
        if (in_array($user['id'], $blacklistedUsers, true)) {
            $blacklistNames[] = ["id" => $user['id'], "username" => $user['username']];
        }
        if (in_array($user['id'], $whitelistedUsers, true)) {
            $whitelistNames[] = ["id" => $user['id'], "username" => $user['username']];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(["status" => "success", "blacklisted_users" => $blacklistNames, "whitelisted_users" => $whitelistNames]);
}
?>
