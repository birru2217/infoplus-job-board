<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if Employer is logged in
if (!isset($_SESSION['employer_id']) || ($_SESSION['user_role'] ?? 'employer') !== 'employer') {
    header("Location: login.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];
$success_msg = $_SESSION['emp_success'] ?? "";
$error_msg = $_SESSION['emp_error'] ?? "";
unset($_SESSION['emp_success'], $_SESSION['emp_error']);

// Fetch Employer Data
$employer = [];
if (isset($pdo) && $pdo !== null) {
    $stmt = $pdo->prepare("SELECT * FROM employers WHERE id = ?");
    $stmt->execute([$employer_id]);
    $employer = $stmt->fetch();
    
    if (!$employer) {
        header("Location: logout.php");
        exit();
    }
}

// ==========================================
// 🪄 BACKEND MAGIC: Handle Form Submissions
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

   // 1. POST A NEW JOB VACANCY (100% Synchronized with Superadmin)
    if ($action === 'post_job' && isset($pdo)) {
        $title        = trim($_POST['title'] ?? '');
        $company_name = trim($_POST['company_name'] ?? $employer['company_name']);
        $category     = trim($_POST['category'] ?? '');
        $job_type     = trim($_POST['job_type'] ?? 'Full Time');
        $location     = trim($_POST['location'] ?? '');
        $salary       = trim($_POST['salary'] ?? 'Negotiable');
        $career_level = trim($_POST['career_level'] ?? 'Mid Level');
        $positions    = (int)($_POST['positions'] ?? 1);
        $deadline     = !empty($_POST['deadline']) ? $_POST['deadline'] : date('Y-m-d', strtotime('+30 days'));
        $description  = trim($_POST['description'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $how_to_apply = trim($_POST['how_to_apply'] ?? '');

        // 🌟 Custom Job Logo (yoo jijjiiruu barbaade) ykn Logo Kaampaanii
        $logo_url = !empty($employer['logo_url']) ? $employer['logo_url'] : 'logo.png';
        if (isset($_FILES['job_logo']) && $_FILES['job_logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['job_logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                $logo_name = time() . '_joblogo_' . uniqid() . '.' . $ext;
                if (!is_dir('uploads/logos/')) mkdir('uploads/logos/', 0777, true);
                if (move_uploaded_file($_FILES['job_logo']['tmp_name'], 'uploads/logos/' . $logo_name)) {
                    $logo_url = 'uploads/logos/' . $logo_name;
                }
            }
        }

        // 🌟 PDF / Flyer Document Attachment Upload
        $attachment_file = null;
        if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['attachment_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'])) {
                $attach_name = time() . '_attach_' . uniqid() . '.' . $ext;
                if (!is_dir('uploads/attachments/')) mkdir('uploads/attachments/', 0777, true);
                if (move_uploaded_file($_FILES['attachment_file']['tmp_name'], 'uploads/attachments/' . $attach_name)) {
                    $attachment_file = 'uploads/attachments/' . $attach_name;
                }
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO jobs (employer_id, company_name, title, category, job_type, experience, career_level, positions, deadline, location, salary, description, requirements, how_to_apply, logo, attachment_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$employer_id, $company_name, $title, $category, $job_type, $career_level, $career_level, $positions, $deadline, $location, $salary, $description, $requirements, $how_to_apply, $logo_url, $attachment_file]);
            $_SESSION['emp_success'] = "Job vacancy posted successfully! It is now live on Infoplus.";
        } catch (PDOException $e) {
            $_SESSION['emp_error'] = "Error posting job: " . $e->getMessage();
        }
        header("Location: employer_dashboard.php"); exit;
    }

    // 2. UPDATE COMPANY PROFILE & LOGO UPLOAD
    if ($action === 'update_profile' && isset($pdo)) {
        $company_name = trim($_POST['company_name']);
        $contact_name = trim($_POST['contact_name']);
        $phone = trim($_POST['phone']);

        $logo_url = $employer['logo_url']; // keep old logo
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                $file_name = time() . '_logo_' . uniqid() . '.' . $ext;
                $upload_dir = 'uploads/logos/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $file_name)) {
                    $logo_url = $upload_dir . $file_name;
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE employers SET company_name = ?, contact_name = ?, phone = ?, logo_url = ? WHERE id = ?");
        if ($stmt->execute([$company_name, $contact_name, $phone, $logo_url, $employer_id])) {
            $_SESSION['emp_success'] = "Company profile updated successfully!";
            $_SESSION['company_name'] = $company_name;
        } else {
            $_SESSION['emp_error'] = "Failed to update profile.";
        }
        header("Location: employer_dashboard.php"); exit;
    }

    // 3. ACCEPT / REJECT APPLICANT STATUS
    if ($action === 'update_applicant_status' && isset($pdo)) {
        $app_id = (int)$_POST['application_id'];
        $new_status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ? AND job_id IN (SELECT id FROM jobs WHERE employer_id = ?)");
        if($stmt->execute([$new_status, $app_id, $employer_id])) {
            $_SESSION['emp_success'] = "Applicant status updated to " . strtoupper($new_status) . "!";
        }
        header("Location: employer_dashboard.php"); exit;
    }
// 4. CHAT WITH ADMIN & CANDIDATES (Superadmin Style)
    if ($action === 'send_chat' && isset($pdo)) {
        $msg = trim($_POST['chat_message'] ?? '');
        $receiver_email = trim($_POST['receiver_email'] ?? 'tadesebiruk515@gmail.com'); // Default to Admin
        $file_url = null;
        $file_type = null;

        if (isset($_FILES['chat_attachment']) && $_FILES['chat_attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/chats/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['chat_attachment']['name'], PATHINFO_EXTENSION));
            if (empty($ext) || $ext === 'blob') $ext = 'webm'; 
            $file_name = time() . '_' . mt_rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['chat_attachment']['tmp_name'], $upload_dir . $file_name)) {
                $file_url = $upload_dir . $file_name;
                if (in_array($ext, ['jpg','png','jpeg','gif','webp'])) $file_type = 'image';
                elseif (in_array($ext, ['mp4','webm','mov','avi'])) $file_type = 'video';
                elseif (in_array($ext, ['mp3','wav','ogg','m4a','webm'])) $file_type = 'audio';
                elseif ($ext === 'pdf') $file_type = 'pdf';
                else $file_type = 'document';
            }
        }

        if (!empty($msg) || $file_url) {
            try { $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS receiver_email VARCHAR(100) DEFAULT 'general'"); } catch(Exception $e){}
            $stmt = $pdo->prepare("INSERT INTO messages (sender_type, sender_email, receiver_email, message, file_url, file_type) VALUES ('employer', ?, ?, ?, ?, ?)");
            $stmt->execute([$employer['email'], $receiver_email, $msg, $file_url, $file_type]);
            $_SESSION['emp_success'] = "Message sent successfully!";
        }
        header("Location: employer_dashboard.php"); exit;
    }

    // 5. CHANGE PASSWORD
    if ($action === 'update_password' && isset($pdo)) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        if ($new_pass === $confirm_pass && !empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE employers SET password = ? WHERE id = ?")->execute([$hashed, $employer_id]);
            $_SESSION['emp_success'] = "Security settings & Password changed successfully!";
        } else {
            $_SESSION['emp_error'] = "Passwords do not match!";
        }
        header("Location: employer_dashboard.php"); exit;
    }
}

// Fetch Posted Jobs & Applicants
$my_jobs = (isset($pdo) && $pdo !== null) ? $pdo->query("SELECT * FROM jobs WHERE employer_id = $employer_id ORDER BY id DESC")->fetchAll() : [];
$applicants = [];
if (isset($pdo) && $pdo !== null) {
    $stmt = $pdo->prepare("SELECT a.*, u.first_name, u.last_name, u.email as user_email, u.phone as user_phone, u.cv_path, u.work_position, u.profile_pic, j.title as job_title FROM applications a JOIN users u ON a.user_id = u.id JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? ORDER BY a.applied_at DESC");
    $stmt->execute([$employer_id]);
    $applicants = $stmt->fetchAll();
}

// 🌟 MAGIC: BUILD CONTACTS LIST FOR EMPLOYER (SUPERADMIN STYLE) 🌟
$chat_contacts = [];

if (isset($pdo) && $pdo !== null) {
    // 1. Fetch All Admins
    $admins = $pdo->query("SELECT 'admin' as type, email, username as name, profile_pic as avatar FROM admins")->fetchAll();
    
    // 2. Fetch Other Employers (Ofii isaa irraa kan hafe)
    $emp_stmt = $pdo->prepare("SELECT 'employer' as type, email, company_name as name, logo_url as avatar FROM employers WHERE id != ?");
    $emp_stmt->execute([$employer_id]);
    $other_employers = $emp_stmt->fetchAll();
} else {
    $admins = []; $other_employers = [];
}

// 3. Candidates who applied to their jobs
$seen_emails = [];
$candidate_contacts = [];
foreach($applicants as $app) {
    if(!in_array($app['user_email'], $seen_emails)) {
        $candidate_contacts[] = [
            'type' => 'candidate',
            'email' => $app['user_email'],
            'name' => trim($app['first_name'] . ' ' . $app['last_name']),
            'avatar' => !empty($app['profile_pic']) ? $app['profile_pic'] : 'logo.png'
        ];
        $seen_emails[] = $app['user_email'];
    }
}

// Walitti makuu (Merge all 3 groups)
$chat_contacts = array_merge($admins, $other_employers, $candidate_contacts);

// Fetch All Relevant Messages
$chats = [];
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE sender_email=? OR receiver_email=? ORDER BY created_at ASC");
        $stmt->execute([$employer['email'], $employer['email']]);
        $chats = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// Fetch Posted Jobs for this Employer
$my_jobs = (isset($pdo) && $pdo !== null) ? $pdo->query("SELECT * FROM jobs WHERE employer_id = $employer_id ORDER BY id DESC")->fetchAll() : [];

// Fetch Candidates Who Applied
$applicants = [];
if (isset($pdo) && $pdo !== null) {
    $stmt = $pdo->prepare("SELECT a.*, u.first_name, u.last_name, u.email as user_email, u.phone as user_phone, u.cv_path, u.work_position, u.profile_pic, j.title as job_title FROM applications a JOIN users u ON a.user_id = u.id JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = ? ORDER BY a.applied_at DESC");
    $stmt->execute([$employer_id]);
    $applicants = $stmt->fetchAll();
}

// 🌟 MAGIC: CALCULATE PENDING APPLICANTS GLOBALLY (Fixes lines 554, 621, 669, 674) 🌟
$new_apps = count(array_filter($applicants, fn($a) => ($a['status'] ?? '') === 'pending'));

// Fetch Chats with Admin
$chats = [];
if (isset($pdo) && $pdo !== null) {
    // Check if messages table exists
    try {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_email=? AND receiver_email='tadesebiruk515@gmail.com') OR (sender_email='tadesebiruk515@gmail.com' AND receiver_email=?) ORDER BY created_at ASC");
        $stmt->execute([$employer['email'], $employer['email']]);
        $chats = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// 🌟 MAGIC: FETCH TOTAL VIEWS FOR ALL JOBS POSTED BY THIS EMPLOYER 🌟
$total_views = 0;
if (isset($pdo) && $pdo !== null) {
    try {
        // Hojiiwwan inni post godhe hundumaa irraa baay'ina 'views' walitti ida'a (SUM)
        $stmt = $pdo->prepare("SELECT SUM(views) FROM jobs WHERE employer_id = ?");
        $stmt->execute([$employer_id]);
        $total_views = (int)$stmt->fetchColumn(); 
    } catch (Exception $e) {}
}

// Fetch Dynamic Payments
$dyn_payments = [];
$latest_payment_notice = null;
if (isset($pdo) && $pdo !== null) {
    try { 
        $dyn_payments = $pdo->query("SELECT * FROM dynamic_payments ORDER BY id DESC")->fetchAll(); 
        
        // 🌟 FETCH LATEST APPROVED PAYMENT NOTIFICATION 🌟
        $p_stmt = $pdo->prepare("SELECT * FROM employer_payments WHERE employer_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 1");
        $p_stmt->execute([$employer_id]);
        $latest_payment_notice = $p_stmt->fetch();
    } catch (Exception $e) {}
}

// Calculate Profile Completion %
$completion = 20;
if(!empty($employer['company_name'])) $completion += 20;
if(!empty($employer['logo_url'])) $completion += 30;
if(!empty($employer['contact_name'])) $completion += 30;
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Employer Portal — Infoplus</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 h-screen overflow-hidden flex transition-colors duration-300" x-data="{ currentTab: 'dashboard', isSidebarOpen: false }">

    <!-- MOBILE OVERLAY BACKDROP -->
    <div x-show="isSidebarOpen" x-transition.opacity @click="isSidebarOpen = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 lg:hidden" x-cloak></div>

    <!-- 🌟 PALMJOBS STYLE SIDEBAR NAVIGATION 🌟 -->
    <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:relative top-0 left-0 h-full w-[260px] bg-white border-r border-slate-200 flex flex-col flex-shrink-0 z-50 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
        
      <!-- Header / Logo (Clickable Instant Upload) -->
        <div class="pt-6 pb-4 px-6 flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                
                <!-- 🌟 CLICKABLE LOGO FORM 🌟 -->
                <form method="POST" enctype="multipart/form-data" id="sidebarLogoForm" class="flex-shrink-0">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="company_name" value="<?php echo htmlspecialchars($employer['company_name'] ?? ''); ?>">
                    <input type="hidden" name="contact_name" value="<?php echo htmlspecialchars($employer['contact_name'] ?? ''); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($employer['phone'] ?? ''); ?>">
                    
                    <label class="relative block h-9 w-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm shadow-sm overflow-hidden cursor-pointer group border border-slate-200 hover:border-emerald-500 transition-all" title="Click to upload/change logo">
                        <?php if(!empty($employer['logo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($employer['logo_url']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span><?php echo strtoupper(substr($employer['company_name'] ?? 'E', 0, 1)); ?></span>
                        <?php endif; ?>
                        
                        <!-- Camera Icon Hover Overlay -->
                        <div class="absolute inset-0 bg-black/60 hidden group-hover:flex items-center justify-center transition-all">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <input type="file" name="logo_file" accept="image/*" class="hidden" onchange="document.getElementById('sidebarLogoForm').submit();">
                    </label>
                </form>

                <h1 class="font-extrabold text-[17px] text-slate-900 tracking-tight truncate max-w-[130px]" title="<?php echo htmlspecialchars($employer['company_name']); ?>">
                    <?php echo htmlspecialchars($employer['company_name']); ?>
                </h1>
            </div>
            <button @click="isSidebarOpen = false" class="lg:hidden text-slate-400 bg-slate-100 p-1.5 rounded-full hover:bg-slate-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- 🌟 MENU LINKS LIST 🌟 -->
        <nav class="flex-1 overflow-y-auto px-4 space-y-1 no-scrollbar pt-2">
            
            <button @click="currentTab = 'dashboard'; isSidebarOpen = false" :class="currentTab === 'dashboard' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </button>

          <a href="post-vacancy.php" class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-r-xl text-[13px] text-slate-600 hover:bg-slate-50 hover:text-emerald-600 font-medium border-l-4 border-transparent transition-colors">
                <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 9a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V15a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V9z" clip-rule="evenodd" /></svg>
                <span>Post Job</span>
            </a>
            <button @click="currentTab = 'my_jobs'; isSidebarOpen = false" :class="currentTab === 'my_jobs' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <div class="flex items-center gap-3.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span>Jobs</span>
                </div>
            </button>

            <button @click="currentTab = 'candidates'; isSidebarOpen = false" :class="currentTab === 'candidates' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <div class="flex items-center gap-3.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Candidates</span>
                </div>
                <?php if(count($applicants) > 0): ?>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold"><?php echo count($applicants); ?></span>
                <?php endif; ?>
            </button>

            <button @click="currentTab = 'search_talent'; isSidebarOpen = false" :class="currentTab === 'search_talent' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <span>Search Talent</span>
            </button>

            <button @click="currentTab = 'messages'; isSidebarOpen = false" :class="currentTab === 'messages' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <span>Messages</span>
            </button>

            <button @click="currentTab = 'billing'; isSidebarOpen = false" :class="currentTab === 'billing' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                <span>Billing & Plans</span>
            </button>

            <!-- SECTION LABEL -->
            <div class="pt-5 pb-1 px-3 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Account</div>

            <button @click="currentTab = 'profile'; isSidebarOpen = false" :class="currentTab === 'profile' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>Profile</span>
            </button>

            <button @click="currentTab = 'settings'; isSidebarOpen = false" :class="currentTab === 'settings' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Settings</span>
            </button>
            
            <button @click="currentTab = 'support'; isSidebarOpen = false" :class="currentTab === 'support' ? 'text-emerald-700 font-bold bg-[#ecfdf5] border-l-4 border-emerald-600' : 'text-slate-600 hover:bg-slate-50 font-medium border-l-4 border-transparent'" class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-r-xl text-[13px] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Support</span>
            </button>
        </nav>

        <!-- Profile Completion Widget (Bottom Left) -->
        <div class="px-5 pb-6 bg-white border-t border-slate-100 pt-4">
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <p class="text-[10px] font-bold text-emerald-600 mb-1 uppercase">Profile completion</p>
                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden mb-1.5">
                    <div class="bg-emerald-500 h-full rounded-full" style="width: <?php echo $completion; ?>%"></div>
                </div>
                <div class="flex justify-between text-[9px] font-bold text-slate-500">
                    <span><?php echo $completion; ?>% Complete</span>
                    <span>100% Left</span>
                </div>
            </div>

            <!-- Mini User Bar -->
            <div class="mt-4 flex items-center justify-between cursor-pointer group" @click="currentTab = 'profile'">
                <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-[11px] overflow-hidden shadow-inner">
                        <?php if(!empty($employer['logo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($employer['logo_url']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo strtoupper(substr($employer['company_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-xs font-extrabold text-slate-900 leading-tight group-hover:text-emerald-600 transition-colors truncate max-w-[120px]"><?php echo htmlspecialchars($employer['contact_name']); ?></p>
                        <p class="text-[10px] text-slate-500 font-medium">Employer</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" /></svg>
            </div>
        </div>
    </aside>

   <!-- 🌟 MAIN CONTENT AREA 🌟 -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden w-full relative z-0 bg-slate-50/50">
        
       <!-- 🌟 TOPBAR (Visible on PC & Mobile with View Site, Theme, Logout & Avatar) 🌟 -->
        <header class="h-20 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sm:px-8 z-10 flex-shrink-0 transition-colors duration-300">
            
            <div class="flex items-center space-x-3">
                <button @click="isSidebarOpen = true" class="lg:hidden p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 hover:bg-slate-200 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white capitalize truncate" x-text="currentTab.replace('_', ' ')"></h2>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-4">
                <!-- View Site Link -->
                <a href="index.php" target="_blank" class="hidden sm:flex items-center space-x-1 text-xs sm:text-sm font-bold text-emerald-600 dark:text-emerald-400 hover:text-white hover:bg-emerald-600 bg-emerald-500/10 px-3.5 py-2 rounded-xl transition-all">
                    <span>View Site</span> <span>↗</span>
                </a>

                <!-- Dark/Light Mode Toggle -->
                <button id="theme-toggle" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-amber-500 hover:bg-slate-200 dark:hover:text-amber-400 dark:hover:bg-slate-700 transition-colors focus:outline-none shadow-sm border border-slate-200 dark:border-slate-700" title="Toggle Theme">
                    <svg id="sun-icon" class="h-4 w-4 sm:h-5 sm:w-5 hidden text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                    </svg>
                    <svg id="moon-icon" class="h-4 w-4 sm:h-5 sm:w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <!-- Quick Logout Button -->
                <a href="logout.php" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-rose-500 hover:text-white hover:bg-rose-500 transition-colors focus:outline-none shadow-sm border border-slate-200 dark:border-slate-700" title="Sign Out">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>

                <!-- Employer Company Avatar -->
                <div @click="currentTab = 'profile'" class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-white dark:bg-slate-800 overflow-hidden border-2 border-emerald-500 flex-shrink-0 shadow-md cursor-pointer flex items-center justify-center p-0.5" title="Company Profile">
                    <?php if(!empty($employer['logo_url'])): ?>
                        <img src="<?php echo htmlspecialchars($employer['logo_url']); ?>" class="w-full h-full object-cover rounded-lg">
                    <?php else: ?>
                        <span class="text-emerald-600 dark:text-emerald-400 font-black text-sm"><?php echo strtoupper(substr($employer['company_name'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Scrollable Content Area (Reduced Top Padding for Flush Top) -->
        <div class="flex-1 overflow-y-auto p-4 lg:px-8 lg:py-6 pb-20 custom-scrollbar relative">
            <!-- Alerts -->
            <?php if($success_msg): ?>
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" class="max-w-6xl mx-auto mb-6 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 text-sm font-bold flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="max-w-6xl mx-auto mb-6 p-4 rounded-xl bg-rose-50 text-rose-700 border border-rose-100 text-sm font-bold flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

       <!-- ==================== TAB 1: DASHBOARD ==================== -->
            <div x-show="currentTab === 'dashboard'" x-cloak class="space-y-6 max-w-6xl mx-auto">
                
               <!-- 🌟 APPROVED PAYMENT SUCCESS NOTIFICATION BANNER 🌟 -->
                <?php if ($latest_payment_notice && strtotime($latest_payment_notice['created_at']) > (time() - 86400 * 3)): ?>
                <div class="bg-emerald-50 border-2 border-emerald-300 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between shadow-sm gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">🎉</span>
                        <div>
                            <h4 class="text-emerald-900 font-black text-sm">Payment Verified & Credits Added!</h4>
                            <p class="text-xs text-emerald-700 mt-0.5 font-medium">Your payment slip for <strong><?php echo htmlspecialchars($latest_payment_notice['package_name']); ?></strong> was approved by Superadmin. You can now post vacancies!</p>
                        </div>
                    </div>
                    <a href="post-vacancy.php" class="w-full sm:w-auto text-center px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full text-xs font-black shadow-md transition-all whitespace-nowrap">
                        Post Job Now →
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if($employer['status'] === 'pending'): ?>
                <!-- Pending Profile Banner (Like PalmJobs) -->
                <div class="bg-[#fffbeb] border border-[#fef3c7] rounded-2xl p-5 flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0 text-lg">🏢</div>
                    <div class="flex-1">
                        <h4 class="text-amber-800 font-bold text-sm">Complete your company profile to start posting jobs</h4>
                        <p class="text-amber-700 text-xs mt-1">Add your company name, industry, and description so candidates can learn about you. (Currently Pending HR Review)</p>
                        <button @click="currentTab = 'profile'" class="mt-3 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-full text-xs font-bold transition-colors">Set up profile</button>
                    </div>
                </div>
                <?php endif; ?>

              <!-- Green Dashboard Header Box -->
                <div class="bg-[#ecfdf5] border border-emerald-100 rounded-2xl p-8 flex justify-between items-center relative overflow-hidden">
                    <div class="relative z-10">
                        <h1 class="text-2xl font-black text-slate-900">Dashboard</h1>
                        <p class="text-slate-600 text-sm mt-1 mb-5">Your hiring at a glance.</p>
                        <a href="post-vacancy.php" class="inline-flex px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full text-sm font-bold items-center gap-2 shadow-sm transition-transform hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 9a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V15a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V9z" clip-rule="evenodd" /></svg>
                            Post Job
                        </a>
                    </div>
                    <!-- Fake illustration using emoji -->
                    <div class="hidden sm:block text-8xl opacity-80 absolute -right-4 -bottom-4">👨‍💻</div>
                </div>

               <!-- 4 Metrics Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                    <div @click="currentTab = 'my_jobs'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm hover:shadow-xl hover:border-emerald-500 transition-all cursor-pointer">
                        <div class="flex justify-between items-center text-slate-400 mb-2">
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider">Active Jobs</span>
                            <span class="text-base">💼</span>
                        </div>
                        <p class="text-2xl sm:text-4xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?php echo count($my_jobs); ?></p>
                        <p class="text-[10px] text-slate-400 mt-1">Live vacancies</p>
                    </div>

                    <div @click="currentTab = 'candidates'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm hover:shadow-xl hover:border-teal-500 transition-all cursor-pointer">
                        <div class="flex justify-between items-center text-slate-400 mb-2">
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider">Total Applicants</span>
                            <span class="text-base">👥</span>
                        </div>
                        <p class="text-2xl sm:text-4xl font-black text-teal-600 dark:text-teal-400 mt-1"><?php echo count($applicants); ?></p>
                        <p class="text-[10px] text-slate-400 mt-1">Received CVs</p>
                    </div>

                    <div @click="currentTab = 'candidates'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm hover:shadow-xl hover:border-amber-500 transition-all cursor-pointer">
                        <div class="flex justify-between items-center text-slate-400 mb-2">
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider">Awaiting Action</span>
                            <span class="text-base">⏳</span>
                        </div>
                        <p class="text-2xl sm:text-4xl font-black text-amber-500 mt-1"><?php echo $new_apps; ?></p>
                        <p class="text-[10px] text-slate-400 mt-1">Pending review</p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm">
                        <div class="flex justify-between items-center text-slate-400 mb-2">
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider">Total Views</span>
                            <span class="text-base">👁️</span>
                        </div>
                        <p class="text-2xl sm:text-4xl font-black text-blue-600 dark:text-blue-400 mt-1"><?php echo $total_views; ?></p>
                        <p class="text-[10px] text-slate-400 mt-1">Candidate views</p>
                    </div>
                </div>

                <!-- 🌟 LIVE RECRUITMENT DOUGHNUT CHART & ANALYTICS CARD 🌟 -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-sm">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div>
                            <h3 class="text-slate-900 dark:text-white font-black text-lg sm:text-xl">Hiring Performance Analytics</h3>
                            <p class="text-slate-400 text-xs mt-0.5">Real-time breakdown of your job postings and applicant engagements.</p>
                        </div>
                        <span class="px-3.5 py-1.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-bold border border-emerald-500/20">
                            ⚡ Live Metrics
                        </span>
                    </div>

                   <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <!-- 🌟 Doughnut Chart Canvas (Forced Fixed Dimensions) 🌟 -->
                        <div class="lg:col-span-6 flex justify-center items-center">
                            <div style="width: 250px; height: 250px; position: relative;">
                                <canvas id="employerHiringChart"></canvas>
                            </div>
                        </div>

                        <!-- Metrics Breakdown Cards on Right -->
                        <div class="lg:col-span-6 space-y-3">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Metrics Overview</h4>
                            
                            <div @click="currentTab = 'my_jobs'" class="cursor-pointer p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 flex items-center justify-between hover:border-emerald-500 transition-all">
                                <div class="flex items-center space-x-3">
                                    <span class="h-3 w-3 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500"></span>
                                    <span class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white">Active Jobs</span>
                                </div>
                                <span class="text-base font-black text-emerald-600 dark:text-emerald-400"><?php echo count($my_jobs); ?></span>
                            </div>

                            <div @click="currentTab = 'candidates'" class="cursor-pointer p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 flex items-center justify-between hover:border-teal-500 transition-all">
                                <div class="flex items-center space-x-3">
                                    <span class="h-3 w-3 rounded-full bg-teal-500 shadow-sm shadow-teal-500"></span>
                                    <span class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white">Total Applicants</span>
                                </div>
                                <span class="text-base font-black text-teal-600 dark:text-teal-400"><?php echo count($applicants); ?></span>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="h-3 w-3 rounded-full bg-blue-500 shadow-sm shadow-blue-500"></span>
                                    <span class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white">Job Views</span>
                                </div>
                                <span class="text-base font-black text-blue-600 dark:text-blue-400"><?php echo $total_views; ?></span>
                            </div>

                            <div @click="currentTab = 'candidates'" class="cursor-pointer p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 flex items-center justify-between hover:border-amber-500 transition-all">
                                <div class="flex items-center space-x-3">
                                    <span class="h-3 w-3 rounded-full bg-amber-500 shadow-sm shadow-amber-500"></span>
                                    <span class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white">Pending Action</span>
                                </div>
                                <span class="text-base font-black text-amber-500"><?php echo $new_apps; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom 2 Boxes (Active Jobs & Awaiting Action) -->

                <!-- Bottom 2 Boxes (Active Jobs & Awaiting Action) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm min-h-[300px] flex flex-col">
                        <div class="p-4 border-b border-slate-100 font-bold text-sm text-slate-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            Active jobs
                        </div>
                        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                            <?php if(empty($my_jobs)): ?>
                                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mb-3">🍃</div>
                                <h4 class="font-bold text-slate-900 mb-1">No active jobs yet.</h4>
                                <p class="text-xs text-slate-500 mb-4">Post a job to start receiving applications.</p>
                                <a href="post-vacancy.php" class="inline-flex px-5 py-2 bg-emerald-600 text-white rounded-full text-sm font-bold hover:bg-emerald-700 items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 9a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V15a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V9z" clip-rule="evenodd" /></svg>
                                    Post your first job
                                </a>
                            <?php else: ?>
                                <div class="w-full space-y-3">
                                    <?php foreach(array_slice($my_jobs, 0, 3) as $j): ?>
                                    <div class="flex justify-between items-center text-left bg-slate-50 p-3 rounded-xl border border-slate-100">
                                        <div>
                                            <p class="font-bold text-sm text-slate-900"><?php echo htmlspecialchars($j['title']); ?></p>
                                            <p class="text-[10px] text-slate-500 mt-0.5"><?php echo htmlspecialchars($j['location']); ?></p>
                                        </div>
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-[9px] font-bold uppercase rounded-md"><?php echo htmlspecialchars($j['status']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button @click="currentTab = 'my_jobs'" class="mt-4 text-emerald-600 text-xs font-bold hover:underline">View All Active Jobs</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm min-h-[300px] flex flex-col">
                        <div class="p-4 border-b border-slate-100 font-bold text-sm text-slate-700 flex items-center gap-2">
                            <span class="text-amber-500">🔔</span> Awaiting Action
                            <span class="text-[10px] text-slate-400 font-normal ml-2 hidden sm:inline">Applied candidates surface here.</span>
                        </div>
                        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                            <?php if($new_apps === 0): ?>
                                <div class="w-12 h-12 rounded-full bg-slate-50 text-emerald-600 flex items-center justify-center text-xl mb-3">🏅</div>
                                <h4 class="font-bold text-slate-900 text-sm leading-relaxed">Applicants stuck in a stage for 7+ days will appear here.<br>None right now — great.</h4>
                            <?php else: ?>
                                <span class="text-4xl text-amber-500 mb-2">⚠️</span>
                                <h4 class="font-bold text-slate-900 mb-1">You have <?php echo $new_apps; ?> pending candidates.</h4>
                                <p class="text-xs text-slate-500 mb-4">Review them before they lose interest.</p>
                                <button @click="currentTab = 'candidates'" class="px-5 py-2 bg-emerald-600 text-white rounded-full text-sm font-bold hover:bg-emerald-700">Review Candidates</button>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

  
            <!-- ==================== TAB 3: JOBS (MY POSTED JOBS) ==================== -->
            <div x-show="currentTab === 'my_jobs'" class="max-w-6xl mx-auto bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden min-h-[500px]" x-cloak>
                <!-- Header -->
                <div class="bg-[#f0fdf4] p-8 flex justify-between items-center relative overflow-hidden">
                    <div class="relative z-10">
                        <h2 class="text-2xl font-black text-slate-900">Your Jobs</h2>
                        <p class="text-sm text-slate-600 mt-1 mb-4">Manage your active listings, drafts, and closed postings.</p>
                <a href="post-vacancy.php" class="inline-flex px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-bold text-sm items-center gap-2 shadow-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 9a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V15a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V9z" clip-rule="evenodd" /></svg>
                            Post Job
                        </a>
                    </div>
                    <!-- Fake Palmjobs lady illustration -->
                    <div class="hidden sm:block absolute right-4 bottom-0 text-7xl opacity-90">👩‍💼</div>
                </div>

                <!-- Tabs (Active / Closed) -->
                <div class="border-b border-slate-200 px-6 flex gap-6">
                    <button class="py-4 border-b-2 border-emerald-600 text-slate-900 font-bold text-sm flex items-center gap-2">
                        <span class="text-emerald-600">🌱</span> Active
                    </button>
                    <button class="py-4 border-b-2 border-transparent text-slate-500 hover:text-slate-700 font-medium text-sm flex items-center gap-2">
                        Closed
                    </button>
                </div>

                <div class="p-6">
                    <?php if (empty($my_jobs)): ?>
                        <div class="text-center py-16 flex flex-col items-center">
                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-4">🍃</div>
                            <h3 class="text-lg font-bold text-slate-900">No active jobs yet — post one and start hiring.</h3>
                            <p class="text-sm text-slate-500 mt-1 mb-6">Your live listings will show up here, with applicant counts at a glance.</p>
                            <button @click="currentTab = 'post_job'" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-bold text-sm shadow-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 9a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V15a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V9z" clip-rule="evenodd" /></svg>
                                Post your first job
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-xs uppercase text-slate-500 border-b border-slate-100">
                                        <th class="pb-3 px-4 font-bold">Job Role</th>
                                        <th class="pb-3 px-4 font-bold">Location</th>
                                        <th class="pb-3 px-4 font-bold text-center">Applicants</th>
                                        <th class="pb-3 px-4 font-bold text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-sm">
                                    <?php foreach ($my_jobs as $j): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-4">
                                            <a href="job-details.php?id=<?php echo $j['id']; ?>" target="_blank" class="font-bold text-emerald-700 hover:underline"><?php echo htmlspecialchars($j['title']); ?></a>
                                            <p class="text-[11px] text-slate-500 mt-0.5">Deadline: <?php echo htmlspecialchars($j['deadline'] ?? 'Open'); ?></p>
                                        </td>
                                        <td class="py-4 px-4 text-slate-600"><?php echo htmlspecialchars($j['location']); ?></td>
                                        <td class="py-4 px-4 text-center">
                                            <!-- Simple mock count for display -->
                                            <span class="font-bold bg-slate-100 px-3 py-1 rounded-full text-xs">0</span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-[#ecfdf5] text-emerald-700 border border-emerald-200">
                                                <?php echo htmlspecialchars($j['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ==================== TAB 4: CANDIDATES (APPLICANTS) ==================== -->
            <div x-show="currentTab === 'candidates'" class="max-w-6xl mx-auto bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden min-h-[500px]" x-cloak>
                 <!-- Header -->
                 <div class="bg-[#f0fdf4] p-8 flex justify-between items-center relative overflow-hidden">
                    <div class="relative z-10 w-full">
                        <h2 class="text-2xl font-black text-slate-900">Candidates</h2>
                        <p class="text-sm text-slate-600 mt-1 mb-5">Review applications, move candidates through stages, and hire the right talent.</p>
                        
                        <div class="flex items-center gap-4 flex-wrap">
                            <span class="px-3 py-1 bg-rose-50 text-rose-600 text-xs font-bold rounded-full border border-rose-200">👁 0 reveals remaining</span>
                            <a href="#" @click.prevent="currentTab = 'billing'" class="text-emerald-600 text-sm font-bold hover:underline">Buy more</a>
                            
                            <div class="relative ml-auto w-full sm:w-auto">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">🔍</span>
                                <input type="text" placeholder="Search candidates" class="w-full sm:w-64 pl-9 pr-4 py-2 rounded-full border border-slate-300 text-sm outline-none focus:border-emerald-500">
                            </div>
                            <button @click="currentTab = 'post_job'" class="hidden sm:flex px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-bold text-sm items-center gap-2 shadow-sm">
                                🌱 Post Job
                            </button>
                        </div>
                    </div>
                    <!-- Fake Palmjobs lady illustration -->
                    <div class="hidden sm:block absolute right-4 top-4 text-7xl opacity-90">👩‍💼</div>
                </div>

                <div class="p-6">
                    <?php if (empty($applicants)): ?>
                        <div class="text-center py-16 flex flex-col items-center">
                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-4">🍃</div>
                            <h3 class="text-lg font-bold text-slate-900">No candidates yet — post a job to start receiving applications.</h3>
                            <p class="text-sm text-slate-500 mt-1 mb-6">Once your listing is live, applicants will land in a kanban board you can triage here.</p>
                            <button @click="currentTab = 'post_job'" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-bold text-sm shadow-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 9a.75.75 0 00-1.5 0v2.25H9a.75.75 0 000 1.5h2.25V15a.75.75 0 001.5 0v-2.25H15a.75.75 0 000-1.5h-2.25V9z" clip-rule="evenodd" /></svg>
                                Post a job
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Minimalist Applicant List -->
                        <div class="space-y-4">
                            <?php foreach ($applicants as $app): ?>
                            <div class="border border-slate-200 p-5 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:border-emerald-400 transition-colors bg-white">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-emerald-600 flex items-center justify-center font-black text-lg border border-slate-200">
                                        <?php echo strtoupper(substr($app['first_name'],0,1)); ?>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-900 text-base"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h3>
                                        <p class="text-xs text-slate-500 mt-0.5">Applied for <strong class="text-emerald-700"><?php echo htmlspecialchars($app['job_title']); ?></strong></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 w-full sm:w-auto">
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <form action="employer_dashboard.php" method="POST" class="flex-1 sm:flex-none">
                                            <input type="hidden" name="action" value="update_applicant_status">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="status" value="accepted">
                                            <button type="submit" class="w-full px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white rounded-full text-xs font-bold transition-colors border border-emerald-200">Accept</button>
                                        </form>
                                        <form action="employer_dashboard.php" method="POST" class="flex-1 sm:flex-none">
                                            <input type="hidden" name="action" value="update_applicant_status">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="w-full px-4 py-2 bg-white text-slate-600 hover:bg-rose-50 hover:text-rose-600 rounded-full text-xs font-bold transition-colors border border-slate-300">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase border bg-slate-50 text-slate-600 border-slate-200">
                                            <?php echo $app['status']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ==================== TAB 5: BILLING & CREDITS ==================== -->
            <div x-show="currentTab === 'billing'" class="max-w-6xl mx-auto space-y-6" x-cloak>
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900">Billing & Credits</h2>
                        <a href="#" class="text-emerald-600 text-sm font-bold hover:underline">View pricing</a>
                    </div>
                    <button class="px-5 py-2.5 bg-emerald-600 text-white font-bold rounded-full shadow-sm text-sm hover:bg-emerald-700 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                        Upload payment slip
                    </button>
                </div>

                <!-- Credits Overview Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                        <div class="flex justify-between items-center text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                            <span>Job Posts</span> <span>💼</span>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 mb-2">3 <span class="text-base text-slate-400 font-bold">/ 3</span></h3>
                        <div class="w-full bg-emerald-600 h-1.5 rounded-full mb-3"></div>
                        <p class="text-xs text-slate-500 mb-4">3 posts left</p>
                        <button class="w-full py-2 border border-slate-300 rounded-full text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Top up ></button>
                    </div>
                    <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                        <div class="flex justify-between items-center text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                            <span>Reveals</span> <span>🔍</span>
                        </div>
                        <h3 class="text-3xl font-black text-amber-500 mb-2">0 <span class="text-base text-slate-400 font-bold">/ 0</span></h3>
                        <div class="w-full bg-slate-200 h-1.5 rounded-full mb-3"></div>
                        <p class="text-xs text-slate-500 mb-4">No reveals</p>
                        <button class="w-full py-2 border border-slate-300 rounded-full text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Buy unlocks ></button>
                    </div>
                    <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                        <div class="flex justify-between items-center text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                            <span>Urgent</span> <span>⚡</span>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 mb-2">3 <span class="text-base text-slate-400 font-bold">/ 3</span></h3>
                        <div class="w-full bg-emerald-600 h-1.5 rounded-full mb-3"></div>
                        <p class="text-xs text-slate-500 mb-4">Same-week hire</p>
                        <button class="w-full py-2 border border-slate-300 rounded-full text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Buy urgent ></button>
                    </div>
                    <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                        <div class="flex justify-between items-center text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                            <span>Featured</span> <span>⭐</span>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 mb-2">3 <span class="text-base text-slate-400 font-bold">/ 3</span></h3>
                        <div class="w-full bg-emerald-600 h-1.5 rounded-full mb-3"></div>
                        <p class="text-xs text-slate-500 mb-4">~4x more views</p>
                        <button class="w-full py-2 border border-slate-300 rounded-full text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Feature a job ></button>
                    </div>
                </div>

                <!-- Pricing Packages -->
                <div class="pt-4">
                    <div class="flex items-center gap-6 border-b border-slate-200 mb-6 text-sm font-bold text-slate-500">
                        <button class="py-3 border-b-2 border-slate-900 text-slate-900 flex items-center gap-2"><span>💼</span> Job Posts</button>
                        <button class="py-3 border-b-2 border-transparent hover:text-slate-700 flex items-center gap-2"><span>⭐</span> Annual Plans</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Basic Plan -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Infoplus Basic</h3>
                                <p class="text-[11px] text-slate-500 mt-1">1 job post • valid 1mo</p>
                                <div class="mt-4 mb-2 flex items-baseline gap-1">
                                    <span class="text-sm font-bold text-slate-500">ETB</span>
                                    <span class="text-3xl font-black text-slate-900">0</span>
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">✔ Free</span>
                                <button class="text-sm font-bold text-slate-700 hover:text-slate-900">Current Plan</button>
                            </div>
                        </div>

                        <!-- Standard Plan -->
                        <div class="bg-white border-2 border-emerald-500 rounded-2xl p-6 flex flex-col justify-between shadow-md relative">
                            <span class="absolute top-4 right-4 bg-emerald-500 text-white text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">Popular</span>
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Infoplus Standard</h3>
                                <p class="text-[11px] text-slate-500 mt-1">5 job posts • valid 3mo</p>
                                <div class="mt-4 mb-2 flex items-baseline gap-1">
                                    <span class="text-sm font-bold text-slate-500">ETB</span>
                                    <span class="text-3xl font-black text-emerald-600">500</span>
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">✔ One-time</span>
                                <button class="px-5 py-2 bg-emerald-600 text-white rounded-full text-xs font-bold hover:bg-emerald-700 transition">Buy pack ></button>
                            </div>
                        </div>

                        <!-- Pro Plan -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Infoplus Pro</h3>
                                <p class="text-[11px] text-slate-500 mt-1">15 job posts • valid 6mo</p>
                                <div class="mt-4 mb-2 flex items-baseline gap-1">
                                    <span class="text-sm font-bold text-slate-500">ETB</span>
                                    <span class="text-3xl font-black text-emerald-600">1,200</span>
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">✔ One-time</span>
                                <button class="px-5 py-2 border border-slate-300 text-slate-700 rounded-full text-xs font-bold hover:bg-slate-50 transition">Buy pack ></button>
                            </div>
                        </div>

                        <!-- Max Plan -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Infoplus Max</h3>
                                <p class="text-[11px] text-slate-500 mt-1">40 job posts • valid 12mo</p>
                                <div class="mt-4 mb-2 flex items-baseline gap-1">
                                    <span class="text-sm font-bold text-slate-500">ETB</span>
                                    <span class="text-3xl font-black text-emerald-600">2,500</span>
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">✔ One-time</span>
                                <button class="px-5 py-2 border border-slate-300 text-slate-700 rounded-full text-xs font-bold hover:bg-slate-50 transition">Buy pack ></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods Accordion -->
                <div class="bg-white border border-slate-200 rounded-2xl mt-6 p-5 shadow-sm">
                    <div class="flex justify-between items-center text-sm font-bold text-slate-700">
                        <div class="flex items-center gap-3"><span>💳</span> Payment methods <span class="text-slate-400 font-normal">CBE, Telebirr</span></div>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>

                <!-- Payment History Empty State -->
                <div class="bg-white border border-slate-200 rounded-3xl mt-6 p-8 shadow-sm text-center min-h-[300px] flex flex-col items-center justify-center relative">
                    <div class="absolute top-4 left-6 text-sm font-bold text-slate-700 flex items-center gap-2"><span>🕒</span> Payment History</div>
                    <div class="absolute top-4 right-6 bg-slate-100 text-slate-500 text-[10px] font-bold px-3 py-1 rounded-full">Last 90 days</div>

                    <div class="w-12 h-12 flex items-center justify-center text-3xl mb-4 opacity-50">💸</div>
                    <h3 class="text-base font-black text-slate-900">No payments yet</h3>
                    <p class="text-xs text-slate-500 mt-1 mb-6 max-w-sm">Transfer to one of the accounts above, then upload your slip and we'll apply the credits within 24h.</p>
                    <button class="px-6 py-2.5 bg-emerald-600 text-white font-bold text-sm rounded-full shadow-sm hover:bg-emerald-700 transition">Upload payment slip</button>
                </div>

            </div>

        <!-- ==================== TAB 6: PROFILE (COMPANY SETTINGS) ==================== -->
            <div x-show="currentTab === 'profile'" class="max-w-5xl mx-auto space-y-6 pb-12" x-cloak x-data="{ isEditing: false }">
                
                <?php
                // Check missing profile fields
                $missing_fields = [];
                if(empty($employer['company_name'])) $missing_fields[] = 'Company Name';
                if(empty($employer['phone'])) $missing_fields[] = 'Phone Number';
                if(empty($employer['logo_url'])) $missing_fields[] = 'Company Logo';
                ?>

                <!-- 🌟 TOP GREEN BANNER 🌟 -->
                <div class="bg-[#f0fdf4] p-6 lg:p-8 rounded-3xl flex justify-between items-center border border-emerald-100 relative overflow-hidden shadow-sm">
                    <div class="flex items-center gap-5 relative z-10">
                        
                        <!-- Logo with Instant Upload -->
                        <div class="relative w-[70px] h-[70px] rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-3xl shadow-md border-2 border-white flex-shrink-0 group overflow-hidden">
                            <?php if(!empty($employer['logo_url'])): ?>
                                <img src="<?php echo htmlspecialchars($employer['logo_url']); ?>" class="w-full h-full object-cover bg-white">
                            <?php else: ?>
                                <?php echo strtoupper(substr($employer['company_name'], 0, 1)); ?>
                            <?php endif; ?>
                            
                            <!-- Hover Camera Icon -->
                            <form method="POST" enctype="multipart/form-data" id="quickAvatarForm" class="absolute inset-0 bg-black/50 hidden group-hover:flex items-center justify-center transition-all cursor-pointer">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="company_name" value="<?php echo htmlspecialchars($employer['company_name']); ?>">
                                <input type="hidden" name="contact_name" value="<?php echo htmlspecialchars($employer['contact_name']); ?>">
                                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($employer['phone']); ?>">
                                <label class="w-full h-full flex items-center justify-center cursor-pointer">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                    <input type="file" name="logo_file" accept="image/*" class="hidden" onchange="document.getElementById('quickAvatarForm').submit();">
                                </label>
                            </form>
                        </div>

                        <div>
                            <h2 class="text-xl font-black text-slate-900">Set your company name</h2>
                            <p class="text-[13px] text-slate-600 mt-0.5">Manage your company profile — this is what candidates see on every job you post.</p>
                            <div class="inline-flex items-center gap-1 mt-1.5 px-2 py-0.5 bg-white rounded-md border border-emerald-200 text-[10px] font-bold text-emerald-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                <?php echo htmlspecialchars($employer['company_name'] ?? 'Your Company'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block text-6xl opacity-90 relative z-10">🪴</div>
                </div>

                <!-- 🌟 WARNING ALERT 🌟 -->
                <?php if(!empty($missing_fields)): ?>
                <div class="bg-[#fffbeb] border border-[#fef3c7] p-4 rounded-xl flex items-center justify-between shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="text-amber-500 mt-0.5">⚠️</span>
                        <div>
                            <p class="text-sm font-bold text-amber-900">Complete your profile to stand out</p>
                            <p class="text-xs text-amber-700 mt-0.5">Missing: <?php echo implode(', ', $missing_fields); ?>.</p>
                        </div>
                    </div>
                    <button @click="isEditing = true" class="px-4 py-1.5 bg-amber-500 text-white text-xs font-bold rounded-full hover:bg-amber-600 transition-colors hidden sm:block">Edit Profile</button>
                </div>
                <?php endif; ?>

                <!-- 🌟 MAIN PROFILE FORM 🌟 -->
                <form action="employer_dashboard.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">

                    <!-- CARD 1: BASICS -->
                    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <div class="flex items-center gap-2.5">
                                <span class="text-emerald-600">🏢</span>
                                <h3 class="font-bold text-slate-800 text-sm">Basics</h3>
                                <span class="bg-amber-100 text-amber-700 text-[9px] px-2 py-0.5 rounded uppercase font-bold tracking-wider">Required</span>
                            </div>
                            <button type="button" @click="isEditing = !isEditing" class="text-[11px] font-bold px-3 py-1 rounded-md border transition-colors flex items-center gap-1.5" :class="isEditing ? 'bg-slate-800 text-white border-slate-800' : 'text-slate-600 border-slate-200 hover:bg-slate-50'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                <span x-text="isEditing ? 'Cancel' : 'Edit'"></span>
                            </button>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Company Name -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Company Name</label>
                                <div x-show="!isEditing" class="text-sm font-semibold text-slate-900 pb-2 border-b border-slate-100">
                                    <?php echo !empty($employer['company_name']) ? htmlspecialchars($employer['company_name']) : '<span class="text-slate-300 italic">Add a company name</span>'; ?>
                                </div>
                                <input x-show="isEditing" x-cloak type="text" name="company_name" value="<?php echo htmlspecialchars($employer['company_name']); ?>" required class="w-full bg-[#f8fafc] border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-emerald-500 transition-colors font-semibold" placeholder="Enter company name">
                            </div>

                            <!-- Contact Person -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Contact Person (HR)</label>
                                <div x-show="!isEditing" class="text-sm font-semibold text-slate-900 pb-2 border-b border-slate-100">
                                    <?php echo !empty($employer['contact_name']) ? htmlspecialchars($employer['contact_name']) : '<span class="text-slate-300 italic">Add a contact person</span>'; ?>
                                </div>
                                <input x-show="isEditing" x-cloak type="text" name="contact_name" value="<?php echo htmlspecialchars($employer['contact_name']); ?>" required class="w-full bg-[#f8fafc] border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-emerald-500 transition-colors font-semibold" placeholder="Enter full name">
                            </div>
                            
                            <!-- Logo Upload (Only in edit mode) -->
                            <div x-show="isEditing" x-cloak class="pt-2">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Company Logo (Image)</label>
                                <input type="file" name="logo_file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-bold hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded-xl p-1 bg-white">
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: VERIFICATION DETAILS -->
                    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <div class="flex items-center gap-2.5">
                                <span class="text-emerald-600">🛡️</span>
                                <h3 class="font-bold text-slate-800 text-sm">Verification Details</h3>
                                <span class="bg-amber-100 text-amber-700 text-[9px] px-2 py-0.5 rounded uppercase font-bold tracking-wider">Required</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Company Email (Login ID)</label>
                            <div class="text-sm font-semibold text-slate-600 pb-2 flex items-center gap-2">
                                <?php echo htmlspecialchars($employer['email']); ?>
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[9px] font-bold uppercase">Verified</span>
                            </div>
                            <p class="text-[10px] text-rose-500 mt-2 font-bold">Email address cannot be changed directly. Contact support if needed.</p>
                        </div>
                    </div>

                    <!-- CARD 3: ADDITIONAL DETAILS -->
                    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <div class="flex items-center gap-2.5">
                                <span class="text-emerald-600">🌐</span>
                                <h3 class="font-bold text-slate-800 text-sm">Contact Details</h3>
                                <span class="bg-slate-200 text-slate-600 text-[9px] px-2 py-0.5 rounded uppercase font-bold tracking-wider">Optional</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Phone Number</label>
                            <div x-show="!isEditing" class="text-sm font-semibold text-slate-900 pb-2 border-b border-slate-100">
                                <?php echo !empty($employer['phone']) ? htmlspecialchars($employer['phone']) : '<span class="text-slate-300 italic">Add a contact number</span>'; ?>
                            </div>
                            <input x-show="isEditing" x-cloak type="text" name="phone" value="<?php echo htmlspecialchars($employer['phone']); ?>" required class="w-full sm:w-1/2 bg-[#f8fafc] border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-emerald-500 transition-colors font-semibold" placeholder="+251...">
                        </div>
                    </div>

                  <!-- SAVE ACTION BAR (Only shows when Edit is clicked) -->
                    <div x-show="isEditing" x-cloak x-transition.opacity class="fixed sm:sticky bottom-6 sm:bottom-0 left-0 w-full z-40 px-4 sm:px-0">
                        <div class="max-w-5xl mx-auto bg-slate-900 rounded-2xl sm:rounded-xl p-4 flex items-center justify-between shadow-2xl">
                            <p class="text-sm font-bold text-white hidden sm:block">You have unsaved changes.</p>
                            <div class="flex items-center gap-3 w-full sm:w-auto">
                                <button type="button" @click="isEditing = false" class="flex-1 sm:flex-none px-6 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-sm font-bold transition-colors">Discard</button>
                                <button type="submit" class="flex-1 sm:flex-none px-8 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-bold shadow-md transition-colors">Save Profile</button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <!-- ==================== TAB 7: SETTINGS (SECURITY) ==================== -->
            <div x-show="currentTab === 'settings'" class="max-w-4xl mx-auto space-y-6" x-cloak>
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-slate-900">Settings</h2>
                    <p class="text-sm text-slate-600 mt-1">Manage your password, email notifications, and account.</p>
                </div>

                <div class="flex flex-col md:flex-row items-start gap-8">
                    
                    <!-- Left Sidebar Menu -->
                    <div class="w-full md:w-48 flex flex-col gap-1">
                        <button class="px-4 py-2.5 bg-[#ecfdf5] text-emerald-700 font-bold text-sm rounded-lg flex items-center gap-3 w-full text-left">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L3 6v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V6l-9-4zm0 6c1.4 0 2.8 1.4 2.8 2.8S13.4 13.6 12 13.6s-2.8-1.4-2.8-2.8S10.6 8 12 8zm0 10.4c-2.67 0-5.11-1.35-6.6-3.46.04-2.18 4.41-3.34 6.6-3.34 2.18 0 6.55 1.16 6.6 3.34-1.49 2.11-3.93 3.46-6.6 3.46z"/></svg>
                            Security
                        </button>
                        <button class="px-4 py-2.5 text-slate-600 hover:bg-slate-50 font-medium text-sm rounded-lg flex items-center gap-3 w-full text-left">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            Notifications
                        </button>
                    </div>

                    <!-- Right Content Box -->
                    <div class="flex-1 w-full space-y-6">
                        
                        <!-- Change Password Box -->
                        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                            <div class="p-6 border-b border-slate-100">
                                <h3 class="text-base font-bold text-slate-900">Password</h3>
                                <p class="text-xs text-slate-500 mt-1">Change the password you use to sign in.</p>
                            </div>
                            
                            <form method="POST" class="p-6 space-y-6">
                                <input type="hidden" name="action" value="update_password">
                                
                                <div>
                                    <label class="block text-sm font-bold text-slate-900 mb-1">New password</label>
                                    <p class="text-xs text-slate-500 mb-2">At least 8 characters, with upper and lower case letters and a number.</p>
                                    <input type="password" name="new_password" required placeholder="Create a new password" class="w-full bg-[#f8fafc] border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-emerald-500 transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-900 mb-1">Confirm password</label>
                                    <input type="password" name="confirm_password" required placeholder="Repeat the new password" class="w-full bg-[#f8fafc] border border-slate-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-emerald-500 transition-colors">
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-full text-sm shadow-sm transition">Update password</button>
                                </div>
                            </form>
                        </div>

                        <!-- Delete Account Box -->
                        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden p-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Delete account</h3>
                                <p class="text-xs text-slate-500 mt-1">Deactivated immediately and kept for 30 days before permanent deletion.<br>Changed your mind? Sign back in within that window to recover it.</p>
                            </div>
                            <button class="px-5 py-2 text-rose-500 hover:bg-rose-50 font-bold rounded-full text-sm transition">Delete account</button>
                        </div>

                    </div>
                </div>
            </div>

          <!-- ==================== TAB 8: SUPPORT / MESSAGES (SUPERADMIN STYLE) ==================== -->
            <div x-show="currentTab === 'messages' || currentTab === 'support'" 
                 x-data="{ 
                     chatFolder: 'all', 
                     mobileView: 'list', 
                     activeContact: JSON.parse(localStorage.getItem('emp_last_contact')) || <?php echo !empty($chat_contacts) ? htmlspecialchars(json_encode($chat_contacts[0])) : 'null'; ?>
                 }" 
                 x-init="
                     $watch('activeContact', val => localStorage.setItem('emp_last_contact', JSON.stringify(val))); 
                     setTimeout(() => { const b = document.getElementById('chatHistoryBox'); if(b) b.scrollTop = b.scrollHeight; }, 300);
                 "
                 class="h-[75vh] sm:h-[700px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm flex flex-col md:flex-row overflow-hidden relative max-w-6xl mx-auto" x-cloak>
                
                <!-- 🌟 LEFT SIDEBAR: CONTACTS LIST 🌟 -->
                <div :class="mobileView === 'list' ? 'flex' : 'hidden md:flex'" class="w-full md:w-80 lg:w-96 border-r border-slate-200 dark:border-slate-800 flex-col bg-slate-50/50 dark:bg-slate-900/50 flex-shrink-0 z-10 h-full">
                    
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <h3 class="font-black text-slate-900 dark:text-white text-base mb-2">Messages & Support</h3>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">🔍</span>
                            <input type="text" placeholder="Search contacts..." class="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs text-slate-900 dark:text-white outline-none focus:border-emerald-500 border border-transparent">
                        </div>
                    </div>

                    <!-- Folders Switcher (4 Tabs like Superadmin) -->
                    <div class="flex overflow-x-auto custom-scrollbar border-b border-slate-200 dark:border-slate-800 px-2 py-2 bg-white dark:bg-slate-900">
                        <button @click="chatFolder = 'all'" :class="chatFolder === 'all' ? 'bg-emerald-500/10 text-emerald-600 font-bold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] transition-all whitespace-nowrap">All (<?php echo count($chat_contacts); ?>)</button>
                        <button @click="chatFolder = 'employer'" :class="chatFolder === 'employer' ? 'bg-amber-500/10 text-amber-600 font-bold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] transition-all whitespace-nowrap">🏢 Employers</button>
                        <button @click="chatFolder = 'candidate'" :class="chatFolder === 'candidate' ? 'bg-emerald-500/10 text-emerald-600 font-bold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] transition-all whitespace-nowrap">👥 Candidates</button>
                        <button @click="chatFolder = 'admin'" :class="chatFolder === 'admin' ? 'bg-blue-500/10 text-blue-600 font-bold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] transition-all whitespace-nowrap">👨‍💻 Admins</button>
                    </div>

                    <!-- Dynamic Contacts List -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-slate-100 dark:divide-slate-800/50">
                        <?php if(empty($chat_contacts)): ?>
                            <p class="p-6 text-center text-xs text-slate-400">No contacts available.</p>
                        <?php else: ?>
                            <?php foreach ($chat_contacts as $contact): ?>
                            <div x-show="chatFolder === 'all' || chatFolder === '<?php echo $contact['type']; ?>'" 
                                 @click="activeContact = <?php echo htmlspecialchars(json_encode($contact)); ?>; mobileView = 'chat'; setTimeout(() => { const b = document.getElementById('chatHistoryBox'); if(b) b.scrollTop = b.scrollHeight; }, 100);" 
                                 :class="activeContact && activeContact.email === '<?php echo $contact['email']; ?>' ? 'bg-emerald-500/5 border-l-4 border-emerald-500' : 'hover:bg-slate-50'"
                                 class="flex items-center space-x-3 p-3.5 cursor-pointer transition-all">
                                
                                <div class="w-10 h-10 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 flex-shrink-0 bg-white">
                                    <img src="<?php echo !empty($contact['avatar']) ? $contact['avatar'] : 'logo.png'; ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($contact['name']); ?>&background=059669&color=fff'" class="w-full h-full object-cover">
                                </div>
                                
                                <div class="flex-1 min-w-0 text-left">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($contact['name']); ?></h4>
                                        <span class="text-[8px] px-1.5 py-0.5 rounded font-bold uppercase <?php echo $contact['type'] === 'admin' ? 'bg-blue-500/10 text-blue-600' : ($contact['type'] === 'employer' ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600'); ?>">
                                            <?php echo htmlspecialchars($contact['type']); ?>
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 truncate"><?php echo htmlspecialchars($contact['email']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 🌟 RIGHT MAIN CHAT WINDOW 🌟 -->
                <div :class="mobileView === 'chat' ? 'flex' : 'hidden md:flex'" class="w-full md:flex-1 flex-col bg-slate-50 dark:bg-slate-950 h-full">
                    
                    <!-- Active Contact Header -->
                    <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex justify-between items-center shadow-sm z-20">
                        <div class="flex items-center space-x-3 text-left">
                            <button @click="mobileView = 'list'" class="md:hidden text-slate-500 hover:text-emerald-600 px-2 font-bold">← Back</button>
                            <div class="w-10 h-10 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 bg-white flex-shrink-0">
                                <img :src="activeContact ? activeContact.avatar : 'logo.png'" onerror="this.src='logo.png'" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white leading-tight" x-text="activeContact ? activeContact.name : 'Select a Contact'"></h3>
                                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold" x-text="activeContact ? activeContact.email : ''"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat History (Filtered Messages for Selected User) -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 custom-scrollbar flex flex-col z-10" id="chatHistoryBox">
                        
                        <?php if (empty($chats)): ?>
                            <div class="text-center py-12 text-slate-400 text-xs">
                                💬 No messages in this conversation yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($chats as $msg): ?>
                            <!-- STRICT 1-on-1 Message Filtering -->
                            <template x-if="activeContact && (('<?php echo $msg['sender_email']; ?>' === '<?php echo $employer['email']; ?>' && '<?php echo $msg['receiver_email']; ?>' === activeContact.email) || ('<?php echo $msg['sender_email']; ?>' === activeContact.email && '<?php echo $msg['receiver_email']; ?>' === '<?php echo $employer['email']; ?>'))">
                                <div class="flex <?php echo $msg['sender_type'] === 'employer' ? 'justify-end' : 'justify-start'; ?> w-full mb-3 group">
                                    <div class="relative <?php echo $msg['sender_type'] === 'employer' ? 'bg-emerald-600 text-white rounded-br-none' : 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700 rounded-bl-none'; ?> p-3 sm:p-4 rounded-2xl max-w-[85%] sm:max-w-md text-xs sm:text-sm shadow-sm text-left">
                                        
                                        <!-- Image Attachment -->
                                        <?php if ($msg['file_type'] === 'image'): ?>
                                            <a href="<?php echo htmlspecialchars($msg['file_url']); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($msg['file_url']); ?>" class="w-full max-h-60 object-cover rounded-xl mb-2 hover:opacity-90">
                                            </a>
                                        <?php endif; ?>

                                        <!-- Video Attachment -->
                                        <?php if ($msg['file_type'] === 'video'): ?>
                                            <video controls class="w-full rounded-xl mb-2 max-h-56 bg-black">
                                                <source src="<?php echo htmlspecialchars($msg['file_url']); ?>">
                                            </video>
                                        <?php endif; ?>

                                        <!-- Audio Attachment -->
                                        <?php if ($msg['file_type'] === 'audio'): ?>
                                            <div class="p-2 bg-black/10 rounded-xl mb-2">
                                                <audio controls class="w-full h-8">
                                                    <source src="<?php echo htmlspecialchars($msg['file_url']); ?>">
                                                </audio>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Document Attachment -->
                                        <?php if ($msg['file_type'] === 'pdf' || $msg['file_type'] === 'document'): ?>
                                            <a href="<?php echo htmlspecialchars($msg['file_url']); ?>" target="_blank" class="block p-2 bg-black/10 hover:bg-black/20 rounded-xl mb-2 text-xs font-bold transition">
                                                📄 Download Attachment ↗
                                            </a>
                                        <?php endif; ?>

                                        <!-- Text Message -->
                                        <?php if(!empty($msg['message'])): ?>
                                            <p class="leading-relaxed"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                        <?php endif; ?>

                                        <!-- Time & Edited Badge -->
                                        <div class="flex items-center justify-end space-x-1.5 mt-1.5">
                                            <?php if(!empty($msg['is_edited']) && $msg['is_edited'] == 1): ?>
                                                <span class="text-[8px] font-bold opacity-75 italic">(edited)</span>
                                            <?php endif; ?>
                                            <span class="text-[8px] opacity-75"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                                        </div>

                                    </div>
                                </div>
                            </template>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 🌟 CHAT INPUT AREA (Press Enter to Send & Auto File Upload) 🌟 -->
                    <div class="p-3 sm:p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 z-20">
                        <form method="POST" enctype="multipart/form-data" id="chatSendForm" class="flex items-end space-x-1.5 sm:space-x-2">
                            <input type="hidden" name="action" value="send_chat">
                            <input type="hidden" name="receiver_email" x-bind:value="activeContact ? activeContact.email : ''">
                            
                            <!-- Voice / Attachment Buttons -->
                            <div class="flex space-x-1 sm:space-x-2">
                                <label class="cursor-pointer p-2.5 bg-slate-100 dark:bg-slate-800 rounded-full text-slate-500 hover:text-emerald-600 hover:bg-slate-200 transition text-lg flex-shrink-0" title="Attach File">
                                    📎 <input type="file" name="chat_attachment" id="chatFileInput" accept="image/*,video/*,audio/*,.pdf,.doc,.docx" class="hidden" onchange="document.getElementById('chatSendForm').submit();">
                                </label>
                                <button type="button" onmousedown="startVoiceRecord()" onmouseup="stopVoiceRecord()" ontouchstart="startVoiceRecord()" ontouchend="stopVoiceRecord()" class="p-2.5 bg-slate-100 dark:bg-slate-800 rounded-full text-slate-500 hover:text-rose-500 hover:bg-rose-50 transition text-lg flex-shrink-0" title="Hold to Record Audio">
                                    🎤
                                </button>
                            </div>
                            
                            <!-- Text Input area -->
                            <div class="flex-1 bg-slate-100 dark:bg-slate-800 rounded-2xl border border-transparent focus-within:border-emerald-500 transition-colors flex items-center">
                                <textarea name="chat_message" rows="1" placeholder="Type message... (Press Enter to send)" 
                                          @keydown.enter.prevent="if($el.value.trim() !== '') document.getElementById('chatSendForm').submit();"
                                          class="w-full bg-transparent border-none px-4 py-3 text-slate-900 dark:text-white text-xs sm:text-sm outline-none resize-none custom-scrollbar max-h-24"></textarea>
                            </div>
                            
                            <!-- Send Button -->
                            <button type="submit" :disabled="!activeContact" :class="!activeContact ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105 hover:bg-emerald-500'" class="p-2.5 sm:p-3 bg-emerald-600 rounded-full text-white shadow-sm transition-all text-lg sm:text-xl flex-shrink-0">
                                ✈️
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <!-- ==================== PLACEHOLDER TABS ==================== -->
            <template x-for="tab in ['search_talent']" :key="tab">
                <div x-show="currentTab === tab" x-cloak class="max-w-4xl mx-auto pt-10">
                    <div class="bg-white rounded-3xl p-16 text-center border border-slate-200 shadow-sm flex flex-col items-center">
                        <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mb-5 text-2xl">🔍</div>
                        <h3 class="text-xl font-black text-slate-900" x-text="tab.replace('_', ' ') + ' is Coming Soon'"></h3>
                        <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto leading-relaxed">Search thousands of verified Ethiopian candidate profiles directly. Available in the next update.</p>
                    </div>
                </div>
            </template>

        </div>
        
       </main>

    <!-- 🌟 SCRIPTS: CHART.JS & DUAL-SYNC DARK/LIGHT THEME TOGGLE 🌟 -->
    <script>
      <!-- 🌟 SCRIPTS: CHART.JS & DUAL-SYNC DARK/LIGHT THEME TOGGLE 🌟 -->
    <script>
        // 1. Bulletproof Chart.js Doughnut Renderer
        let chartInitialized = false;
        function renderEmployerChart() {
            if (chartInitialized) return;
            const canvas = document.getElementById('employerHiringChart');
            if (canvas && typeof Chart !== 'undefined') {
                const ctx = canvas.getContext('2d');
                const jobs = <?php echo (int)count($my_jobs); ?>;
                const applicants = <?php echo (int)count($applicants); ?>;
                const views = <?php echo (int)$total_views; ?>;
                const pending = <?php echo (int)$new_apps; ?>;
                
                const totalData = jobs + applicants + views + pending;
                const displayData = totalData === 0 ? [1] : [jobs, applicants, views, pending];
                const displayBg = totalData === 0 ? ['#e2e8f0'] : ['#059669', '#0d9488', '#3b82f6', '#f59e0b'];

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active Jobs', 'Total Applicants', 'Job Views', 'Pending Action'],
                        datasets: [{
                            data: displayData,
                            backgroundColor: displayBg,
                            borderWidth: 0,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
                chartInitialized = true;
            }
        }
        
        document.addEventListener('DOMContentLoaded', renderEmployerChart);
        // Fallback in case Alpine.js delays rendering
        setTimeout(renderEmployerChart, 500); 
        setTimeout(renderEmployerChart, 1500);
                
                const totalData = jobs + applicants + views + pending;
                // Yoo ragaan duwwaa ta'e qofa halluu daalacha agarsiisa, yoo ragaan jiru halluu magariisa/cuquliisa agarsiisa
                const displayData = totalData === 0 ? [1] : [jobs, applicants, views, pending];
                const displayBg = totalData === 0 ? ['#e2e8f0'] : ['#059669', '#0d9488', '#3b82f6', '#f59e0b'];

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active Jobs', 'Total Applicants', 'Job Views', 'Pending Action'],
                        datasets: [{
                            data: displayData,
                            backgroundColor: displayBg,
                            borderWidth: 0,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        if (totalData === 0) return ' No data yet';
                                        return ' ' + context.label + ': ' + context.raw;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
        document.addEventListener('DOMContentLoaded', renderEmployerChart);
        window.addEventListener('load', renderEmployerChart);

        // 2. Powerful Theme Switcher (Synchronized with Cookie & LocalStorage)
        const themeToggleBtn = document.getElementById('theme-toggle');
        const sunIcon = document.getElementById('sun-icon');
        const moonIcon = document.getElementById('moon-icon');

        function updateThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            if (sunIcon && moonIcon) {
                if (isDark) {
                    sunIcon.classList.remove('hidden');
                    moonIcon.classList.add('hidden');
                } else {
                    sunIcon.classList.add('hidden');
                    moonIcon.classList.remove('hidden');
                }
            }
        }
        updateThemeIcons();

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.classList.contains('dark');
                const newTheme = isDark ? 'light' : 'dark';

                if (newTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                localStorage.setItem('theme', newTheme);
                document.cookie = "theme=" + newTheme + "; path=/; max-age=" + (60 * 60 * 24 * 365);
                updateThemeIcons();
            });
        }
    </script>
</body>
</html>