<?php
// post-vacancy.php
include_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config.php';

$success_msg = "";
$error_msg = "";
$employer_id = $_SESSION['employer_id'] ?? null;
$is_admin = isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === true;

// 🌟 MAGIC: STRICT GUEST BLOCKER 🌟
$is_guest = (!$employer_id && !$is_admin);
if ($is_guest && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['submit_payment_slip'])) {
    $_SESSION['error_msg'] = "Please Log In or Create an Employer Account to publish your vacancy.";
    header("Location: signup.php?type=employer");
    exit();
}

// 🌟 1. MAGIC: AUTO-CREATE MISSING COLUMNS IN DATABASE 🌟
if (isset($pdo) && $pdo !== null) {
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN selected_package VARCHAR(50) DEFAULT 'free'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN payment_status VARCHAR(50) DEFAULT 'approved'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE employers ADD COLUMN job_credits INT DEFAULT 0"); } catch (Exception $e) {}
    try { 
        $pdo->exec("CREATE TABLE IF NOT EXISTS employer_payments (id INT AUTO_INCREMENT PRIMARY KEY, employer_id INT, package_name VARCHAR(50), amount VARCHAR(20), payment_method VARCHAR(50), receipt_file VARCHAR(255), status ENUM('pending','approved','rejected') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); 
    } catch (Exception $e) {}
}

// Fetch Employer's Real Credits and Posts Stats
$free_posts_used = 0;
$my_credits = 0;
$total_posted_count = 0;

if ($employer_id && isset($pdo)) {
    try {
        // Free post check (Mirkaneessa yoo duraan Free Trial fayyadameera ta'e)
        $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND selected_package = 'free'");
        $stmt1->execute([$employer_id]);
        $free_posts_used = (int)$stmt1->fetchColumn();

        // Available Paid Credits
        $stmt2 = $pdo->prepare("SELECT job_credits FROM employers WHERE id = ?");
        $stmt2->execute([$employer_id]);
        $my_credits = (int)$stmt2->fetchColumn();

        // Total Published by this employer
        $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_id = ?");
        $stmt3->execute([$employer_id]);
        $total_posted_count = (int)$stmt3->fetchColumn();
    } catch (Exception $e) {}
}

// ==========================================
// 🌟 MAGIC: HANDLE JOB SUBMISSION & CREDITS 🌟
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['submit_payment_slip'])) {
    
    $company_name = trim($_POST['company_name'] ?? '');
    $title        = trim($_POST['title'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $job_type     = trim($_POST['job_type'] ?? 'Full Time');
    $career_level = trim($_POST['career_level'] ?? 'Mid Level (2-5 years)');
    $location     = trim($_POST['location'] ?? '');
    $salary       = trim($_POST['salary'] ?? 'Negotiable');
    $positions    = (int)($_POST['positions'] ?? 1);
    $package      = $_POST['selected_package'] ?? 'plus'; 
    $description  = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $how_to_apply = trim($_POST['how_to_apply'] ?? '');
    $deadline_in  = trim($_POST['deadline'] ?? '');
    
    // 🌟 VALIDATE FREE POST LIMIT & CREDITS 🌟
    $deadline = null;
    $can_post = false;

    if ($is_admin) {
        $can_post = true; // Admin posts are always free & unlimited
        $deadline = !empty($deadline_in) ? $deadline_in : date('Y-m-d', strtotime('+30 days'));
    } else {
        if ($package === 'free') {
            if ($free_posts_used >= 1) {
                $error_msg = "You have already used your 1 Free Trial post. Please purchase a package with Social Media & Telegram broadcasting to continue.";
            } else {
                $deadline = !empty($deadline_in) ? $deadline_in : date('Y-m-d', strtotime('+3 days')); // 🌟 Free is strictly 3 days on Website
                $can_post = true;
            }
        } else {
            // Premium Packages require Credits!
            if ($my_credits <= 0) {
                $error_msg = "You have 0 Job Credits available. Please choose a package below and submit your payment slip to acquire credits.";
            } else {
                $deadline = !empty($deadline_in) ? $deadline_in : date('Y-m-d', strtotime('+30 days')); // 🌟 Paid is 30 days
                $can_post = true;
            }
        }
    }

    if (empty($error_msg) && $can_post && $company_name && $title && $category && $job_type && $location && $description && $requirements) {
        
        // 1. Logo Upload Handling
        $logo_url = 'logo.png';
        $file_logo = $_FILES['logo_file'] ?? $_FILES['job_logo'] ?? null;
        if ($file_logo && $file_logo['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file_logo['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                $file_name = time() . '_logo_' . uniqid() . '.' . $ext;
                if (!is_dir('uploads/logos/')) mkdir('uploads/logos/', 0777, true);
                if (move_uploaded_file($file_logo['tmp_name'], 'uploads/logos/' . $file_name)) {
                    $logo_url = 'uploads/logos/' . $file_name;
                }
            }
        }

        // 2. Document Attachment Handling (PDF / Flyer)
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

        if (isset($pdo) && $pdo !== null) {
            try {
                // Insert Job
                $sql = "INSERT INTO jobs (employer_id, company_name, title, category, job_type, experience, career_level, positions, deadline, location, salary, description, requirements, how_to_apply, logo, attachment_file, status, selected_package, payment_status, is_top_company) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, 'approved', 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$employer_id, $company_name, $title, $category, $job_type, $career_level, $career_level, $positions, $deadline, $location, $salary, $description, $requirements, $how_to_apply, $logo_url, $attachment_file, $package]);

                $new_job_id = $pdo->lastInsertId();
                $job_link = "https://infoplus.et/job-details.php?id=" . $new_job_id;

                // 🌟 DEDUCT CREDIT IF PREMIUM POST 🌟
                if (!$is_admin && $package !== 'free') {
                    $pdo->prepare("UPDATE employers SET job_credits = job_credits - 1 WHERE id = ?")->execute([$employer_id]);
                    $my_credits--; 
                }

                // 🌟 AUTO TELEGRAM POST FOR PAID PACKAGES 🌟
                if ($package !== 'free' || $is_admin) {
                    $bot_token = "TOKEN_KEE_ASITTI_GALCHI"; 
                    $chat_id = "@infopluscallcenter"; 
                    $telegram_msg = "🌟 *Infoplus (Premium Vacancy)*\n\n💼 *Job Title:* {$title}\n🏢 *Company:* {$company_name}\n📍 *Location:* {$location}\n⏱ *Job Type:* {$job_type}\n💰 *Salary:* {$salary}\n📅 *Deadline:* {$deadline}\n👥 *Required:* {$positions} Position(s)\n\n👉 *[View Details & Apply Here]({$job_link})*\n\n☎️ *Call 6781 Hotline* for phone applications.";
                    @file_get_contents("https://api.telegram.org/bot{$bot_token}/sendMessage", false, stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type:application/x-www-form-urlencoded\r\n", 'content' => http_build_query(['chat_id' => $chat_id, 'text' => $telegram_msg, 'parse_mode' => 'Markdown'])]]));
                }

                if ($package === 'free') {
                    $success_msg = "Your 1-time Free Job has been published for 3 days on Website!";
                    $free_posts_used++;
                } else {
                    $success_msg = "Premium Job published successfully across Web & Telegram! (1 Credit Deducted. Remaining: {$my_credits})";
                }
            } catch (PDOException $e) {
                $error_msg = "Database Error: " . $e->getMessage();
            }
        }
    } elseif (empty($error_msg)) {
        $error_msg = "Please fill out all mandatory fields marked with an asterisk (*).";
    }
}

// ==========================================
// 🌟 MAGIC: HANDLE PAYMENT SLIP SUBMISSION 🌟
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment_slip'])) {
    if ($is_guest) {
        $_SESSION['error_msg'] = "Please log in to upload a payment slip.";
        header("Location: signup.php?type=employer"); 
        exit();
    }

    $pay_package = $_POST['pay_package'] ?? '';
    $pay_amount  = $_POST['pay_amount'] ?? '';
    $pay_method  = $_POST['pay_method'] ?? 'CBE';
    
    $receipt_url = null;
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'pdf'])) {
            $file_name = time() . '_receipt_' . uniqid() . '.' . $ext;
            if (!is_dir('uploads/payments/')) mkdir('uploads/payments/', 0777, true);
            if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], 'uploads/payments/' . $file_name)) {
                $receipt_url = 'uploads/payments/' . $file_name;
            }
        }
    }

    if ($receipt_url && isset($pdo)) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS employer_payments (id INT AUTO_INCREMENT PRIMARY KEY, employer_id INT, package_name VARCHAR(50), amount VARCHAR(20), payment_method VARCHAR(50), receipt_file VARCHAR(255), status ENUM('pending','approved','rejected') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            
            $stmt = $pdo->prepare("INSERT INTO employer_payments (employer_id, package_name, amount, payment_method, receipt_file) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$employer_id, $pay_package, $pay_amount, $pay_method, $receipt_url]);
            $success_msg = "Payment slip submitted successfully! Superadmin will verify and assign your Job Credits shortly.";
        } catch (Exception $e) { 
            $error_msg = "Error submitting payment: " . $e->getMessage(); 
        }
    } else {
        $error_msg = "Please upload a valid payment receipt.";
    }
}

// Fetch Dynamic Payment Methods
$dyn_payments = [];
if (isset($pdo) && $pdo !== null) {
    try { 
        $dyn_payments = $pdo->query("SELECT * FROM dynamic_payments ORDER BY id ASC")->fetchAll(); 
    } catch (Exception $e) {}
}
?>

<div class="py-8 sm:py-16 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- 🌟 HERO HEADER SECTION 🌟 -->
        <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-14">
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 mb-4 uppercase tracking-widest shadow-sm">
                📢 Employer Recruitment Portal
            </span>
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                Post a Vacancy & Reach <br class="hidden sm:inline">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 via-emerald-500 to-cyan-500">Top Talent Across Ethiopia</span>
            </h1>
            <p class="mt-4 text-slate-500 dark:text-slate-400 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto font-medium">
                Broadcast job openings directly to thousands of active Ethiopian candidates across Web, Telegram Channel (@infoplus), Facebook, TikTok, and Call Center 6781.
            </p>
        </div>

       <!-- 🌟 MAIN CONTENT AREA 🌟 -->
        <div class="w-full" x-data="{ 
            selectedPack: '<?php echo $free_posts_used > 0 ? 'plus' : 'free'; ?>',
            showPaymentModal: false,
            selectedPackageName: 'Info+ Plus — 5 job posts',
            selectedPackagePrice: 'ETB 6,000',
            selectedPackageIndex: 1,
            packagesList: [
                { name: 'Info+ Lite — 1 job post', price: 'ETB 1,500' },
                { name: 'Info+ Plus — 5 job posts', price: 'ETB 6,000' },
                { name: 'Info+ Pro — 15 job posts', price: 'ETB 15,000' },
                { name: 'Info+ Max — 40 job posts', price: 'ETB 32,000' }
            ],
            openPayment(name, price) {
                this.selectedPackageName = name;
                this.selectedPackagePrice = price;
                let foundIdx = this.packagesList.findIndex(p => p.price === price);
                this.selectedPackageIndex = foundIdx !== -1 ? foundIdx : 1;
                this.showPaymentModal = true;
            }
        }">
            
            <!-- 🌟 CREDITS & PRICING PACKAGES SECTION 🌟 -->
            <div class="mb-12 sm:mb-16">
                
                <!-- 🌟 LIVE CREDITS & POSTS DASHBOARD BAR 🌟 -->
                <?php if (!$is_guest && !$is_admin): ?>
                <div class="max-w-5xl mx-auto mb-8 bg-white/90 dark:bg-slate-900/90 border border-teal-500/30 p-4 sm:p-6 rounded-3xl shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center text-2xl font-black border border-teal-500/20 flex-shrink-0">
                            💳
                        </div>
                        <div class="text-left">
                            <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest block">Account Balance</span>
                            <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">
                                <span class="text-teal-600 dark:text-teal-400"><?php echo $my_credits; ?></span> Available Job Credits
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Free Trial: <strong class="<?php echo $free_posts_used >= 1 ? 'text-rose-500' : 'text-emerald-500'; ?>"><?php echo $free_posts_used >= 1 ? 'Used (0 Left)' : '1 Post Available'; ?></strong> &nbsp;|&nbsp; Total Posted: <strong><?php echo $total_posted_count; ?> Vacancies</strong>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button type="button" @click="openPayment('Info+ Plus — 5 job posts', 'ETB 6,000')" class="w-full sm:w-auto px-6 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-500 hover:from-teal-500 hover:to-emerald-400 text-white font-extrabold text-xs rounded-xl shadow-md transition-all">
                            + Buy Job Credits
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 🌟 FREE TRIAL VS PREMIUM COMPARISON BANNER 🌟 -->
                <?php if ($free_posts_used == 0 && !$is_admin): ?>
                <div @click="selectedPack = 'free'" :class="selectedPack === 'free' ? 'ring-2 ring-emerald-500 bg-emerald-50/80 dark:bg-emerald-950/20' : 'bg-slate-50 dark:bg-slate-800 hover:bg-slate-100'" class="mb-8 p-5 rounded-3xl border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 cursor-pointer transition-all max-w-5xl mx-auto shadow-sm">
                    <div class="text-left">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 bg-emerald-600 text-white rounded-md text-[9px] font-black uppercase tracking-wider">Free Trial</span>
                            <h4 class="font-black text-slate-900 dark:text-white text-base">Post 1 Free Job (Website Only)</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                            ⏱️ Valid for <strong>3 Days Only</strong> &nbsp;•&nbsp; ⚠️ <span class="text-rose-500 font-bold">No Telegram or Social Media Auto-Post</span> (Upgrade below for full multi-platform reach).
                        </p>
                    </div>
                    <span :class="selectedPack === 'free' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600'" class="px-5 py-2 rounded-xl font-black text-xs shadow-sm transition-colors whitespace-nowrap">
                        <span x-text="selectedPack === 'free' ? 'Selected Free ✓' : 'Select Free (3 Days)'"></span>
                    </span>
                </div>
                <?php endif; ?>

                <!-- 🌟 4 PERSUASIVE PRICING CARDS WITH FULL FEATURE LIST 🌟 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 max-w-7xl mx-auto">
                    
                    <!-- 1. Info+ Lite -->
                    <div @click="selectedPack = 'lite'" :class="selectedPack === 'lite' ? 'border-emerald-500 ring-2 ring-emerald-500 shadow-xl bg-white dark:bg-slate-900' : 'border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 hover:border-emerald-400'" class="border rounded-[2rem] p-6 cursor-pointer transition-all flex flex-col justify-between h-full shadow-sm text-left group">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-black text-slate-900 dark:text-white text-lg">Info+ Lite</h3>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Single Post</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">1 Job Post • Valid 30 Days</p>
                            
                            <div class="mt-4 mb-5 flex items-baseline gap-1">
                                <span class="text-xs font-bold text-slate-400">ETB</span>
                                <span class="text-3xl font-black text-slate-900 dark:text-white">1,500</span>
                            </div>

                            <!-- Features Checklist -->
                            <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <li class="flex items-center gap-2"><span>✈️</span> <span>Telegram Channel Broadcast</span></li>
                                <li class="flex items-center gap-2"><span>☎️</span> <span>Call Center 6781 Screening</span></li>
                                <li class="flex items-center gap-2"><span>⏱️</span> <span>30 Days Live Visibility</span></li>
                                <li class="flex items-center gap-2 text-slate-400"><span>✖</span> <span>Social Media Boost (FB/TikTok)</span></li>
                            </ul>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400">One-time</span>
                            <button type="button" @click.stop="openPayment('Info+ Lite — 1 job post', 'ETB 1,500')" class="px-4 py-2 rounded-xl text-xs font-black bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 hover:bg-emerald-600 hover:text-white transition-all">Buy Pack ❯</button>
                        </div>
                    </div>

                    <!-- 2. Info+ Plus (MOST POPULAR) -->
                    <div @click="selectedPack = 'plus'" :class="selectedPack === 'plus' ? 'border-emerald-500 ring-2 ring-emerald-500 shadow-2xl bg-emerald-50/20 dark:bg-emerald-950/20' : 'border-emerald-300 dark:border-emerald-900/60 bg-white dark:bg-slate-900 hover:border-emerald-500'" class="border-2 rounded-[2rem] p-6 cursor-pointer transition-all flex flex-col justify-between h-full shadow-md text-left relative group">
                        <span class="absolute -top-3 right-6 bg-emerald-600 text-white text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow-md">Most Popular</span>
                        
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-black text-slate-900 dark:text-white text-lg">Info+ Plus</h3>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600">Save 20%</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">5 Job Posts (ETB 1,200/post)</p>
                            
                            <div class="mt-4 mb-5 flex items-baseline gap-1">
                                <span class="text-xs font-bold text-slate-400">ETB</span>
                                <span class="text-3xl font-black text-emerald-600">6,000</span>
                            </div>

                            <!-- Features Checklist -->
                            <ul class="space-y-2 text-xs text-slate-700 dark:text-slate-200 pt-3 border-t border-emerald-100 dark:border-emerald-900/40 font-semibold">
                                <li class="flex items-center gap-2"><span>✈️</span> <span>Telegram Channel Auto-Blast</span></li>
                                <li class="flex items-center gap-2"><span>📢</span> <span>Facebook & TikTok Boost</span></li>
                                <li class="flex items-center gap-2"><span>☎️</span> <span>6781 Hotline Matchmaking</span></li>
                                <li class="flex items-center gap-2"><span>✨</span> <span>Verified HR Stamp</span></li>
                            </ul>
                        </div>

                        <div class="mt-6 pt-4 border-t border-emerald-100 dark:border-emerald-900/40 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-emerald-600">Best Value</span>
                            <button type="button" @click.stop="openPayment('Info+ Plus — 5 job posts', 'ETB 6,000')" class="px-5 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-md transition-all">Buy Pack ❯</button>
                        </div>
                    </div>

                    <!-- 3. Info+ Pro -->
                    <div @click="selectedPack = 'pro'" :class="selectedPack === 'pro' ? 'border-emerald-500 ring-2 ring-emerald-500 shadow-xl bg-white dark:bg-slate-900' : 'border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 hover:border-emerald-400'" class="border rounded-[2rem] p-6 cursor-pointer transition-all flex flex-col justify-between h-full shadow-sm text-left group">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-black text-slate-900 dark:text-white text-lg">Info+ Pro</h3>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Save 33%</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">15 Job Posts (ETB 1,000/post)</p>
                            
                            <div class="mt-4 mb-5 flex items-baseline gap-1">
                                <span class="text-xs font-bold text-slate-400">ETB</span>
                                <span class="text-3xl font-black text-slate-900 dark:text-white">15,000</span>
                            </div>

                            <!-- Features Checklist -->
                            <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <li class="flex items-center gap-2"><span>🌐</span> <span>Multi-Socials (Telegram, FB, TikTok, X)</span></li>
                                <li class="flex items-center gap-2"><span>🎯</span> <span>Top Search Result Placement</span></li>
                                <li class="flex items-center gap-2"><span>🏢</span> <span>Top Hiring Logo on Homepage</span></li>
                                <li class="flex items-center gap-2"><span>⚡</span> <span>Instant SMS Candidate Alerts</span></li>
                            </ul>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400">One-time</span>
                            <button type="button" @click.stop="openPayment('Info+ Pro — 15 job posts', 'ETB 15,000')" class="px-4 py-2 rounded-xl text-xs font-black bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 hover:bg-emerald-600 hover:text-white transition-all">Buy Pack ❯</button>
                        </div>
                    </div>

                    <!-- 4. Info+ Max (ENTERPRISE) -->
                    <div @click="selectedPack = 'max'" :class="selectedPack === 'max' ? 'border-emerald-500 ring-2 ring-emerald-500 shadow-xl bg-white dark:bg-slate-900' : 'border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 hover:border-emerald-400'" class="border rounded-[2rem] p-6 cursor-pointer transition-all flex flex-col justify-between h-full shadow-sm text-left group">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-black text-slate-900 dark:text-white text-lg">Info+ Max</h3>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600">Save 47%</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">40 Job Posts (ETB 800/post)</p>
                            
                            <div class="mt-4 mb-5 flex items-baseline gap-1">
                                <span class="text-xs font-bold text-slate-400">ETB</span>
                                <span class="text-3xl font-black text-emerald-600">32,000</span>
                            </div>

                            <!-- Features Checklist -->
                            <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <li class="flex items-center gap-2"><span>👑</span> <span>Enterprise Priority Broadcast</span></li>
                                <li class="flex items-center gap-2"><span>🎧</span> <span>Dedicated 6781 Account Manager</span></li>
                                <li class="flex items-center gap-2"><span>📂</span> <span>Full Candidate CV Access</span></li>
                                <li class="flex items-center gap-2"><span>⭐</span> <span>Permanent Verified Employer Status</span></li>
                            </ul>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400">Unlimited 1 Year</span>
                            <button type="button" @click.stop="openPayment('Info+ Max — 40 job posts', 'ETB 32,000')" class="px-4 py-2 rounded-xl text-xs font-black bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 hover:bg-emerald-600 hover:text-white transition-all">Buy Pack ❯</button>
                        </div>
                    </div>

                </div>

            </div>

            <!-- 🌟 VACANCY FORM SECTION (100% SYNCHRONIZED WITH SUPERADMIN) 🌟 -->
            <form action="post-vacancy.php" method="POST" enctype="multipart/form-data" class="max-w-4xl mx-auto">
                <input type="hidden" name="selected_package" :value="selectedPack">
                
                <div class="dotted-card bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-10 rounded-[2.5rem] shadow-xl text-left">
                    
                    <div class="border-b border-slate-100 dark:border-slate-800 pb-5 mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Vacancy Announcement Form</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Provide clear requirements. Our AI formats this into a verified official announcement.</p>
                        </div>
                        <span class="text-[9px] font-extrabold text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-full uppercase tracking-widest">* Mandatory Fields</span>
                    </div>

                    <?php if ($success_msg): ?>
                        <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-bold">🎉 <?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs sm:text-sm font-bold">⚠️ <?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <!-- Form Fields -->
                    <div class="space-y-5">
                        
                        <!-- Row 1: Company Name & Job Title -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Company Name *</label>
<input type="text" name="company_name" value="<?php echo htmlspecialchars($_SESSION['company_name'] ?? ''); ?>" required placeholder="e.g. Dashen Bank PLC / Tech Solutions" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-semibold">                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Job Title *</label>
                                <input type="text" name="title" required placeholder="e.g. Senior Software Engineer" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-semibold">
                            </div>
                        </div>

                        <!-- Row 2: Category, Employment Type & Location -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Category *</label>
                                <input type="text" name="category" list="cat_list" required placeholder="Select or type..." class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-semibold">
                                <datalist id="cat_list">
                                    <option value="Accounting and Finance"><option value="Admin, Secretarial and Clerical"><option value="Banking and Insurance"><option value="Customer Service"><option value="Engineering"><option value="Health Care"><option value="ICT and Telecom"><option value="Sales and Marketing">
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Employment Type *</label>
                                <select name="job_type" required class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-semibold">
                                    <option value="Full Time">Full Time</option><option value="Part Time">Part Time</option><option value="Contract">Contract</option><option value="Internship">Internship</option><option value="Remote">Remote</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Location / City *</label>
                                <input type="text" name="location" required placeholder="e.g. Addis Ababa, Bole" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-semibold">
                            </div>
                        </div>

                        <!-- Row 3: Career Level, Salary & Deadline -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Career Level *</label>
                                <select name="career_level" required class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-semibold">
                                    <option value="Entry Level (Fresh Graduate)">Entry Level (Fresh)</option><option value="Junior Level (1-3 years)">Junior Level (1-3 yrs)</option><option value="Mid Level (3-5 years)">Mid Level (3-5 yrs)</option><option value="Senior (5-8 years)">Senior Level (5+ yrs)</option><option value="Managerial / Executive">Managerial / Executive</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Salary Offer *</label>
                                <input type="text" name="salary" placeholder="e.g. 25,000 ETB / Mon or Negotiable" required class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-semibold">
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-rose-500 uppercase mb-1.5">Application Deadline *</label>
                                <input type="date" name="deadline" required class="w-full px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 text-rose-700 dark:text-rose-400 font-bold text-sm outline-none focus:border-rose-500 transition-colors">
                            </div>
                        </div>

                        <!-- Row 4: Descriptions -->
                        <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Job Description & Position Summary *</label>
                                <textarea name="description" required rows="3" placeholder="Detail the duties and overall role summary..." class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-medium leading-relaxed"></textarea>
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Duties, Requirements & Qualifications *</label>
                                <textarea name="requirements" required rows="3" placeholder="- Bachelor's Degree in related field...&#10;- Proven work experience and skills..." class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-medium leading-relaxed"></textarea>
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">How To Apply (Instructions)</label>
                                <textarea name="how_to_apply" rows="2" placeholder="e.g. Submit CV through Quick Apply or call shortcode 6781..." class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500 transition-colors font-medium"></textarea>
                            </div>
                        </div>

                        <!-- 🌟 ROW 5: TOTAL QUANTITY REQUIRED (Prominent Amber Box) 🌟 -->
                        <div class="bg-amber-50 dark:bg-amber-500/10 border-2 border-amber-400/50 dark:border-amber-500/30 p-4 sm:p-5 rounded-2xl">
                            <label class="block text-[10px] sm:text-xs font-black text-amber-800 dark:text-amber-400 uppercase mb-1">Total Quantity Required (Open Positions) *</label>
                            <input type="number" name="positions" value="1" min="1" required class="w-full sm:w-1/3 bg-white dark:bg-slate-800 border border-amber-300 dark:border-amber-600 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-base font-black outline-none focus:border-amber-500">
                            <p class="text-[9px] sm:text-[10px] text-amber-700 dark:text-amber-300 mt-1.5 font-bold">⚠️ Note: Infoplus system uses this number to calculate and display the Live Applicants Progress Bar.</p>
                        </div>

                        <!-- 🌟 ROW 6: LOGO & ATTACHMENT UPLOAD (Identical to Superadmin) 🌟 -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 p-4 rounded-2xl border-dashed">
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Company Logo (Optional)</label>
                                <p class="text-[10px] text-slate-400 mb-2">Upload custom logo image (PNG/JPG).</p>
                                <input type="file" name="logo_file" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-emerald-100 dark:file:bg-emerald-500/20 file:text-emerald-700 dark:file:text-emerald-300 file:font-bold hover:file:bg-emerald-200 cursor-pointer">
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 p-4 rounded-2xl border-dashed">
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Attach Full Document (PDF / Flyer)</label>
                                <p class="text-[10px] text-slate-400 mb-2">Upload official vacancy notice or poster.</p>
                                <input type="file" name="attachment_file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-emerald-100 dark:file:bg-emerald-500/20 file:text-emerald-700 dark:file:text-emerald-300 file:font-bold hover:file:bg-emerald-200 cursor-pointer">
                            </div>
                        </div>

                    </div>

                    <!-- Submit Button Row with Guest Check -->
                    <div class="pt-8 mt-8 border-t border-slate-100 dark:border-slate-800">
                        <div x-data="{ isGuest: <?php echo $is_guest ? 'true' : 'false'; ?>, showGuestModal: false }">
                            
                            <!-- If Guest: Open Modal -->
                            <button type="button" x-show="isGuest" @click="showGuestModal = true" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-4 rounded-2xl text-base sm:text-lg shadow-xl shadow-emerald-500/30 transition-transform transform hover:-translate-y-0.5 cursor-pointer">
                                🚀 Publish Job Vacancy
                            </button>
                            
                            <!-- If Logged in Employer/Admin: Real Submit -->
                            <button type="submit" x-show="!isGuest" onclick="if(this.form.checkValidity()){ this.disabled=true; this.innerHTML='⏳ Publishing Job...'; this.form.submit(); }" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-4 rounded-2xl text-base sm:text-lg shadow-xl shadow-emerald-500/30 transition-transform transform hover:-translate-y-0.5 cursor-pointer" x-cloak>
                                🚀 Publish Job Vacancy
                            </button>

                            <!-- Guest Blocker Modal -->
                            <div x-show="showGuestModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 transition-all" x-cloak>
                                <div @click.away="showGuestModal = false" class="bg-white dark:bg-slate-900 border border-emerald-500/40 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl text-center space-y-4">
                                    <div class="w-16 h-16 bg-amber-500/10 text-amber-500 rounded-full flex items-center justify-center mx-auto text-3xl border border-amber-500/20">🏢</div>
                                    <h3 class="font-black text-slate-900 dark:text-white text-lg">Employer Account Required</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">To publish this job vacancy, please sign in or create a free employer account.</p>
                                    <div class="flex gap-3 pt-4">
                                        <a href="signup.php?type=employer" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-black text-xs shadow-md transition-colors">Create Account</a>
                                        <a href="login.php" class="flex-1 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs hover:bg-slate-200 transition-colors">Log In</a>
                                    </div>
                                    <button type="button" @click="showGuestModal = false" class="absolute top-3 right-4 text-slate-400 font-bold text-sm">✕</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>

            <!-- 🌟 PAYMENT SLIP MODAL (TELEPORTED TO BODY) 🌟 -->
            <template x-teleport="body">
                <div x-show="showPaymentModal" class="fixed inset-0 z-[9999] bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
                    <div @click.away="showPaymentModal = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-6 sm:p-8 max-w-lg w-full shadow-2xl text-left space-y-5">
                        <div class="flex justify-between items-start border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div>
                                <h3 class="font-black text-xl text-slate-900 dark:text-white">Upload Payment Slip</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Send transaction screenshot/PDF. We'll credit your posts within 24h.</p>
                            </div>
                            <button @click="showPaymentModal = false" class="text-slate-400 hover:text-rose-500 p-1 bg-slate-50 dark:bg-slate-800 rounded-full w-8 h-8 font-bold text-sm flex items-center justify-center">✕</button>
                        </div>

                        <!-- 🌟 INTERACTIVE SELECTABLE PACKAGE DROPDOWN BOX 🌟 -->
                        <div class="bg-emerald-50 dark:bg-emerald-500/10 border-2 border-emerald-500/40 rounded-2xl p-3.5 sm:p-4 shadow-sm">
                            <label class="block text-[10px] font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-400 mb-1.5 flex items-center justify-between">
                                <span>Selected Package (Click to switch)</span>
                                <span class="text-[9px] lowercase font-normal text-emerald-600 dark:text-emerald-300">▼ change package</span>
                            </label>
                            
                            <div class="relative flex items-center justify-between">
                                <select x-model="selectedPackageIndex"
                                        @change="
                                            selectedPackageName = packagesList[selectedPackageIndex].name;
                                            selectedPackagePrice = packagesList[selectedPackageIndex].price;
                                        "
                                        class="w-full bg-transparent font-black text-xs sm:text-sm text-slate-900 dark:text-white outline-none cursor-pointer pr-10 appearance-none py-1">
                                    <template x-for="(pkg, idx) in packagesList" :key="idx">
                                        <option :value="idx" 
                                                class="bg-white dark:bg-slate-900 text-slate-900 dark:text-white py-2 font-bold" 
                                                x-text="pkg.name + ' (' + pkg.price + ')'">
                                        </option>
                                    </template>
                                </select>
                                
                                <!-- Custom Dropdown Icon Indicator -->
                                <div class="pointer-events-none absolute right-0 flex items-center space-x-1.5 bg-emerald-600 text-white px-2.5 py-1 rounded-lg shadow-sm">
                                    <span class="font-black text-xs" x-text="selectedPackagePrice"></span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <form action="post-vacancy.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                            <input type="hidden" name="submit_payment_slip" value="1">
                            <input type="hidden" name="pay_package" :value="selectedPackageName">
                            <input type="hidden" name="pay_amount" :value="selectedPackagePrice">

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Select Payment Method</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php if(empty($dyn_payments)): ?>
                                        <p class="col-span-2 text-xs text-slate-400">Default: CBE / Telebirr</p>
                                    <?php else: ?>
                                        <?php foreach($dyn_payments as $idx => $p): ?>
                                        <label class="flex items-center space-x-2 p-3 rounded-xl border cursor-pointer hover:border-emerald-500 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700">
                                            <input type="radio" name="pay_method" value="<?php echo htmlspecialchars($p['provider_name']); ?>" <?php echo $idx === 0 ? 'checked' : ''; ?> class="accent-emerald-500">
                                            <div>
                                                <span class="text-[10px] font-black uppercase block text-slate-900 dark:text-white"><?php echo htmlspecialchars($p['provider_name']); ?></span>
                                                <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($p['account_details']); ?></span>
                                            </div>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Upload Receipt / Screenshot *</label>
                                <div class="border-2 border-dashed border-emerald-300 dark:border-emerald-500/40 bg-emerald-50/30 dark:bg-emerald-950/10 rounded-2xl p-6 text-center cursor-pointer relative group hover:bg-emerald-50 transition-colors">
                                    <input type="file" name="receipt_file" accept=".png,.jpg,.jpeg,.webp,.pdf" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    <div class="flex flex-col items-center gap-1.5 text-slate-500 dark:text-slate-400 group-hover:text-emerald-600">
                                        <span class="text-3xl">📤</span>
                                        <span class="text-xs font-bold">Drop your image (PNG/JPG) or PDF receipt here</span>
                                        <span class="text-[10px] text-slate-400">Max file size: 5MB</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showPaymentModal = false" class="px-5 py-2.5 rounded-full border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-50">Cancel</button>
                                <button type="submit" class="px-7 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-md transition-colors">✔ Submit Payment Slip</button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
            
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>