<?php
// signup.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php'; 
require_once __DIR__ . '/includes/emailmailer.php'; 

$error_msg = "";
$success_msg = "";
$errors = []; // 🌟 MAGIC: Saanduqa kamtu akka dogoggore qabachuuf 🌟

$initial_step = 1;
$initial_role = '';

// 🌟 MAGIC: Ragaa hunda qabachuu (Password dabalatee) akka hin banneef 🌟
$fname_val = $_POST['first_name'] ?? '';
$lname_val = $_POST['last_name'] ?? '';
$email_val = $_POST['email'] ?? '';
$phone_val = $_POST['phone'] ?? '';
$cname_val = $_POST['company_name'] ?? '';
$cperson_val = $_POST['contact_name'] ?? '';
$password_val = $_POST['password'] ?? '';
$confirm_password_val = $_POST['confirm_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $initial_step = 2; // Dogoggorri yoo uumame Step 2 irra tursiisa
    $initial_role = $role;

if ($role === 'seeker') {
        if (empty($fname_val) || empty($lname_val) || empty($email_val) || empty($phone_val) || empty($password_val)) {
            $error_msg = "Please fill out all required fields.";
        } elseif ($password_val !== $confirm_password_val) {
            $error_msg = "Passwords do not match. Please try again.";
            $errors['password'] = true;
        } else {
            if(!isset($pdo) || $pdo === null) {
                $error_msg = "Database connection error!";
            } else {
                // 🪄 GLOBAL UNIQUE IDENTITY CHECK
                $stmt = $pdo->prepare("
                    SELECT email, phone FROM admins WHERE email = ? OR phone = ?
                    UNION SELECT email, phone FROM users WHERE email = ? OR phone = ?
                    UNION SELECT email, phone FROM employers WHERE email = ? OR phone = ?
                ");
                $stmt->execute([$email_val, $phone_val, $email_val, $phone_val, $email_val, $phone_val]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    if (strtolower($existing['email']) === strtolower($email_val)) {
                        $error_msg = "The Email Address ({$email_val}) is already registered. <a href='login.php' class='underline font-black text-rose-800 ml-1'>Log in here ↗</a>";
                        $errors['email'] = true;
                    } else {
                        $error_msg = "The Phone Number ({$phone_val}) is already registered. <a href='login.php' class='underline font-black text-rose-800 ml-1'>Log in here ↗</a>";
                        $errors['phone'] = true;
                    }
                } else {
                    // 🌟 MAGIC: HANGAMAA DATABASE KEESSATTI HIN GALCHINU! (Session'tti qabanna) 🌟
                    $hashed_pw = password_hash($password_val, PASSWORD_DEFAULT);
                    $otp_code = sprintf("%06d", mt_rand(100000, 999999));

                    $_SESSION['temp_reg'] = [
                        'role' => 'seeker',
                        'first_name' => $fname_val,
                        'last_name' => $lname_val,
                        'email' => $email_val,
                        'phone' => $phone_val,
                        'password' => $hashed_pw,
                        'otp_code' => $otp_code
                    ];

                    $mail_status = sendInfoplusOTP($email_val, $fname_val, $otp_code);

                    if($mail_status === true) {
                        $_SESSION['pending_user_email'] = $email_val;
                        $_SESSION['pending_role'] = 'seeker';
                        $_SESSION['pending_2fa_method'] = 'email';
                        $_SESSION['pending_2fa_dest'] = $email_val;
                        header("Location: verify.php");
                        exit();
                    } else {
                        $error_msg = "Failed to send OTP email! Error: " . htmlspecialchars($mail_status);
                        unset($_SESSION['temp_reg']);
                    }
                }
            }
        }

    } else if ($role === 'employer') {
        if (empty($cname_val) || empty($cperson_val) || empty($email_val) || empty($phone_val) || empty($password_val)) {
            $error_msg = "Please fill out all company details.";
        } elseif ($password_val !== $confirm_password_val) {
            $error_msg = "Passwords do not match. Please try again.";
            $errors['password'] = true;
        } else {
            if(!isset($pdo) || $pdo === null) {
                $error_msg = "Database connection error!";
            } else {
                $stmt = $pdo->prepare("
                    SELECT email, phone FROM admins WHERE email = ? OR phone = ?
                    UNION SELECT email, phone FROM users WHERE email = ? OR phone = ?
                    UNION SELECT email, phone FROM employers WHERE email = ? OR phone = ?
                ");
                $stmt->execute([$email_val, $phone_val, $email_val, $phone_val, $email_val, $phone_val]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    if (strtolower($existing['email']) === strtolower($email_val)) {
                        $error_msg = "This Company Email ({$email_val}) is already registered. <a href='login.php' class='underline font-black text-rose-800 ml-1'>Log in here ↗</a>";
                        $errors['email'] = true;
                    } else {
                        $error_msg = "This Company Phone Number ({$phone_val}) is already registered. <a href='login.php' class='underline font-black text-rose-800 ml-1'>Log in here ↗</a>";
                        $errors['phone'] = true;
                    }
                } else {
                    // 🌟 MAGIC: HANGAMAA DATABASE KEESSATTI HIN GALCHINU!
                    $hashed_pw = password_hash($password_val, PASSWORD_DEFAULT);
                    $otp_code = sprintf("%06d", mt_rand(100000, 999999));

                    $_SESSION['temp_reg'] = [
                        'role' => 'employer',
                        'company_name' => $cname_val,
                        'contact_name' => $cperson_val,
                        'email' => $email_val,
                        'phone' => $phone_val,
                        'password' => $hashed_pw,
                        'otp_code' => $otp_code
                    ];
                    
                    $mail_status = sendInfoplusOTP($email_val, $cname_val, $otp_code);

                    if($mail_status === true) {
                        $_SESSION['pending_user_email'] = $email_val;
                        $_SESSION['pending_role'] = 'employer';
                        $_SESSION['pending_2fa_method'] = 'email';
                        $_SESSION['pending_2fa_dest'] = $email_val;
                        header("Location: verify.php");
                        exit();
                    } else {
                        $error_msg = "Failed to send OTP! Error: " . htmlspecialchars($mail_status);
                        unset($_SESSION['temp_reg']);
                    }
                }
            }
        }
    }
} else {
    $preset_type = $_GET['type'] ?? '';
    if (!empty($preset_type)) {
        $initial_step = 2;
        $initial_role = ($preset_type === 'employer') ? 'employer' : 'seeker';
    }
}

// 🌟 2. INCLUDE HEADER ONLY FOR DISPLAYING PAGE 🌟
include_once __DIR__ . '/includes/header.php';
?>
<!-- 🌟 CSS & JS for International Phone Flags 🌟 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/css/intlTelInput.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/intlTelInput.min.js"></script>

<style>
    /* Full width fix for Phone input wrapper */
    .iti { width: 100%; display: block; }
    /* Dark mode styling for country dropdown list */
    .dark .iti__country-list { background-color: #1e293b; border-color: #334155; color: #fff; z-index: 50; }
    .dark .iti__country-list .iti__country.iti__highlight { background-color: #0f172a; }
</style>
<!-- AlpineJS State -->
<div class="py-6 sm:py-10 relative overflow-hidden" x-data="{ step: <?php echo $initial_step; ?>, role: '<?php echo $initial_role; ?>', agreed: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- STEP 1: CHOICE SELECTION -->
        <div x-show="step === 1" class="max-w-3xl mx-auto">
            <div class="dotted-card bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-12 rounded-[2.5rem] shadow-2xl text-center">
                
                <div class="flex justify-center mb-6 relative group">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 sm:w-48 sm:h-48 bg-gradient-to-tr from-teal-500 via-emerald-400 to-amber-500 rounded-full blur-2xl opacity-60 animate-pulse pointer-events-none transition-all duration-500 group-hover:opacity-100 group-hover:scale-110"></div>
                    <img src="sign.png" onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/job-search-5353005-4468699.png'" alt="Sign Up Choice" class="relative z-10 w-full h-auto max-h-[160px] sm:max-h-[220px] object-contain filter drop-shadow-2xl animate-float mix-blend-multiply dark:mix-blend-normal">
                </div>

                <p class="text-sm sm:text-xl font-black text-slate-800 dark:text-slate-200 mb-6 sm:mb-8">
                    Please choose one of these to continue.
                </p>
<!-- CLEAR ROLES (Taller Boxy Cards on Mobile, Side-by-Side on PC) -->
                <div class="grid grid-cols-2 gap-3 sm:gap-6 mb-6 sm:mb-8">
                    
                    <button type="button" @click="role = 'seeker'" 
                            :class="role === 'seeker' ? 'bg-gradient-to-r from-teal-600 to-emerald-500 text-white border-teal-500 shadow-xl scale-[1.02]' : 'bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-teal-500 hover:shadow-md'" 
                            class="p-4 sm:p-6 rounded-2xl sm:rounded-3xl border-2 transition-all duration-300 flex flex-col sm:flex-row items-center justify-center sm:justify-start text-center sm:text-left cursor-pointer min-h-[130px] sm:min-h-0">
                        <div class="text-3xl sm:text-4xl bg-white/20 p-2 rounded-xl sm:rounded-2xl flex-shrink-0 mb-2 sm:mb-0 sm:mr-4">💼</div>
                        <div class="min-w-0">
                            <span class="text-xs sm:text-lg font-black block leading-tight">I'm looking a job</span>
                            <span class="text-[9px] sm:text-xs opacity-80 font-bold block mt-1 leading-tight">Create candidate profile</span>
                        </div>
                    </button>

                    <button type="button" @click="role = 'employer'" 
                            :class="role === 'employer' ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white border-amber-500 shadow-xl scale-[1.02]' : 'bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-amber-500 hover:shadow-md'" 
                            class="p-4 sm:p-6 rounded-2xl sm:rounded-3xl border-2 transition-all duration-300 flex flex-col sm:flex-row items-center justify-center sm:justify-start text-center sm:text-left cursor-pointer min-h-[130px] sm:min-h-0">
                        <div class="text-3xl sm:text-4xl bg-white/20 p-2 rounded-xl sm:rounded-2xl flex-shrink-0 mb-2 sm:mb-0 sm:mr-4">🏢</div>
                        <div class="min-w-0">
                            <span class="text-xs sm:text-lg font-black block leading-tight">I'm looking to hire</span>
                            <span class="text-[9px] sm:text-xs opacity-80 font-bold block mt-1 leading-tight">Enterprise / Employer</span>
                        </div>
                    </button>
                    
                </div>
                <div class="flex items-center justify-center space-x-2 sm:space-x-3 mb-6 sm:mb-8">
                    <input type="checkbox" id="terms" x-model="agreed" class="w-4 h-4 sm:w-5 sm:h-5 text-teal-600 rounded cursor-pointer">
                    <label for="terms" class="text-[10px] sm:text-sm text-slate-600 dark:text-slate-400 cursor-pointer">
                        I agree to the <a href="terms.php" class="text-teal-600 dark:text-teal-400 underline font-bold">Terms and Conditions</a>
                    </label>
                </div>

                <button type="button" @click="step = 2" :disabled="!agreed || role === ''" :class="(!agreed || role === '') ? 'opacity-50 cursor-not-allowed bg-slate-300 dark:bg-slate-700 text-slate-500' : 'bg-teal-600 hover:bg-teal-500 text-white shadow-xl shadow-teal-500/30 cursor-pointer'" class="w-full max-w-md py-3.5 sm:py-4 rounded-xl sm:rounded-2xl font-extrabold text-sm sm:text-base transition-all duration-300">
                    Continue →
                </button>
            </div>
        </div>
<!-- 🌟 STEP 2: SPLIT SCREEN FORMS 🌟 -->
        <div x-show="step === 2" class="max-w-6xl mx-auto" x-cloak>
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-0 overflow-hidden rounded-3xl sm:rounded-[2.5rem] shadow-2xl border border-slate-200/80 dark:border-slate-800">
                
                <!-- LEFT COLUMN: BANNER (Moves to BOTTOM on Mobile via order-2, Left on PC via lg:order-1) -->
                <div class="lg:col-span-5 order-2 lg:order-1 text-white p-6 sm:p-12 flex flex-col justify-between items-center text-center relative overflow-hidden transition-colors duration-500" :class="role === 'seeker' ? 'bg-teal-600' : 'bg-amber-600'">
                    <div class="flex flex-col items-center relative z-10">
                        <img src="logo.png" alt="Logo" class="h-12 w-12 sm:h-16 sm:w-16 mb-2 rounded-full border-2 border-white/40 shadow-lg object-cover bg-white">
                        <span class="text-xl sm:text-2xl font-black tracking-tight">INFO<span class="text-slate-900">+</span></span>
                        <span class="text-[8px] sm:text-[10px] uppercase tracking-widest opacity-90 font-bold" x-text="role === 'seeker' ? 'Candidate Registration' : 'Employer Registration'"></span>
                    </div>

                    <div class="my-6 sm:my-8 relative z-10 animate-float w-full flex justify-center">
                        <img src="signup.png" onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/job-registration-5353004-4468698.png'" class="w-full h-auto max-h-[220px] sm:max-h-[280px] object-contain filter drop-shadow-2xl mix-blend-multiply dark:mix-blend-normal">
                    </div>

                    <div class="text-center space-y-2 sm:space-y-3 relative z-10">
                        <h2 class="text-lg sm:text-2xl font-black uppercase tracking-wide text-white" x-text="role === 'seeker' ? 'YOUR DREAM JOBS ARE WAITING' : 'FIND THE BEST TALENT'"></h2>
                        <div class="w-12 sm:w-16 h-1 bg-white/50 mx-auto rounded-full"></div>
                        <p class="text-[10px] sm:text-xs leading-relaxed opacity-90 font-medium max-w-sm" x-text="role === 'seeker' ? 'Enhance your professional trajectory with Infoplus by applying to top verified jobs.' : 'Post your vacancies and connect with thousands of active job seekers.'"></p>
                    </div>
                </div>

                <!-- RIGHT COLUMN: CLEAN SIGNUP FORM (Moves to TOP on Mobile via order-1, Right on PC via lg:order-2) -->
                <div class="lg:col-span-7 order-1 lg:order-2 bg-white dark:bg-slate-900 p-6 sm:p-12 text-left flex flex-col justify-between">
                    
                    <div>
                        <!-- Header Back & Title -->
                        <div class="flex items-center justify-between mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
                            <!-- 🪄 MAGIC: Reset errors and step correctly -->
                            <button type="button" @click="step = 1; '<?php $error_msg=''; ?>'" class="text-[10px] sm:text-xs font-bold text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1.5 cursor-pointer bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg transition-colors">
                                <span>←</span> <span>Back</span>
                            </button>
                            <div class="text-right">
                                <span class="text-base sm:text-xl font-black text-slate-900 dark:text-white block">INFO<span class="text-teal-600">+</span></span>
                                <span class="text-[9px] sm:text-xs text-slate-400 block -mt-1 uppercase tracking-wider" x-text="role === 'seeker' ? 'Candidate Portal' : 'Enterprise Portal'"></span>
                            </div>
                        </div>

                        <!-- 🌟 INLINE ERROR NOTIFICATION (Stays on Step 2) 🌟 -->
                        <?php if (!empty($error_msg)): ?>
                            <div class="mb-4 sm:mb-6 p-3 sm:p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 text-xs sm:text-sm font-bold flex items-start gap-2 animate-pulse">
                                <span class="text-lg">⚠️</span> <span><?php echo $error_msg; ?></span>
                            </div>
                        <?php endif; ?>
<!-- FORM START -->
                        <form action="signup.php" method="POST" onsubmit="triggerUssdLoader('Creating account & sending OTP... *6781#')" class="space-y-4">
                            <input type="hidden" name="role" :value="role">

                            <!-- =================== CANDIDATE FORM FIELDS =================== -->
                            <template x-if="role === 'seeker'">
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">First Name *</label>
                                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($fname_val); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Last Name *</label>
                                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($lname_val); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                                        </div>
                                    </div>
                                    
                                    <!-- 🌟 DYNAMIC EMAIL ERROR HIGHLIGHT 🌟 -->
                                    <div>
                                        <label class="block text-[10px] sm:text-xs font-bold <?php echo isset($errors['email']) ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'; ?> mb-1.5">Email Address *</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($email_val); ?>" required class="w-full px-4 py-3 rounded-xl border <?php echo isset($errors['email']) ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/10 text-rose-600 ring-2 ring-rose-500/30' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-teal-500'; ?> text-sm outline-none transition-all">
                                    </div>
                                    
                                    <!-- 🌟 DYNAMIC PHONE ERROR HIGHLIGHT 🌟 -->
                                    <div x-init="setTimeout(() => { 
                                        const input = $el.querySelector('.phone-input');
                                        const hidden = $el.querySelector('.phone-hidden');
                                        const iti = window.intlTelInput(input, {
                                            initialCountry: 'et',
                                            nationalMode: false,
                                            autoInsertDialCode: true,
                                            separateDialCode: false,
                                            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/utils.js'
                                        });
                                        if(!input.value) input.value = '+' + iti.getSelectedCountryData().dialCode + ' ';
                                        input.addEventListener('countrychange', () => { input.value = '+' + iti.getSelectedCountryData().dialCode + ' '; input.focus(); });
                                        $el.closest('form').addEventListener('submit', () => { hidden.value = iti.getNumber(); });
                                    }, 50)">
                                        <label class="block text-[10px] sm:text-xs font-bold <?php echo isset($errors['phone']) ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'; ?> mb-1.5">Phone Number *</label>
                                        <input type="tel" value="<?php echo htmlspecialchars($phone_val); ?>" required class="phone-input w-full px-4 py-3 rounded-xl border <?php echo isset($errors['phone']) ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/10 text-rose-600 ring-2 ring-rose-500/30' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-teal-500'; ?> text-sm outline-none transition-all">
                                        <input type="hidden" name="phone" class="phone-hidden" value="<?php echo htmlspecialchars($phone_val); ?>">
                                    </div>

                                    <!-- 🌟 PASSWORDS PRESERVED 🌟 -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div x-data="{ show: false }" class="relative">
                                            <label class="block text-[10px] sm:text-xs font-bold <?php echo isset($errors['password']) ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'; ?> mb-1.5">Password *</label>
                                            <input :type="show ? 'text' : 'password'" name="password" value="<?php echo htmlspecialchars($password_val); ?>" required class="w-full px-4 py-3 rounded-xl border <?php echo isset($errors['password']) ? 'border-rose-500 bg-rose-50 ring-2 ring-rose-500/30' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800'; ?> text-slate-900 dark:text-white text-sm outline-none transition-all pr-10">
                                            <button type="button" @click="show = !show" class="absolute right-3 top-[34px] text-slate-400 hover:text-teal-600 focus:outline-none">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            </button>
                                        </div>
                                        <div x-data="{ show: false }" class="relative">
                                            <label class="block text-[10px] sm:text-xs font-bold <?php echo isset($errors['password']) ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'; ?> mb-1.5">Confirm Password *</label>
                                            <input :type="show ? 'text' : 'password'" name="confirm_password" value="<?php echo htmlspecialchars($confirm_password_val); ?>" required class="w-full px-4 py-3 rounded-xl border <?php echo isset($errors['password']) ? 'border-rose-500 bg-rose-50 ring-2 ring-rose-500/30' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800'; ?> text-slate-900 dark:text-white text-sm outline-none transition-all pr-10">
                                            <button type="button" @click="show = !show" class="absolute right-3 top-[34px] text-slate-400 hover:text-teal-600 focus:outline-none">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- =================== EMPLOYER FORM FIELDS =================== -->
                            <template x-if="role === 'employer'">
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Company Name *</label>
                                            <input type="text" name="company_name" value="<?php echo htmlspecialchars($cname_val); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] sm:text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Contact Person *</label>
                                            <input type="text" name="contact_name" value="<?php echo htmlspecialchars($cperson_val); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500 transition-all">
                                        </div>
                                    </div>
                                    
                                    <!-- 🌟 DYNAMIC EMAIL ERROR HIGHLIGHT (EMPLOYER) 🌟 -->
                                    <div>
                                        <label class="block text-[10px] sm:text-xs font-bold <?php echo isset($errors['email']) ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'; ?> mb-1.5">Corporate Email *</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($email_val); ?>" required class="w-full px-4 py-3 rounded-xl border <?php echo isset($errors['email']) ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/10 text-rose-600 ring-2 ring-rose-500/30' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-amber-500'; ?> text-sm outline-none transition-all">
                                    </div>
                                    
                                    <!-- 🌟 DYNAMIC PHONE ERROR HIGHLIGHT (EMPLOYER) 🌟 -->
                                    <div x-init="setTimeout(() => { 
                                        const input = $el.querySelector('.phone-input');
                                        const hidden = $el.querySelector('.phone-hidden');
                                        if(!input) return;
                                        const iti = window.intlTelInput(input, {
                                            initialCountry: 'et',
                                            nationalMode: false,
                                            autoInsertDialCode: true,
                                            separateDialCode: false,
                                            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/utils.js'
                                        });
                                        if(!input.value) input.value = '+' + iti.getSelectedCountryData().dialCode + ' ';
                                        input.addEventListener('countrychange', () => { input.value = '+' + iti.getSelectedCountryData().dialCode + ' '; input.focus(); });
                                        $el.closest('form').addEventListener('submit', () => { hidden.value = iti.getNumber(); });
                                    }, 50)">
                                        <label class="block text-[10px] sm:text-xs font-bold <?php echo isset($errors['phone']) ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'; ?> mb-1.5">Phone Number *</label>
                                        <input type="tel" value="<?php echo htmlspecialchars($phone_val); ?>" required class="phone-input w-full px-4 py-3 rounded-xl border <?php echo isset($errors['phone']) ? 'border-rose-500 bg-rose-50 dark:bg-rose-900/10 text-rose-600 ring-2 ring-rose-500/30' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-amber-500'; ?> text-sm outline-none transition-all">
                                        <input type="hidden" name="phone" class="phone-hidden" value="<?php echo htmlspecialchars($phone_val); ?>">
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div x-data="{ show: false }" class="relative">
                                            <label class="block text-[10px] sm:text-xs font-bold <?php echo isset($errors['password']) ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'; ?> mb-1.5">Password *</label>
                                            <input :type="show ? 'text' : 'password'" name="password" value="<?php echo htmlspecialchars($password_val); ?>" required class="w-full px-4 py-3 rounded-xl border <?php echo isset($errors['password']) ? 'border-rose-500 bg-rose-50 ring-2 ring-rose-500/30' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800'; ?> text-slate-900 dark:text-white text-sm outline-none transition-all pr-10">
                                            <button type="button" @click="show = !show" class="absolute right-3 top-[34px] text-slate-400 hover:text-amber-600 focus:outline-none">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                            </button>
                                        </div>
                                        <div x-data="{ show: false }" class="relative">
                                            <label class="block text-[10px] sm:text-xs font-bold <?php echo isset($errors['password']) ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'; ?> mb-1.5">Confirm Password *</label>
                                            <input :type="show ? 'text' : 'password'" name="confirm_password" value="<?php echo htmlspecialchars($confirm_password_val); ?>" required class="w-full px-4 py-3 rounded-xl border <?php echo isset($errors['password']) ? 'border-rose-500 bg-rose-50 ring-2 ring-rose-500/30' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800'; ?> text-slate-900 dark:text-white text-sm outline-none transition-all pr-10">
                                            <button type="button" @click="show = !show" class="absolute right-3 top-[34px] text-slate-400 hover:text-amber-600 focus:outline-none">
                                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <button type="submit" 
                                    :class="role === 'seeker' ? 'bg-teal-600 hover:bg-teal-500 shadow-teal-500/20' : 'bg-amber-600 hover:bg-amber-500 shadow-amber-500/20'"
                                    class="w-full py-3.5 sm:py-4 rounded-xl sm:rounded-2xl text-white font-extrabold text-sm sm:text-base transition-all shadow-lg mt-6 cursor-pointer">
                                Create My Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

  </div>
</div>
<!-- 🌟 MAGIC CLEAN SPINNER MODAL (No Text, Spinning Logo & Glowing Rings - Matches Login) 🌟 -->
<div id="ussdOverlay" class="fixed inset-0 z-[100] bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300 hidden select-none">
    
    <!-- Center Animation Container -->
    <div class="relative w-32 h-32 sm:w-40 sm:h-40 flex items-center justify-center">
        
        <!-- Outer Glowing Aura -->
        <div class="absolute inset-0 bg-gradient-to-tr from-teal-500/30 via-emerald-500/20 to-amber-500/20 rounded-full blur-2xl animate-pulse pointer-events-none"></div>

        <!-- Ring 1: Fast Spin Clockwise -->
        <div class="absolute inset-0 rounded-full border-[3px] border-slate-500/10 border-t-teal-500 shadow-[0_0_15px_rgba(20,184,166,0.6)] animate-spin pointer-events-none"></div>
        
        <!-- Ring 2: Medium Spin Counter-Clockwise -->
        <div class="absolute inset-2 sm:inset-3 rounded-full border-[3px] border-slate-500/10 border-b-amber-500 shadow-[0_0_15px_rgba(245,158,11,0.6)] animate-[spin_1.5s_linear_infinite_reverse] pointer-events-none"></div>
        
        <!-- Center: Spinning Infoplus Logo -->
        <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 p-1.5 flex items-center justify-center shadow-2xl z-10 animate-[spin_4s_linear_infinite]">
            <img src="logo.png" onerror="this.src='https://ui-avatars.com/api/?name=I+P&background=0D9488&color=fff'" alt="Loading..." class="w-full h-full object-cover rounded-full">
        </div>

    </div>

</div>

<script>
// Clean Trigger Function for Spinner Modal
function triggerUssdLoader(text) {
    const overlay = document.getElementById('ussdOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
        overlay.style.display = 'flex';
    }
}

// Initialize Phone Input for dynamically rendered AlpineJS templates
function initPhoneInput() {
    const phoneInputs = document.querySelectorAll('.phone-input');
    
    phoneInputs.forEach(input => {
        if(input.getAttribute('data-intl-tel-input-id')) return;

        const hiddenInput = input.closest('div').querySelector('.phone-hidden');
        
        // 🪄 MAGIC 1: Plugin Initialize gochuu
        const iti = window.intlTelInput(input, {
            initialCountry: "et", // Default Ethiopia 🇪🇹
            nationalMode: false,
            autoInsertDialCode: true,
            separateDialCode: false,
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/utils.js",
        });

        // 🪄 MAGIC 2: Koodii biyyaa (+251) saanduqa keessa barreessuu
        input.value = '+' + iti.getSelectedCountryData().dialCode + ' ';

        // 🪄 MAGIC 3: Yoo user-ni biyya biraa filate ofumaan jijjiira
        input.addEventListener("countrychange", function() {
            input.value = '+' + iti.getSelectedCountryData().dialCode + ' ';
            input.focus(); 
        });

        // Yeroo form submit ta'u lakkoofsa guutuu hidden input keessa galcha
        const form = input.closest('form');
        if(form) {
            form.addEventListener('submit', () => {
                hiddenInput.value = iti.getNumber(); 
            });
        }
    });
}
</script>
<?php include_once __DIR__ . '/includes/footer.php'; ?>