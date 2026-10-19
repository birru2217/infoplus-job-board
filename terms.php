<?php
include_once __DIR__ . '/includes/header.php';

// Dynamic Tab Selector (Default: terms, or privacy if ?tab=privacy)
$tab = isset($_GET['tab']) && $_GET['tab'] === 'privacy' ? 'privacy' : 'terms';
?>

<div class="py-12 relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-left">
        
        <!-- Dynamic Header Toggle Pills (Terms vs Privacy) -->
        <div class="flex justify-center mb-10">
            <div class="inline-flex p-1.5 rounded-full bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 shadow-inner">
                <a href="terms.php?tab=terms" class="px-6 py-2.5 rounded-full text-xs sm:text-sm font-extrabold transition-all <?php echo $tab === 'terms' ? 'bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-md' : 'text-slate-600 dark:text-slate-300 hover:text-teal-600'; ?>">
                    📜 Terms of Service
                </a>
                <a href="terms.php?tab=privacy" class="px-6 py-2.5 rounded-full text-xs sm:text-sm font-extrabold transition-all <?php echo $tab === 'privacy' ? 'bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-md' : 'text-slate-600 dark:text-slate-300 hover:text-teal-600'; ?>">
                    🔒 Privacy Policy
                </a>
            </div>
        </div>

        <?php if ($tab === 'privacy'): ?>
            <!-- ==================== PRIVACY POLICY SECTION ==================== -->
            <!-- Header Title -->
            <div class="mb-8 border-b border-slate-200 dark:border-slate-800 pb-6">
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 mb-3 uppercase tracking-widest">
                    Privacy Protection
                </span>
                <h1 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight">Privacy Policy</h1>
                <p class="text-xs text-slate-400 mt-2">Last updated: July 31, 2026 | Infoplus Call Center 6781 & Web Platforms</p>
            </div>

            <!-- CONTENTS TABLE BOX -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl mb-12 shadow-sm">
                <h3 class="text-xs font-black uppercase text-teal-600 dark:text-teal-400 tracking-widest mb-4">Privacy Policy Index</h3>
                <ol class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-bold text-slate-700 dark:text-slate-300">
                    <li><a href="#p-intro" class="hover:text-teal-600 transition-colors">1. Introduction to Infoplus Privacy</a></li>
                    <li><a href="#p-collect" class="hover:text-teal-600 transition-colors">2. Information We Collect</a></li>
                    <li><a href="#p-hotline" class="hover:text-teal-600 transition-colors">3. Call Center 6781 Data Privacy</a></li>
                    <li><a href="#p-usage" class="hover:text-teal-600 transition-colors">4. How We Use Your Information</a></li>
                    <li><a href="#p-sharing" class="hover:text-teal-600 transition-colors">5. Information Sharing & Employers</a></li>
                    <li><a href="#p-security" class="hover:text-teal-600 transition-colors">6. Data Security and Protection</a></li>
                    <li><a href="#p-rights" class="hover:text-teal-600 transition-colors">7. Candidate Rights & Control</a></li>
                    <li><a href="#p-cookies" class="hover:text-teal-600 transition-colors">8. Cookies & Digital Tracking</a></li>
                    <li><a href="#p-law" class="hover:text-teal-600 transition-colors">9. Compliance with Ethiopian Law</a></li>
                </ol>
            </div>

            <!-- MAIN PRIVACY CONTENT BODY -->
            <div class="space-y-8 text-slate-600 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
                
                <!-- 1. Introduction -->
                <section id="p-intro" class="scroll-mt-24">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">1. Introduction to Infoplus Privacy Policy</h2>
                    <p>
                        Infoplus (operating through our online web portal and <strong>Call Center 6781</strong>) values the trust you place in us when sharing your personal information. This Privacy Policy details our practices regarding the collection, storage, protection, and usage of your personal data as you use our job matchmaking services in Ethiopia.
                    </p>
                </section>

                <!-- 2. Information We Collect -->
                <section id="p-collect" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">2. Information We Collect</h2>
                    <p>
                        We collect personal information to facilitate job matching between job seekers and employers across Ethiopia.
                    </p>
                    <ul class="list-disc list-inside mt-3 space-y-2 text-xs sm:text-sm">
                        <li><strong>Candidate Data:</strong> Name, phone number, email address, physical location, educational qualification, work experience, uploaded CV/resumes, and skill profile.</li>
                        <li><strong>Employer Data:</strong> Company name, tax registration/TIN details, contact person details, official email, corporate phone number, and job vacancy requirements.</li>
                    </ul>
                </section>

                <!-- 3. Call Center 6781 Data Privacy -->
                <section id="p-hotline" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">3. Call Center 6781 & SMS Data Privacy</h2>
                    <p>
                        For job seekers registering or inquiring via phone shortcode <strong>6781</strong>:
                    </p>
                    <ul class="list-disc list-inside mt-3 space-y-2 text-xs sm:text-sm">
                        <li>Voice calls may be recorded for quality assurance, training, and accurate candidate profile creation.</li>
                        <li>By providing your phone number to our agents, you consent to receiving SMS job alerts and status updates regarding your job applications.</li>
                        <li>Your phone number will never be sold to third-party telemarketers or advertisers.</li>
                    </ul>
                </section>

                <!-- 4. How We Use Information -->
                <section id="p-usage" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">4. How We Use Your Information</h2>
                    <p>
                        Your information is strictly utilized to deliver recruitment and job placement services, including:
                    </p>
                    <ul class="list-disc list-inside mt-3 space-y-2 text-xs sm:text-sm">
                        <li>Matching candidate qualifications with active employer job vacancies.</li>
                        <li>Verifying company legitimacy and vacancy authenticity.</li>
                        <li>Communicating interview invitations and recruitment updates.</li>
                    </ul>
                </section>

                <!-- 5. Information Sharing -->
                <section id="p-sharing" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">5. Information Sharing & Verified Employers</h2>
                    <p>
                        We share candidate CVs and contact details exclusively with verified employers offering legitimate job opportunities. We do not monetize or sell user personal data to external marketing networks.
                    </p>
                </section>

                <!-- 6. Security -->
                <section id="p-security" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">6. Data Security and Protection</h2>
                    <p>
                        We employ modern SSL encryption, secure cloud database hosting, and strict administrative access controls to safeguard your data against unauthorized access, loss, or alteration.
                    </p>
                </section>

                <!-- 7. Rights -->
                <section id="p-rights" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">7. Candidate Rights & Control</h2>
                    <p>
                        You have full right to request profile updates, request deletion of your account/CV, or opt out of SMS job alert notifications by contacting our team via 6781 or emailing <strong class="text-teal-600 dark:text-teal-400">support@infoplus.et</strong>.
                    </p>
                </section>

                <!-- 8. Cookies -->
                <section id="p-cookies" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">8. Cookies & Digital Tracking</h2>
                    <p>
                        Our web portal uses essential session cookies to keep you logged in, save language preferences, and enhance web browsing performance.
                    </p>
                </section>

                <!-- 9. Law -->
                <section id="p-law" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">9. Compliance with Ethiopian Data Privacy Laws</h2>
                    <p>
                        This Privacy Policy is formulated in compliance with the data protection laws and commercial regulations of the <strong>Federal Democratic Republic of Ethiopia</strong>.
                    </p>
                    <div class="mt-6 dotted-card p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50">
                        <p class="font-bold text-slate-900 dark:text-white text-xs">Infoplus Privacy Desk:</p>
                        <p class="text-xs text-slate-500 mt-1">Shortcode Call Center: 6781 | Email: support@infoplus.et</p>
                        <p class="text-xs text-slate-500">Address: Bole, Addis Ababa, Ethiopia</p>
                    </div>
                </section>

            </div>

        <?php else: ?>
            <!-- ==================== TERMS OF SERVICE SECTION ==================== -->
            <!-- Header Title -->
            <div class="mb-8 border-b border-slate-200 dark:border-slate-800 pb-6">
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 mb-3 uppercase tracking-widest">
                    Legal Agreement
                </span>
                <h1 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight">Terms of Service</h1>
                <p class="text-xs text-slate-400 mt-2">Last updated: July 31, 2026 | Infoplus Call Center 6781 & Web Portal</p>
            </div>

            <!-- CONTENTS TABLE BOX -->
            <div class="dotted-card bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 rounded-3xl mb-12 shadow-sm">
                <h3 class="text-xs font-black uppercase text-teal-600 dark:text-teal-400 tracking-widest mb-4">Table of Contents</h3>
                <ol class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-bold text-slate-700 dark:text-slate-300">
                    <li><a href="#intro" class="hover:text-teal-600 transition-colors">1. Introduction to Infoplus Terms</a></li>
                    <li><a href="#general" class="hover:text-teal-600 transition-colors">2. General Terms Applicable to All Users</a></li>
                    <li><a href="#seekers" class="hover:text-teal-600 transition-colors">3. Specific Terms for Job Seekers</a></li>
                    <li><a href="#employers" class="hover:text-teal-600 transition-colors">4. Specific Terms for Employers</a></li>
                    <li><a href="#content" class="hover:text-teal-600 transition-colors">5. User Content and Conduct</a></li>
                    <li><a href="#applying" class="hover:text-teal-600 transition-colors">6. Applying to Jobs Through Infoplus</a></li>
                    <li><a href="#salary" class="hover:text-teal-600 transition-colors">7. Salary and Information Disclaimer</a></li>
                    <li><a href="#communications" class="hover:text-teal-600 transition-colors">8. User Communications & SMS 6781</a></li>
                    <li><a href="#jurisdiction" class="hover:text-teal-600 transition-colors">9. Law and Jurisdiction (Ethiopia)</a></li>
                </ol>
            </div>

            <!-- MAIN TERMS CONTENT BODY -->
            <div class="space-y-8 text-slate-600 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
                
                <!-- 1. Introduction -->
                <section id="intro" class="scroll-mt-24">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">1. Introduction to Infoplus Terms of Service</h2>
                    <p>
                        Each time you access or use Infoplus online and mobile services, websites, shortcode 6781 call center, including any Infoplus mobile applications, browser extensions, or plugins (collectively referred to as the <strong>"Infoplus Platforms"</strong>), you are engaging with a comprehensive digital matchmaking platform designed to connect job seekers, employers, and recruiters in Ethiopia.
                    </p>
                    <p class="mt-2">
                        By interacting with any part of the Infoplus Platforms, you represent that you have read, understood, and agreed to be bound by these terms and conditions.
                    </p>
                </section>

                <!-- 2. General Terms -->
                <section id="general" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">2. General Terms Applicable to All Users</h2>
                    <p>
                        Welcome to Infoplus. By accessing or using our platform, you agree to these terms and acknowledge your understanding and commitment to comply with them. Our platform serves as a bridge between job seekers and employers, facilitating the sharing of opportunities.
                    </p>
                    <ul class="list-disc list-inside mt-3 space-y-2 text-xs sm:text-sm">
                        <li>We require all users to adhere to the rule of law and avoid posting misleading, false, or inappropriate content.</li>
                        <li>Intellectual property, including text, graphics, logos, and software code on Infoplus, is protected under Ethiopian copyright laws.</li>
                        <li>We reserve the right to suspend or terminate access for any user violating these terms without prior notice.</li>
                    </ul>
                </section>

                <!-- 3. Job Seekers -->
                <section id="seekers" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">3. Specific Terms for Job Seekers</h2>
                    <p>
                        Creating an account or registering via Call Center 6781 obliges you to provide accurate and current information.
                    </p>
                    <ul class="list-disc list-inside mt-3 space-y-2 text-xs sm:text-sm">
                        <li><strong>Free Usage:</strong> Searching for jobs and applying on Infoplus is 100% free for job seekers.</li>
                        <li><strong>Accuracy of CVs:</strong> You are solely responsible for the truthfulness of your CV, cover letters, and qualifications submitted to employers.</li>
                        <li><strong>Call Center 6781 Registration:</strong> Candidates registering over the phone give consent to agents to enter their profile data into the system on their behalf.</li>
                    </ul>
                </section>

                <!-- 4. Employers -->
                <section id="employers" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">4. Specific Terms for Employers and Recruiters</h2>
                    <p>
                        As an employer or recruiter using Infoplus, you must register your account with accurate company details.
                    </p>
                    <ul class="list-disc list-inside mt-3 space-y-2 text-xs sm:text-sm">
                        <li><strong>24-Hour Verification:</strong> All job vacancy postings undergo manual HR screening before being published to the public.</li>
                        <li><strong>Candidate Data Privacy:</strong> You must treat candidate CVs and personal phone numbers with strict confidentiality and use them solely for legitimate recruitment.</li>
                        <li><strong>Equal Opportunity:</strong> Discriminatory job postings based on illegal criteria are strictly prohibited.</li>
                    </ul>
                </section>

                <!-- 5. User Content -->
                <section id="content" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">5. User Content and Conduct</h2>
                    <p>
                        User content encompasses job postings, resumes, messages, and uploaded company logos. You retain ownership of your content but grant Infoplus a non-exclusive license to store, process, and display it for matching purposes.
                    </p>
                </section>

                <!-- 6. Applying to Jobs -->
                <section id="applying" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">6. Applying to Jobs Through Infoplus</h2>
                    <p>
                        Infoplus facilitates the job application process but is not directly involved in the final hiring decisions of independent employers. We cannot guarantee employment or job placement for every applicant.
                    </p>
                </section>

                <!-- 7. Salary Disclaimer -->
                <section id="salary" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">7. Salary and Information Disclaimer</h2>
                    <p>
                        Salary figures listed on vacancies are provided by employers or calculated as general approximations. Infoplus is not liable for negotiations or contract terms made between candidate and employer.
                    </p>
                </section>

                <!-- 8. Communications -->
                <section id="communications" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">8. User Communications & SMS 6781</h2>
                    <p>
                        By registering, users consent to receive transactional emails, SMS notifications (for hotline 6781 users), and account updates necessary for service delivery.
                    </p>
                </section>

                <!-- 9. Jurisdiction -->
                <section id="jurisdiction" class="scroll-mt-24 border-t border-slate-200/60 dark:border-slate-800/60 pt-8">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-3">9. Law and Jurisdiction (Ethiopia)</h2>
                    <p>
                        These terms and conditions shall be governed by and construed in accordance with the laws of the <strong>Federal Democratic Republic of Ethiopia</strong>. Any disputes arising in connection with these terms shall be subject to the exclusive jurisdiction of the courts in Addis Ababa, Ethiopia.
                    </p>
                    <div class="mt-6 dotted-card p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50">
                        <p class="font-bold text-slate-900 dark:text-white text-xs">Infoplus Contact Details:</p>
                        <p class="text-xs text-slate-500 mt-1">Shortcode Call Center: 6781 | Email: support@infoplus.et</p>
                        <p class="text-xs text-slate-500">Address: Bole, Addis Ababa, Ethiopia</p>
                    </div>
                </section>

            </div>
        <?php endif; ?>

    </div>
</div>

<?php
include_once __DIR__ . '/includes/footer.php';
?>