<?php
// linkedin-login.php
session_start();
require_once __DIR__ . '/config.php';

// =========================================================================
// 🌟 MAGIC: LINKEDIN API CREDENTIALS (Asitti ragaa dhugaa galchitu) 🌟
// =========================================================================
$linkedin_client_id     = 'APP_CLIENT_ID_KEESSAN_ASITTI_GALCHAA'; // Fkn: 86abc123def456
$linkedin_client_secret = 'APP_CLIENT_SECRET_KEESSAN_ASITTI_GALCHAA'; // Fkn: XyZ123AbC456
$redirect_uri           = 'http://localhost/infoplus/linkedin-login.php'; // Yeroo host gootan: https://infoplus.et/linkedin-login.php
$scopes                 = 'openid profile email';
// =========================================================================

// 1. YOO LINKEDIN IRRAA DEEBI'E (Callback Handling)
if (isset($_GET['code'])) {
    
    // Security Check: State match
    if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['linkedin_state'])) {
        $_SESSION['error_msg'] = "Security Error: Invalid State. Please try again.";
        header('Location: login.php');
        exit;
    }

    // A. Koodii fudhattee Access Token gaafachuu
    $token_url = "https://www.linkedin.com/oauth/v2/accessToken";
    $token_data = [
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code'],
        'redirect_uri'  => $redirect_uri,
        'client_id'     => $linkedin_client_id,
        'client_secret' => $linkedin_client_secret
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For localhost testing
    
    $token_response = curl_exec($ch);
    $token_info = json_decode($token_response, true);
    curl_close($ch);

    if (isset($token_info['access_token'])) {
        $access_token = $token_info['access_token'];

        // B. Ragaa Namaa (Profile & Email) Fetch Gochuu (OIDC API)
        $profile_url = "https://api.linkedin.com/v2/userinfo";
        
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $profile_url);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $access_token]);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        
        $profile_response = curl_exec($ch2);
        $profile_data = json_decode($profile_response, true);
        curl_close($ch2);

        if (isset($profile_data['email'])) {
            $email = $profile_data['email'];
            $first_name = $profile_data['given_name'] ?? 'LinkedIn';
            $last_name = $profile_data['family_name'] ?? 'User';
            $avatar = $profile_data['picture'] ?? null;

            // 🌟 C. MAGIC AUTO-LOGIN YKN AUTO-REGISTER 🌟
            if (isset($pdo) && $pdo !== null) {
                // Check if user exists in DB
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // NAMNI KUN DURAA JIRA -> KALLATTIIN LOGIN GODHA
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = 'seeker';
                    $_SESSION['success_msg'] = "Welcome back via LinkedIn!";
                } else {
                    // NAMNI KUN HAARAA DHA -> OFUMAAN ACCOUNT UUMEEFII LOGIN GODHA
                    $random_pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                    $dummy_phone = '+2519' . mt_rand(10000000, 99999999); // DB requires unique phone
                    
                    $insert_stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, is_verified, onboarding_completed) VALUES (?, ?, ?, ?, ?, 1, 0)");
                    $insert_stmt->execute([$first_name, $last_name, $email, $dummy_phone, $random_pass]);
                    
                    $new_user_id = $pdo->lastInsertId();

                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'seeker';
                    $_SESSION['success_msg'] = "LinkedIn Account linked successfully! Welcome to Infoplus.";
                }

                // Redirect to home or onboarding
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['error_msg'] = "Database Connection Error!";
                header("Location: login.php");
                exit;
            }
        } else {
            $_SESSION['error_msg'] = "Failed to retrieve Email from LinkedIn. Please try normal signup.";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "LinkedIn Authentication Failed! Please try again.";
        header("Location: login.php");
        exit;
    }

} 
// 2. YEROO JALQABAA LOGIN BUTTON XUQAMU (Initiate Login)
else {
    // Generate Security State
    $_SESSION['linkedin_state'] = bin2hex(random_bytes(16));
    
    // Redirect User to LinkedIn
    $auth_url = "https://www.linkedin.com/oauth/v2/authorization?" . http_build_query([
        'response_type' => 'code',
        'client_id'     => $linkedin_client_id,
        'redirect_uri'  => $redirect_uri,
        'state'         => $_SESSION['linkedin_state'],
        'scope'         => $scopes
    ]);

    header("Location: " . $auth_url);
    exit;
}
?>