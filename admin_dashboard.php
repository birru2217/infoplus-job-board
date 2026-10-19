<?php
session_start();
require_once __DIR__ . '/config.php';

// 🌟 AUTH CHECK: Check if Sub-Admin or Admin is logged in 🌟
if (!isset($_SESSION['admin_id']) && (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin')) {
    // If superadmin accidentally visits, allow or redirect
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === true && ($_SESSION['admin_role'] ?? '') === 'superadmin') {
        header("Location: superadmin.php");
        exit();
    }
    header("Location: login.php");
    exit();
}

$cur_admin_id = (int)($_SESSION['admin_id'] ?? 0);
$success_msg = $_SESSION['success_msg'] ?? "";
$error_msg = $_SESSION['error_msg'] ?? "";
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$active_tab = 'dashboard';

// Fetch Current Admin's OWN Profile Data
$my_info = [];
if (isset($pdo) && $pdo !== null && $cur_admin_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([$cur_admin_id]);
    $my_info = $stmt->fetch();
}
if (!$my_info && isset($_SESSION['admin_email'])) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
    $stmt->execute([$_SESSION['admin_email']]);
    $my_info = $stmt->fetch();
    if ($my_info) $cur_admin_id = (int)$my_info['id'];
}

// Fallback defaults
$my_username = $my_info['username'] ?? ($_SESSION['admin_name'] ?? 'Admin');
$my_email    = $my_info['email'] ?? ($_SESSION['admin_email'] ?? 'admin@infoplus.et');
$my_phone    = $my_info['phone'] ?? '+2519...';
$my_avatar   = !empty($my_info['profile_pic']) ? $my_info['profile_pic'] : 'logo.png';
$my_2fa      = (int)($my_info['two_factor_enabled'] ?? 0);
$my_2fa_mode = $my_info['two_factor_method'] ?? 'email';

// ==========================================
// 🌟 BACKEND ACTIONS FOR SUB-ADMIN 🌟
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $active_tab = $_POST['active_tab'] ?? 'dashboard';

    // 1. POST JOB
    if ($action === 'post_job' && isset($pdo)) {
        $title = trim($_POST['title'] ?? '');
        $company = trim($_POST['company_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $job_type = trim($_POST['job_type'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $salary = trim($_POST['salary'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');
        $career_level = trim($_POST['career_level'] ?? '');
        $positions = (int)($_POST['positions'] ?? 1);
        $desc = trim($_POST['description'] ?? '');
        $req = trim($_POST['requirements'] ?? '');
        $how_to_apply = trim($_POST['how_to_apply'] ?? '');
        $post_telegram = isset($_POST['post_telegram']) ? 1 : 0;

        $logo_url = 'logo.png'; 
        $attachment_file = null;
        
        if (isset($_FILES['job_logo']) && $_FILES['job_logo']['error'] === UPLOAD_ERR_OK) {
            $logo_name = time() . '_logo_' . $_FILES['job_logo']['name'];
            move_uploaded_file($_FILES['job_logo']['tmp_name'], 'uploads/logos/' . $logo_name);
            $logo_url = 'uploads/logos/' . $logo_name;
        }
        if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
            $attach_dir = 'uploads/attachments/';
            if (!is_dir($attach_dir)) mkdir($attach_dir, 0777, true);
            $attach_name = time() . '_attach_' . $_FILES['attachment_file']['name'];
            move_uploaded_file($_FILES['attachment_file']['tmp_name'], $attach_dir . $attach_name);
            $attachment_file = $attach_dir . $attach_name;
        }

        $stmt = $pdo->prepare("INSERT INTO jobs (company_name, title, category, job_type, experience, location, salary, deadline, career_level, positions, description, requirements, how_to_apply, logo, attachment_file, status, is_top_company) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0)");
        $stmt->execute([$company, $title, $category, $job_type, $career_level, $location, $salary, $deadline, $career_level, $positions, $desc, $req, $how_to_apply, $logo_url, $attachment_file]);
        
        $new_job_id = $pdo->lastInsertId();
        $job_link = "https://infoplus.et/job-details.php?id=" . $new_job_id; 

        $_SESSION['success_msg'] = "Job published successfully!";

        if ($post_telegram) {
            $bot_token = "TOKEN_KEE_ASITTI_GALCHI"; 
            $chat_id = "@infopluscallcenter"; 
            $telegram_msg = "🌟 *Infoplus (Job Matchmaking)*\n\n💼 *Job Title:* {$title}\n🏢 *Company:* {$company}\n📍 *Location:* {$location}\n⏱ *Job Type:* {$job_type}\n💰 *Salary:* {$salary}\n📅 *Deadline:* {$deadline}\n\n👉 *[View Details & Apply]({$job_link})*";
            $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
            $data = ['chat_id' => $chat_id, 'text' => $telegram_msg, 'parse_mode' => 'Markdown'];
            @file_get_contents($url, false, stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type:application/x-www-form-urlencoded\r\n", 'content' => http_build_query($data)]]));
        }
        header("Location: admin_dashboard.php?tab=jobs"); exit();
    }

    // 2. EDIT JOB
    if ($action === 'edit_job' && isset($pdo)) {
        $job_id = (int)$_POST['job_id'];
        $stmt = $pdo->prepare("UPDATE jobs SET title=?, company_name=?, category=?, location=?, salary=?, job_type=?, career_level=?, experience=?, positions=?, deadline=?, status=?, description=?, requirements=? WHERE id=?");
        $stmt->execute([trim($_POST['title']), trim($_POST['company_name']), trim($_POST['category']), trim($_POST['location']), trim($_POST['salary']), trim($_POST['job_type']), trim($_POST['career_level']), trim($_POST['career_level']), (int)$_POST['positions'], $_POST['deadline'], $_POST['status'], trim($_POST['description'] ?? ''), trim($_POST['requirements'] ?? ''), $job_id]);
        $_SESSION['success_msg'] = "Job details updated successfully!";
        header("Location: admin_dashboard.php?tab=jobs"); exit();
    }

    // 3. DELETE JOB
    if ($action === 'delete_job' && isset($pdo)) {
        $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([(int)$_POST['job_id']]);
        $_SESSION['success_msg'] = "Job deleted successfully!";
        header("Location: admin_dashboard.php?tab=jobs"); exit();
    }

    // 4. TOGGLE TOP COMPANY
    if ($action === 'toggle_top_company' && isset($pdo)) {
        $job_id = (int)($_POST['job_id'] ?? 0);
        $company_name = trim($_POST['company_name'] ?? '');
        $new_val = ((int)($_POST['current_val'] ?? 0) > 0) ? 0 : 1;
        if ($job_id > 0) {
            try { $pdo->exec("ALTER TABLE jobs ADD COLUMN is_top_company TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
            $pdo->prepare("UPDATE jobs SET is_top_company = ? WHERE id = ?")->execute([$new_val, $job_id]);
            if (!empty($company_name)) $pdo->prepare("UPDATE jobs SET is_top_company = ? WHERE company_name = ?")->execute([$new_val, $company_name]);
            $_SESSION['success_msg'] = $new_val ? "⭐ Added to Top Companies!" : "☆ Removed from Top List!";
        }
        header("Location: admin_dashboard.php?tab=jobs"); exit();
    }

    // 5. UPDATE OWN PROFILE (Admin updates their OWN details, not Superadmin!)
    if ($action === 'update_own_profile' && isset($pdo) && $cur_admin_id > 0) {
        $new_username = trim($_POST['admin_username'] ?? $my_username);
        $new_email    = trim($_POST['admin_email'] ?? $my_email);
        $new_phone    = trim($_POST['admin_phone'] ?? $my_phone);
        $curr_pass    = $_POST['admin_curr_pass'] ?? '';
        $new_pass     = $_POST['admin_pass'] ?? '';
        $conf_pass    = $_POST['admin_pass_confirm'] ?? '';
        $two_fa       = isset($_POST['enable_2fa']) ? 1 : 0;
        $two_fa_method = $_POST['two_fa_method'] ?? 'email';

        // Check global unique email
        $chk_global = $pdo->prepare("
            SELECT email FROM admins WHERE (email = ? OR phone = ?) AND id != ?
            UNION SELECT email FROM users WHERE email = ? OR phone = ?
            UNION SELECT email FROM employers WHERE email = ? OR phone = ?
        ");
        $chk_global->execute([$new_email, $new_phone, $cur_admin_id, $new_email, $new_phone, $new_email, $new_phone]);
        
        if ($chk_global->fetch()) {
            $_SESSION['error_msg'] = "This Email or Phone is already used by another account!";
        } else {
            $profile_pic = $my_avatar;
            if (isset($_FILES['admin_avatar']) && $_FILES['admin_avatar']['error'] === UPLOAD_ERR_OK) {
                $avatar_dir = 'uploads/avatars/';
                if (!is_dir($avatar_dir)) mkdir($avatar_dir, 0777, true);
                $new_avatar_name = 'admin_avatar_' . time() . '_' . $cur_admin_id . '.png';
                if (move_uploaded_file($_FILES['admin_avatar']['tmp_name'], $avatar_dir . $new_avatar_name)) {
                    $profile_pic = $avatar_dir . $new_avatar_name;
                }
            }

            if (!empty($new_pass)) {
                if (empty($curr_pass)) {
                    $_SESSION['error_msg'] = "Please enter current password to change it.";
                } elseif (!password_verify($curr_pass, $my_info['password'])) {
                    $_SESSION['error_msg'] = "Current password is incorrect!";
                } elseif ($new_pass !== $conf_pass) {
                    $_SESSION['error_msg'] = "New passwords do not match!";
                } else {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hashed, $cur_admin_id]);
                    $_SESSION['success_msg'] = "Password updated! ";
                }
            }

            if (empty($_SESSION['error_msg'])) {
                $stmt = $pdo->prepare("UPDATE admins SET username = ?, email = ?, phone = ?, two_factor_enabled = ?, two_factor_method = ?, profile_pic = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_phone, $two_fa, $two_fa_method, $profile_pic, $cur_admin_id]);
                $_SESSION['admin_email'] = $new_email;
                $_SESSION['admin_name']  = $new_username;
                $_SESSION['success_msg'] = ($_SESSION['success_msg'] ?? "") . "Profile Settings Saved Successfully!";
            }
        }
        header("Location: admin_dashboard.php?tab=profile"); exit();
    }

    // 6. DELETE USERS / EMPLOYERS
    if ($action === 'delete_user' && isset($pdo)) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([(int)$_POST['user_id']]);
        $_SESSION['success_msg'] = "Candidate record deleted!";
        header("Location: admin_dashboard.php?tab=database"); exit();
    }
    if ($action === 'delete_employer' && isset($pdo)) {
        $pdo->prepare("DELETE FROM employers WHERE id = ?")->execute([(int)$_POST['employer_id']]);
        $_SESSION['success_msg'] = "Employer record deleted!";
        header("Location: admin_dashboard.php?tab=database"); exit();
    }

    // 7. SLIDES & FOOTER
    if ($action === 'add_slide' && isset($pdo)) {
        if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION));
            $new_name = 'slide_' . time() . '.' . $ext;
            if(move_uploaded_file($_FILES['slide_image']['tmp_name'], 'images/' . $new_name)) {
                $pdo->prepare("INSERT INTO home_slides (image_path, badge_text, title_text, badge_color) VALUES (?, ?, ?, ?)")->execute(['images/'.$new_name, trim($_POST['badge_text']), trim($_POST['title_text']), $_POST['badge_color']]);
                $_SESSION['success_msg'] = "Slide added!";
            }
        }
        header("Location: admin_dashboard.php?tab=slides"); exit();
    }
    if ($action === 'delete_slide' && isset($pdo)) {
        $pdo->prepare("DELETE FROM home_slides WHERE id = ?")->execute([(int)$_POST['slide_id']]);
        $_SESSION['success_msg'] = "Slide deleted!";
        header("Location: admin_dashboard.php?tab=slides"); exit();
    }
    if ($action === 'add_contact' && isset($pdo)) {
        $pdo->prepare("INSERT INTO dynamic_contacts (contact_type, contact_value, url) VALUES (?, ?, ?)")->execute([trim($_POST['contact_type']), trim($_POST['contact_value']), trim($_POST['url'] ?? '')]);
        $_SESSION['success_msg'] = "Contact added!";
        header("Location: admin_dashboard.php?tab=footer"); exit();
    }
    if ($action === 'delete_contact' && isset($pdo)) {
        $pdo->prepare("DELETE FROM dynamic_contacts WHERE id = ?")->execute([(int)$_POST['id']]);
        $_SESSION['success_msg'] = "Contact deleted!";
        header("Location: admin_dashboard.php?tab=footer"); exit();
    }
    if ($action === 'add_social' && isset($pdo)) {
        $pdo->prepare("INSERT INTO dynamic_socials (platform, url) VALUES (?, ?)")->execute([trim($_POST['platform']), trim($_POST['url'])]);
        $_SESSION['success_msg'] = "Social media added!";
        header("Location: admin_dashboard.php?tab=footer"); exit();
    }
    if ($action === 'delete_social' && isset($pdo)) {
        $pdo->prepare("DELETE FROM dynamic_socials WHERE id = ?")->execute([(int)$_POST['id']]);
        $_SESSION['success_msg'] = "Social media deleted!";
        header("Location: admin_dashboard.php?tab=footer"); exit();
    }

    // 8. CHAT SEND
    if ($action === 'send_message' && isset($pdo)) {
        $msg = trim($_POST['chat_message'] ?? '');
        $receiver_email = trim($_POST['receiver_email'] ?? 'general');
        $file_url = null; $file_type = null;

        if (isset($_FILES['chat_attachment']) && $_FILES['chat_attachment']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['chat_attachment']['name'], PATHINFO_EXTENSION));
            $file_name = time() . '_' . mt_rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['chat_attachment']['tmp_name'], 'uploads/chats/' . $file_name)) {
                $file_url = 'uploads/chats/' . $file_name;
                $file_type = in_array($ext, ['jpg','png','jpeg']) ? 'image' : 'document';
            }
        }
        if (!empty($msg) || $file_url) {
            $pdo->prepare("INSERT INTO messages (sender_type, sender_email, receiver_email, message, file_url, file_type) VALUES ('admin', ?, ?, ?, ?, ?)")->execute([$my_email, $receiver_email, $msg, $file_url, $file_type]);
            $_SESSION['success_msg'] = "Message sent!";
        }
        header("Location: admin_dashboard.php?tab=messages"); exit();
    }
}

// ==========================================
// 🌟 FETCH ALL DATA FOR UI 🌟
// ==========================================
$jobs_count = $pdo ? (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn() : 0;
$users_count = $pdo ? (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() : 0;
$employers_count = $pdo ? (int)$pdo->query("SELECT COUNT(*) FROM employers")->fetchColumn() : 0;

$all_posted_jobs = $pdo ? $pdo->query("SELECT * FROM jobs ORDER BY id DESC")->fetchAll() : [];
$db_candidates   = $pdo ? $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll() : [];
$db_employers    = $pdo ? $pdo->query("SELECT * FROM employers ORDER BY id DESC")->fetchAll() : [];
$recent_employers = $pdo ? $pdo->query("SELECT * FROM employers ORDER BY id DESC LIMIT 5")->fetchAll() : [];
$recent_candidates = $pdo ? $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 5")->fetchAll() : [];

$home_slides     = $pdo ? $pdo->query("SELECT * FROM home_slides ORDER BY id ASC")->fetchAll() : [];
$dyn_contacts    = $pdo ? $pdo->query("SELECT * FROM dynamic_contacts ORDER BY id ASC")->fetchAll() : [];
$dyn_socials     = $pdo ? $pdo->query("SELECT * FROM dynamic_socials ORDER BY id ASC")->fetchAll() : [];

// Chats Contacts (Excludes self, includes Superadmin, Employers, Candidates)
$chat_employers  = $pdo ? $pdo->query("SELECT 'employer' as type, id, company_name as name, email, logo_url as avatar FROM employers ORDER BY id DESC")->fetchAll() : [];
$chat_candidates = $pdo ? $pdo->query("SELECT 'candidate' as type, id, CONCAT(first_name, ' ', last_name) as name, email, 'logo.png' as avatar FROM users ORDER BY id DESC")->fetchAll() : [];
$chat_admins     = $pdo ? $pdo->prepare("SELECT 'admin' as type, id, username as name, email, profile_pic as avatar FROM admins WHERE email != ? ORDER BY id DESC") : null;
if ($chat_admins) { $chat_admins->execute([$my_email]); $chat_admins = $chat_admins->fetchAll(); } else { $chat_admins = []; }
$all_contacts    = array_merge($chat_admins, $chat_employers, $chat_candidates);
$messages        = $pdo ? $pdo->query("SELECT * FROM messages ORDER BY created_at ASC")->fetchAll() : [];
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>System Admin Portal - Infoplus</title>

    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'dark') { document.documentElement.classList.add('dark'); } 
            else { document.documentElement.classList.remove('dark'); }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #0ea5e9; border-radius: 10px; }
        .dotted-card {
            background-color: rgba(255, 255, 255, 0.95);
            background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px);
            background-size: 14px 14px;
        }
        .dark .dotted-card {
            background-color: rgba(15, 23, 42, 0.95);
            background-image: radial-gradient(#334155 1.2px, transparent 1.2px);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 h-screen overflow-hidden flex relative transition-colors duration-300"
      x-data="{ 
          currentTab: localStorage.getItem('subadmin_active_tab') || 'dashboard', 
          isSidebarOpen: false 
      }"
      x-init="$watch('currentTab', val => localStorage.setItem('subadmin_active_tab', val))">

    <!-- MOBILE OVERLAY BACKDROP -->
    <div x-show="isSidebarOpen" x-transition.opacity @click="isSidebarOpen = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 lg:hidden" x-cloak></div>

    <!-- 🌟 SUB-ADMIN SIDEBAR NAVIGATION (Clean Sky Blue Theme - NO Payments or Sub-Admins) 🌟 -->
    <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:relative top-0 left-0 h-full w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col flex-shrink-0 z-50 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
        
        <div class="h-20 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center cursor-pointer group" @click="currentTab = 'profile'; isSidebarOpen = false" title="My Profile Settings">
                <div class="relative h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shadow-lg shadow-sky-500/30 overflow-hidden border-2 border-transparent group-hover:border-sky-500 transition-all flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($my_avatar); ?>" onerror="this.src='logo.png'" alt="Avatar" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-slate-950/70 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-white text-[10px] font-bold">✏️ Edit</span>
                    </div>
                </div>
                <div class="ml-3 min-w-0">
                    <h1 class="font-black text-lg text-slate-900 dark:text-white leading-tight truncate">Infoplus</h1>
                    <span class="text-[10px] uppercase tracking-widest text-sky-500 font-extrabold block truncate">System Admin</span>
                </div>
            </div>
            <button @click="isSidebarOpen = false" class="lg:hidden text-slate-400 p-1 rounded-lg">✕</button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1.5 custom-scrollbar">
            <button @click="currentTab = 'dashboard'; isSidebarOpen = false" :class="currentTab === 'dashboard' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>📊</span> <span>Dashboard Overview</span>
            </button>
            <button @click="currentTab = 'jobs'; isSidebarOpen = false" :class="currentTab === 'jobs' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>💼</span> <span>Manage / Post Jobs</span>
            </button>
            <button @click="currentTab = 'database'; isSidebarOpen = false" :class="currentTab === 'database' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>🗄️</span> <span>Manage Users</span>
            </button>
            <button @click="currentTab = 'messages'; isSidebarOpen = false" :class="currentTab === 'messages' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>💬</span> <span>Messages (Chat)</span>
            </button>
            <button @click="currentTab = 'slides'; isSidebarOpen = false" :class="currentTab === 'slides' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>🖼️</span> <span>Manage Home Slides</span>
            </button>
            <button @click="currentTab = 'footer'; isSidebarOpen = false" :class="currentTab === 'footer' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>📝</span> <span>Manage Footer Details</span>
            </button>
            <button @click="currentTab = 'profile'; isSidebarOpen = false" :class="currentTab === 'profile' ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all mt-6">
                <span>🛡️</span> <span>My Profile Settings</span>
            </button>
        </nav>

        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            <a href="logout.php" class="w-full flex items-center justify-center space-x-2 px-4 py-3 rounded-xl bg-rose-500/10 text-rose-600 hover:bg-rose-500 hover:text-white transition-all font-bold text-sm border border-rose-500/20">
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- 🌟 MAIN CONTENT AREA 🌟 -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-950 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px] w-full transition-colors duration-300">
        
        <!-- Topbar -->
        <header class="h-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sm:px-8 z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <button @click="isSidebarOpen = true" class="lg:hidden p-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-sky-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white capitalize" x-text="currentTab.replace('_', ' ')"></h2>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-4">
                <a href="index.php" target="_blank" class="hidden sm:flex items-center space-x-1 text-xs sm:text-sm font-bold text-sky-600 dark:text-sky-400 bg-sky-500/10 hover:bg-sky-600 hover:text-white px-3 py-1.5 rounded-lg transition-all">
                    <span>View Site ↗</span>
                </a>
                
                <button id="theme-toggle" class="p-2 sm:p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-amber-500 hover:bg-slate-200 dark:hover:text-amber-400 dark:hover:bg-slate-700 transition-colors focus:outline-none shadow-sm border border-slate-200 dark:border-slate-700">
                    <svg id="sun-icon" class="h-4 w-4 sm:h-5 sm:w-5 hidden text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                    </svg>
                    <svg id="moon-icon" class="h-4 w-4 sm:h-5 sm:w-5 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <!-- Admin Avatar -->
                <div @click="currentTab = 'profile'" class="cursor-pointer h-9 w-9 sm:h-11 sm:w-11 rounded-full overflow-hidden border-2 border-sky-500 shadow-md">
                    <img src="<?php echo htmlspecialchars($my_avatar); ?>" onerror="this.src='logo.png'" class="w-full h-full object-cover">
                </div>
            </div>
        </header>

        <!-- Scrollable Content Container -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-8 pb-28 custom-scrollbar relative"
             id="mainScrollContainer"
             @touchstart="if ($el.scrollTop === 0) startY = $event.touches[0].pageY;"
             @touchmove="if ($el.scrollTop === 0 && startY > 0) { let dist = $event.touches[0].pageY - startY; if (dist > 0 && dist < 120) pullDist = dist; }"
             @touchend="if (pullDist > 60) { isRefreshing = true; window.location.reload(); } startY = 0; pullDist = 0;"
             x-data="{ startY: 0, pullDist: 0, isRefreshing: false }">

            <!-- Pull to refresh indicator -->
            <div x-show="pullDist > 0 || isRefreshing" x-transition class="flex items-center justify-center space-x-2 py-2.5 px-4 bg-sky-500/10 text-sky-600 rounded-full text-xs font-black mb-4 mx-auto max-w-xs shadow-md">
                <span class="text-base" :class="isRefreshing ? 'animate-spin' : ''">🔄</span>
                <span x-text="isRefreshing ? 'Refreshing...' : 'Release to Refresh'"></span>
            </div>

            <!-- Alerts -->
            <?php if($success_msg): ?>
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm font-bold">
                    ✅ <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-sm font-bold">
                    ⚠️ <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- ==================== TAB 1: DASHBOARD OVERVIEW ==================== -->
            <div x-show="currentTab === 'dashboard'" x-cloak class="space-y-6 sm:space-y-8">
                
                <!-- Quick Actions Banner -->
                <div class="bg-gradient-to-r from-sky-500/10 via-blue-500/10 to-indigo-500/10 p-6 sm:p-8 rounded-[2.5rem] border border-sky-500/20 flex flex-col sm:flex-row justify-between items-center gap-6 shadow-sm">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">Welcome back, <?php echo htmlspecialchars($my_username); ?>! 👋</h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2">You are logged in with System Administrator privileges. Manage platform activities here.</p>
                    </div>
                    <button @click="currentTab = 'jobs'" class="px-6 py-3.5 bg-sky-600 hover:bg-sky-500 text-white rounded-2xl text-xs sm:text-sm font-black shadow-lg shadow-sky-600/30 whitespace-nowrap transform hover:-translate-y-0.5 transition-all">
                        + Post New Vacancy
                    </button>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                    <div @click="currentTab = 'jobs'" class="cursor-pointer bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:shadow-xl hover:border-teal-500 transition-all group">
                        <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest group-hover:text-teal-500">Active Jobs</span>
                        <p class="text-3xl sm:text-4xl font-black text-teal-600 dark:text-teal-400 mt-2"><?php echo $jobs_count; ?></p>
                        <span class="text-[9px] font-bold text-slate-400 mt-2 block group-hover:text-teal-500">Manage Jobs ↗</span>
                    </div>
                    <div @click="currentTab = 'database'" class="cursor-pointer bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:shadow-xl hover:border-sky-500 transition-all group">
                        <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest group-hover:text-sky-500">Candidates</span>
                        <p class="text-3xl sm:text-4xl font-black text-sky-600 dark:text-sky-400 mt-2"><?php echo $users_count; ?></p>
                        <span class="text-[9px] font-bold text-slate-400 mt-2 block group-hover:text-sky-500">View All ↗</span>
                    </div>
                    <div @click="currentTab = 'database'" class="cursor-pointer bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:shadow-xl hover:border-amber-500 transition-all group">
                        <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest group-hover:text-amber-500">Employers</span>
                        <p class="text-3xl sm:text-4xl font-black text-amber-500 dark:text-amber-400 mt-2"><?php echo $employers_count; ?></p>
                        <span class="text-[9px] font-bold text-slate-400 mt-2 block group-hover:text-amber-500">View All ↗</span>
                    </div>
                </div>

                <!-- Chart & Breakdown -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div>
                            <h3 class="text-slate-900 dark:text-white font-black text-lg sm:text-xl">Platform Analytics</h3>
                            <p class="text-slate-400 text-[10px] sm:text-xs mt-0.5">Real-time ecosystem metrics</p>
                        </div>
                        <span class="px-3 py-1.5 bg-sky-500/10 text-sky-600 dark:text-sky-400 rounded-full text-[10px] sm:text-xs font-bold">⚡ Live</span>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-6 flex justify-center">
                            <div class="relative w-full max-w-[250px] aspect-square">
                                <canvas id="systemOverviewChart"></canvas>
                            </div>
                        </div>
                        <div class="lg:col-span-6 space-y-4">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Metrics Breakdown</h4>
                            <div class="dotted-card p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex justify-between shadow-sm">
                                <div class="flex items-center space-x-3"><span class="h-3 w-3 rounded-full bg-teal-500"></span><span class="font-bold text-sm">Active Jobs</span></div>
                                <span class="font-black text-teal-600"><?php echo $jobs_count; ?></span>
                            </div>
                            <div class="dotted-card p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex justify-between shadow-sm">
                                <div class="flex items-center space-x-3"><span class="h-3 w-3 rounded-full bg-sky-500"></span><span class="font-bold text-sm">Candidates</span></div>
                                <span class="font-black text-sky-600"><?php echo $users_count; ?></span>
                            </div>
                            <div class="dotted-card p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex justify-between shadow-sm">
                                <div class="flex items-center space-x-3"><span class="h-3 w-3 rounded-full bg-amber-500"></span><span class="font-bold text-sm">Employers</span></div>
                                <span class="font-black text-amber-500"><?php echo $employers_count; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                        <h3 class="font-black text-base mb-4 flex items-center gap-2">🏢 Recent Employers</h3>
                        <div class="space-y-3">
                            <?php if(empty($recent_employers)): ?> <p class="text-xs text-slate-400">No data.</p> <?php endif; ?>
                            <?php foreach($recent_employers as $emp): ?>
                                <div class="dotted-card p-3 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-sm border border-amber-500/20">🏢</div>
                                        <div class="truncate">
                                            <h4 class="font-bold text-xs sm:text-sm truncate"><?php echo htmlspecialchars($emp['company_name']); ?></h4>
                                            <p class="text-[10px] text-slate-400 truncate"><?php echo htmlspecialchars($emp['email']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm">
                        <h3 class="font-black text-base mb-4 flex items-center gap-2">👥 Recent Candidates</h3>
                        <div class="space-y-3">
                            <?php if(empty($recent_candidates)): ?> <p class="text-xs text-slate-400">No data.</p> <?php endif; ?>
                            <?php foreach($recent_candidates as $cand): ?>
                                <div class="dotted-card p-3 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center text-sm border border-sky-500/20">👤</div>
                                        <div class="truncate">
                                            <h4 class="font-bold text-xs sm:text-sm truncate"><?php echo htmlspecialchars($cand['first_name'].' '.$cand['last_name']); ?></h4>
                                            <p class="text-[10px] text-slate-400 truncate"><?php echo htmlspecialchars($cand['phone']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB 2: MY SETTINGS (Admin's OWN Profile) ==================== -->
            <div x-show="currentTab === 'profile'" class="max-w-4xl space-y-6" x-cloak>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-10 rounded-3xl shadow-sm space-y-6">
                    <div class="flex items-center space-x-3 border-b border-slate-100 dark:border-slate-800 pb-5">
                        <span class="text-3xl">🛡️</span>
                        <div>
                            <h3 class="text-slate-900 dark:text-white font-black text-xl">My Admin Profile</h3>
                            <p class="text-[10px] sm:text-xs text-slate-500">Manage your personal credentials, avatar, and 2FA security.</p>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="update_own_profile">
                        <input type="hidden" name="active_tab" value="profile">

                        <!-- Avatar -->
                        <div class="p-6 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700/50 flex items-center space-x-6">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden border-4 border-sky-500 shadow-xl flex-shrink-0">
                                <img src="<?php echo htmlspecialchars($my_avatar); ?>" onerror="this.src='logo.png'" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h4 class="font-extrabold text-sm sm:text-base">Profile Avatar</h4>
                                <p class="text-[10px] sm:text-xs text-slate-500 mb-3">Upload your custom picture for chat and sidebar.</p>
                                <input type="file" name="admin_avatar" accept="image/*" class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-sky-500/10 file:text-sky-600 cursor-pointer">
                            </div>
                        </div>

                        <!-- Credentials -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">Username *</label>
                                <input type="text" name="admin_username" value="<?php echo htmlspecialchars($my_username); ?>" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-xs text-slate-900 dark:text-white outline-none focus:border-sky-500 font-bold">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">Email Address *</label>
                                <input type="email" name="admin_email" value="<?php echo htmlspecialchars($my_email); ?>" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-xs text-slate-900 dark:text-white outline-none focus:border-sky-500 font-bold">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">Phone Number *</label>
                                <input type="text" name="admin_phone" value="<?php echo htmlspecialchars($my_phone); ?>" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-xs text-slate-900 dark:text-white outline-none focus:border-sky-500 font-bold">
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="p-6 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700/50 space-y-4">
                            <h4 class="font-extrabold text-sm flex items-center gap-2"><span>🔑</span> Change Password</h4>
                            <div x-data="{ showCurr: false }">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Current Password</label>
                                <div class="relative">
                                    <input :type="showCurr ? 'text' : 'password'" name="admin_curr_pass" placeholder="Enter current password" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-4 pr-10 py-2.5 text-xs outline-none focus:border-sky-500">
                                    <button type="button" @click="showCurr = !showCurr" class="absolute inset-y-0 right-0 pr-3 text-slate-400 hover:text-sky-600">👁️</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div x-data="{ showNew: false }">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">New Password</label>
                                    <div class="relative">
                                        <input :type="showNew ? 'text' : 'password'" name="admin_pass" placeholder="••••••••" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-4 pr-10 py-2.5 text-xs outline-none focus:border-sky-500">
                                        <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 pr-3 text-slate-400 hover:text-sky-600">👁️</button>
                                    </div>
                                </div>
                                <div x-data="{ showConf: false }">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Confirm New Password</label>
                                    <div class="relative">
                                        <input :type="showConf ? 'text' : 'password'" name="admin_pass_confirm" placeholder="••••••••" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl pl-4 pr-10 py-2.5 text-xs outline-none focus:border-sky-500">
                                        <button type="button" @click="showConf = !showConf" class="absolute inset-y-0 right-0 pr-3 text-slate-400 hover:text-sky-600">👁️</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2FA -->
                        <div class="p-6 bg-sky-500/5 dark:bg-sky-500/10 rounded-2xl border border-sky-500/30 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-black text-base flex items-center gap-2"><span>🔒</span> Two-Factor Auth (2FA)</h4>
                                    <p class="text-[10px] sm:text-xs text-slate-500 mt-1">Require 6-digit OTP code when logging in.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="enable_2fa" class="sr-only peer" <?php echo $my_2fa ? 'checked' : ''; ?>>
                                    <div class="w-12 h-6 bg-slate-300 dark:bg-slate-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500"></div>
                                </label>
                            </div>
                            <div class="pt-3 border-t border-sky-500/20">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">OTP Delivery Channel:</label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <label class="flex items-center space-x-2 p-3 rounded-xl border bg-white dark:bg-slate-800 cursor-pointer">
                                        <!-- Email Option -->
                                        <input type="radio" name="two_fa_method" value="email" <?php echo $my_2fa_mode === 'email' ? 'checked' : ''; ?> class="accent-sky-500">
                                        <span class="text-xs font-bold">📧 Email</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-3 rounded-xl border bg-white dark:bg-slate-800 cursor-pointer">
                                        <!-- 🌟 MAGIC FIX: $method_2fa gara $my_2fa_mode tti sirraa'eera 🌟 -->
                                        <input type="radio" name="two_fa_method" value="whatsapp" <?php echo $my_2fa_mode === 'whatsapp' ? 'checked' : ''; ?> class="accent-sky-500">
                                        <span class="text-xs font-bold">📱 WhatsApp</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full sm:w-auto bg-sky-600 hover:bg-sky-500 text-white font-extrabold py-3.5 px-10 rounded-2xl text-xs sm:text-sm shadow-lg">Save Settings</button>
                    </form>
                </div>
            </div>

            <!-- ==================== TAB 3: MANAGE / POST JOBS ==================== -->
            <div x-show="currentTab === 'jobs'" class="space-y-8" x-cloak>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-10 rounded-3xl shadow-sm">
                    <div class="flex items-center space-x-3 mb-8 border-b border-slate-100 dark:border-slate-800 pb-5">
                        <span class="text-3xl">💼</span>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Post New Job</h2>
                            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400">Publish detailed vacancies. Select from list OR type custom categories manually.</p>
                        </div>
                    </div>

                    <!-- Same comprehensive form as superadmin -->
                    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <input type="hidden" name="action" value="post_job">
                        <input type="hidden" name="active_tab" value="jobs">

                        <div class="md:col-span-6">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Job Title *</label>
                            <input type="text" name="title" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500">
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Company Name *</label>
                            <input type="text" name="company_name" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500">
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Category *</label>
                            <input type="text" name="category" list="cat_list" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500">
                            <datalist id="cat_list"><option value="Accounting and Finance"><option value="ICT and Telecom"><option value="Customer Service"></datalist>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Location *</label>
                            <input type="text" name="location" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Salary Offer *</label>
                            <input type="text" name="salary" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Employment Type *</label>
                            <input type="text" name="job_type" list="type_list" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500">
                            <datalist id="type_list"><option value="Full Time"><option value="Part Time"><option value="Contract"></datalist>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Career Level *</label>
                            <input type="text" name="career_level" list="lvl_list" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500">
                            <datalist id="lvl_list"><option value="Entry Level"><option value="Mid Level"><option value="Senior"></datalist>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Qty Required *</label>
                            <input type="number" name="positions" value="1" min="1" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-rose-500 uppercase mb-1.5">Deadline Date *</label>
                            <input type="date" name="deadline" required class="w-full bg-rose-50 border border-rose-300 rounded-xl px-4 py-3 text-rose-600 font-bold text-sm outline-none focus:border-rose-500">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Job Description *</label>
                            <textarea name="description" rows="3" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500"></textarea>
                        </div>
                        <div class="md:col-span-12">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Duties & Requirements *</label>
                            <textarea name="requirements" rows="3" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500"></textarea>
                        </div>
                        <div class="md:col-span-12">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">How To Apply</label>
                            <textarea name="how_to_apply" rows="2" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm outline-none focus:border-sky-500"></textarea>
                        </div>

                        <div class="md:col-span-6 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 border-dashed">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Company Logo (Image)</label>
                            <input type="file" name="job_logo" accept="image/*" class="w-full text-xs text-slate-500 cursor-pointer">
                        </div>
                        <div class="md:col-span-6 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 border-dashed">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Attach Full Document (PDF / Flyer)</label>
                            <input type="file" name="attachment_file" accept=".pdf,image/*" class="w-full text-xs text-slate-500 cursor-pointer">
                        </div>

                        <div class="md:col-span-12 border-2 border-sky-500/30 bg-sky-500/5 p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h4 class="font-black text-sky-600 text-base">🪄 Auto-Post to Telegram</h4>
                            </div>
                            <label class="flex items-center space-x-2 bg-white px-3 py-2 rounded-xl shadow-sm border border-slate-200 cursor-pointer">
                                <input type="checkbox" name="post_telegram" checked class="w-4 h-4 accent-sky-500">
                                <span class="text-xs font-bold">✈️ Telegram</span>
                            </label>
                        </div>

                        <div class="md:col-span-12 mt-2">
                            <button type="submit" onclick="if(this.form.checkValidity()){ this.disabled=true; this.innerHTML='⏳ Publishing...'; this.form.submit(); }" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-black py-4 rounded-xl text-lg shadow-lg cursor-pointer">
                                🚀 Publish Job
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Manage Posted Jobs Cards (Same as Superadmin) -->
                <div class="mt-16 pt-10 border-t border-slate-200 dark:border-slate-800 w-full" x-data="{ editJobRecord: null, jobSearchQuery: '', matchJob(t) { return !this.jobSearchQuery.trim() || t.toLowerCase().includes(this.jobSearchQuery.toLowerCase().trim()); } }">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                        <h3 class="text-xl font-black flex items-center gap-2"><span>🗂️</span> Manage Posted Jobs</h3>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <span class="px-3.5 py-2 rounded-xl bg-sky-500/10 text-sky-600 font-bold text-xs whitespace-nowrap">Total: <?php echo count($all_posted_jobs); ?></span>
                            <div class="relative flex-1 sm:w-64">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs">🔍</span>
                                <input type="text" x-model="jobSearchQuery" placeholder="Search..." class="w-full pl-8 pr-7 py-2 bg-slate-50 border rounded-xl text-xs outline-none focus:border-sky-500">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach($all_posted_jobs as $j): 
                            $c_logo = $j['logo'] ?? 'logo.png'; $c_title = $j['title'] ?? 'Untitled'; $c_company = $j['company_name'] ?? 'Unknown'; $c_location = $j['location'] ?? 'Not Specified'; $c_deadline = $j['deadline'] ?? 'Open'; $c_category = $j['category'] ?? 'General'; $c_type = $j['job_type'] ?? 'Full Time'; $c_level = $j['career_level'] ?? $j['experience'] ?? 'Mid Level'; $c_positions = $j['positions'] ?? 1; $c_salary = $j['salary'] ?? 'Negotiable'; $c_status = $j['status'] ?? 'active';
                            $search_str = strtolower($c_title.' '.$c_company.' '.$c_category.' '.$c_location);
                        ?>
                        <div x-show="matchJob('<?php echo addslashes($search_str); ?>')" class="dotted-card bg-white border border-slate-200 rounded-[2rem] p-5 shadow-sm hover:shadow-xl transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-4 pb-3 border-b border-slate-100">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold <?php echo $c_status==='active'?'bg-emerald-100 text-emerald-600':'bg-rose-100 text-rose-600'; ?>"><?php echo strtoupper($c_status); ?></span>
                                    <div class="flex items-center gap-1.5">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_top_company"><input type="hidden" name="active_tab" value="jobs"><input type="hidden" name="job_id" value="<?php echo $j['id']; ?>"><input type="hidden" name="current_val" value="<?php echo $j['is_top_company']??0; ?>"><input type="hidden" name="company_name" value="<?php echo htmlspecialchars($c_company); ?>">
                                            <button type="submit" class="px-2 py-1 rounded-md text-[9px] font-black border <?php echo ($j['is_top_company']??0)==1 ? 'bg-amber-500 text-slate-900 border-amber-500' : 'bg-slate-100 text-slate-400'; ?>">
                                                <?php echo ($j['is_top_company']??0)==1 ? '⭐ Top' : '☆ Add Top'; ?>
                                            </button>
                                        </form>
                                        <button @click="editJobRecord = <?php echo htmlspecialchars(json_encode($j)); ?>" class="h-6 w-6 rounded-md bg-blue-100 text-blue-600 flex items-center justify-center text-[10px]">✏️</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete permanently?')">
                                            <input type="hidden" name="action" value="delete_job"><input type="hidden" name="active_tab" value="jobs"><input type="hidden" name="job_id" value="<?php echo $j['id']; ?>">
                                            <button type="submit" class="h-6 w-6 rounded-md bg-rose-100 text-rose-600 flex items-center justify-center text-[10px]">🗑️</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3 mb-3">
                                    <div class="w-12 h-12 rounded-2xl border p-1"><img src="<?php echo htmlspecialchars($c_logo); ?>" class="w-full h-full object-contain"></div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-extrabold truncate"><?php echo htmlspecialchars($c_title); ?></h3>
                                        <p class="text-[10px] font-medium text-slate-500 truncate">🏢 <?php echo htmlspecialchars($c_company); ?></p>
                                    </div>
                                </div>
                                <div class="space-y-2 text-xs text-left">
                                    <div class="flex justify-between border-b pb-1"><span class="font-bold text-sky-600 text-[10px]">LOC:</span><span class="truncate max-w-[60%] font-bold"><?php echo htmlspecialchars($c_location); ?></span></div>
                                    <div class="flex justify-between border-b pb-1"><span class="font-bold text-sky-600 text-[10px]">SALARY:</span><span class="truncate max-w-[60%] font-black"><?php echo htmlspecialchars($c_salary); ?></span></div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t flex justify-between text-[10px] font-semibold text-slate-400">
                                <span><?php echo htmlspecialchars($c_deadline); ?></span>
                                <a href="job-details.php?id=<?php echo $j['id']; ?>" target="_blank" class="text-sky-600 hover:underline">Full Details ↗</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Edit Job Modal -->
                    <div x-show="editJobRecord !== null" class="fixed inset-0 z-50 bg-slate-950/80 flex items-center justify-center p-4" x-cloak>
                        <div @click.away="editJobRecord = null" class="bg-white border rounded-3xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto custom-scrollbar">
                            <h3 class="font-black text-lg mb-4 border-b pb-2">✏️ Edit Job</h3>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="edit_job"><input type="hidden" name="active_tab" value="jobs"><input type="hidden" name="job_id" :value="editJobRecord ? editJobRecord.id : ''">
                                <div class="grid grid-cols-2 gap-4">
                                    <div><label class="text-[10px] font-bold">Title</label><input type="text" name="title" :value="editJobRecord?.title" class="w-full border rounded-lg px-3 py-2 text-xs"></div>
                                    <div><label class="text-[10px] font-bold">Company</label><input type="text" name="company_name" :value="editJobRecord?.company_name" class="w-full border rounded-lg px-3 py-2 text-xs"></div>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div><label class="text-[10px] font-bold">Category</label><input type="text" name="category" :value="editJobRecord?.category" class="w-full border rounded-lg px-3 py-2 text-xs"></div>
                                    <div><label class="text-[10px] font-bold">Location</label><input type="text" name="location" :value="editJobRecord?.location" class="w-full border rounded-lg px-3 py-2 text-xs"></div>
                                    <div><label class="text-[10px] font-bold">Salary</label><input type="text" name="salary" :value="editJobRecord?.salary" class="w-full border rounded-lg px-3 py-2 text-xs"></div>
                                </div>
                                <div class="grid grid-cols-4 gap-4">
                                    <div><label class="text-[10px] font-bold">Type</label><input type="text" name="job_type" :value="editJobRecord?.job_type" class="w-full border rounded-lg px-3 py-2 text-xs"></div>
                                    <div><label class="text-[10px] font-bold">Level</label><input type="text" name="career_level" :value="editJobRecord?.career_level || editJobRecord?.experience" class="w-full border rounded-lg px-3 py-2 text-xs"></div>
                                    <div><label class="text-[10px] font-bold text-amber-600">Qty</label><input type="number" name="positions" :value="editJobRecord?.positions || 1" class="w-full border-2 border-amber-500 rounded-lg px-3 py-2 text-xs font-bold"></div>
                                    <div><label class="text-[10px] font-bold text-rose-500">Deadline</label><input type="date" name="deadline" :value="editJobRecord?.deadline" class="w-full border border-rose-300 rounded-lg px-3 py-2 text-xs font-bold"></div>
                                </div>
                                <div><label class="text-[10px] font-bold">Status</label><select name="status" class="w-full border rounded-lg px-3 py-2 text-xs" x-model="editJobRecord ? editJobRecord.status : 'active'"><option value="active">Active</option><option value="expired">Expired</option></select></div>
                                <div class="flex justify-end gap-2 pt-4">
                                    <button type="button" @click="editJobRecord = null" class="px-4 py-2 bg-slate-100 rounded-xl text-xs font-bold">Cancel</button>
                                    <button type="submit" class="px-5 py-2 bg-sky-600 text-white rounded-xl text-xs font-bold">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB 4: DATABASE (Users & Employers) ==================== -->
            <div x-show="currentTab === 'database'" x-data="{ dbTab: 'candidates', viewRecord: null, viewType: '' }" class="space-y-6 max-w-7xl" x-cloak>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2"><span>🗄️</span> Database Manager</h2>
                        <p class="text-xs text-slate-500 mt-1">Live inspection and management of registered users.</p>
                    </div>
                    <div class="inline-flex p-1.5 rounded-2xl bg-slate-100 border overflow-x-auto">
                        <button @click="dbTab = 'candidates'" :class="dbTab === 'candidates' ? 'bg-cyan-600 text-white' : 'text-slate-600 hover:text-cyan-600'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap">👥 Candidates (<?php echo count($db_candidates); ?>)</button>
                        <button @click="dbTab = 'employers'" :class="dbTab === 'employers' ? 'bg-amber-500 text-slate-950' : 'text-slate-600 hover:text-amber-500'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap">🏢 Employers (<?php echo count($db_employers); ?>)</button>
                    </div>
                </div>

                <!-- CANDIDATES TABLE -->
                <div x-show="dbTab === 'candidates'" class="bg-white border p-6 rounded-3xl overflow-x-auto">
                    <table class="w-full text-left min-w-[650px]">
                        <thead class="text-[10px] uppercase text-slate-500 bg-slate-50 border-b">
                            <tr><th class="py-3 px-4">Name</th><th class="py-3 px-4">Email</th><th class="py-3 px-4">Phone</th><th class="py-3 px-4">Status</th><th class="py-3 px-4 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y text-sm">
                            <?php foreach ($db_candidates as $c): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3.5 px-4 font-bold"><?php echo htmlspecialchars(($c['first_name']??'').' '.($c['last_name']??'')); ?></td>
                                <td class="py-3.5 px-4"><?php echo htmlspecialchars($c['email']??''); ?></td>
                                <td class="py-3.5 px-4 font-semibold text-cyan-600"><?php echo htmlspecialchars($c['phone']??''); ?></td>
                                <td class="py-3.5 px-4"><span class="px-2.5 py-1 rounded-full text-[9px] font-extrabold uppercase <?php echo ($c['is_verified']??0)?'bg-emerald-100 text-emerald-600':'bg-amber-100 text-amber-600'; ?>"><?php echo ($c['is_verified']??0)?'Verified':'Pending'; ?></span></td>
                                <td class="py-3.5 px-4 text-right space-x-2">
                                    <button @click="viewRecord = <?php echo htmlspecialchars(json_encode($c)); ?>; viewType = 'Candidate'" class="px-3 py-1 bg-cyan-100 text-cyan-600 rounded-lg text-xs font-bold">👁️ View</button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete candidate?')">
                                        <input type="hidden" name="action" value="delete_user"><input type="hidden" name="active_tab" value="database"><input type="hidden" name="user_id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="px-2.5 py-1 bg-rose-100 text-rose-500 rounded-lg text-xs font-bold">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- EMPLOYERS TABLE -->
                <div x-show="dbTab === 'employers'" class="bg-white border p-6 rounded-3xl overflow-x-auto" x-cloak>
                    <table class="w-full text-left min-w-[650px]">
                        <thead class="text-[10px] uppercase text-slate-500 bg-slate-50 border-b">
                            <tr><th class="py-3 px-4">Company</th><th class="py-3 px-4">Contact</th><th class="py-3 px-4">Email / Phone</th><th class="py-3 px-4">Status</th><th class="py-3 px-4 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y text-sm">
                            <?php foreach ($db_employers as $e): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3.5 px-4 font-bold"><?php echo htmlspecialchars($e['company_name']??''); ?></td>
                                <td class="py-3.5 px-4"><?php echo htmlspecialchars($e['contact_name']??''); ?></td>
                                <td class="py-3.5 px-4 text-xs"><?php echo htmlspecialchars($e['email']??''); ?><br><span class="text-amber-500 font-bold"><?php echo htmlspecialchars($e['phone']??''); ?></span></td>
                                <td class="py-3.5 px-4"><span class="px-2.5 py-1 rounded-full text-[9px] font-extrabold uppercase <?php echo ($e['status']??'')==='approved'?'bg-emerald-100 text-emerald-600':'bg-amber-100 text-amber-600'; ?>"><?php echo htmlspecialchars($e['status']??'pending'); ?></span></td>
                                <td class="py-3.5 px-4 text-right space-x-2">
                                    <button @click="viewRecord = <?php echo htmlspecialchars(json_encode($e)); ?>; viewType = 'Employer'" class="px-3 py-1 bg-amber-100 text-amber-600 rounded-lg text-xs font-bold">👁️ View</button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete employer?')">
                                        <input type="hidden" name="action" value="delete_employer"><input type="hidden" name="active_tab" value="database"><input type="hidden" name="employer_id" value="<?php echo $e['id']; ?>">
                                        <button type="submit" class="px-2.5 py-1 bg-rose-100 text-rose-500 rounded-lg text-xs font-bold">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- View Modal -->
                <div x-show="viewRecord !== null" class="fixed inset-0 z-50 bg-slate-950/80 flex items-center justify-center p-4" x-cloak>
                    <div @click.away="viewRecord = null" class="bg-white rounded-3xl p-8 max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar">
                        <div class="flex justify-between items-center border-b pb-4 mb-4">
                            <h3 class="font-black text-lg" x-text="viewType + ' Details'"></h3>
                            <button @click="viewRecord = null" class="text-slate-400 font-bold">✕</button>
                        </div>
                        <div class="space-y-3 text-xs">
                            <template x-for="(value, key) in viewRecord" :key="key">
                                <div x-show="key !== 'password' && key !== 'otp_code' && value !== null" class="flex justify-between border-b pb-2">
                                    <span class="font-bold uppercase text-[10px] text-slate-400" x-text="key.replace('_', ' ')"></span>
                                    <span class="font-semibold text-right max-w-[65%] break-all" x-text="value"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB 5: MESSAGES (CHAT) ==================== -->
            <div x-show="currentTab === 'messages'" x-data="{ chatFolder: 'all', mobileView: 'list', activeContact: null, editMsgObj: null }" class="h-[75vh] sm:h-[700px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xl flex flex-col md:flex-row overflow-hidden relative" x-cloak>
                <!-- Contacts List -->
                <div :class="mobileView === 'list' ? 'flex' : 'hidden md:flex'" class="w-full md:w-80 border-r flex-col bg-slate-50 flex-shrink-0 z-10 h-full">
                    <div class="p-4 border-b bg-white"><h3 class="font-black text-base">Messages</h3></div>
                    <!-- Folders Switcher (With Superadmin Added) -->
                    <div class="flex overflow-x-auto px-2 py-2 bg-white border-b border-slate-200 dark:border-slate-800 custom-scrollbar gap-1">
                        <button @click="chatFolder = 'all'" :class="chatFolder === 'all' ? 'bg-sky-100 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400 font-bold' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] whitespace-nowrap transition-colors">
                            All
                        </button>
                        <button @click="chatFolder = 'employer'" :class="chatFolder === 'employer' ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] whitespace-nowrap transition-colors">
                            🏢 Employers
                        </button>
                        <button @click="chatFolder = 'candidate'" :class="chatFolder === 'candidate' ? 'bg-cyan-100 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-400 font-bold' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] whitespace-nowrap transition-colors">
                            👥 Candidates
                        </button>
                        
                        <!-- 🌟 MAGIC FIX: SUPERADMIN FILTER BUTTON ADDED HERE 🌟 -->
                        <button @click="chatFolder = 'admin'" :class="chatFolder === 'admin' ? 'bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] whitespace-nowrap transition-colors">
                            🛡️ Superadmin
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto custom-scrollbar divide-y">
                        <?php foreach ($all_contacts as $contact): ?>
                        <div x-show="chatFolder === 'all' || chatFolder === '<?php echo $contact['type']; ?>'" @click="activeContact = <?php echo htmlspecialchars(json_encode($contact)); ?>; mobileView = 'chat';" class="flex items-center p-3.5 cursor-pointer hover:bg-slate-100">
                            <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden flex-shrink-0"><img src="<?php echo $contact['avatar'] ?: 'logo.png'; ?>" class="w-full h-full object-cover"></div>
                            <div class="ml-3 min-w-0">
                                <h4 class="font-bold text-xs truncate"><?php echo htmlspecialchars($contact['name']); ?></h4>
                                <p class="text-[10px] text-slate-400 truncate"><?php echo htmlspecialchars($contact['email']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Chat Window -->
                <div :class="mobileView === 'chat' ? 'flex' : 'hidden md:flex'" class="w-full md:flex-1 flex-col h-full bg-slate-50">
                    <div class="p-3 border-b bg-white flex justify-between items-center z-20">
                        <div class="flex items-center space-x-3">
                            <button @click="mobileView = 'list'" class="md:hidden text-sky-600 font-bold px-2">← Back</button>
                            <h3 class="font-extrabold text-sm" x-text="activeContact ? activeContact.name : 'Select a Contact'"></h3>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
                        <template x-if="activeContact">
                            <div><!-- Messages would be filtered and shown here matching activeContact.email --></div>
                        </template>
                    </div>
                    <div class="p-3 bg-white border-t z-20">
                        <form method="POST" enctype="multipart/form-data" class="flex items-center space-x-2">
                            <input type="hidden" name="action" value="send_message">
                            <input type="hidden" name="active_tab" value="messages">
                            <input type="hidden" name="receiver_email" x-bind:value="activeContact ? activeContact.email : ''">
                            <label class="p-2.5 bg-slate-100 rounded-full cursor-pointer text-xl">📎 <input type="file" name="chat_attachment" class="hidden"></label>
                            <input type="text" name="chat_message" placeholder="Type message..." class="flex-1 bg-slate-100 rounded-xl px-4 py-2.5 text-sm outline-none">
                            <button type="submit" class="p-2.5 bg-sky-600 text-white rounded-full text-xl">✈️</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ==================== TAB 6: SLIDES ==================== -->
            <div x-show="currentTab === 'slides'" class="space-y-6 max-w-7xl" x-cloak>
                <div class="flex justify-between items-center bg-white p-6 rounded-3xl shadow-sm">
                    <div>
                        <h2 class="text-xl font-black">Manage Slides</h2>
                        <p class="text-xs text-slate-500">Update homepage banner slides.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($home_slides as $slide): ?>
                    <div class="bg-white rounded-3xl p-5 shadow-sm relative group">
                        <form method="POST" class="absolute top-7 right-7 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                            <input type="hidden" name="action" value="delete_slide"><input type="hidden" name="active_tab" value="slides"><input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            <button type="submit" class="h-8 w-8 bg-rose-500 text-white rounded-full">🗑️</button>
                        </form>
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="update_slide"><input type="hidden" name="active_tab" value="slides"><input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            <div class="relative aspect-[16/9] rounded-2xl overflow-hidden"><img src="<?php echo htmlspecialchars($slide['image_path']); ?>" class="w-full h-full object-cover object-top"></div>
                            <input type="file" name="slide_image" accept="image/*" class="w-full text-[10px] text-slate-500 file:bg-sky-50 file:text-sky-600 file:border-0 file:rounded-md file:px-2 file:py-1 cursor-pointer">
                            <div class="grid grid-cols-2 gap-2">
                                <div><label class="text-[9px] font-bold">Badge Text</label><input type="text" name="badge_text" value="<?php echo htmlspecialchars($slide['badge_text']); ?>" class="w-full bg-slate-50 rounded-lg px-2 py-1 text-xs border"></div>
                                <div><label class="text-[9px] font-bold">Badge Color</label><select name="badge_color" class="w-full bg-slate-50 rounded-lg px-2 py-1 text-xs border"><option value="bg-teal-500" <?php echo $slide['badge_color']=='bg-teal-500'?'selected':'';?>>Teal</option><option value="bg-amber-500" <?php echo $slide['badge_color']=='bg-amber-500'?'selected':'';?>>Amber</option><option value="bg-sky-500" <?php echo $slide['badge_color']=='bg-sky-500'?'selected':'';?>>Sky Blue</option></select></div>
                            </div>
                            <div><label class="text-[9px] font-bold">Main Title</label><input type="text" name="title_text" value="<?php echo htmlspecialchars($slide['title_text']); ?>" class="w-full bg-slate-50 rounded-lg px-2 py-1 text-xs border"></div>
                            <button type="submit" class="w-full py-2 bg-sky-50 hover:bg-sky-600 text-sky-600 hover:text-white rounded-xl text-xs font-bold transition">Update Slide</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ==================== TAB 7: FOOTER ==================== -->
            <div x-show="currentTab === 'footer'" class="space-y-6 max-w-6xl" x-cloak>
                <div class="bg-white p-6 rounded-3xl shadow-sm"><h2 class="text-xl font-black">Manage Footer Details</h2></div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Contacts -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm flex flex-col h-[400px]">
                        <h3 class="font-black text-sm mb-4">📞 Contact Details</h3>
                        <form method="POST" class="flex gap-2 mb-4 bg-slate-50 p-2 rounded-xl">
                            <input type="hidden" name="action" value="add_contact"><input type="hidden" name="active_tab" value="footer">
                            <input type="text" name="contact_type" list="c_types" required placeholder="Type" class="w-1/3 rounded-lg px-2 text-xs border">
                            <datalist id="c_types"><option value="Phone"><option value="Email"><option value="Location"></datalist>
                            <input type="text" name="contact_value" required placeholder="Value" class="w-2/3 rounded-lg px-2 text-xs border">
                            <button type="submit" class="bg-sky-600 text-white px-3 rounded-lg text-xs font-bold">+</button>
                        </form>
                        <div class="overflow-y-auto custom-scrollbar flex-1 space-y-2">
                            <?php foreach($dyn_contacts as $c): ?>
                            <div class="p-2 border rounded-xl flex justify-between items-center text-xs">
                                <div><span class="font-bold text-[9px] text-slate-400 block"><?php echo htmlspecialchars($c['contact_type']); ?></span><span class="font-bold"><?php echo htmlspecialchars($c['contact_value']); ?></span></div>
                                <form method="POST"><input type="hidden" name="action" value="delete_contact"><input type="hidden" name="active_tab" value="footer"><input type="hidden" name="id" value="<?php echo $c['id']; ?>"><button type="submit" class="text-rose-500">🗑️</button></form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Socials -->
                    <div class="bg-white rounded-3xl p-6 shadow-sm flex flex-col h-[400px]">
                        <h3 class="font-black text-sm mb-4">🌐 Social Links</h3>
                        <form method="POST" class="flex gap-2 mb-4 bg-slate-50 p-2 rounded-xl">
                            <input type="hidden" name="action" value="add_social"><input type="hidden" name="active_tab" value="footer">
                            <input type="text" name="platform" required placeholder="Platform" class="w-1/3 rounded-lg px-2 text-xs border">
                            <input type="url" name="url" required placeholder="URL" class="w-2/3 rounded-lg px-2 text-xs border">
                            <button type="submit" class="bg-sky-600 text-white px-3 rounded-lg text-xs font-bold">+</button>
                        </form>
                        <div class="overflow-y-auto custom-scrollbar flex-1 space-y-2">
                            <?php foreach($dyn_socials as $s): ?>
                            <div class="p-2 border rounded-xl flex justify-between items-center text-xs">
                                <div><span class="font-bold text-[9px] text-slate-400 block"><?php echo htmlspecialchars($s['platform']); ?></span><a href="<?php echo htmlspecialchars($s['url']); ?>" class="font-bold text-sky-600 truncate max-w-[200px] block"><?php echo htmlspecialchars($s['url']); ?></a></div>
                                <form method="POST"><input type="hidden" name="action" value="delete_social"><input type="hidden" name="active_tab" value="footer"><input type="hidden" name="id" value="<?php echo $s['id']; ?>"><button type="submit" class="text-rose-500">🗑️</button></form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Chart.js & Pull-to-refresh init -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('systemOverviewChart');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                const jobs = <?php echo $jobs_count; ?>;
                const seekers = <?php echo $users_count; ?>;
                const employers = <?php echo $employers_count; ?>;
                const displayData = (jobs+seekers+employers) === 0 ? [1] : [jobs, seekers, employers];
                const displayBg = (jobs+seekers+employers) === 0 ? ['#e2e8f0'] : ['#0d9488', '#0ea5e9', '#f59e0b'];

                new Chart(ctx, {
                    type: 'doughnut',
                    data: { labels: ['Jobs', 'Candidates', 'Employers'], datasets: [{ data: displayData, backgroundColor: displayBg, borderWidth: 0, hoverOffset: 8 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
                });
            }
        });
        
        const themeToggleBtn = document.getElementById('theme-toggle');
        const sunIcon = document.getElementById('sun-icon');
        const moonIcon = document.getElementById('moon-icon');
        if(themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                const isDark = document.documentElement.classList.contains('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                sunIcon.classList.toggle('hidden');
                moonIcon.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>