<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if real Google Client ID is configured
$is_live_configured = defined('GOOGLE_CLIENT_ID') 
                      && GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID' 
                      && !empty(GOOGLE_CLIENT_ID);

if ($is_live_configured) {
    // 🌟 LIVE SERVER MODE: Redirect directly to Google's Official OAuth Server
    $google_oauth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'prompt' => 'select_account'
    ]);
    header("Location: " . $google_oauth_url);
    exit;
}

// 🌟 LOCALHOST / TEST MODE: Render Pixel-Perfect Google Account Chooser UI
$users_list = [];
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM users LIMIT 3");
        $users_list = $stmt->fetchAll();
    } catch (PDOException $e) {
        $users_list = [];
    }
}

if (empty($users_list)) {
    $users_list = [
        ['id' => 1, 'first_name' => 'Biruk', 'last_name' => 'Tadese', 'email' => 'tadesebiruk515@gmail.com'],
        ['id' => 2, 'first_name' => 'Chala', 'last_name' => 'Kebede', 'email' => 'chala.kebede@gmail.com']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - Google Accounts - Infoplus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 dark:bg-slate-900 font-sans flex items-center justify-center min-h-screen p-4">

    <!-- Official Google Account Chooser Box -->
    <div class="max-w-md w-full bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-700 p-8 text-center relative overflow-hidden">
        
        <div class="flex justify-center mb-6">
            <svg class="h-10 w-10" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Choose an account</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-6">to continue to <strong class="text-teal-600 dark:text-teal-400">Infoplus Job Board</strong></p>

        <!-- List of Accounts on Device -->
        <div class="space-y-3 text-left mb-6">
            <?php foreach ($users_list as $u): ?>
            <a href="google-callback.php?user_id=<?php echo $u['id']; ?>&email=<?php echo urlencode($u['email']); ?>&name=<?php echo urlencode($u['first_name'] . ' ' . $u['last_name']); ?>" 
               class="flex items-center space-x-4 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-teal-500 hover:bg-teal-50/50 dark:hover:bg-slate-700/50 transition-all group">
                <div class="h-10 w-10 rounded-full bg-teal-500 text-white font-black flex items-center justify-center text-sm shadow-md uppercase">
                    <?php echo substr($u['first_name'], 0, 1); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate group-hover:text-teal-600 transition-colors">
                        <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?php echo htmlspecialchars($u['email']); ?></p>
                </div>
                <span class="text-slate-400 group-hover:text-teal-600 transition-colors">→</span>
            </a>
            <?php endforeach; ?>

            <a href="login.php" class="flex items-center space-x-4 p-3.5 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 hover:border-teal-500 text-slate-500 dark:text-slate-400 hover:text-teal-600 transition-all">
                <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-base">
                    👤
                </div>
                <span class="text-xs font-bold">Use another account</span>
            </a>
        </div>

        <div class="text-xs text-slate-400 border-t border-slate-100 dark:border-slate-800 pt-4 flex justify-between items-center">
            <span>Infoplus OAuth System</span>
            <a href="login.php" class="text-teal-600 hover:underline font-bold">Cancel</a>
        </div>

    </div>

</body>
</html>