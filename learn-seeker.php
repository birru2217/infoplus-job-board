<?php
include_once __DIR__ . '/includes/header.php';
?>

<!-- 🌟 HERO SECTION: CANDIDATE GUIDE 🌟 -->
<section class="relative pt-10 pb-16 overflow-hidden z-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <!-- Left Text Content -->
            <div class="lg:col-span-7 text-center lg:text-left space-y-6">
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 uppercase tracking-widest shadow-sm">
                    🎓 Candidate Masterclass
                </span>
                
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                    How to Land Your <br class="hidden sm:block"> 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-500 to-cyan-500">Dream Job</span> With Us.
                </h1>
                
                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Infoplus is Ethiopia's premier digital and offline career matchmaking platform. Whether you are using our advanced web portal or our dedicated 6781 Hotline, here is everything you need to know to build your profile, stand out to verified employers, and get hired fast.
                </p>
                
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="signup.php?type=seeker" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-sm transition-all shadow-lg shadow-teal-600/30 flex items-center justify-center gap-2 transform hover:-translate-y-1">
                        <span>Create Your Profile</span>
                        <span>→</span>
                    </a>
                    <a href="#how-it-works" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl border-2 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm hover:border-teal-500 dark:hover:border-teal-500 hover:text-teal-600 transition-all text-center">
                        Read Guide Below
                    </a>
                </div>
            </div>

            <!-- Right 3D Graphic -->
            <div class="lg:col-span-5 flex justify-center relative py-6">
                <div class="absolute inset-0 bg-gradient-to-tr from-teal-500/20 via-cyan-500/10 to-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <img src="seeker_transparent.png" 
                     onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/job-search-5352994-4468688.png'" 
                     alt="Job Seeker Guide" 
                     class="relative z-10 w-full max-w-[300px] sm:max-w-md h-auto object-contain filter drop-shadow-2xl animate-float">
                
                <!-- Floating Info Badge -->
                <div class="absolute top-10 -left-4 sm:left-4 z-20 animate-float-reverse">
                    <div class="dotted-card border border-teal-500/40 shadow-xl px-4 py-2.5 rounded-2xl flex items-center space-x-2 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md">
                        <span class="text-xl">✨</span>
                        <div class="text-left">
                            <span class="text-[9px] font-extrabold text-teal-600 uppercase tracking-widest block">Success Rate</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">98% Hired</span>
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
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-3">Your Journey to Success</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Follow these comprehensive steps to maximize your visibility and secure your next opportunity through Infoplus.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
            
            <!-- STEP 1: Registration & Profile -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-sm hover:shadow-2xl hover:border-teal-500/50 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-teal-500/10 rounded-full blur-2xl group-hover:bg-teal-500/20 transition-all"></div>
                
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-cyan-500 text-white flex items-center justify-center text-xl font-black shadow-lg shadow-teal-500/30 mb-6 relative z-10">
                    01
                </div>
                
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 relative z-10">Sign Up & Build Your CV</h3>
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed relative z-10">
                    <p>Start by clicking the <strong>"Sign Up"</strong> button and selecting <strong>"I'm looking for a job"</strong>. You will be asked to provide your basic details including your valid Phone Number and Email Address.</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 font-medium">
                        <li><strong>Secure OTP Verification:</strong> Verify your account using the 6-digit code sent to your email.</li>
                        <li><strong>Digital CV Builder:</strong> Once logged in, complete our detailed onboarding process. Add your educational background, years of experience, soft/hard skills, and upload your existing PDF resume.</li>
                        <li><strong>Profile Visibility:</strong> A 100% completed profile ranks higher in our AI matching algorithm, making you highly visible to premium employers.</li>
                    </ul>
                </div>
            </div>

            <!-- STEP 2: Search & Smart Matching -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-sm hover:shadow-2xl hover:border-blue-500/50 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-500 text-white flex items-center justify-center text-xl font-black shadow-lg shadow-blue-500/30 mb-6 relative z-10">
                    02
                </div>
                
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 relative z-10">Discover Opportunities</h3>
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed relative z-10">
                    <p>Navigate to the <strong>Jobs</strong> page to explore thousands of verified vacancies posted by trusted organizations across Ethiopia.</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 font-medium">
                        <li><strong>Smart Search Engine:</strong> Type keywords like "Accountant" or "ICT". Our smart dictionary will automatically find exact and related job matches for you.</li>
                        <li><strong>Advanced Filtering:</strong> Filter results by Category, Location, Employment Type (Full Time, Remote), Career Level, and Salary.</li>
                        <li><strong>AI Recommendations:</strong> If you are logged in, our system will automatically recommend "Hot Jobs" at the top of the page based on your saved profile data.</li>
                    </ul>
                </div>
            </div>

            <!-- STEP 3: 1-Click Application -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-sm hover:shadow-2xl hover:border-emerald-500/50 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-500 text-white flex items-center justify-center text-xl font-black shadow-lg shadow-emerald-500/30 mb-6 relative z-10">
                    03
                </div>
                
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 relative z-10">1-Click Apply & Track</h3>
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed relative z-10">
                    <p>Found a job that fits your skills? Applying is seamless and instant.</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 font-medium">
                        <li><strong>Quick Apply:</strong> Click the "Apply Now" button on any job card. Your digital profile and attached CV will be instantly forwarded directly to the employer's dashboard.</li>
                        <li><strong>Safe & Secure:</strong> All employer accounts are strictly verified by the Infoplus HR team. Your data is encrypted and securely submitted.</li>
                        <li><strong>Status Tracking:</strong> Keep an eye on your emails and phone. Employers will contact you directly for interviews once you are shortlisted.</li>
                    </ul>
                </div>
            </div>

            <!-- STEP 4: The 6781 Offline Hotline -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-2 border-amber-500/40 p-8 sm:p-10 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:border-amber-500 transition-all duration-300 relative group overflow-hidden">
                <div class="absolute -left-6 -bottom-6 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 transition-all"></div>
                
                <div class="flex items-center justify-between mb-6 relative z-10">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-amber-500/30">
                        ☎️
                    </div>
                    <span class="px-3 py-1 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-extrabold text-[10px] uppercase tracking-widest rounded-full border border-amber-500/20">Offline Feature</span>
                </div>
                
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 relative z-10">No Internet? Call 6781</h3>
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400 leading-relaxed relative z-10">
                    <p>We believe everyone deserves an equal opportunity. If you do not have a smartphone, a PDF CV, or an active internet connection, you can still use Infoplus!</p>
                    <ul class="list-disc list-inside space-y-1.5 ml-2 font-bold text-slate-700 dark:text-slate-300">
                        <li>Dial <strong class="text-amber-600 dark:text-amber-400 text-base">6781</strong> from any phone in Ethiopia.</li>
                        <li>Speak to our professional Call Center Agents in your preferred language (Amharic, Afaan Oromoo, etc.).</li>
                        <li>Dictate your work experience and skills. Our agents will create a digital CV for you and apply to matching jobs on your behalf!</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 🌟 CALL TO ACTION BANNER 🌟 -->
<section class="py-12 mb-10 relative z-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-slate-900 via-teal-900 to-slate-900 rounded-[3rem] p-8 sm:p-14 text-center shadow-2xl relative overflow-hidden border border-teal-500/30">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[80%] h-32 bg-teal-500/20 rounded-full blur-[80px] pointer-events-none"></div>
            
            <h2 class="text-2xl sm:text-4xl font-black text-white mb-4 relative z-10">Ready to start your career journey?</h2>
            <p class="text-teal-100/80 text-sm sm:text-base font-medium max-w-2xl mx-auto mb-8 relative z-10">
                Join over 45,000 active job seekers. Create your professional Infoplus profile today and let the best employers find you.
            </p>
            
            <a href="signup.php?type=seeker" class="inline-block px-10 py-4 bg-teal-500 hover:bg-teal-400 text-slate-950 font-black text-sm sm:text-base rounded-2xl shadow-xl shadow-teal-500/30 transform hover:-translate-y-1 transition-all relative z-10">
                Create My Account Now
            </a>
        </div>
    </div>
</section>

<?php
include_once __DIR__ . '/includes/footer.php';
?>