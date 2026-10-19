<?php
session_start();
require_once __DIR__ . '/config.php';

// 1. LIVE SERVER GOOGLE OAUTH RESPONSE HANDLER
if (isset($_GET['code']) && defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID') {
    $code = $_GET['code'];
    
    // Exchange Authorization Code for Access Token via Google Token Endpoint
    $token_url = 'https://oauth2.googleapis.com/token';
    $post_data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt maternal_curl_opt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        // Fetch User Profile Info from Google API
        $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_data['access_token'];
        $user_info = json_decode(@file_get_contents($user_info_url), true);

        if (isset($user_info['email'])) {
            $email = $user_info['email'];
            $first_name = $user_info['given_name'] ?? 'Google';
            $last_name = $user_info['family_name'] ?? 'User';

            // Check or Auto-Register User in MySQL Database
            if (isset($pdo) && $pdo !== null) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if (!$user) {
                    $stmtInsert = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
                    $stmtInsert->execute([$first_name, $last_name, $email, 'GoogleOAuth', password_hash(uniqid(), PASSWORD_BCRYPT)]);
                    $user_id = $pdo->lastInsertId();
                } else {
                    $user_id = $user['id'];
                }

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
            }
        }
    }
    header("Location: index.php");
    exit;
}

// 2. LOCALHOST / SIMULATED GOOGLE ACCOUNT HANDLER
$user_id = $_GET['user_id'] ?? 1;
$user_email = $_GET['email'] ?? 'tadesebiruk515@gmail.com';
$user_name = $_GET['name'] ?? 'Biruk Tadese';

if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? OR email = ? LIMIT 1");
        $stmt->execute([$user_id, $user_email]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
        } else {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $user_name;
            $_SESSION['user_email'] = $user_email;
        }
    } catch (PDOException $e) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['user_email'] = $user_email;
    }
} else {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $user_name;
    $_SESSION['user_email'] = $user_email;
}

header("Location: index.php");
exit;
?>