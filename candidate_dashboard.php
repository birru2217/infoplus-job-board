<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if Candidate is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'seeker') !== 'seeker') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = isset($_GET['status']) && $_GET['status'] === 'success' ? "Welcome! Your Profile & CV have been successfully saved!" : "";
$error_msg = "";

// 🪄 AUTO-CREATE MISSING PROFILE_PIC COLUMN (UNIVERSAL COMPATIBILITY)
if ($pdo) {
    try {
        // Hojii database moofaa fi haaraa hundaa irratti ni hojjeta
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");
        }
    } catch (PDOException $e) {}
}
// Handle POST Updates from Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 🌟 1. UPLOAD AVATAR (PROFILE PICTURE) 🌟
    if ($action === 'upload_avatar' && $pdo) {
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $avatar_name = time() . '_avatar_' . $user_id . '.' . $ext;
                $upload_dir = 'uploads/avatars/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $upload_dir . $avatar_name)) {
                    $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$upload_dir . $avatar_name, $user_id]);
                    $success_msg = "Profile picture updated successfully!";
                }
            } else {
                $error_msg = "Invalid image format. Please use JPG or PNG.";
            }
        }
    }

    // 2. UPLOAD CV
    if ($action === 'upload_cv' && $pdo) {
        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'doc', 'docx'])) {
                $cv_name = time() . '_' . $user_id . '_cv.' . $ext;
                $upload_dir = 'uploads/cvs/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $upload_dir . $cv_name)) {
                    $pdo->prepare("UPDATE users SET cv_path = ? WHERE id = ?")->execute([$upload_dir . $cv_name, $user_id]);
                    $success_msg = "Resume Document updated successfully!";
                }
            } else {
                $error_msg = "Invalid file type. Only PDF and DOC allowed.";
            }
        }
    }

    // 3. CHANGE PASSWORD
    if ($action === 'update_security' && $pdo) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        if ($new_pass === $confirm_pass && !empty($new_pass)) {
            $hashed_pw = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_pw, $user_id]);
            $success_msg = "Password updated successfully!";
        } else {
            $error_msg = "Passwords do not match!";
        }
    }
}

// Fetch Complete User CV Data
$user = [];
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Recommended Jobs
$recommended_jobs = $pdo ? $pdo->query("SELECT * FROM jobs WHERE status = 'active' ORDER BY RAND() LIMIT 4")->fetchAll() : [];

// Applications
$my_applications = $pdo ? $pdo->query("SELECT a.*, j.title, j.company_name, j.location FROM applications a JOIN jobs j ON a.job_id = j.id WHERE a.user_id = $user_id ORDER BY a.applied_at DESC")->fetchAll() : [];

// Calculate Profile Completion %
$completion = 30;
if(!empty($user['work_position'])) $completion += 15;
if(!empty($user['skills_list'])) $completion += 15;
if(!empty($user['education_level'])) $completion += 15;
if(!empty($user['bio'])) $completion += 10;
if(!empty($user['cv_path'])) $completion += 15;
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Candidate Portal — Infoplus</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#f9fafb] dark:bg-slate-950 text-slate-800 dark:text-slate-200 h-screen overflow-hidden flex transition-colors duration-300" x-data="{ currentTab: 'dashboard', isSidebarOpen: false }">

    <!-- MOBILE OVERLAY BACKDROP -->
    <div x-show="isSidebarOpen" x-transition.opacity @click="isSidebarOpen = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 lg:hidden" x-cloak></div>

   <!-- 🌟 PALMJOBS STYLE SIDEBAR NAVIGATION 🌟 -->
    <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:relative top-0 left-0 h-full w-[280px] bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col flex-shrink-0 z-50 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
        
        <!-- 🌟 TOP SNAPPED PROFILE CARD (With Integrated Close 'X' Button) 🌟 -->
        <div class="p-3 sm:p-4 pb-2 pt-3">
            <div class="relative bg-[#f8fafc] dark:bg-slate-800/60 rounded-2xl p-3 sm:p-3.5 flex items-center gap-3 border border-slate-100 dark:border-slate-800 shadow-sm">
                
                <!-- Avatar Form (Click to Upload) -->
                <form method="POST" enctype="multipart/form-data" id="avatarForm" class="flex-shrink-0">
                    <input type="hidden" name="action" value="upload_avatar">
                    <label class="relative block h-11 w-11 sm:h-12 sm:w-12 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-base shadow-inner overflow-hidden cursor-pointer group border-2 border-white dark:border-slate-700 hover:border-emerald-400 transition-all">
                        <?php if(!empty($user['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        <?php endif; ?>
                        
                        <!-- Camera Icon Hover Overlay -->
                        <div class="absolute inset-0 bg-black/50 hidden group-hover:flex items-center justify-center transition-all">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        
                        <input type="file" name="avatar_file" accept="image/*" class="hidden" onchange="document.getElementById('avatarForm').submit();">
                    </label>
                </form>

                <!-- User Info -->
                <div class="min-w-0 flex-1 pr-6 lg:pr-0">
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-[14px] leading-tight truncate"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-0.5">Candidate</p>
                    <div class="flex items-center gap-1.5 mt-1">
                        <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 8.11 1 12c1.73 3.89 6 7.5 11 7.5s9.27-3.61 11-7.5c-1.73-3.89-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 truncate">Visible to employers</span>
                    </div>
                </div>

                <!-- 🌟 INTEGRATED MOBILE CLOSE (X) BUTTON - INLINE WITH PROFILE 🌟 -->
                <button @click="isSidebarOpen = false" class="lg:hidden absolute top-2.5 right-2.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 p-1.5 rounded-full hover:bg-slate-200/70 dark:hover:bg-slate-700 transition-colors" title="Close menu">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Menu Links -->
        <nav class="flex-1 overflow-y-auto px-3 space-y-0.5 no-scrollbar pb-6">
            
            <button @click="currentTab = 'dashboard'; isSidebarOpen = false" :class="currentTab === 'dashboard' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </button>
            
            <a href="jobs.php" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <span>Find a Job</span>
            </a>

            <button @click="currentTab = 'for_you'; isSidebarOpen = false" :class="currentTab === 'for_you' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                <span>For You</span>
            </button>

            <button @click="currentTab = 'saved'; isSidebarOpen = false" :class="currentTab === 'saved' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                <span>Saved Jobs</span>
            </button>

            <button @click="currentTab = 'applications'; isSidebarOpen = false" :class="currentTab === 'applications' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center justify-between px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <div class="flex items-center gap-3.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span>Applications</span>
                </div>
                <?php if(count($my_applications) > 0): ?>
                    <span class="px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[11px] font-extrabold"><?php echo count($my_applications); ?></span>
                <?php endif; ?>
            </button>

            <button @click="currentTab = 'messages'; isSidebarOpen = false" :class="currentTab === 'messages' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <span>Messages</span>
            </button>

            <button @click="currentTab = 'alerts'; isSidebarOpen = false" :class="currentTab === 'alerts' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span>Alerts</span>
            </button>

            <!-- SECTION LABEL -->
            <div class="pt-6 pb-2 px-4 text-[12px] font-bold text-emerald-700/70 dark:text-emerald-500/70 uppercase">Account</div>

            <button @click="currentTab = 'my_cv'; isSidebarOpen = false" :class="currentTab === 'my_cv' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>Profile</span>
            </button>

            <a href="onboarding.php" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Resume Builder</span>
            </a>
            
            <button @click="currentTab = 'manage_cv'; isSidebarOpen = false" :class="currentTab === 'manage_cv' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>Upload Document</span>
            </button>

            <button @click="currentTab = 'cover_letters'; isSidebarOpen = false" :class="currentTab === 'cover_letters' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <span>Cover Letters</span>
            </button>

            <button @click="currentTab = 'security'; isSidebarOpen = false" :class="currentTab === 'security' ? 'text-emerald-700 dark:text-emerald-400 font-bold bg-[#ecfdf5] dark:bg-emerald-500/10' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium'" class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-[14px] text-[14px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Settings</span>
            </button>
        </nav>

        <!-- Sign Out Button & Footer -->
        <div class="px-5 pt-2 pb-6 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
            <a href="logout.php" class="w-full flex items-center justify-center gap-2.5 px-4 py-3 rounded-full border-2 border-emerald-600 text-emerald-700 dark:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-slate-800 transition-colors font-extrabold text-[15px] mt-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Sign out</span>
            </a>
            <div class="mt-5 flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 font-semibold px-2">
                <span>&copy; 2026 InfoPlus</span>
                <div class="flex gap-4">
                    <a href="terms.php?tab=privacy" class="hover:text-emerald-600">Privacy</a>
                    <a href="terms.php" class="hover:text-emerald-600">Terms</a>
                </div>
            </div>
        </div>
    </aside>

    <!-- 🌟 MAIN CONTENT AREA (WIDE ON PC) 🌟 -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden w-full relative z-0">
        
        <!-- TOPBAR (Mobile Toggle) -->
        <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 lg:px-8 z-10 sticky top-0 lg:hidden">
            <div class="flex items-center gap-3">
                <button @click="isSidebarOpen = true" class="p-2 -ml-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="flex items-center gap-1.5">
                    <img src="logo.png" onerror="this.src='https://ui-avatars.com/api/?name=I+P&background=0D9488&color=fff'" class="h-6 w-6 rounded">
                    <h2 class="text-[17px] font-black text-slate-900 dark:text-white tracking-tight">Info<span class="text-emerald-600">Plus</span></h2>
                </div>
            </div>
            <button @click="currentTab = 'my_cv'" class="h-8 w-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold shadow-sm overflow-hidden border border-emerald-400">
                 <?php if(!empty($user['profile_pic'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" class="w-full h-full object-cover">
                 <?php else: ?>
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                 <?php endif; ?>
            </button>
        </header>

        <!-- Scrollable Content (Widened to max-w-7xl) -->
        <div class="flex-1 overflow-y-auto bg-[#f9fafb] dark:bg-slate-950 p-4 lg:p-10 pb-20 custom-scrollbar relative">

            <!-- Title for Desktop -->
            <div class="hidden lg:flex items-center justify-between w-full max-w-7xl mx-auto mb-8 border-b border-slate-200 dark:border-slate-800 pb-4">
                <h2 class="text-3xl font-black text-slate-900 dark:text-white capitalize" x-text="currentTab.replace('_', ' ')"></h2>
                <a href="jobs.php" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm rounded-full shadow-sm transition">
                    Explore Jobs
                </a>
            </div>

            <!-- Alerts -->
            <?php if($success_msg): ?>
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" class="max-w-7xl mx-auto mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 text-sm font-bold shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="max-w-7xl mx-auto mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30 text-sm font-bold shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <!-- ==================== TAB 1: DASHBOARD ==================== -->
            <div x-show="currentTab === 'dashboard'" x-cloak class="space-y-6 max-w-7xl mx-auto w-full">
                
                <!-- PalmJobs Style Clean Stat Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                    <div @click="currentTab = 'applications'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between cursor-pointer hover:border-emerald-500 dark:hover:border-emerald-500 transition-colors">
                        <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-4 border border-slate-100 dark:border-slate-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <p class="text-4xl font-black text-slate-900 dark:text-white"><?php echo count($my_applications); ?></p>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-1">Applications</p>
                    </div>
                    
                    <div @click="currentTab = 'saved'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between cursor-pointer hover:border-emerald-500 transition-colors">
                        <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-4 border border-slate-100 dark:border-slate-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                        </div>
                        <p class="text-4xl font-black text-slate-900 dark:text-white">0</p>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-1">Saved Jobs</p>
                    </div>

                    <div @click="currentTab = 'my_cv'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between cursor-pointer hover:border-emerald-500 transition-colors">
                        <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-4 border border-slate-100 dark:border-slate-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <p class="text-4xl font-black text-slate-900 dark:text-white"><?php echo $completion; ?>%</p>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-1">Profile Score</p>
                    </div>

                    <div @click="currentTab = 'alerts'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between cursor-pointer hover:border-emerald-500 transition-colors">
                        <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-4 border border-slate-100 dark:border-slate-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        </div>
                        <p class="text-4xl font-black text-slate-900 dark:text-white">0</p>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-1">Job Alerts</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Applications Summary -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 lg:p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-xl">Recent Applications</h3>
                            <button @click="currentTab = 'applications'" class="text-emerald-600 dark:text-emerald-400 text-sm font-bold hover:underline">View all</button>
                        </div>
                        <?php if(empty($my_applications)): ?>
                            <div class="text-center py-10 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700 border-dashed">
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">You haven't applied to any jobs recently.</p>
                                <a href="jobs.php" class="inline-block mt-4 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-full text-sm font-bold transition-colors shadow-md shadow-emerald-500/20">Browse Jobs</a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-5">
                                <?php foreach(array_slice($my_applications, 0, 3) as $app): ?>
                                <div class="flex items-center justify-between border-b border-slate-50 dark:border-slate-800/60 pb-5 last:border-0 last:pb-0">
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-base"><?php echo htmlspecialchars($app['title']); ?></p>
                                        <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-0.5"><?php echo htmlspecialchars($app['company_name']); ?></p>
                                    </div>
                                    <?php 
                                        $statusColor = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
                                        if($app['status'] == 'pending') $statusColor = 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
                                        if($app['status'] == 'reviewed') $statusColor = 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
                                        if($app['status'] == 'accepted') $statusColor = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
                                        if($app['status'] == 'rejected') $statusColor = 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400';
                                    ?>
                                    <span class="px-3.5 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $statusColor; ?>">
                                        <?php echo htmlspecialchars($app['status']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recommended Jobs Inline -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 lg:p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-xl">Top Picks for You</h3>
                            <button @click="currentTab = 'for_you'" class="text-emerald-600 dark:text-emerald-400 text-sm font-bold hover:underline">View all</button>
                        </div>
                        <?php if (empty($recommended_jobs)): ?>
                            <div class="text-center py-10 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700 border-dashed">
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Complete your profile to get matches.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach(array_slice($recommended_jobs, 0, 3) as $job): ?>
                                <a href="job-details.php?id=<?php echo $job['id']; ?>" class="block bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 p-4 rounded-2xl hover:border-emerald-500 dark:hover:border-emerald-400 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex-shrink-0 p-1.5">
                                            <img src="<?php echo $job['logo'] ?? 'logo.png'; ?>" class="w-full h-full object-contain">
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-bold text-slate-900 dark:text-white text-sm truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"><?php echo htmlspecialchars($job['title']); ?></h4>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate"><?php echo htmlspecialchars($job['company_name']); ?> • <?php echo htmlspecialchars($job['location']); ?></p>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- ==================== TAB: FOR YOU ==================== -->
            <div x-show="currentTab === 'for_you'" x-cloak class="space-y-6 max-w-7xl mx-auto w-full">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($recommended_jobs)): ?>
                        <div class="col-span-full bg-white dark:bg-slate-900 p-12 rounded-3xl text-center border border-slate-100 dark:border-slate-800">
                            <span class="text-5xl drop-shadow-md">✨</span>
                            <p class="text-slate-900 dark:text-white font-black mt-4 text-xl">No perfect matches yet</p>
                            <p class="text-slate-500 dark:text-slate-400 text-sm mt-2">Complete your profile and skills section to help our AI find better recommendations tailored for you.</p>
                            <button @click="currentTab = 'my_cv'" class="mt-6 px-6 py-2.5 bg-emerald-600 text-white rounded-full font-bold shadow-md hover:bg-emerald-500">Update Profile</button>
                        </div>
                    <?php else: ?>
                        <?php foreach($recommended_jobs as $job): ?>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:shadow-xl hover:border-emerald-500 transition-all group flex flex-col justify-between">
                            <div>
                                <div class="flex items-start gap-4 mb-5">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex-shrink-0 p-2 flex items-center justify-center">
                                        <img src="<?php echo $job['logo'] ?? 'logo.png'; ?>" class="w-full h-full object-contain">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-black text-slate-900 dark:text-white text-base leading-tight truncate group-hover:text-emerald-600 transition-colors"><?php echo htmlspecialchars($job['title']); ?></h4>
                                        <p class="text-[12px] font-bold text-emerald-600 dark:text-emerald-400 mt-1 truncate"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-2 mb-6 flex-wrap">
                                    <span class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg text-[10px] font-bold flex items-center gap-1.5 border border-slate-100 dark:border-slate-700">📍 <?php echo htmlspecialchars($job['location']); ?></span>
                                    <span class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg text-[10px] font-bold flex items-center gap-1.5 border border-slate-100 dark:border-slate-700">⏱️ <?php echo htmlspecialchars($job['job_type']); ?></span>
                                </div>
                            </div>
                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="block w-full py-3 bg-emerald-50 dark:bg-emerald-500/10 hover:bg-emerald-600 text-emerald-700 dark:text-emerald-400 hover:text-white dark:hover:text-white text-center text-sm font-bold rounded-xl transition-colors border border-emerald-100 dark:border-emerald-500/20">View Details</a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ==================== TAB: PROFILE (MY CV) ==================== -->
            <div x-show="currentTab === 'my_cv'" x-cloak class="max-w-7xl mx-auto w-full space-y-6">
                
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 lg:p-12 rounded-[2.5rem] shadow-sm relative text-left">
                    
                  <!-- CV Header -->
                    <div class="flex flex-col md:flex-row items-center md:items-start gap-8 pb-8 border-b border-slate-100 dark:border-slate-800">
                        
                        <!-- 🌟 EXPLICIT AVATAR UPLOAD IN PROFILE SECTION 🌟 -->
                        <div class="relative flex-shrink-0 group">
                            <form method="POST" enctype="multipart/form-data" id="mainAvatarForm">
                                <input type="hidden" name="action" value="upload_avatar">
                                <label class="cursor-pointer block relative h-28 w-28 lg:h-32 lg:w-32 rounded-full bg-emerald-600 flex items-center justify-center text-4xl lg:text-5xl font-black text-white shadow-xl border-4 border-emerald-50 dark:border-slate-800 overflow-hidden transition-all hover:border-emerald-400">
                                    <?php if(!empty($user['profile_pic'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                    
                                    <!-- Hover Overlay (Change Picture) -->
                                    <div class="absolute inset-0 bg-black/60 hidden group-hover:flex flex-col items-center justify-center transition-all">
                                        <svg class="w-6 h-6 text-white mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span class="text-[10px] font-bold text-white uppercase tracking-wider">Change</span>
                                    </div>
                                    <input type="file" name="avatar_file" accept="image/*" class="hidden" onchange="document.getElementById('mainAvatarForm').submit();">
                                </label>
                            </form>
                        </div>
                        
                        <div class="text-center md:text-left flex-1 min-w-0">
                            <h1 class="text-3xl lg:text-4xl font-black text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                            <p class="text-base lg:text-lg font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?php echo htmlspecialchars($user['work_position'] ?? 'Professional Position Not Set'); ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 font-medium flex items-center justify-center md:justify-start gap-3">
                                <span>📍 <?php echo htmlspecialchars($user['city'] ?? 'Ethiopia'); ?></span>
                                <span>•</span>
                                <span>⏱️ <?php echo htmlspecialchars($user['years_experience'] ?? 'Fresh'); ?></span>
                            </p>
                        </div>
                        
                        <a href="onboarding.php" class="px-8 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 font-bold text-sm rounded-full transition-colors whitespace-nowrap shadow-sm">
                            Edit Profile
                        </a>
                    </div>

                    <!-- Contact Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 py-8 border-b border-slate-100 dark:border-slate-800 text-sm">
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                            <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold mb-1 tracking-wider">Email Address</span>
                            <span class="text-slate-900 dark:text-white font-bold truncate block"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                            <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold mb-1 tracking-wider">Phone Number</span>
                            <span class="text-slate-900 dark:text-white font-bold block"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                            <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold mb-1 tracking-wider">Expected Salary</span>
                            <span class="text-slate-900 dark:text-white font-black block"><?php echo htmlspecialchars($user['expected_salary'] ?? 'Negotiable'); ?></span>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                            <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold mb-1 tracking-wider">Gender & Status</span>
                            <span class="text-slate-900 dark:text-white font-bold block"><?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($user['marital_status'] ?? 'Single'); ?></span>
                        </div>
                    </div>

                    <!-- Bio -->
                    <?php if(!empty($user['bio'])): ?>
                    <div class="py-8 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mb-3">About Me</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-loose"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-8">
                        <!-- Education -->
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-5">Education</h3>
                            <div class="flex items-start gap-5">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0 text-xl">🎓</div>
                                <div>
                                    <h4 class="font-extrabold text-slate-900 dark:text-white text-base"><?php echo htmlspecialchars($user['field_of_study'] ?? 'Not Specified'); ?></h4>
                                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 mt-1"><?php echo htmlspecialchars($user['institution'] ?? 'University / College'); ?></p>
                                    <span class="inline-block mt-3 px-3.5 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-[11px] font-bold rounded-lg tracking-wide uppercase">
                                        <?php echo htmlspecialchars($user['education_level'] ?? 'Degree'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Skills -->
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-5">Skills & Expertise</h3>
                            <div class="flex flex-wrap gap-2.5">
                                <?php 
                                    $skills = explode(',', $user['skills_list'] ?? 'General Skills');
                                    foreach($skills as $s):
                                        if(trim($s) === '') continue;
                                ?>
                                    <span class="px-4 py-2 bg-[#ecfdf5] dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 rounded-xl text-xs font-bold border border-emerald-100 dark:border-emerald-500/20 shadow-sm cursor-default">
                                        <?php echo htmlspecialchars(trim($s)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ==================== TAB 4: APPLICATIONS ==================== -->
            <div x-show="currentTab === 'applications'" x-cloak class="max-w-7xl mx-auto w-full">
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white">Applications Tracker</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Monitor the status of all jobs you've applied for.</p>
                </div>
                
                <?php if(empty($my_applications)): ?>
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-16 text-center border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner">📄</div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">No applications found</h3>
                        <p class="text-base text-slate-500 dark:text-slate-400 mt-2 mb-8 max-w-sm mx-auto">You haven't submitted any job applications yet. Let's find your next opportunity!</p>
                        <button @click="currentTab = 'for_you'" class="px-8 py-3.5 bg-emerald-600 text-white rounded-full text-sm font-extrabold shadow-lg hover:bg-emerald-500 hover:-translate-y-0.5 transition-all transform">View Job Matches</button>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse min-w-[700px]">
                                <thead>
                                    <tr class="text-[11px] uppercase tracking-wider text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800/50 border-b-2 border-slate-100 dark:border-slate-800">
                                        <th class="py-4 px-6 font-bold">Job Role</th>
                                        <th class="py-4 px-6 font-bold">Location</th>
                                        <th class="py-4 px-6 font-bold">Date Applied</th>
                                        <th class="py-4 px-6 font-bold text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm">
                                    <?php foreach($my_applications as $app): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
                                        <td class="py-5 px-6">
                                            <p class="font-black text-slate-900 dark:text-white text-[15px] group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"><?php echo htmlspecialchars($app['title']); ?></p>
                                            <p class="text-[13px] font-semibold text-slate-500 dark:text-slate-400 mt-1"><?php echo htmlspecialchars($app['company_name']); ?></p>
                                        </td>
                                        <td class="py-5 px-6 text-slate-600 dark:text-slate-300 font-medium">
                                            <?php echo htmlspecialchars($app['location']); ?>
                                        </td>
                                        <td class="py-5 px-6 text-slate-500 dark:text-slate-400 font-medium">
                                            <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                        </td>
                                        <td class="py-5 px-6 text-right">
                                            <?php 
                                                $bg = 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20';
                                                if($app['status'] === 'reviewed') $bg = 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20';
                                                if($app['status'] === 'accepted') $bg = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20';
                                                if($app['status'] === 'rejected') $bg = 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20';
                                            ?>
                                            <span class="px-3.5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border shadow-sm <?php echo $bg; ?>">
                                                <?php echo htmlspecialchars($app['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ==================== TAB 5: UPLOAD RESUME ==================== -->
            <div x-show="currentTab === 'manage_cv'" x-cloak class="max-w-4xl mx-auto lg:mx-0">
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white">Resume Document</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Upload your official PDF resume to attach to your applications.</p>
                </div>
                
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 sm:p-12 rounded-[2.5rem] shadow-sm">
                    <?php if(!empty($user['cv_path'])): ?>
                        <div class="mb-8 p-5 sm:p-6 bg-[#ecfdf5] dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white dark:bg-slate-800 rounded-xl text-emerald-600 dark:text-emerald-400 text-2xl shadow-sm border border-slate-100 dark:border-slate-700">📄</div>
                                <div>
                                    <h4 class="font-black text-slate-900 dark:text-white text-base">Resume_Uploaded.pdf</h4>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-bold mt-1">Your document is active and ready to be sent to employers.</p>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars($user['cv_path']); ?>" target="_blank" class="px-6 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:border-emerald-500 dark:hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 text-xs font-bold rounded-full shadow-sm transition-colors text-center">View Document</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="upload_cv">
                        
                        <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 rounded-3xl p-12 text-center hover:bg-slate-100 dark:hover:bg-slate-800 hover:border-emerald-500 dark:hover:border-emerald-500 transition-colors cursor-pointer relative group mb-6">
                            <input type="file" name="cv_file" accept=".pdf,.doc,.docx" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="w-16 h-16 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm text-slate-400 dark:text-slate-500 border border-slate-100 dark:border-slate-800 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 group-hover:scale-110 transition-all duration-300">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            </div>
                            <p class="text-base font-black text-slate-700 dark:text-slate-300">Click or Drag & Drop to Upload New PDF</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 font-medium">Supported: PDF, DOC, DOCX (Max 5MB)</p>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold py-3.5 px-10 rounded-full shadow-lg shadow-emerald-600/20 transition-transform transform hover:-translate-y-0.5 text-sm">
                                Upload & Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================== TAB 6: SECURITY SETTINGS ==================== -->
            <div x-show="currentTab === 'security'" x-cloak class="max-w-4xl mx-auto lg:mx-0">
                <div class="mb-8">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white">Security Settings</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Manage your password and secure your account.</p>
                </div>
                
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 sm:p-12 rounded-[2.5rem] shadow-sm max-w-2xl">
                    <form method="POST" class="space-y-6 text-left">
                        <input type="hidden" name="action" value="update_security">
                        
                        <div x-data="{ show: false }">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">New Password</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="new_password" required placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3.5 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 font-medium transition-colors">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400">
                                    <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="show" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                </button>
                            </div>
                        </div>

                        <div x-data="{ show: false }">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Confirm New Password</label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" name="confirm_password" required placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3.5 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 font-medium transition-colors">
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-4 text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400">
                                    <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="show" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                </button>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold py-3.5 px-10 rounded-full shadow-lg shadow-emerald-600/20 transition-transform transform hover:-translate-y-0.5 text-sm w-full sm:w-auto">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================== PLACEHOLDER TABS ==================== -->
            <template x-for="tab in ['saved', 'messages', 'alerts', 'cover_letters']" :key="tab">
                <div x-show="currentTab === tab" x-cloak class="max-w-4xl mx-auto lg:mx-0 pt-10">
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-16 text-center border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-400 dark:text-slate-500 shadow-inner">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.71-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white capitalize" x-text="tab.replace('_', ' ') + ' Coming Soon'"></h3>
                        <p class="text-base text-slate-500 dark:text-slate-400 mt-2 max-w-sm mx-auto leading-relaxed">We are actively building this feature. It will be available in the next Infoplus update.</p>
                        <button @click="currentTab = 'dashboard'" class="mt-8 px-8 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-full text-sm font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Return to Dashboard</button>
                    </div>
                </div>
            </template>

        </div>
    </main>

</body>
</html>