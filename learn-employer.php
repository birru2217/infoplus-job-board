<?php
include_once __DIR__ . '/includes/header.php';
?>

<!-- 🌟 HERO SECTION: ENTERPRISE GUIDE 🌟 -->
<section class="relative pt-10 pb-16 overflow-hidden z-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <!-- Left Text Content -->
            <div class="lg:col-span-7 text-center lg:text-left space-y-6">
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 uppercase tracking-widest shadow-sm">
                    🏢 Enterprise Recruitment
                </span>
                
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                    Transform Your <br class="hidden sm:block"> 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-500">Hiring Process</span> With Us.
                </h1>
                
                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Infoplus Cloud HR is the ultimate recruitment solution in Ethiopia. Reach thousands of verified active job seekers through our Web Portal, Telegram integration, and our exclusive 6781 Offline Call Center Hotline.
                </p>
                
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="signup.php?type=employer" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-sm transition-all shadow-lg shadow-amber-500/30 flex items-center justify-center gap-2 transform hover:-translate-y-1">
                        <span>Register Company</span>
                        <span>→</span>
                    </a>
                    <a href="#how-it-works" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl border-2 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm hover:border-amber-500 dark:hover:border-amber-500 hover:text-amber-600 transition-all text-center">
                        Read Employer Guide
                    </a>
                </div>
            </div>

            <!-- Right 3D Graphic -->
            <div class="lg:col-span-5 flex justify-center relative py-6">
                <div class="absolute inset-0 bg-gradient-to-tr from-amber-500/20 via-orange-500/10 to-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <img src="employer_transparent.png" 
                     onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/businessman-working-on-laptop-5353006-4468700.png'" 
                     alt="Employer Guide Graphic" 
                     class="relative z-10 w-full max-w-[300px] sm:max-w-md h-auto object-contain filter drop-shadow-2xl animate-float">
                
                <!-- Floating Info Badge -->
                <div class="absolute top-10 -right-4 sm:right-4 z-20 animate-float-reverse">
                    <div class="dotted-card border border-amber-500/40 shadow-xl px-4 py-2.5 rounded-2xl flex items-center space-x-2 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md">
                        <span class="text-xl">💼</span>
                        <div class="text-left">
                            <span class="text-[9px] font-extrabold text-amber-600 uppercase tracking-widest block">Talent Pool</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">45K+ Seekers</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 🌟 DETAILED STEP-BY-STEP GUIDE SECTION 🌟 -->
<section id="how-it-works" class="py-16 sm:py-24 bg-slate-50/50 dark:bg-slate-950/50 border-t border-slate-200/50 dark:border-slate-800/50 relative z-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-3">How to Post & Hire</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">A seamless 4-step workflow designed to save you time, reduce hiring costs, and find the perfect candidate.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
            
            <!-- STEP 1: Registration & Submission -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-sm hover:shadow-2xl hover:border-amber-500/50 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all"></div>
                
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-white flex items-center justify-center text-xl font-black shadow-lg shadow-amber-500/30 mb-6 relative z-10">
                    01
                </div>
                
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 relative z-10">Register & Submit Vacancy</h3>
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed relative z-10">
                    <p>Click on <strong>Register Company</strong> to create your secure Enterprise account. Fill in your corporate details and contact information.</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 font-medium">
                        <li><strong>Vacancy Details:</strong> Navigate to 'Post Vacancy' on your dashboard. Enter the job title, category, salary, and deadline.</li>
                        <li><strong>Upload Documents:</strong> You can attach official PDF vacancy flyers and your company logo to make your post stand out.</li>
                        <li><strong>Target Requirements:</strong> Clearly specify the required experience and education level to ensure our AI matches you with the right candidates.</li>
                    </ul>
                </div>
            </div>

            <!-- STEP 2: HR Verification -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-sm hover:shadow-2xl hover:border-teal-500/50 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-teal-500/10 rounded-full blur-2xl group-hover:bg-teal-500/20 transition-all"></div>
                
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-400 to-emerald-500 text-white flex items-center justify-center text-xl font-black shadow-lg shadow-teal-500/30 mb-6 relative z-10">
                    02
                </div>
                
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 relative z-10">Quality Verification</h3>
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed relative z-10">
                    <p>To maintain a high-quality ecosystem and protect our job seekers, all submitted vacancies undergo a strict validation process.</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 font-medium">
                        <li><strong>Manual HR Review:</strong> Our internal administrators will review your job posting within 12 to 24 hours.</li>
                        <li><strong>Legitimacy Check:</strong> We ensure that the salary, company details, and job descriptions are accurate and comply with Ethiopian labor laws.</li>
                        <li><strong>Notification:</strong> You will be notified via your registered email or dashboard once your vacancy is approved.</li>
                    </ul>
                </div>
            </div>

            <!-- STEP 3: Multi-Channel Publishing -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-sm hover:shadow-2xl hover:border-blue-500/50 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-500 text-white flex items-center justify-center text-xl font-black shadow-lg shadow-blue-500/30 mb-6 relative z-10">
                    03
                </div>
                
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 relative z-10">Multi-Channel Broadcast</h3>
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed relative z-10">
                    <p>Once verified, your vacancy is instantly pushed to thousands of active candidates through our powerful broadcasting system.</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 font-medium">
                        <li><strong>Web Portal:</strong> Your job is published live on the Infoplus Jobs board with SEO optimization.</li>
                        <li><strong>Telegram Auto-Post:</strong> It is instantly formatted and broadcasted to our large subscriber base on Telegram.</li>
                        <li><strong>6781 Offline Access:</strong> Candidates without internet access can apply to your job by calling our 6781 Hotline agents.</li>
                    </ul>
                </div>
            </div>

            <!-- STEP 4: Manage Applications -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-2 border-emerald-500/40 p-8 sm:p-10 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:border-emerald-500 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute -left-6 -bottom-6 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl group-hover:bg-emerald-500/20 transition-all"></div>
                
                <div class="flex items-center justify-between mb-6 relative z-10">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-500 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-emerald-500/30">
                        🤝
                    </div>
                    <span class="px-3 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-extrabold text-[10px] uppercase tracking-widest rounded-full border border-emerald-500/20">Final Step</span>
                </div>
                
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 relative z-10">Receive & Hire Top Talent</h3>
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed relative z-10">
                    <p>Sit back and watch the applications roll in directly to your Employer Dashboard.</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 font-bold text-slate-700 dark:text-slate-300">
                        <li>View applicant profiles, cover letters, and download their CVs in one place.</li>
                        <li>Filter and shortlist the best matching candidates using our simple UI.</li>
                        <li>Contact the candidates directly via their provided phone numbers or email addresses to schedule interviews!</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 🌟 CALL TO ACTION BANNER (Amber Theme) 🌟 -->
<section class="py-12 mb-10 relative z-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-slate-900 via-amber-900/60 to-slate-900 rounded-[3rem] p-8 sm:p-14 text-center shadow-2xl relative overflow-hidden border border-amber-500/30">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[80%] h-32 bg-amber-500/20 rounded-full blur-[80px] pointer-events-none"></div>
            
            <h2 class="text-2xl sm:text-4xl font-black text-white mb-4 relative z-10">Ready to build your dream team?</h2>
            <p class="text-amber-100/80 text-sm sm:text-base font-medium max-w-2xl mx-auto mb-8 relative z-10">
                Join hundreds of verified companies hiring through Infoplus. Create your enterprise account today and post your first vacancy.
            </p>
            
            <a href="signup.php?type=employer" class="inline-block px-10 py-4 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-sm sm:text-base rounded-2xl shadow-xl shadow-amber-500/30 transform hover:-translate-y-1 transition-all relative z-10">
                Register Company Now
            </a>
        </div>
    </div>
</section>

<?php
include_once __DIR__ . '/includes/footer.php';
?>