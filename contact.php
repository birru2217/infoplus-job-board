<?php
// contact.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
include_once __DIR__ . '/includes/header.php';

$success_msg = "";
$error_msg = "";

// 🌟 MAGIC: Handle Form Submission & Send to Superadmin Chat 🌟
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email_phone = trim($_POST['email_phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email_phone) && !empty($message)) {
        if (isset($pdo) && $pdo !== null) {
            try {
                // Combine Name and Message for Admin visibility
                $full_message = "👤 Name: {$name}\n📞 Contact: {$email_phone}\n\n📝 Message:\n{$message}";
                
                // Insert into messages table (Receiver: Superadmin email)
                $stmt = $pdo->prepare("INSERT INTO messages (sender_type, sender_email, receiver_email, message) VALUES ('user', ?, 'tadesebiruk515@gmail.com', ?)");
                $stmt->execute([$email_phone, $full_message]);
                
                $success_msg = "Thank you, {$name}! Your message has been sent successfully. Our support team will get back to you within 12 hours.";
            } catch (PDOException $e) {
                $error_msg = "A database error occurred. Please try again later or call 6781.";
            }
        } else {
            $success_msg = "Thank you, {$name}! Your message has been received (Simulation mode).";
        }
    } else {
        $error_msg = "Please fill out all required fields to send your message.";
    }
}
?>

<div class="relative pt-12 sm:pt-20 pb-16 overflow-hidden">
    <!-- Ambient Background Glows -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[400px] bg-gradient-to-b from-teal-500/20 via-emerald-500/5 to-transparent rounded-full blur-3xl pointer-events-none -z-10"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Header -->
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <span class="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full text-xs font-black bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 mb-4 uppercase tracking-widest shadow-sm">
                <span>📞</span> <span>Get in Touch</span>
            </span>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white tracking-tight leading-tight mb-4">
                Contact <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-cyan-500">Our Team</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                Whether you have a question about features, pricing, or need technical assistance, our support team is available 24/7 to help you out.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start max-w-6xl mx-auto">
            
            <!-- LEFT: Contact Information Cards -->
            <div class="lg:col-span-5 space-y-4">
                <h3 class="text-lg font-black text-slate-900 dark:text-white mb-6">Contact Information</h3>
                
                <!-- Hotline Card -->
                <div class="dotted-card bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:border-amber-500 transition-all duration-300 group flex items-start space-x-5">
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-2xl flex-shrink-0 border border-amber-500/20 group-hover:scale-110 transition-transform">
                        ☎️
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-500 dark:text-slate-400 text-[10px] uppercase tracking-widest mb-1">24/7 Hotline Support</h4>
                        <p class="text-2xl font-black text-slate-900 dark:text-white leading-none">6781</p>
                        <p class="text-[10px] text-slate-400 mt-1 font-medium">Call us toll-free from anywhere in Ethiopia.</p>
                    </div>
                </div>

                <!-- Email Card -->
                <div class="dotted-card bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:border-teal-500 transition-all duration-300 group flex items-start space-x-5">
                    <div class="w-14 h-14 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center text-2xl flex-shrink-0 border border-teal-500/20 group-hover:scale-110 transition-transform">
                        📧
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-slate-500 dark:text-slate-400 text-[10px] uppercase tracking-widest mb-1">Email Address</h4>
                        <p class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight truncate">infopluscallcenter@gmail.com</p>
                        <p class="text-[10px] text-slate-400 mt-1 font-medium">For corporate inquiries and support.</p>
                    </div>
                </div>

                <!-- Location Card -->
                <div class="dotted-card bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-xl hover:border-rose-500 transition-all duration-300 group flex items-start space-x-5">
                    <div class="w-14 h-14 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-2xl flex-shrink-0 border border-rose-500/20 group-hover:scale-110 transition-transform">
                        📍
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-500 dark:text-slate-400 text-[10px] uppercase tracking-widest mb-1">Headquarters Location</h4>
                        <p class="text-sm sm:text-base font-black text-slate-900 dark:text-white leading-tight">Abado Ena Tafou 20/21</p>
                        <p class="text-[10px] text-slate-400 mt-1 font-medium">Addis Ababa, Ethiopia.</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Interactive Contact Form -->
            <div class="lg:col-span-7">
                <div class="dotted-card bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border border-slate-200/80 dark:border-slate-800 rounded-[2.5rem] p-8 sm:p-10 shadow-2xl relative overflow-hidden">
                    
                    <!-- Decorative Form Glow -->
                    <div class="absolute -top-24 -right-24 w-64 h-64 bg-teal-500/20 rounded-full blur-3xl pointer-events-none"></div>

                    <h3 class="font-black text-slate-900 dark:text-white text-2xl mb-2 relative z-10">Send us a Message</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-8 relative z-10">Fill out the form below and we'll reply to your email or phone as soon as possible.</p>

                    <!-- Alert Messages -->
                    <?php if ($success_msg): ?>
                        <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-sm animate-bounce relative z-10">
                            <span class="text-lg">✅</span> <span><?php echo $success_msg; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-sm animate-pulse relative z-10">
                            <span class="text-lg">⚠️</span> <span><?php echo $error_msg; ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="contact.php" method="POST" class="space-y-5 relative z-10">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1.5">Full Name *</label>
                                <input type="text" name="name" required placeholder="e.g. Chala Kebede" class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all font-medium">
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1.5">Email / Phone *</label>
                                <input type="text" name="email_phone" required placeholder="e.g. 0911... or user@mail.com" class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all font-medium">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] sm:text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1.5">Your Message *</label>
                            <textarea name="message" required rows="5" placeholder="How can we help you today? Please be as detailed as possible..." class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all font-medium custom-scrollbar"></textarea>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" onclick="this.innerHTML='<span class=\'animate-spin mr-2\'>🔄</span> Sending Message...'; this.style.opacity='0.85';" class="w-full py-4 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-black text-sm sm:text-base transition-all shadow-xl shadow-teal-600/30 transform hover:-translate-y-1 flex items-center justify-center gap-2 cursor-pointer">
                                <span>Send Message 🚀</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/includes/footer.php';
?>