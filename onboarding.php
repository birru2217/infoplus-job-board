<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// 🌟 MAGIC: Check if User is Logged In OR is a New Verified Registration
if (!isset($_SESSION['user_id']) && !isset($_SESSION['temp_reg'])) {
    header("Location: login.php");
    exit();
}

$error_msg = "";
$success_msg = "";
$initial_step = 1; // Default step

// 🌟 MAGIC: Smart Back Button Logic 🌟
$back_link = isset($_SESSION['temp_reg']) ? 'logout.php' : 'candidate_dashboard.php';
$back_text = isset($_SESSION['temp_reg']) ? 'Cancel Registration' : 'Back to Dashboard';

// 🪄 1. BULLETPROOF AUTO-CREATE MISSING COLUMNS (Fixes SQLSTATE 42S22)
if ($pdo) {
    $required_columns = [
        'bio' => 'TEXT DEFAULT NULL',
        'education_level' => 'VARCHAR(100) DEFAULT NULL',
        'institution' => 'VARCHAR(150) DEFAULT NULL',
        'field_of_study' => 'VARCHAR(150) DEFAULT NULL',
        'graduation_year' => 'VARCHAR(10) DEFAULT NULL',
        'expected_salary' => 'VARCHAR(50) DEFAULT NULL',
        'languages' => 'VARCHAR(255) DEFAULT NULL',
        'portfolio_url' => 'VARCHAR(255) DEFAULT NULL',
        'reference_contact' => 'TEXT DEFAULT NULL',
        'previous_company' => 'VARCHAR(150) DEFAULT NULL'
    ];

    foreach ($required_columns as $col => $def) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
            }
        } catch (PDOException $e) {}
    }
}

// Fetch User Current Data (If already logged in)
$user = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} else {
    // New user from Temp Session!
    $user = [
        'first_name' => $_SESSION['temp_reg']['first_name'] ?? '',
        'last_name'  => $_SESSION['temp_reg']['last_name'] ?? '',
        'email'      => $_SESSION['temp_reg']['email'] ?? '',
        'phone'      => $_SESSION['temp_reg']['phone'] ?? '',
    ];
}

// 🌟 2. PROCESS SAVING AND REDIRECTING 🌟
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ragaa User-ni guute qabachuu akka dogoggoraan yoo deebi'e hin banneef
    $user['first_name']       = trim($_POST['first_name'] ?? $user['first_name']);
    $user['last_name']        = trim($_POST['last_name'] ?? $user['last_name']);
    $user['work_position']    = trim($_POST['work_position'] ?? '');
    $user['years_experience'] = trim($_POST['years_experience'] ?? '');
    $user['gender']           = trim($_POST['gender'] ?? '');
    $user['birth_date']       = trim($_POST['birth_date'] ?? '');
    $user['marital_status']   = trim($_POST['marital_status'] ?? '');
    $user['city']             = trim($_POST['city'] ?? '');
    $user['subcity']          = trim($_POST['subcity'] ?? '');
    $user['woreda']           = trim($_POST['woreda'] ?? '');
    $user['alternate_phone']  = trim($_POST['alternate_phone'] ?? '');
    $user['skills_list']      = trim($_POST['skills_list'] ?? '');
    $user['bio']              = trim($_POST['bio'] ?? '');
    $user['education_level']  = trim($_POST['education_level'] ?? '');
    $user['institution']      = trim($_POST['institution'] ?? '');
    $user['field_of_study']   = trim($_POST['field_of_study'] ?? '');
    $user['graduation_year']  = trim($_POST['graduation_year'] ?? '');
    $user['expected_salary']  = trim($_POST['expected_salary'] ?? '');
    $user['languages']        = trim($_POST['languages'] ?? '');
    $user['portfolio_url']    = trim($_POST['portfolio_url'] ?? '');
    $user['previous_company'] = trim($_POST['previous_company'] ?? '');
    $user['reference_contact']= trim($_POST['reference_contact'] ?? '');

    $cv_path = $user['cv_path'] ?? null;
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'doc', 'docx'])) {
            $cv_name = time() . '_' . mt_rand(100,999) . '_cv.' . $ext;
            $upload_dir = 'uploads/cvs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $upload_dir . $cv_name)) {
                $cv_path = $upload_dir . $cv_name;
            }
        }
    }

    try {
        if (isset($_SESSION['temp_reg'])) {
            // 🌟 NEW USER: INSERT INTO DB NOW! 🌟
            $temp = $_SESSION['temp_reg'];
            $sql = "INSERT INTO users (
                        first_name, last_name, email, phone, password, is_verified, onboarding_completed,
                        work_position, years_experience, gender, birth_date, marital_status,
                        city, subcity, woreda, alternate_phone, skills_list, bio, education_level,
                        institution, field_of_study, graduation_year, expected_salary, languages,
                        portfolio_url, previous_company, reference_contact, cv_path
                    ) VALUES (?, ?, ?, ?, ?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user['first_name'], $user['last_name'], $temp['email'], $temp['phone'], $temp['password'],
                $user['work_position'], $user['years_experience'], $user['gender'], $user['birth_date'], $user['marital_status'],
                $user['city'], $user['subcity'], $user['woreda'], $user['alternate_phone'], $user['skills_list'], $user['bio'], $user['education_level'],
                $user['institution'], $user['field_of_study'], $user['graduation_year'], $user['expected_salary'], $user['languages'],
                $user['portfolio_url'], $user['previous_company'], $user['reference_contact'], $cv_path
            ]);
            
            $new_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $new_id;
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $temp['email'];
            $_SESSION['user_role'] = 'seeker';
            
            unset($_SESSION['temp_reg'], $_SESSION['otp_verified_temp']);

            if (isset($_SESSION['apply_redirect_job_id'])) {
                $redirect_job = $_SESSION['apply_redirect_job_id'];
                unset($_SESSION['apply_redirect_job_id']);
                header("Location: apply_action.php?job_id={$redirect_job}");
                exit();
            }
        } else {
            // 🌟 EXISTING USER: UPDATE DB 🌟
            $sql = "UPDATE users SET 
                    first_name = ?, last_name = ?,
                    work_position = ?, years_experience = ?, gender = ?, birth_date = ?, marital_status = ?,
                    city = ?, subcity = ?, woreda = ?, alternate_phone = ?, skills_list = ?,
                    bio = ?, education_level = ?, institution = ?, field_of_study = ?, graduation_year = ?,
                    expected_salary = ?, languages = ?, portfolio_url = ?, previous_company = ?, reference_contact = ?,
                    cv_path = ?, onboarding_completed = 1 WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user['first_name'], $user['last_name'],
                $user['work_position'], $user['years_experience'], $user['gender'], $user['birth_date'], $user['marital_status'],
                $user['city'], $user['subcity'], $user['woreda'], $user['alternate_phone'], $user['skills_list'],
                $user['bio'], $user['education_level'], $user['institution'], $user['field_of_study'], $user['graduation_year'],
                $user['expected_salary'], $user['languages'], $user['portfolio_url'], $user['previous_company'], $user['reference_contact'],
                $cv_path, $_SESSION['user_id']
            ]);
        }

        // 🌟 INSTANT REDIRECT TO DASHBOARD 🌟
        $_SESSION['onboarding_completed'] = 1;
        header("Location: candidate_dashboard.php?status=success");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
        $initial_step = 6; // Stay on the last step so the user can see the error and not lose data
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Comprehensive Candidate CV Builder — Infoplus</title>
    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'dark') { document.documentElement.classList.add('dark'); } 
            else { document.documentElement.classList.remove('dark'); }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        .dotted-card {
            background-color: rgba(255, 255, 255, 0.95);
            background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px);
            background-size: 14px 14px;
            backdrop-filter: blur(16px);
        }
        .dark .dotted-card {
            background-color: rgba(15, 23, 42, 0.95);
            background-image: radial-gradient(#334155 1.2px, transparent 1.2px);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 10px; }
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        .animate-float { animation: floatSlow 4s ease-in-out infinite; }
        
        /* Highlight input error style dynamically via class insertion */
        .input-error {
            border-color: #ef4444 !important;
            background-color: rgba(254, 226, 226, 0.5) !important;
        }
        .dark .input-error {
            background-color: rgba(153, 27, 27, 0.2) !important;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex items-center justify-center py-8 px-3 sm:px-6 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px] transition-colors duration-300" x-data="cvWizard()">

    <!-- 🌟 SMART BACK BUTTON (Floating Top Left) 🌟 -->
    <div class="fixed top-4 left-4 sm:top-8 sm:left-8 z-50">
        <a href="<?php echo $back_link; ?>" class="flex items-center gap-2 px-4 py-2.5 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-full shadow-md text-xs font-black text-slate-600 dark:text-slate-300 hover:text-teal-600 dark:hover:text-teal-400 hover:border-teal-500 transition-all group">
            <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span class="hidden sm:inline"><?php echo $back_text; ?></span>
            <span class="sm:hidden">Back</span>
        </a>
    </div>

    <div class="max-w-4xl w-full mx-auto my-auto relative z-10 pt-10 sm:pt-0">
        
        <!-- Header Branding & Navigation -->
        <div class="text-center mb-6 sm:mb-10">
            <div class="inline-flex items-center space-x-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-5 py-2.5 rounded-full shadow-lg mb-4 transition-colors">
                <img src="logo.png" onerror="this.src='https://ui-avatars.com/api/?name=I+P&background=0D9488&color=fff'" class="h-8 w-8 rounded-full border border-teal-500/40">
                <span class="text-lg font-black text-slate-900 dark:text-white">INFO<span class="text-teal-600 dark:text-teal-400">+</span></span>
                <span class="text-[10px] uppercase tracking-widest text-teal-600 dark:text-teal-400 font-bold border-l border-slate-300 dark:border-slate-700 pl-3">Professional CV Builder</span>
            </div>
            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight">Build Your Professional Profile</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-xl mx-auto font-medium">Complete this 6-step profile wizard to generate your digital CV and get matched with top recruiters across Ethiopia.</p>
        </div>

        <!-- 🌟 6-STEP WIZARD PROGRESS INDICATOR 🌟 -->
        <div class="mb-8 max-w-3xl mx-auto">
            <div class="grid grid-cols-6 gap-1 text-center text-[9px] sm:text-xs font-bold text-slate-400 mb-3">
                <span :class="step >= 1 ? 'text-teal-600 dark:text-teal-400' : ''" class="truncate transition-colors">1. Personal</span>
                <span :class="step >= 2 ? 'text-teal-600 dark:text-teal-400' : ''" class="truncate transition-colors">2. Career</span>
                <span :class="step >= 3 ? 'text-teal-600 dark:text-teal-400' : ''" class="truncate transition-colors">3. Education</span>
                <span :class="step >= 4 ? 'text-teal-600 dark:text-teal-400' : ''" class="truncate transition-colors">4. Skills</span>
                <span :class="step >= 5 ? 'text-teal-600 dark:text-teal-400' : ''" class="truncate transition-colors">5. Experience</span>
                <span :class="step >= 6 ? 'text-teal-600 dark:text-teal-400' : ''" class="truncate transition-colors">6. Resume</span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-full h-2.5 p-0.5 shadow-inner">
                <div class="bg-gradient-to-r from-teal-500 via-emerald-400 to-cyan-500 h-1.5 rounded-full transition-all duration-500 shadow-md shadow-teal-500/50"
                     :style="'width: ' + ((step / 6) * 100) + '%'"></div>
            </div>
        </div>

        <!-- Error Notification -->
        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 font-bold text-xs sm:text-sm text-center animate-pulse">
                ⚠️ <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- MAIN WIZARD CONTAINER CARD -->
        <div class="dotted-card border border-slate-200 dark:border-slate-800 rounded-[2.5rem] p-5 sm:p-10 shadow-2xl relative overflow-hidden transition-colors duration-300">
            
            <form action="onboarding.php" method="POST" enctype="multipart/form-data" id="cvWizardForm">
                
                <!-- ==================== STEP 1: PERSONAL ==================== -->
                <div x-show="step === 1" class="space-y-5 step-container">
                    <div class="text-center mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <span class="text-3xl mb-1 inline-block drop-shadow-md">👤</span>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Personal & Location Information</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Basic details to help recruiters identify you.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">First Name *</label>
                            <input type="text" name="first_name" x-model="formData.first_name" required class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition-all font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Last Name *</label>
                            <input type="text" name="last_name" x-model="formData.last_name" required class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition-all font-semibold">
                        </div>
                    </div>

                   

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Gender *</label>
                            <select name="gender" x-model="formData.gender" required class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Birth Date</label>
                            <input type="date" name="birth_date" x-model="formData.birth_date" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Marital Status</label>
                            <select name="marital_status" x-model="formData.marital_status" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">City / Region *</label>
                            <input type="text" name="city" x-model="formData.city" required placeholder="e.g. Addis Ababa" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Subcity / Zone</label>
                            <input type="text" name="subcity" x-model="formData.subcity" placeholder="e.g. Bole / Kirkos" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Woreda / Area</label>
                            <input type="text" name="woreda" x-model="formData.woreda" placeholder="e.g. Woreda 03" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                        </div>
                    </div>

                   <!-- 🌟 MAGIC: REGISTERED EMAIL & PHONE MOVED TO BOTTOM 🌟 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Registered Email</label>
                            <input type="email" x-model="formData.email" readonly class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none opacity-80 cursor-default font-bold transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Registered Phone</label>
                            <input type="tel" x-model="formData.phone" readonly class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none opacity-80 cursor-default font-bold transition-all">
                        </div>
                    </div>

                    <!-- 🌟 MAGIC VALIDATION ON NEXT 🌟 -->
                    <button type="button" @click="if(validateStep($el)) step = 2" class="w-full py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-sm transition-all shadow-lg shadow-teal-500/20 transform hover:-translate-y-0.5 cursor-pointer">
                        Continue: Career Objectives →
                    </button>
                </div>

                <!-- ==================== STEP 2: CAREER OBJECTIVE & BIO ==================== -->
                <div x-show="step === 2" x-cloak class="space-y-5 step-container">
                    <div class="text-center mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <span class="text-3xl mb-1 inline-block drop-shadow-md">💼</span>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Career Title & Professional Bio</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Define your primary target job position and summary.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] sm:text-xs font-black text-teal-600 dark:text-teal-400 uppercase mb-1.5">Primary Target Position / Job Title *</label>
                        <input type="text" name="work_position" x-model="formData.work_position" required placeholder="e.g. Senior Software Engineer / Sales Specialist" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-teal-500/30 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition-all font-bold">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Years of Experience *</label>
                            <select name="years_experience" x-model="formData.years_experience" required class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                                <option value="0-1 Year">Fresh Graduate (0-1 Year)</option>
                                <option value="1-3 Years">Junior Level (1-3 Years)</option>
                                <option value="3-5 Years">Mid Level (3-5 Years)</option>
                                <option value="5+ Years">Senior Level (5+ Years)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Expected Monthly Salary (ETB)</label>
                            <input type="text" name="expected_salary" x-model="formData.expected_salary" placeholder="e.g. 25,000 Birr/Mon" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Professional Bio / Summary *</label>
                        <textarea name="bio" x-model="formData.bio" rows="4" required placeholder="Write a short summary about your background, achievements, and career aspirations..." class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-medium leading-relaxed"></textarea>
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <button type="button" @click="step = 1" class="w-1/3 py-3.5 rounded-2xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition-all border border-slate-300 dark:border-slate-700">← Back</button>
                        <button type="button" @click="if(validateStep($el)) step = 3" class="w-2/3 py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-xs sm:text-sm transition-all shadow-lg shadow-teal-500/20 transform hover:-translate-y-0.5 cursor-pointer">Next: Education →</button>
                    </div>
                </div>

                <!-- ==================== STEP 3: EDUCATION ==================== -->
                <div x-show="step === 3" x-cloak class="space-y-5 step-container">
                    <div class="text-center mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <span class="text-3xl mb-1 inline-block drop-shadow-md">🎓</span>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Educational Background</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Add details of your highest education level and degrees.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Highest Education Level *</label>
                            <select name="education_level" x-model="formData.education_level" required class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                                <option value="High School / TVET">High School / TVET Certificate</option>
                                <option value="Diploma">Diploma / TVET Level IV</option>
                                <option value="Bachelor Degree">Bachelor's Degree (BSc/BA)</option>
                                <option value="Master Degree">Master's Degree (MSc/MA)</option>
                                <option value="Doctorate / PhD">Doctorate / PhD</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Graduation Year</label>
                            <input type="text" name="graduation_year" x-model="formData.graduation_year" placeholder="e.g. 2024" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">University / Institution Name *</label>
                            <input type="text" name="institution" x-model="formData.institution" required placeholder="e.g. Addis Ababa University" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                        </div>
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Field of Study / Major *</label>
                            <input type="text" name="field_of_study" x-model="formData.field_of_study" required placeholder="e.g. Computer Science, Accounting" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                        </div>
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <button type="button" @click="step = 2" class="w-1/3 py-3.5 rounded-2xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition-all border border-slate-300 dark:border-slate-700">← Back</button>
                        <button type="button" @click="if(validateStep($el)) step = 4" class="w-2/3 py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-xs sm:text-sm transition-all shadow-lg shadow-teal-500/20 transform hover:-translate-y-0.5 cursor-pointer">Next: Skills →</button>
                    </div>
                </div>

                <!-- ==================== STEP 4: SKILLS & LANGUAGES ==================== -->
                <div x-show="step === 4" x-cloak class="space-y-5 step-container">
                    <div class="text-center mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <span class="text-3xl mb-1 inline-block drop-shadow-md">🛠️</span>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Skills, Tools & Languages</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Recruiters use skills tags to filter candidate profiles.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] sm:text-xs font-black text-teal-600 dark:text-teal-400 uppercase mb-1.5">Top Technical & Professional Skills (Comma separated) *</label>
                        <input type="text" name="skills_list" x-model="formData.skills_list" required placeholder="e.g. PHP, Customer Support, Financial Analysis, AutoCAD" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-teal-500/30 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition-all font-bold">
                    </div>

                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Languages Spoken (Comma separated)</label>
                        <input type="text" name="languages" x-model="formData.languages" placeholder="e.g. Amharic (Fluent), Afaan Oromoo (Native), English (Professional)" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                    </div>

                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Portfolio / LinkedIn / Website Link (Optional)</label>
                        <input type="url" name="portfolio_url" x-model="formData.portfolio_url" placeholder="https://linkedin.com/in/yourprofile" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <button type="button" @click="step = 3" class="w-1/3 py-3.5 rounded-2xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition-all border border-slate-300 dark:border-slate-700">← Back</button>
                        <button type="button" @click="if(validateStep($el)) step = 5" class="w-2/3 py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-xs sm:text-sm transition-all shadow-lg shadow-teal-500/20 transform hover:-translate-y-0.5 cursor-pointer">Next: Work Experience →</button>
                    </div>
                </div>

                <!-- ==================== STEP 5: WORK EXPERIENCE ==================== -->
                <div x-show="step === 5" x-cloak class="space-y-5 step-container">
                    <div class="text-center mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <span class="text-3xl mb-1 inline-block drop-shadow-md">📂</span>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Work Experience & References</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Tell us about your previous employment history.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Most Recent Previous Employer / Company</label>
                        <input type="text" name="previous_company" x-model="formData.previous_company" placeholder="e.g. Dashen Bank PLC / Freelance" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-semibold">
                    </div>

                    <div>
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">Reference Person Contact Details (Optional)</label>
                        <textarea name="reference_contact" x-model="formData.reference_contact" rows="3" placeholder="e.g. Ato Kebede (Manager) - Phone: 0911223344" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm outline-none focus:border-teal-500 transition-all font-medium"></textarea>
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <button type="button" @click="step = 4" class="w-1/3 py-3.5 rounded-2xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition-all border border-slate-300 dark:border-slate-700">← Back</button>
                        <button type="button" @click="if(validateStep($el)) step = 6" class="w-2/3 py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-xs sm:text-sm transition-all shadow-lg shadow-teal-500/20 transform hover:-translate-y-0.5 cursor-pointer">Next: Resume & Review →</button>
                    </div>
                </div>

                <!-- ==================== STEP 6: RESUME UPLOAD & SUBMIT ==================== -->
                <div x-show="step === 6" x-cloak class="space-y-6">
                    <div class="text-center mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <span class="text-3xl mb-1 inline-block drop-shadow-md">📄</span>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white">Upload Resume & Final Review</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Review your generated CV profile and upload your official PDF document.</p>
                    </div>

                    <!-- Upload PDF Box -->
                    <div>
                        <label class="block text-[10px] sm:text-xs font-black text-teal-600 dark:text-teal-400 uppercase mb-1.5">Upload PDF/DOC Resume (Optional)</label>
                        <div class="border-2 border-dashed border-teal-500/50 rounded-2xl p-6 text-center hover:bg-teal-50 dark:hover:bg-teal-900/10 transition cursor-pointer relative bg-slate-50 dark:bg-slate-900/40 group">
                            <input type="file" name="cv_file" accept=".pdf,.doc,.docx" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="text-3xl mb-2 group-hover:scale-110 transition-transform">📁</div>
                            <p class="text-xs font-black text-teal-600 dark:text-teal-400">Click or Drag & Drop Resume File Here</p>
                            <p class="text-[10px] text-slate-500 mt-1 font-semibold">Supported formats: PDF, DOC, DOCX (Max 5MB)</p>
                        </div>
                    </div>

                    <!-- 🌟 LIVE DIGITAL CV PREVIEW CARD 🌟 -->
                    <div class="bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 rounded-2xl p-6 text-left relative overflow-hidden shadow-lg">
                        <div class="flex justify-between items-start mb-4 border-b border-slate-200 dark:border-slate-800 pb-3">
                            <div>
                                <h4 class="font-black text-lg text-slate-900 dark:text-white">
                                    <span x-text="formData.first_name || 'First'"></span> <span x-text="formData.last_name || 'Last'"></span>
                                </h4>
                                <p class="text-xs text-teal-600 dark:text-teal-400 font-bold" x-text="formData.work_position || 'Target Profession'"></p>
                            </div>
                            <span class="px-3 py-1 bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 text-[10px] font-black rounded-full uppercase shadow-sm">LIVE PREVIEW</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs text-slate-600 dark:text-slate-300 font-medium">
                            <div><strong class="text-slate-800 dark:text-slate-400">Location:</strong> <span x-text="formData.city || 'Ethiopia'"></span></div>
                            <div><strong class="text-slate-800 dark:text-slate-400">Experience:</strong> <span x-text="formData.years_experience || 'Fresh'"></span></div>
                            <div><strong class="text-slate-800 dark:text-slate-400">Education:</strong> <span x-text="formData.education_level || 'Not set'"></span></div>
                            <div><strong class="text-slate-800 dark:text-slate-400">Expected Salary:</strong> <span x-text="formData.expected_salary || 'Negotiable'"></span></div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800">
                            <strong class="text-[10px] text-slate-500 dark:text-slate-400 block mb-1 uppercase tracking-wider">Top Skills:</strong>
                            <p class="text-xs text-teal-700 dark:text-teal-300 font-bold leading-relaxed" x-text="formData.skills_list || 'No skills entered'"></p>
                        </div>
                    </div>

                   <div class="flex space-x-3 pt-2">
                        <button type="button" @click="step = 5" class="w-1/3 py-4 rounded-2xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm transition-all border border-slate-300 dark:border-slate-700">← Back</button>
                        
                        <!-- 🌟 FINAL SUBMIT BUTTON (FIXED: Forces Instant Submission & Redirect) 🌟 -->
                        <button type="button" onclick="this.innerHTML='<span class=\'animate-spin mr-2\'>🔄</span> Saving Profile...'; this.style.opacity='0.8'; this.disabled=true; document.getElementById('cvWizardForm').submit();" class="w-2/3 py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm transition-all shadow-xl shadow-emerald-600/30 cursor-pointer transform hover:-translate-y-0.5">
                            Complete & Save Full Profile 🚀
                        </button>
                    </div>
                </div>

            </form>

        </div>

        <div class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400 font-medium pb-8">
            Need help? Call hotline <strong class="text-teal-600 dark:text-teal-400">6781</strong> for phone-assisted profile registration.
        </div>

    </div>

    <!-- AlpineJS Data State Logic -->
    <script>
       function cvWizard() {
            return {
                step: <?php echo $initial_step; ?>,
                formData: {
                    first_name: '<?php echo addslashes($user['first_name'] ?? ''); ?>',
                    last_name: '<?php echo addslashes($user['last_name'] ?? ''); ?>',
                    email: '<?php echo addslashes($user['email'] ?? ''); ?>',
                    phone: '<?php echo addslashes($user['phone'] ?? ''); ?>',
                    gender: '<?php echo addslashes($user['gender'] ?? 'Male'); ?>',
                    birth_date: '<?php echo addslashes($user['birth_date'] ?? ''); ?>',
                    marital_status: '<?php echo addslashes($user['marital_status'] ?? 'Single'); ?>',
                    city: '<?php echo addslashes($user['city'] ?? ''); ?>',
                    subcity: '<?php echo addslashes($user['subcity'] ?? ''); ?>',
                    woreda: '<?php echo addslashes($user['woreda'] ?? ''); ?>',
                    alternate_phone: '<?php echo addslashes($user['alternate_phone'] ?? ''); ?>',
                    work_position: '<?php echo addslashes($user['work_position'] ?? ''); ?>',
                    years_experience: '<?php echo addslashes($user['years_experience'] ?? '0-1 Year'); ?>',
                    expected_salary: '<?php echo addslashes($user['expected_salary'] ?? ''); ?>',
                    bio: '<?php echo addslashes($user['bio'] ?? ''); ?>',
                    education_level: '<?php echo addslashes($user['education_level'] ?? 'Bachelor Degree'); ?>',
                    institution: '<?php echo addslashes($user['institution'] ?? ''); ?>',
                    field_of_study: '<?php echo addslashes($user['field_of_study'] ?? ''); ?>',
                    graduation_year: '<?php echo addslashes($user['graduation_year'] ?? ''); ?>',
                    skills_list: '<?php echo addslashes($user['skills_list'] ?? ''); ?>',
                    languages: '<?php echo addslashes($user['languages'] ?? ''); ?>',
                    portfolio_url: '<?php echo addslashes($user['portfolio_url'] ?? ''); ?>',
                    previous_company: '<?php echo addslashes($user['previous_company'] ?? ''); ?>',
                    reference_contact: '<?php echo addslashes($user['reference_contact'] ?? ''); ?>'
                },
                // 🪄 MAGIC: Step Validation with Red Border Highlight!
                validateStep(btnElement) {
                    let container = btnElement.closest('.step-container');
                    let inputs = container.querySelectorAll('input[required], select[required], textarea[required]');
                    let isValid = true;
                    
                    inputs.forEach(inp => {
                        if (!inp.value.trim()) {
                            inp.classList.add('input-error');
                            isValid = false;
                        } else {
                            inp.classList.remove('input-error');
                        }
                        
                        // Remove red border as soon as user types
                        inp.addEventListener('input', function() {
                             this.classList.remove('input-error');
                        }, { once: true });
                    });
                    
                    if (!isValid) {
                        // Shake effect for visual feedback
                        container.classList.add('animate-pulse');
                        setTimeout(() => container.classList.remove('animate-pulse'), 500);
                    }
                    return isValid;
                }
            };
        }
    </script>

</body>
</html>