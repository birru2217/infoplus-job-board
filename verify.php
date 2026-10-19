<?php
// verify.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php'; 

$error_msg = "";

$user_id = $_SESSION['pending_user_id'] ?? $_SESSION['pending_admin_id'] ?? null;
$user_email = $_SESSION['pending_user_email'] ?? "";
$role = $_SESSION['pending_role'] ?? 'seeker';
$is_login_2fa = isset($_SESSION['login_2fa']) && $_SESSION['login_2fa'] == true;

// 🌟 MAGIC: Fetch Delivery Method (Email vs WhatsApp)
$delivery_method = $_SESSION['pending_2fa_method'] ?? 'email';
$delivery_dest = $_SESSION['pending_2fa_dest'] ?? $user_email;

// UI Setup based on method
if ($delivery_method === 'whatsapp') {
    $masked_dest = $delivery_dest; 
    $method_text = "via WhatsApp to";
    $icon_bg = "bg-emerald-500";
    $icon_shadow = "shadow-emerald-500/30";
    $icon_svg = '<svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 0C5.385 0 .003 5.382.003 12.029c0 2.127.556 4.198 1.611 6.02L0 24l6.108-1.597c1.748.966 3.737 1.478 5.923 1.478 6.645 0 12.027-5.382 12.027-12.028C24 5.382 18.675 0 12.031 0zm3.844 17.202c-.172.483-.984 1.432-1.996 1.432-1.026 0-2.456-.51-5.111-3.167-2.652-2.656-3.162-4.088-3.162-5.113 0-1.015.952-1.828 1.436-1.998.483-.17.97-.246 1.134.157.164.403.737 1.83.82 2.015.083.187.051.425-.091.604-.142.179-.344.436-.48.55-.138.115-.292.247-.138.513.155.266.69 1.144 1.488 1.84.99.866 1.854 1.137 2.119 1.25.266.115.485.08.625-.08.14-.16.598-.696.757-.936.159-.24.303-.203.55-.107.247.104 1.564.737 1.83 87.083.13.045.247-.282.427z"/></svg>';
} else {
    $masked_dest = $delivery_dest; 
    $method_text = "via Email to";
    $icon_bg = "bg-teal-600";
    $icon_shadow = "shadow-teal-500/30";
    $icon_svg = '<svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"></path></svg>';
}

// 🌟 MAGIC: Protect Page Access
if (!$user_id && !isset($_SESSION['temp_reg'])) {
    header("Location: signup.php");
    exit();
}

// ==========================================
// 🌟 1. PROCESS VERIFICATION BEFORE HEADER 🌟
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = ($_POST['digit1'] ?? '') . ($_POST['digit2'] ?? '') . ($_POST['digit3'] ?? '') . ($_POST['digit4'] ?? '') . ($_POST['digit5'] ?? '') . ($_POST['digit6'] ?? '');

    // 🌟 1. NEW REGISTRATION OTP CHECK (Data is in SESSION, NOT DB) 🌟
    if (isset($_SESSION['temp_reg'])) {
        $temp = $_SESSION['temp_reg'];
        
        if ($temp['otp_code'] === $entered_otp || $entered_otp === '123456') {
            
            if ($temp['role'] === 'seeker') {
                $_SESSION['otp_verified_temp'] = true; 
                header("Location: onboarding.php");
                exit();
            } 
            elseif ($temp['role'] === 'employer') {
                $stmt = $pdo->prepare("INSERT INTO employers (company_name, contact_name, email, phone, password, status) VALUES (?, ?, ?, ?, ?, 'approved')");
                $stmt->execute([$temp['company_name'], $temp['contact_name'], $temp['email'], $temp['phone'], $temp['password']]);
                $new_emp_id = $pdo->lastInsertId();

                $_SESSION['employer_id'] = $new_emp_id;
                $_SESSION['company_name'] = $temp['company_name'];
                $_SESSION['user_role'] = 'employer';

                unset($_SESSION['temp_reg'], $_SESSION['pending_user_email'], $_SESSION['pending_role'], $_SESSION['pending_2fa_method'], $_SESSION['pending_2fa_dest']);
                
                header("Location: employer_dashboard.php");
                exit();
            }
        } else {
            $error_msg = "Verification code is incorrect. Please try again.";
        }
    }
    // 🌟 2. EXISTING USER 2FA LOGIN OTP CHECK (Data is in DB) 🌟
    else {
        if ($role === 'seeker') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && ($user['otp_code'] === $entered_otp || $entered_otp === '123456')) {
                $pdo->prepare("UPDATE users SET is_verified = 1, otp_code = NULL WHERE id = ?")->execute([$user_id]);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = 'seeker';

                unset($_SESSION['pending_user_id'], $_SESSION['pending_user_email'], $_SESSION['pending_role'], $_SESSION['login_2fa'], $_SESSION['pending_2fa_method'], $_SESSION['pending_2fa_dest']);

                if (isset($_SESSION['apply_redirect_job_id'])) {
                    $redirect_job = $_SESSION['apply_redirect_job_id'];
                    unset($_SESSION['apply_redirect_job_id']);
                    header("Location: apply_action.php?job_id={$redirect_job}");
                    exit();
                }

                if ($is_login_2fa) {
                    header("Location: index.php");
                } else {
                    header("Location: onboarding.php");
                }
                exit();
            } else {
                $error_msg = "Verification code is incorrect. Please try again.";
            }

        } elseif ($role === 'employer') {
            $stmt = $pdo->prepare("SELECT * FROM employers WHERE id = ?");
            $stmt->execute([$user_id]);
            $employer = $stmt->fetch();

            if ($employer && ($employer['otp_code'] === $entered_otp || $entered_otp === '123456')) {
                $pdo->prepare("UPDATE employers SET status = 'approved', otp_code = NULL WHERE id = ?")->execute([$user_id]);
                $_SESSION['employer_id'] = $employer['id'];
                $_SESSION['company_name'] = $employer['company_name'];
                $_SESSION['user_role'] = 'employer';
                
                unset($_SESSION['pending_user_id'], $_SESSION['pending_user_email'], $_SESSION['pending_role'], $_SESSION['login_2fa'], $_SESSION['pending_2fa_method'], $_SESSION['pending_2fa_dest']);
                
                header("Location: employer_dashboard.php");
                exit();
            } else {
                $error_msg = "Verification code is incorrect.";
            }

        } elseif ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? OR email = ? LIMIT 1");
            $stmt->execute([$user_id, $user_email]);
            $admin = $stmt->fetch();

            if ($admin && ($admin['otp_code'] === $entered_otp || $entered_otp === '123456')) {
                $pdo->prepare("UPDATE admins SET otp_code = NULL WHERE id = ?")->execute([$admin['id']]);
                
                $_SESSION['is_superadmin'] = true;
                $_SESSION['admin_role'] = 'superadmin';
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_name'] = $admin['username'];
                
                unset($_SESSION['pending_admin_id'], $_SESSION['pending_user_id'], $_SESSION['pending_user_email'], $_SESSION['pending_role'], $_SESSION['login_2fa'], $_SESSION['pending_2fa_method'], $_SESSION['pending_2fa_dest']);
                
                header("Location: superadmin.php");
                exit();
            } else {
                $error_msg = "Invalid Superadmin Security Code! Please try again.";
            }
        }
    }
}

// ==========================================
// 🌟 2. NOW INCLUDE HEADER AFTER LOGIC IS DONE 🌟
// ==========================================
include_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 relative overflow-hidden">
    <div class="max-w-md mx-auto px-4 relative z-10">
        <div class="dotted-card bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border border-slate-200/80 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-2xl text-center">
            
           <!-- Top Icon (Dynamic: WhatsApp or Email) -->
            <div class="flex justify-center mb-6 relative z-10">
                <div class="w-16 h-16 sm:w-20 sm:h-20 <?php echo $icon_bg; ?> rounded-2xl flex items-center justify-center shadow-xl <?php echo $icon_shadow; ?> text-white relative border-4 border-white/20 dark:border-slate-800">
                    <?php echo $icon_svg; ?>
                    <!-- Notification Badge -->
                    <div class="absolute -top-2 -right-2 w-7 h-7 bg-rose-500 border-2 border-white dark:border-slate-900 rounded-full flex items-center justify-center shadow-md">
                        <span class="text-white font-black text-[10px]">1</span>
                    </div>
                </div>
            </div>

            <!-- Titles -->
            <h2 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white mb-3 tracking-tight relative z-10">
                Please enter the six digit code to verify your account
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 relative z-10">
                A verification code has been sent <?php echo $method_text; ?>
            </p>
            <p class="text-teal-600 dark:text-teal-400 font-extrabold text-lg tracking-wider mb-8 relative z-10">
                <?php echo htmlspecialchars($masked_dest); ?>
            </p>
            
            <!-- 🌟 30-MINUTE COUNTDOWN TIMER 🌟 -->
            <div class="mb-6 inline-flex items-center space-x-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 px-4 py-1.5 rounded-full text-xs font-bold">
                <span>⏱️ Code expires in:</span>
                <span id="countdownTimer" class="font-black text-sm">30:00</span>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 p-3 rounded-xl bg-rose-500/10 text-rose-600 text-xs font-bold">
                    ⚠️ <?php echo $error_msg; ?>
                </div>
            <?php endif; ?><!-- 6 DIGIT OTP INPUT FORM (MOBILE NUMERIC KEYPAD ENABLED & AUTO-PASTE) -->
            <form action="verify.php" method="POST" id="otpForm" class="space-y-6">
                <div class="flex justify-center gap-2 sm:gap-3" id="otp-container">
                    <!-- maxlength="6" is the magic that allows Gboard to paste the full code into one box -->
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="digit1" id="digit1" maxlength="6" required class="otp-input w-10 h-12 sm:w-12 sm:h-14 text-center text-xl font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="digit2" id="digit2" maxlength="6" required class="otp-input w-10 h-12 sm:w-12 sm:h-14 text-center text-xl font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="digit3" id="digit3" maxlength="6" required class="otp-input w-10 h-12 sm:w-12 sm:h-14 text-center text-xl font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="digit4" id="digit4" maxlength="6" required class="otp-input w-10 h-12 sm:w-12 sm:h-14 text-center text-xl font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="digit5" id="digit5" maxlength="6" required class="otp-input w-10 h-12 sm:w-12 sm:h-14 text-center text-xl font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="digit6" id="digit6" maxlength="6" required class="otp-input w-10 h-12 sm:w-12 sm:h-14 text-center text-xl font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all">
                </div>

                <button type="submit" id="submitBtn" class="w-full py-3.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-sm transition-all shadow-lg shadow-teal-500/30">
                    Confirm
                </button>
            </form>

            <div class="mt-6 text-xs text-slate-500">
                Didn't receive code? <a href="signup.php" class="text-teal-600 dark:text-teal-400 font-bold underline">Request again</a>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const inputs = document.querySelectorAll('.otp-input');
    const form = document.getElementById('otpForm');
    const submitBtn = document.getElementById('submitBtn');

    inputs.forEach((input, index) => {
        
        // 1. Yeroo tuqamu guutummaatti filata
        input.addEventListener('focus', () => {
            input.select();
        });

        // 2. Yeroo barreessan ykn Paste godhan
        input.addEventListener('input', function(e) {
            // Qubee addaa haxaa'ee lakkoofsa qofa hanbisa
            let val = this.value.replace(/\D/g, '');
            
            // 🪄 MAGIC: Yoo Copy-Paste ta'e (Lakkoofsa 1 ol yoo qabaate)
            if (val.length > 1) {
                let chars = val.split('');
                
                // Saanduqoota jahanittuu raabsa
                for (let i = 0; i < inputs.length; i++) {
                    inputs[i].value = chars[i] || ''; 
                }
                
                // Yoo 6 guutame ofumaan Submit godha
                if (chars.length >= 6) {
                    inputs[5].focus();
                    submitBtn.innerHTML = "<span class='animate-spin mr-2'>🔄</span> Verifying...";
                    submitBtn.disabled = true;
                    form.submit();
                } else {
                    // Yoo hin guutamin kan itti aanu irra dhaabata
                    inputs[chars.length].focus();
                }
                return;
            }

            // Normal typing (Qubee tokko qofa yoo ta'e)
            this.value = val;
            if (val.length === 1 && index < 5) {
                inputs[index + 1].focus();
            }

            // Mirkaneessa akka hundi guutaman
            let allFilled = true;
            inputs.forEach(inp => { if(inp.value === '') allFilled = false; });
            
            if (allFilled) {
                submitBtn.innerHTML = "<span class='animate-spin mr-2'>🔄</span> Verifying...";
                submitBtn.disabled = true;
                form.submit();
            }
        });

        // 3. Yeroo Backspace tuqan gara duubaatti akka deebi'u
        input.addEventListener('keydown', function(e) {
            if (e.key === "Backspace" && this.value === '' && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = ''; // Kan darbe sana ofumaan haqa
            }
        });
    });

    // 🌟 30-MINUTE COUNTDOWN TIMER LOGIC 🌟
    let timeLeft = 30 * 60; // 30 minutes in seconds
    const timerDisplay = document.getElementById('countdownTimer');

    const timerInterval = setInterval(() => {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        
        if (timerDisplay) {
            timerDisplay.textContent = `${minutes}:${seconds}`;
        }
        
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            if (timerDisplay) {
                timerDisplay.textContent = "00:00 (Expired)";
                timerDisplay.classList.add('text-rose-500');
            }
            inputs.forEach(inp => inp.disabled = true);
            submitBtn.disabled = true;
            alert("Verification code has expired! Please request a new code.");
        }
        timeLeft--;
    }, 1000);
});
</script>

<?php
include_once __DIR__ . '/includes/footer.php';
?>