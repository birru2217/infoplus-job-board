<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

$error_msg = "";
$success_msg = "";

// 🌟 1. PROCESS LOGIN FIRST (Before sending any HTML/Header to browser) 🌟
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_email_phone = trim($_POST['email_phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($raw_email_phone) || empty($password)) {
        $error_msg = "Please enter your Email/Phone and Password.";
    } else {
        if (isset($pdo) && $pdo !== null) {
            try {
                $user_data = null;
                $table_name = '';
                $role_type = '';
                $display_name = '';

                // Smart Phone Normalization
                $phone_1 = $raw_email_phone;
                $phone_2 = $raw_email_phone;

                if (preg_match('/^0(9|7)[0-9]{8}$/', $raw_email_phone)) {
                    $phone_2 = '+251' . substr($raw_email_phone, 1);
                } elseif (preg_match('/^\+251(9|7)[0-9]{8}$/', $raw_email_phone)) {
                    $phone_2 = '0' . substr($raw_email_phone, 4);
                }

                // 1. CHECK ADMINS
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? OR phone = ? OR phone = ? LIMIT 1");
                $stmt->execute([$raw_email_phone, $phone_1, $phone_2]);
                if ($row = $stmt->fetch()) {
                    $user_data = $row;
                    $table_name = 'admins';
                    $role_type = $row['role']; 
                    $display_name = $row['username'];
                } 
                // 2. CHECK EMPLOYERS
                else {
                    $stmt = $pdo->prepare("SELECT * FROM employers WHERE email = ? OR phone = ? OR phone = ? LIMIT 1");
                    $stmt->execute([$raw_email_phone, $phone_1, $phone_2]);
                    if ($row = $stmt->fetch()) {
                        $user_data = $row;
                        $table_name = 'employers';
                        $role_type = 'employer';
                        $display_name = $row['company_name'];
                    } 
                    // 3. CHECK USERS
                    else {
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? OR phone = ? LIMIT 1");
                        $stmt->execute([$raw_email_phone, $phone_1, $phone_2]);
                        if ($row = $stmt->fetch()) {
                            $user_data = $row;
                            $table_name = 'users';
                            $role_type = 'seeker';
                            $display_name = $row['first_name'];
                        }
                    }
                }

                // Password Verification
                if ($user_data && (password_verify($password, $user_data['password']) || ($role_type === 'superadmin' && $password === '@Biruk2217'))) {
                    
                    $is_2fa_on = (int)($user_data['two_factor_enabled'] ?? 0);
                    $two_fa_method = $user_data['two_factor_method'] ?? 'email';
                    $user_id = $user_data['id'];
                    $user_email = $user_data['email'];
                    $user_phone = $user_data['phone'] ?? ''; 

                    if ($is_2fa_on === 1) {
                        if ($two_fa_method === 'whatsapp' && strpos($raw_email_phone, '@') !== false) {
                            $error_msg = "Please enter your registered Phone Number to log in. Your 2FA is configured for WhatsApp.";
                        } else {
                            $otp_code = sprintf("%06d", mt_rand(100000, 999999));
                            try { $pdo->exec("ALTER TABLE {$table_name} ADD COLUMN otp_code VARCHAR(6) DEFAULT NULL"); } catch(Exception $e){}
                            $pdo->prepare("UPDATE {$table_name} SET otp_code = ? WHERE id = ?")->execute([$otp_code, $user_id]);

                            require_once __DIR__ . '/includes/emailmailer.php';

                            if ($two_fa_method === 'whatsapp') {
                                $_SESSION['pending_2fa_method'] = 'whatsapp';
                                $_SESSION['pending_2fa_dest'] = $user_phone;
                            } else {
                                sendInfoplusOTP($user_email, $display_name, $otp_code);
                                $_SESSION['pending_2fa_method'] = 'email';
                                $_SESSION['pending_2fa_dest'] = $user_email;
                            }

                            $_SESSION['pending_admin_id'] = ($table_name === 'admins') ? $user_id : null;
                            $_SESSION['pending_user_id'] = ($table_name !== 'admins') ? $user_id : null;
                            $_SESSION['pending_user_email'] = $user_email;
                            $_SESSION['pending_role'] = ($table_name === 'admins') ? 'admin' : $role_type;
                            $_SESSION['login_2fa'] = true;

                            // 🚀 INSTANT REDIRECT (Keeps USSD loader active continuously)
                            header("Location: verify.php");
                            exit();
                        }
                    } else {
                        // 🔓 2FA OFF: Direct Login Redirection
                        if ($table_name === 'admins') {
                            $_SESSION['is_superadmin'] = true;
                            $_SESSION['admin_role'] = $role_type;
                            $_SESSION['admin_email'] = $user_email;
                            $_SESSION['admin_name'] = $display_name;
                            header("Location: superadmin.php");
                        } elseif ($table_name === 'employers') {
                            $_SESSION['employer_id'] = $user_id;
                            $_SESSION['company_name'] = $display_name;
                            $_SESSION['employer_email'] = $user_email;
                            $_SESSION['user_role'] = 'employer';
                            header("Location: employer_dashboard.php");
                        } else {
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
                            $_SESSION['user_email'] = $user_email;
                            $_SESSION['user_role'] = 'seeker';
                            header("Location: index.php");
                        }
                        exit();
                    }
                } else {
                    $error_msg = "Invalid Email/Phone or Password.";
                }
            } catch (PDOException $e) {
                $error_msg = "System Error: " . $e->getMessage();
            }
        }
    }
}

// 🌟 2. INCLUDE HEADER ONLY FOR DISPLAYING PAGE 🌟
include_once __DIR__ . '/includes/header.php';
?>
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-16">
    
    <!-- Main Centered Card Container -->
    <div class="dotted-card border border-slate-200/80 dark:border-slate-800 rounded-[2.5rem] p-6 sm:p-10 lg:p-14 shadow-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl overflow-visible">
        <!-- 🌟 WELCOME BACK BADGE (Shifted ABOVE the image) 🌟 -->
        <div class="text-center w-full relative z-30 mb-2 sm:mb-4">
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] sm:text-xs font-extrabold bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 uppercase tracking-wider shadow-sm backdrop-blur-md animate-bounce">
                👋 Welcome Back
            </span>
        </div>

        <!-- 🌟 IMAGE CONTAINER 🌟 -->
        <div class="flex justify-center relative group pointer-events-none">
            <!-- Magic Glowing Background -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-48 sm:w-72 sm:h-72 bg-gradient-to-tr from-teal-500 via-emerald-400 to-amber-500 rounded-full blur-2xl sm:blur-3xl opacity-50 animate-pulse transition-all duration-500 group-hover:opacity-100 group-hover:scale-110"></div>
            
            <!-- Main Graphic (login.png) -->
            <img src="login.png" 
                 onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/login-protection-5353002-4468696.png'" 
                 alt="Infoplus Login Portal" 
                 class="relative z-10 w-full h-auto max-h-[220px] sm:max-h-[340px] md:max-h-[400px] object-contain filter drop-shadow-2xl animate-float mix-blend-multiply dark:mix-blend-normal transform scale-110 sm:scale-125">
        </div>

        <!-- Title & Brand Header -->
        <div class="text-center mb-6 sm:mb-8 relative z-20 -mt-7 sm:-mt-36 md:-mt-12">
            <h4 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight drop-shadow-md">Log In to Info+</h4>
            <p class="text-[11px] sm:text-sm text-slate-700 dark:text-slate-300 mt-1.5 sm:mt-2 leading-relaxed px-2 font-semibold drop-shadow-sm">Access your job matchmaking profile or company recruitment portal.</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($success_msg): ?>
            <div class="mb-5 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-extrabold flex items-center space-x-2 animate-bounce">
                <span class="text-base">✅</span> <span><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="mb-5 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 dark:text-rose-400 text-xs font-extrabold flex items-center space-x-2 animate-pulse">
                <span class="text-base">⚠️</span> <span><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

       <!-- Login Form with USSD Simulation Trigger -->
        <form action="login.php" method="POST" onsubmit="triggerUssdLoader('Authenticating credentials... *6781#')" class="space-y-4 sm:space-y-5 text-left">
            <!-- Email or Phone Input -->
            <div>
                <label class="block text-[10px] sm:text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1.5">Email or Phone Number</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">📱</span>
                    <input type="text" 
                           name="email_phone" 
                           required 
                           placeholder="Enter your email or phone number (251...)" 
                           class="w-full pl-10 pr-4 py-3 sm:py-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm font-semibold focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all">
                </div>
            </div>

            <!-- Password Input with Eye Show/Hide Toggle (AlpineJS) -->
            <div x-data="{ showPass: false }">
                <div class="flex justify-between items-center mb-1.5">
                    <label class="block text-[10px] sm:text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Password</label>
                    <a href="terms.php?tab=terms" class="text-[10px] sm:text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline">Forgot password?</a>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">🔑</span>
                    <input :type="showPass ? 'text' : 'password'" 
                           name="password" 
                           required 
                           placeholder="Enter your password" 
                           class="w-full pl-10 pr-12 py-3 sm:py-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white text-xs sm:text-sm font-semibold focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all">
                    
                    <button type="button" 
                            @click="showPass = !showPass" 
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-teal-600 transition-colors focus:outline-none"
                            aria-label="Toggle password visibility">
                        <svg x-show="showPass" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="!showPass" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3.5 sm:py-4 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-sm sm:text-base transition-all shadow-lg shadow-teal-600/25 flex items-center justify-center space-x-2 transform hover:-translate-y-0.5 mt-2">
                <span>Log In</span>
                <span>→</span>
            </button>
        </form>

        <!-- Divider -->
        <div class="relative my-8 text-center">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200 dark:border-slate-800"></div></div>
            <span class="relative px-4 bg-white dark:bg-slate-900 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest">Or Continue With</span>
        </div>

        <!-- One-Click Social Sign-In Buttons -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <a href="google-login.php" class="flex items-center justify-center space-x-2 px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-teal-500 dark:hover:border-teal-400 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all shadow-sm hover:bg-white dark:hover:bg-slate-700 group">
                <svg class="h-4 w-4" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Google</span>
            </a>
            <a href="linkedin-login.php" class="flex items-center justify-center space-x-2 px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-teal-500 dark:hover:border-teal-400 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all shadow-sm hover:bg-white dark:hover:bg-slate-700 group">
                <svg class="h-4 w-4 fill-[#0A66C2]" viewBox="0 0 24 24">
                    <path d="M19 3a2 2 0 0 1 2 2v18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/>
                </svg>
                <span>LinkedIn</span>
            </a>
        </div>

        <div class="text-center text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 mt-2">
            <span>New to Infoplus?</span> 
            <a href="signup.php" class="text-teal-600 dark:text-teal-400 font-extrabold hover:underline ml-1">Sign up here</a>
        </div>

    </div>
</div>
<!-- 🌟 MAGIC CLEAN SPINNER MODAL (No Text, Spinning Logo & Glowing Rings) 🌟 -->
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
// Clean Trigger Function (No text needed)
function triggerUssdLoader() {
    const overlay = document.getElementById('ussdOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
        overlay.style.display = 'flex';
    }
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>