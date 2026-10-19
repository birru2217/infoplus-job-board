<?php
include_once __DIR__ . '/includes/header.php';

// 🌟 MAGIC VIEWS ENGINE FORMULA (+30 Initial Boost + 50 Daily Views) 🌟
function calculateMagicViews($job) {
    $real_views = (int)($job['views'] ?? 0);
    $created_time = !empty($job['created_at']) ? strtotime($job['created_at']) : time();
    $seconds_passed = max(0, time() - $created_time);
    $days_passed = floor($seconds_passed / 86400);
    $minutes_passed = floor($seconds_passed / 60);

    // Initial 30 views boost after 5 minutes + 50 views for every passing day
    $initial_boost = ($minutes_passed >= 5) ? 30 : ($minutes_passed * 6);
    $daily_boost = $days_passed * 50;

    $total_calculated_views = $real_views + $initial_boost + $daily_boost;

    if ($total_calculated_views >= 1000) {
        return number_format($total_calculated_views / 1000, 1) . 'k';
    }
    return number_format($total_calculated_views);
}

// Fetch active jobs from DB
$featured_jobs = [];
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->query("SELECT * FROM jobs WHERE status = 'active' ORDER BY id DESC LIMIT 6");
        $featured_jobs = $stmt->fetchAll();
    } catch (PDOException $e) {
        $featured_jobs = [];
    }
}
// Fetch jobs dynamically
$featured_jobs = [];
if (isset($pdo) && $pdo !== null) {
    try {
        // 🌟 MAGIC DAILY BOOST (FIXED): Hojii har'a post ta'etti view hin dabalu. Sa'aatii 24 (Guyyaa itti aanu) eega! 🌟
        $pdo->exec("
            UPDATE jobs 
            SET views = views + FLOOR(10 + (RAND() * 30)), 
                last_view_boost_date = CURRENT_DATE() 
            WHERE status = 'active' 
            AND DATE(created_at) < CURRENT_DATE() 
            AND (last_view_boost_date IS NULL OR last_view_boost_date < CURRENT_DATE())
        ");

        $stmt = $pdo->query("SELECT * FROM jobs WHERE status = 'active' ORDER BY id DESC LIMIT 6");
        $featured_jobs = $stmt->fetchAll();
    } catch (PDOException $e) {
        $featured_jobs = [];
    }
}
// Fallback Mock array if DB is empty
if (empty($featured_jobs)) {
    $featured_jobs = [
        [
            'id' => 1,
            'company_name' => 'Info+ Call Center',
            'title' => 'Customer Support Specialist',
            'category' => 'Customer Service',
            'job_type' => 'Full Time',
            'experience' => '0-1 Year',
            'location' => 'Addis Ababa',
            'salary' => '7,000 Birr/Mon',
            'views' => 246
        ],
        [
            'id' => 2,
            'company_name' => 'Spring of Knowledge Academy',
            'title' => 'Mathematics Teacher',
            'category' => 'Education',
            'job_type' => 'Full Time',
            'experience' => '2-5 Years',
            'location' => 'Adama',
            'salary' => '15,000 Birr/Mon',
            'views' => 212
        ],
        [
            'id' => 3,
            'company_name' => 'Safdar Agro-Industry',
            'title' => 'Agricultural Field Officer',
            'category' => 'Natural Science',
            'job_type' => 'Contract',
            'experience' => '2-4 Years',
            'location' => 'Hawassa',
            'salary' => '12,000 Birr/Mon',
            'views' => 188
        ],
       [
            'id' => 4,
            'company_name' => 'Dashen Bank',
            'title' => 'Junior Web Developer',
            'category' => 'ICT',
            'job_type' => 'Full Time',
            'experience' => '1-2 Years',
            'location' => 'Addis Ababa',
            'salary' => '18,000 Birr/Mon',
            'views' => 310
        ]
    ];
}

// 🌟 MAGIC: Fetch slides dynamically from DB (Koodii Haaraa Asitti Dabalami) 🌟
$home_slides = [];
if (isset($pdo) && $pdo !== null) {
    try { 
        $home_slides = $pdo->query("SELECT * FROM home_slides ORDER BY id ASC")->fetchAll(); 
    } catch (PDOException $e) {}
}

// Yoo Database keessa hin jirre ykn DB'n offline ta'e fakkiiwwan default kana fayyadama
if (empty($home_slides)) {
    $home_slides = [
        ['image_path' => 'images/slide1.png', 'badge_text' => 'Shortcode 6781', 'title_text' => 'Apply to Top Jobs via Phone Hotline', 'badge_color' => 'bg-teal-500'],
        ['image_path' => 'images/slide2.png', 'badge_text' => 'Enterprise Matchmaking', 'title_text' => 'Connect directly with Verified Employers', 'badge_color' => 'bg-amber-500'],
        ['image_path' => 'images/slide3.png', 'badge_text' => 'Verified Listings', 'title_text' => 'Fast, Secure and Trusted Job Placement', 'badge_color' => 'bg-emerald-500'],
        ['image_path' => 'images/slide4.png', 'badge_text' => 'Infoplus Network', 'title_text' => 'Empowering Ethiopian Talent Everywhere', 'badge_color' => 'bg-blue-500']
    ];
}
?>

<style>
    /* Continuous Marquee Animation for Top Companies (Reversed - Left to Right) */
/* Continuous Marquee Animation for Top Companies (Fast & Smooth Speed) */
@keyframes marquee-reverse {
    0% { transform: translateX(-50%); }
    100% { transform: translateX(0%); }
}
.animate-marquee-reverse {
    display: flex;
    width: max-content;
    animation: marquee-reverse 15s linear infinite; /* 🚀 Saffisni 35s irraa gara 15s tti saffiseera! */
}
.animate-marquee-reverse:hover {
    animation-play-state: paused; /* Yeroo mouse irra kaayan suuta jedhee dhaabata */
}
    /* Logo I+ Slow Spinning Animation */
@keyframes spinSlow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spinSlow 12s linear infinite;
}
    /* Animation Duumessa Yaadaa (Floating Thought Bubble) */
@keyframes floatSlow {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}
.animate-float {
    animation: floatSlow 3.5s ease-in-out infinite;
}
    /* Animation Duumessa Yaadaa fi Badgii */
@keyframes floatSlow {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-14px) rotate(1.5deg); }
}
@keyframes floatReverse {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(10px) rotate(-1.5deg); }
}

.animate-float {
    animation: floatSlow 4s ease-in-out infinite;
}
.animate-float-reverse {
    animation: floatReverse 4.5s ease-in-out infinite;
}

/* Suuraa FAQ fi Graphic adda addaa Dark Mode irratti background qulqulluu akka qabaatan gochuuf */
.transparent-graphic {
    mix-blend-mode: normal;
    background: transparent !important;
}
.dark .transparent-graphic {
    /* Yoo suuraan background gurraacha qabaate, akka transparent ta'u godha */
    mix-blend-mode: screen; 
    filter: brightness(1.2) contrast(1.1);
}
.dark .blend-seeker-img {
    mix-blend-mode: normal;
    filter: brightness(0.9) contrast(1.1);
}
/* Continuous Marquee Animation for Top Companies */
@keyframes marquee {
    0% { transform: translateX(0%); }
    100% { transform: translateX(-50%); }
}
.animate-marquee {
    display: flex;
    width: max-content;
    animation: marquee 35s linear infinite;
}
.animate-marquee:hover {
    animation-play-state: paused;
}

/* Fade Effect on Left and Right edges */
.marquee-mask {
    -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
    mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
}

/* 🌟 Dotted Pattern Style for All Cards (Xuqa xuqa suuraa 2ffaa) */
.dotted-card {
    background-color: rgba(255, 255, 255, 0.65);
    background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px);
    background-size: 14px 14px;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
.dark .dotted-card {
    background-color: rgba(15, 23, 42, 0.65);
    background-image: radial-gradient(#334155 1.2px, transparent 1.2px);
    background-size: 14px 14px;
}
</style>
<!-- SECTION 1: Hero Section (Search Input & Find Job Button 100% Side-by-Side on Mobile & PC) -->
<section class="relative pt-4 sm:pt-6 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-semibold bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 mb-3 tracking-wide uppercase">
            <?php echo __('hero_badge'); ?>
        </span>
        
        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white max-w-4xl mx-auto leading-tight">
            All Ethiopian Jobs in <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-emerald-500">One Place.</span>
        </h1>
        <p class="mt-2.5 text-base sm:text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">
            Find career opportunities in Ethiopia easily through web and shortcode 6781.
        </p>

        <!-- Search Box (100% Side-by-Side in 1 Row on Mobile & PC) -->
        <div class="mt-4 sm:mt-6 max-w-2xl mx-auto relative z-30">
            <form action="jobs.php" method="GET" class="flex flex-row items-center gap-1.5 sm:gap-2 p-1.5 sm:p-2 dotted-card rounded-2xl shadow-xl border border-slate-200/80 dark:border-slate-700/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl">
                
                <!-- Search Input Field -->
                <div class="relative w-full flex-1 min-w-0">
                    <span class="absolute inset-y-0 left-0 pl-2.5 sm:pl-4 flex items-center text-slate-400 text-xs sm:text-base">
                        🔍
                    </span>
                    <input type="text" name="q" placeholder="Search jobs (e.g. Developer...)" class="w-full pl-7 sm:pl-11 pr-2 sm:pr-4 py-2.5 sm:py-3 rounded-xl bg-transparent text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none text-xs sm:text-sm font-semibold">
                </div>

                <!-- Search Button (Side-by-Side on Mobile) -->
                <button type="submit" class="px-3.5 py-2.5 sm:px-8 sm:py-3 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-xs sm:text-sm transition-all shadow-md shadow-teal-600/20 flex items-center justify-center space-x-1 sm:space-x-2 flex-shrink-0 whitespace-nowrap">
                    <span>Find Job</span>
                </button>

            </form>
        </div>
    </div>
</section>
<!-- SECTION 2: Auto-sliding Image Banner Container (Dynamic Database Loop) -->
<section class="relative z-10 pt-6 sm:pt-8 pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Fixed Responsive Height Container -->
        <div class="relative w-full rounded-3xl overflow-hidden shadow-2xl border border-slate-200/80 dark:border-slate-800/80 bg-slate-950 h-[280px] sm:h-[400px] lg:h-[480px]">
            
            <?php foreach ($home_slides as $index => $slide): ?>
            <!-- Dynamic Slide <?php echo $index + 1; ?> -->
            <div class="hero-slide absolute inset-0 transition-opacity duration-1000 <?php echo $index === 0 ? 'opacity-100' : 'opacity-0'; ?> flex items-center justify-center overflow-hidden">
                
                <img src="<?php echo htmlspecialchars($slide['image_path']); ?>" onerror="this.src='https://placehold.co/1200x600/1e293b/0d9488?text=Infoplus+Slide';" class="w-full h-full object-fill filter drop-shadow-2xl" alt="Slide <?php echo $index + 1; ?>">
                
                <div class="absolute inset-0 z-20 bg-gradient-to-t from-slate-950/90 via-slate-900/20 to-transparent flex items-end p-5 sm:p-8 lg:p-10 pointer-events-none">
                    <div class="text-white text-left max-w-2xl">
                        <!-- Dynamic Badge Color and Text -->
                        <span class="<?php echo htmlspecialchars($slide['badge_color']); ?> text-[10px] sm:text-xs font-black px-3.5 py-1.5 rounded-full uppercase mb-2 inline-block shadow-md border border-white/20">
                            <?php echo htmlspecialchars($slide['badge_text']); ?>
                        </span>
                        <!-- Dynamic Title Text -->
                        <h3 class="text-xl sm:text-3xl lg:text-4xl font-black leading-tight drop-shadow-md">
                            <?php echo htmlspecialchars($slide['title_text']); ?>
                        </h3>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
        
        <!-- Hero Dots -->
        <div id="heroDots" class="flex justify-center items-center space-x-2 mt-5">
            <!-- Dynamic Indicators -->
        </div>
    </div>
</section>
<!-- SECTION 3: Hotline Call Center 6781 Banner -->
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-amber-500/10 via-teal-500/10 to-emerald-500/10 border border-amber-500/30 rounded-3xl p-8 md:p-10 flex flex-col md:flex-row justify-between items-center gap-6 shadow-lg relative overflow-hidden dotted-card">
            <div class="space-y-3 max-w-2xl text-left">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/30 uppercase">
                    📞 Call Center Hotline
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                    No Internet? Call 6781 Directly!
                </h2>
                <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
                    Our dedicated Call Center agents record your CV over the phone and match you with suitable jobs instantly.
                </p>
            </div>
            <div class="flex-shrink-0">
                <a href="tel:6781" class="inline-flex items-center space-x-3 px-8 py-4 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-lg shadow-xl shadow-amber-500/20 transition-all transform hover:scale-105">
                    <span>☎️ Call 6781 Now</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 4: HOT JOBS FIRST (With Dynamic Days-Left & Auto-Closed Calculator) -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Centered Header -->
        <div class="text-center max-w-3xl mx-auto mb-10">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-extrabold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 mb-3 uppercase tracking-wider">
                🔥 Urgent Hiring
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">HOT JOBS</h2>
            <p class="mt-2 text-slate-500 dark:text-slate-400 text-sm sm:text-base">Apply to the latest high-priority job openings with good salaries.</p>
        </div>

        <!-- Automatic Sliding Track Container -->
        <div class="overflow-hidden relative rounded-3xl p-1">
            <div id="jobTrack" class="flex transition-transform duration-700 ease-in-out gap-6">
                <?php foreach ($featured_jobs as $job): ?>
                <?php 
                    $default_logo = 'https://ui-avatars.com/api/?name=Info+Plus&background=0D9488&color=fff&size=128&bold=true';
                    $company_logo = !empty($job['logo']) ? $job['logo'] : $default_logo;
                    
                    $positions = $job['positions'] ?? '1 Position';
                    $deadline = $job['deadline'] ?? '2026-08-19';
                    $description = $job['description'] ?? "Bachelor's Degree or Diploma in related field of study with relevant work experience.";
                    $career_lvl = $job['career_level'] ?? $job['experience'] ?? 'Mid Level';

                    // 🌟 DYNAMIC DAYS-LEFT & AUTO-CLOSED CALCULATOR 🌟
                    $days_left_badge = 'Open';
                    $is_expired = false;

                    if (!empty($deadline) && $deadline !== 'Open' && $deadline !== 'Negotiable') {
                        $deadline_time = strtotime($deadline);
                        $today_time = strtotime(date('Y-m-d')); // Current date
                        $diff_seconds = $deadline_time - $today_time;
                        $days_diff = floor($diff_seconds / 86400);

                        if ($days_diff > 1) {
                            $days_left_badge = $days_diff . ' days left';
                        } elseif ($days_diff == 1) {
                            $days_left_badge = '1 day left';
                        } elseif ($days_diff == 0) {
                            $days_left_badge = 'Deadline Today!';
                        } else {
                            $days_left_badge = 'Closed';
                            $is_expired = true;
                        }
                    }
                ?>
                <!-- Dotted Card Pattern -->
                <div class="min-w-[100%] sm:min-w-[48%] lg:min-w-[31.7%] dotted-card border border-slate-200/80 dark:border-slate-800 hover:border-teal-500 dark:hover:border-teal-500 p-5 rounded-3xl flex flex-col justify-between shadow-sm hover:shadow-xl transition-all duration-300 group relative">
                    
                    <div>
                        <!-- Top Badge Row (Dynamic Status based on Deadline) -->
                        <div class="flex justify-between items-center mb-4">
                            <?php if ($is_expired): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    🔴 CLOSED
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                    🔥 HOT JOB • <?php echo $days_left_badge; ?>
                                </span>
                            <?php endif; ?>

                            <button class="text-slate-400 hover:text-teal-600 transition-colors" title="Save Job">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                            </button>
                        </div>

                        <!-- Company Logo & Job Title Header -->
                        <div class="flex items-start space-x-3 mb-4">
                            <div class="w-12 h-12 rounded-2xl border border-slate-200/60 dark:border-slate-800 p-1.5 flex-shrink-0 bg-white/80 dark:bg-slate-800/80 flex items-center justify-center shadow-sm">
                                <img src="<?php echo htmlspecialchars($company_logo); ?>" 
                                     onerror="this.src='https://ui-avatars.com/api/?name=I+P&background=0D9488&color=fff'" 
                                     alt="Logo" class="w-full h-full object-contain rounded-xl">
                            </div>
                            <div class="text-left flex-1 min-w-0">
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider block">JOB TITLE</span>
                                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white leading-snug truncate group-hover:text-teal-600 transition-colors" title="<?php echo htmlspecialchars($job['title']); ?>">
                                    <a href="job-details.php?id=<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a>
                                </h3>
                            </div>
                        </div>

                        <!-- Labeled Data Rows -->
                        <div class="space-y-1.5 text-xs text-left mb-4 bg-slate-50/60 dark:bg-slate-800/40 p-3 sm:p-3.5 rounded-2xl border border-slate-200/50 dark:border-slate-800/50">
                            <!-- Company -->
                            <div class="flex items-center py-1 border-b border-slate-200/40 dark:border-slate-800/40">
                                <span class="w-28 sm:w-32 flex-shrink-0 text-[9.5px] font-bold text-slate-400 uppercase">🏢 COMPANY:</span>
                                <span class="font-extrabold text-slate-900 dark:text-white truncate text-xs"><?php echo htmlspecialchars($job['company_name'] ?? 'N/A'); ?></span>
                            </div>
                            <!-- Location -->
                            <div class="flex items-center py-1 border-b border-slate-200/40 dark:border-slate-800/40">
                                <span class="w-28 sm:w-32 flex-shrink-0 text-[9.5px] font-bold text-slate-400 uppercase">📍 LOCATION:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300 truncate text-xs"><?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?></span>
                            </div>
                            <!-- Category -->
                            <div class="flex items-center py-1 border-b border-slate-200/40 dark:border-slate-800/40">
                                <span class="w-28 sm:w-32 flex-shrink-0 text-[9.5px] font-bold text-slate-400 uppercase">🏷️ CATEGORY:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300 truncate text-xs"><?php echo htmlspecialchars($job['category'] ?? 'N/A'); ?></span>
                            </div>
                            <!-- Salary -->
                            <div class="flex items-center py-1 border-b border-slate-200/40 dark:border-slate-800/40">
                                <span class="w-28 sm:w-32 flex-shrink-0 text-[9.5px] font-bold text-slate-400 uppercase">💵 SALARY:</span>
                                <span class="font-black text-teal-600 dark:text-teal-400 truncate text-xs"><?php echo htmlspecialchars($job['salary'] ?? 'Negotiable'); ?></span>
                            </div>
                            <!-- Level & Type -->
                            <div class="flex items-center py-1 border-b border-slate-200/40 dark:border-slate-800/40">
                                <span class="w-28 sm:w-32 flex-shrink-0 text-[9.5px] font-bold text-slate-400 uppercase">🎓 LEVEL & TYPE:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300 truncate text-xs"><?php echo htmlspecialchars($career_lvl); ?> (<?php echo htmlspecialchars($job['job_type'] ?? 'Full Time'); ?>)</span>
                            </div>
                            <!-- Deadline Date with Days-Left Badge -->
                            <div class="flex items-center pt-1">
                                <span class="w-28 sm:w-32 flex-shrink-0 text-[9.5px] font-extrabold text-rose-500 uppercase">⏳ DEADLINE DATE:</span>
                                <span class="font-black text-rose-500 text-xs">
                                    <?php echo htmlspecialchars($deadline); ?> 
                                    <span class="text-[9px] font-bold <?php echo $is_expired ? 'text-rose-600' : 'text-slate-400'; ?> ml-1">(<?php echo $days_left_badge; ?>)</span>
                                </span>
                            </div>
                        </div>

                        <!-- Snippet -->
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 text-left line-clamp-2 leading-relaxed mb-4">
                            <?php echo htmlspecialchars($description); ?>
                        </p>
                    </div>

                    <!-- Footer Row: Action Buttons & Views/Share -->
                    <div class="border-t border-slate-200/60 dark:border-slate-800 pt-4 mt-auto">
                        <div class="flex items-center gap-2 mb-3">
                            <!-- If Expired, lock apply button -->
                            <?php if ($is_expired): ?>
                                <button disabled class="flex-1 py-2.5 text-center rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-500 text-xs font-bold cursor-not-allowed border border-slate-300 dark:border-slate-700">
                                    Vacancy Closed
                                </button>
                            <?php else: ?>
                                <a href="job-details.php?id=<?php echo $job['id']; ?>" class="flex-1 py-2.5 text-center rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold transition-all shadow-md shadow-teal-600/20">
                                    Apply Now
                                </a>
                            <?php endif; ?>

                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="px-4 py-2.5 text-center rounded-xl border border-teal-600 text-teal-600 hover:bg-teal-50 dark:hover:bg-slate-800 text-xs font-bold transition-all">
                                Read More ↗
                            </a>
                        </div>

                       <!-- Action Footer Row (Views & Share) -->
                                <div class="flex items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800/60 pt-4 mt-2">
                                    <div class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 font-black text-[10px] sm:text-xs border border-teal-500/20">
                                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        <span><?php echo number_format($job['views'] ?? 0); ?> Views</span>
                                    </div>
                                    
                                    <button onclick="shareJob('<?php echo addslashes($job['title']); ?>', 'job-details.php?id=<?php echo $job['id']; ?>')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-teal-600 dark:text-slate-300 font-bold text-[10px] sm:text-xs transition-colors shadow-sm">
                                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                                        <span>Share</span>
                                    </button>
                                </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Dynamic Indicator Dots Container -->
        <div id="jobDots" class="flex justify-center items-center space-x-2 mt-8">
            <!-- JavaScript populates active indicators -->
        </div>
    </div>
</section>
<!-- SECTION 5: Popular Career Sectors (With Dotted Card Pattern) -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">Popular Career Sectors</h2>
            <p class="mt-2 text-slate-500 dark:text-slate-400 text-sm sm:text-base">Explore vacancies grouped by key professional industries.</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
            <?php
            $sectors = [
                ['name' => 'Business & Finance', 'count' => 120, 'icon' => '💼', 'color' => 'bg-blue-500/10 text-blue-600'],
                ['name' => 'Engineering & ICT', 'count' => 85, 'icon' => '💻', 'color' => 'bg-teal-500/10 text-teal-600'],
                ['name' => 'Health & Medical', 'count' => 40, 'icon' => '🩺', 'color' => 'bg-emerald-500/10 text-emerald-600'],
                ['name' => 'Education & Teaching', 'count' => 95, 'icon' => '🏫', 'color' => 'bg-purple-500/10 text-purple-600'],
                ['name' => 'Low/Medium Skilled', 'count' => 57, 'icon' => '🛠️', 'color' => 'bg-amber-500/10 text-amber-600'],
                ['name' => 'Hotel & Hospitality', 'count' => 29, 'icon' => '🏨', 'color' => 'bg-indigo-500/10 text-indigo-600'],
                ['name' => 'Legal Services', 'count' => 16, 'icon' => '⚖️', 'color' => 'bg-rose-500/10 text-rose-600'],
                ['name' => 'Natural Sciences', 'count' => 30, 'icon' => '🔬', 'color' => 'bg-sky-500/10 text-sky-600'],
            ];
            foreach ($sectors as $s):
            ?>
            <!-- Dotted Card Pattern Applied -->
            <a href="jobs.php?sector=<?php echo urlencode($s['name']); ?>" class="dotted-card hover:bg-white dark:hover:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 text-center transition-all shadow-sm hover:shadow-md group">
                <span class="inline-flex p-3 rounded-xl mb-3 text-2xl <?php echo $s['color']; ?>">
                    <?php echo $s['icon']; ?>
                </span>
                <h3 class="font-bold text-slate-800 dark:text-white text-sm group-hover:text-teal-600 transition-colors"><?php echo $s['name']; ?></h3>
                <p class="text-xs text-slate-400 mt-1"><?php echo $s['count']; ?> Open Positions</p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<!-- SECTION 6: Top Hiring Companies (100% Strict Unique Companies - Zero Duplicates!) -->
<section class="py-16 bg-slate-50/50 dark:bg-slate-950/30 border-t border-b border-slate-200/50 dark:border-slate-800/50 my-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center mb-10">
        <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white">Top Hiring Companies</h2>
        <p class="mt-2 text-slate-500 dark:text-slate-400 text-sm">Below are some of the companies with a high number of vacancy announcements.</p>
    </div>
    
    <?php
    $top_companies = [];
    if (isset($pdo) && $pdo !== null) {
        try {
            // 🌟 MAGIC SQL: Group ONLY by company_name (Case-insensitive & Logo-independent)
            $stmt_top = $pdo->query("
                SELECT 
                    TRIM(company_name) AS company_name, 
                    MAX(NULLIF(logo, '')) AS logo, 
                    MAX(id) AS max_id 
                FROM jobs 
                WHERE is_top_company = 1 AND status = 'active' 
                GROUP BY LOWER(TRIM(company_name)) 
                ORDER BY max_id DESC
            ");
            $top_companies_raw = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

            // 🌟 PHP DEDUPLICATION SAFETY NET (Guarantees Each Company Appears Exactly Once)
            $seen_names = [];
            foreach ($top_companies_raw as $tc) {
                $clean_name = strtolower(trim($tc['company_name']));
                if (!in_array($clean_name, $seen_names)) {
                    $seen_names[] = $clean_name;
                    $top_companies[] = $tc;
                }
            }
        } catch (PDOException $e) {
            $top_companies = [];
        }
    }

    // Fallback default mock companies if DB has no top companies yet
    if (empty($top_companies)) {
        $top_companies = [
            ['company_name' => 'Save the Children', 'logo' => 'https://ui-avatars.com/api/?name=SC&background=ef4444&color=fff&size=128&bold=true'],
            ['company_name' => 'Ethiopian Airlines', 'logo' => 'https://ui-avatars.com/api/?name=EA&background=16a34a&color=fff&size=128&bold=true'],
            ['company_name' => 'Dashen Bank', 'logo' => 'https://ui-avatars.com/api/?name=DB&background=1e3a8a&color=fff&size=128&bold=true'],
            ['company_name' => 'Safaricom Ethiopia', 'logo' => 'https://ui-avatars.com/api/?name=SE&background=10b981&color=fff&size=128&bold=true'],
            ['company_name' => 'Awash Bank', 'logo' => 'https://ui-avatars.com/api/?name=AB&background=0f766e&color=fff&size=128&bold=true'],
        ];
    }
    ?>

    <!-- Mask Container -->
    <div class="overflow-hidden relative w-full marquee-mask py-4">
        <!-- Marquee Track Reversed (Left to Right Movement) -->
        <div class="animate-marquee-reverse flex items-center space-x-6 px-4">
            
            <!-- Loop 1 (100% Unique Companies) -->
            <?php foreach ($top_companies as $company): ?>
            <?php 
                $c_name = $company['company_name'] ?? 'Company';
                $c_logo = !empty($company['logo']) ? $company['logo'] : 'logo.png';
            ?>
            <div @click="window.location.href='jobs.php?q=' + encodeURIComponent('<?php echo addslashes($c_name); ?>')" class="flex flex-col items-center justify-center dotted-card border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-lg hover:border-teal-500/30 transition-all min-w-[200px] h-[200px] flex-shrink-0 group cursor-pointer" title="Click to view all jobs by <?php echo htmlspecialchars($c_name); ?>">
                <div class="w-20 h-20 mb-4 rounded-2xl overflow-hidden bg-white/80 dark:bg-slate-800 p-2 border border-slate-200/60 dark:border-slate-700 group-hover:scale-110 transition-transform duration-300 flex items-center justify-center">
                    <img src="<?php echo htmlspecialchars($c_logo); ?>" onerror="this.src='https://ui-avatars.com/api/?name=I+P&background=0D9488&color=fff'" alt="Logo" class="w-full h-full object-contain rounded-xl">
                </div>
                <h3 class="font-bold text-sm text-slate-700 dark:text-slate-200 text-center group-hover:text-teal-600 transition-colors truncate max-w-[180px]">
                    <?php echo htmlspecialchars($c_name); ?>
                </h3>
            </div>
            <?php endforeach; ?>

            <!-- Loop 2 (Continuous Infinite Marquee Seamless Effect) -->
            <?php foreach ($top_companies as $company): ?>
            <?php 
                $c_name = $company['company_name'] ?? 'Company';
                $c_logo = !empty($company['logo']) ? $company['logo'] : 'logo.png';
            ?>
            <div @click="window.location.href='jobs.php?q=' + encodeURIComponent('<?php echo addslashes($c_name); ?>')" class="flex flex-col items-center justify-center dotted-card border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-lg hover:border-teal-500/30 transition-all min-w-[200px] h-[200px] flex-shrink-0 group cursor-pointer" title="Click to view all jobs by <?php echo htmlspecialchars($c_name); ?>">
                <div class="w-20 h-20 mb-4 rounded-2xl overflow-hidden bg-white/80 dark:bg-slate-800 p-2 border border-slate-200/60 dark:border-slate-700 group-hover:scale-110 transition-transform duration-300 flex items-center justify-center">
                    <img src="<?php echo htmlspecialchars($c_logo); ?>" onerror="this.src='https://ui-avatars.com/api/?name=I+P&background=0D9488&color=fff'" alt="Logo" class="w-full h-full object-contain rounded-xl">
                </div>
                <h3 class="font-bold text-sm text-slate-700 dark:text-slate-200 text-center group-hover:text-teal-600 transition-colors truncate max-w-[180px]">
                    <?php echo htmlspecialchars($c_name); ?>
                </h3>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</section>
<!-- SECTION 7: Statistics Section (Deep Water Dark Glassmorphism) -->
<section class="py-12 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Outer Deep Ocean Container -->
        <div class="relative overflow-hidden rounded-[2.5rem] border border-teal-500/30 dark:border-teal-400/30 p-10 sm:p-12 text-center text-white shadow-2xl shadow-teal-950/50 bg-gradient-to-r from-slate-950 via-teal-950/90 via-slate-900 to-slate-950 backdrop-blur-2xl">
            
            <!-- Floating Water Wave Glow Accents -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-teal-500/20 rounded-full blur-3xl pointer-events-none animate-pulse"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl pointer-events-none animate-pulse"></div>
            
            <!-- Water Dotted Mesh Pattern -->
            <div class="absolute inset-0 bg-[radial-gradient(#0d9488_1.2px,transparent_1.2px)] [background-size:16px_16px] opacity-20 pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-8 divide-x-0 md:divide-x divide-slate-800/60">
                <div class="p-2">
                    <span class="text-3xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-teal-300 via-cyan-400 to-emerald-300 block mb-1 drop-shadow-md">1.5K+</span>
                    <span class="text-xs uppercase tracking-widest text-teal-200/80 font-extrabold">Registered Employers</span>
                </div>
                <div class="p-2">
                    <span class="text-3xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 via-teal-400 to-emerald-300 block mb-1 drop-shadow-md">45K+</span>
                    <span class="text-xs uppercase tracking-widest text-teal-200/80 font-extrabold">Job Seekers</span>
                </div>
                <div class="p-2">
                    <span class="text-3xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-300 to-cyan-400 block mb-1 drop-shadow-md">120+</span>
                    <span class="text-xs uppercase tracking-widest text-teal-200/80 font-extrabold">Active Vacancies</span>
                </div>
                <div class="p-2">
                    <span class="text-3xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-teal-300 to-emerald-300 block mb-1 drop-shadow-md">50+</span>
                    <span class="text-xs uppercase tracking-widest text-teal-200/80 font-extrabold">Verified Partners</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 🌟 SECTION 8: Why Choose Us & Post Vacancy Workflow (With Mobile Zigzag Line & Click-Zoom Images) 🌟 -->
<section class="py-8 sm:py-16 bg-slate-50/50 dark:bg-slate-950/30 border-t border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        
        <!-- Row 1: Why Choose Us -->
        <div class="grid grid-cols-12 gap-2 sm:gap-12 items-center mb-10 sm:mb-20">
            <!-- Left Text -->
            <div class="col-span-8 sm:col-span-6 text-left space-y-2 sm:space-y-6">
                <h2 class="text-lg sm:text-4xl font-extrabold text-slate-900 dark:text-white leading-tight">Why Choose Us</h2>
                <p class="text-slate-500 dark:text-slate-400 text-[9px] sm:text-sm leading-relaxed">
                    We make job searching simpler, faster, and more accessible connecting you to the right opportunities.
                </p>

                <div class="space-y-2 sm:space-y-4 pt-1">
                    <div class="flex items-start space-x-1.5 sm:space-x-4">
                        <div class="p-1.5 sm:p-3 rounded-xl sm:rounded-2xl bg-teal-500/10 text-teal-600 font-bold text-xs sm:text-xl">🎯</div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-[10px] sm:text-base">Smart Job Matching</h3>
                            <p class="text-[8.5px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">Jobs come from trusted, quality-checked database.</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-1.5 sm:space-x-4">
                        <div class="p-1.5 sm:p-3 rounded-xl sm:rounded-2xl bg-teal-500/10 text-teal-600 font-bold text-xs sm:text-xl">🌐</div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-[10px] sm:text-base">Accessible Everywhere</h3>
                            <p class="text-[8.5px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">Access via web, Call Center 6781 & Telegram bot.</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-1.5 sm:space-x-4">
                        <div class="p-1.5 sm:p-3 rounded-xl sm:rounded-2xl bg-teal-500/10 text-teal-600 font-bold text-xs sm:text-xl">⚡</div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-[10px] sm:text-base">Easy to Use</h3>
                            <p class="text-[8.5px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">Intuitive experience across Ethiopia.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-1 sm:pt-4">
                    <a href="learn-seeker.php" class="inline-flex items-center space-x-1 sm:space-x-2 px-3 py-1.5 sm:px-6 sm:py-3 rounded-xl sm:rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-bold text-[9px] sm:text-sm transition-all shadow-md shadow-teal-600/20">
                        <span>Learn More (Job Seekers)</span>
                        <span>→</span>
                    </a>
                </div>
            </div>

            <!-- Right 3D Seeker Graphic (Click to Zoom Enabled) -->
            <div class="col-span-4 sm:col-span-6 flex justify-center items-center relative py-2 sm:py-12">
                <div class="relative w-full max-w-[130px] sm:max-w-md min-h-[160px] sm:min-h-[320px] lg:min-h-[460px] flex flex-col items-center justify-center">
                    <div class="absolute inset-0 bg-gradient-to-tr from-teal-500/20 via-emerald-500/15 to-amber-500/20 rounded-full blur-xl sm:blur-3xl pointer-events-none"></div>

                    <!-- Floating Thought Bubble -->
                    <div class="absolute -top-4 sm:-top-16 right-0 sm:-right-2 z-20 animate-float">
                        <div class="dotted-card border border-teal-500/40 shadow-md px-1.5 py-0.5 sm:px-6 sm:py-3.5 rounded-xl sm:rounded-[2.5rem] flex items-center space-x-1 sm:space-x-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl">
                            <span class="text-xs sm:text-3xl filter drop-shadow-md">💭</span>
                            <div class="text-left">
                                <span class="text-[6.5px] sm:text-[10px] font-extrabold text-teal-600 dark:text-teal-400 uppercase tracking-widest block whitespace-nowrap">Salary Target</span>
                                <span class="text-[8px] sm:text-2xl font-black text-slate-900 dark:text-white whitespace-nowrap">50,000 <span class="text-[7px] sm:text-xs text-teal-600 font-bold">ETB/M</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- 3D Image (Tapping/Clicking opens Zoom Modal) -->
                    <div class="w-full h-full flex items-center justify-center p-1 sm:p-2 z-10 my-1 sm:my-4">
                        <img src="seeker_transparent.png" 
                             onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/businessman-walking-with-briefcase-5353000-4468694.png'" 
                             alt="3D Job Seeker Profile" 
                             onclick="openImageModal(this.src, 'Job Seeker Matchmaking Profile')"
                             class="w-full h-auto max-h-[140px] sm:max-h-[300px] lg:max-h-[430px] object-contain filter drop-shadow-2xl hover:scale-105 transition-transform duration-500 cursor-pointer">
                    </div>

                    <!-- Floating Badge -->
                    <div class="absolute -bottom-3 sm:-bottom-8 left-0 sm:left-4 z-20 animate-float-reverse">
                        <div class="dotted-card border border-slate-200/80 dark:border-slate-800 px-1.5 py-0.5 sm:px-5 sm:py-3 rounded-lg sm:rounded-2xl flex items-center space-x-1 sm:space-x-3 shadow-md bg-white/95 dark:bg-slate-900/95 backdrop-blur-md">
                            <div class="h-3 w-3 sm:h-6 sm:w-6 rounded-full bg-teal-500 flex items-center justify-center shadow-inner">
                                <span class="text-white font-extrabold text-[7px] sm:text-xs">✓</span>
                            </div>
                            <span class="text-[7px] sm:text-sm font-extrabold text-slate-800 dark:text-slate-200 tracking-wide whitespace-nowrap">98% Match</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Central Matchmaking Hub (Zigzag Flow Arrow Line VISIBLE ON ALL SCREENS) -->
        <div class="relative py-6 sm:py-12 my-2 sm:my-6 flex flex-col items-center justify-center z-30">
            
            <!-- Curving Dashed Arrow Line (Visible on Mobile & PC) -->
            <div class="block absolute inset-0 pointer-events-none -z-10 overflow-visible">
                <svg class="w-full h-full" viewBox="0 0 1000 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="tealToAmberHub" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0d9488" />
                            <stop offset="50%" stop-color="#10b981" />
                            <stop offset="100%" stop-color="#f59e0b" />
                        </linearGradient>
                        <linearGradient id="amberToTealHub" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#f59e0b" />
                            <stop offset="50%" stop-color="#3b82f6" />
                            <stop offset="100%" stop-color="#0d9488" />
                        </linearGradient>
                        <filter id="glowPath" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="3" result="blur" />
                            <feComposite in="SourceGraphic" in2="blur" operator="over" />
                        </filter>
                    </defs>

                    <!-- Curve 1: Top Right (Seeker) -> Central Hub -->
                    <path d="M 780 -60 Q 660 70, 500 120" stroke="url(#tealToAmberHub)" stroke-width="5" stroke-dasharray="10 10" filter="url(#glowPath)" class="animate-pulse" />
                    
                    <!-- Curve 2: Central Hub -> Bottom Left (Employer) -->
                    <path d="M 500 120 Q 340 170, 220 280" stroke="url(#amberToTealHub)" stroke-width="5" stroke-dasharray="10 10" filter="url(#glowPath)" class="animate-pulse" />
                </svg>
            </div>

            <!-- Central Hub Badge (Click to Zoom Enabled) -->
            <div class="relative group cursor-pointer" onclick="openImageModal('logo.png', 'Infoplus Central HR Matchmaking Hub')">
                <div class="absolute -inset-3 sm:-inset-6 bg-gradient-to-r from-teal-500 via-amber-500 to-emerald-500 rounded-full blur-lg sm:blur-2xl opacity-60 group-hover:opacity-100 transition duration-500 animate-pulse"></div>
                
                <div class="relative dotted-card border-2 sm:border-4 border-teal-500/60 dark:border-teal-400/60 px-4 py-2 sm:px-12 sm:py-7 rounded-full shadow-2xl flex items-center space-x-2 sm:space-x-6 bg-white/95 dark:bg-slate-900/95 backdrop-blur-3xl transform hover:scale-105 transition-all duration-300">
                    <div class="h-8 w-8 sm:h-20 sm:w-20 rounded-xl sm:rounded-3xl bg-gradient-to-tr from-teal-600 via-teal-500 to-amber-500 p-1 sm:p-2.5 flex items-center justify-center shadow-xl shadow-teal-500/40 flex-shrink-0 animate-spin-slow border-2 border-white/40">
                        <img src="logo.png" onerror="this.src='https://ui-avatars.com/api/?name=Info+Plus&background=0D9488&color=fff&size=128&bold=true'" alt="Infoplus Logo" class="w-full h-full object-contain filter drop-shadow-md">
                    </div>
                    
                    <div class="text-left">
                        <span class="text-[8px] sm:text-sm font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest block mb-0.5 whitespace-nowrap">Central HR Hub. Call 6781.</span>
                        <span class="text-xs sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white tracking-tight whitespace-nowrap">Infoplus Matchmaking</span>
                    </div>

                    <div class="h-5 w-5 sm:h-12 sm:w-12 rounded-full bg-teal-500/20 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xs sm:text-xl font-bold animate-bounce shadow-inner border border-teal-500/30">
                        ⚡
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Post Vacancy Workflow -->
        <div class="dotted-card border border-slate-200/80 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-3 sm:p-8 lg:p-12">
            <div class="grid grid-cols-12 gap-2 sm:gap-12 items-center">
                
                <!-- LEFT SIDE: CEO / Employer 3D Graphic (Click to Zoom Enabled) -->
                <div class="col-span-4 sm:col-span-6 flex justify-center items-center relative py-2 sm:py-6">
                    <div class="relative w-full max-w-[130px] sm:max-w-md min-h-[160px] sm:min-h-[320px] lg:min-h-[440px] flex flex-col items-center justify-center">
                        <div class="absolute inset-0 bg-gradient-to-tr from-amber-500/20 via-teal-500/15 to-emerald-500/20 rounded-full blur-xl sm:blur-3xl pointer-events-none"></div>

                        <div class="absolute -top-4 sm:-top-14 left-0 sm:-left-2 z-20 animate-float">
                            <div class="dotted-card border border-amber-500/40 shadow-md px-1.5 py-0.5 sm:px-6 sm:py-3.5 rounded-xl sm:rounded-[2.5rem] flex items-center space-x-1 sm:space-x-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl">
                                <span class="text-xs sm:text-3xl filter drop-shadow-md">💼</span>
                                <div class="text-left">
                                    <span class="text-[6.5px] sm:text-[10px] font-extrabold text-amber-600 dark:text-amber-400 uppercase tracking-widest block whitespace-nowrap">Company Goal</span>
                                    <span class="text-[8px] sm:text-2xl font-black text-slate-900 dark:text-white whitespace-nowrap">HIRE <span class="text-[7px] sm:text-xs text-amber-600 font-bold">TALENT</span></span>
                                </div>
                            </div>
                        </div>

                        <!-- 3D CEO Image (Click to Zoom) -->
                        <div class="w-full h-full flex items-center justify-center p-1 z-10 my-1 sm:my-4">
                            <img src="employer_transparent.png" 
                                 onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/businessman-working-on-laptop-5353006-4468700.png'" 
                                 alt="3D Employer CEO" 
                                 onclick="openImageModal(this.src, 'Employer Hiring Enterprise Profile')"
                                 class="w-full h-auto max-h-[140px] sm:max-h-[300px] lg:max-h-[420px] object-contain filter drop-shadow-2xl hover:scale-105 transition-transform duration-500 cursor-pointer">
                        </div>

                        <div class="absolute -bottom-3 sm:-bottom-8 right-0 sm:right-4 z-20 animate-float-reverse">
                            <div class="dotted-card border border-slate-200/80 dark:border-slate-800 px-1.5 py-0.5 sm:px-5 sm:py-3 rounded-lg sm:rounded-2xl flex items-center space-x-1 sm:space-x-3 shadow-md bg-white/95 dark:bg-slate-900/95 backdrop-blur-md">
                                <div class="h-3 w-3 sm:h-6 sm:w-6 rounded-full bg-amber-500 flex items-center justify-center shadow-inner">
                                    <span class="text-slate-950 font-extrabold text-[7px] sm:text-xs">★</span>
                                </div>
                                <span class="text-[7px] sm:text-sm font-extrabold text-slate-800 dark:text-slate-200 tracking-wide whitespace-nowrap">Verified HR</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: Post Vacancy Steps -->
                <div class="col-span-8 sm:col-span-6 text-left space-y-2 sm:space-y-6">
                    <div>
                        <span class="inline-flex px-2 py-0.5 sm:px-3 sm:py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-[8px] sm:text-xs border border-amber-500/20 mb-1 sm:mb-3">
                            🏢 Enterprise HR
                        </span>
                        <h2 class="text-base sm:text-4xl font-extrabold text-slate-900 dark:text-white">Post Vacancy</h2>
                        <p class="mt-0.5 sm:mt-2 text-slate-500 dark:text-slate-400 text-[8.5px] sm:text-sm leading-tight sm:leading-relaxed">
                            Infoplus provides affordable cloud HR services. Post vacancies and reach verified candidates.
                        </p>
                    </div>

                    <div class="space-y-1.5 sm:space-y-4 pt-1">
                        <div class="flex items-start space-x-1.5 sm:space-x-4 bg-white/40 dark:bg-slate-900/40 p-1.5 sm:p-3.5 rounded-xl sm:rounded-2xl border border-slate-200/50 dark:border-slate-800/50">
                            <span class="h-5 w-5 sm:h-8 sm:w-8 rounded-lg sm:rounded-xl bg-teal-500 text-white font-black flex items-center justify-center text-[9px] sm:text-xs flex-shrink-0">1</span>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-[10px] sm:text-sm">Receive</h3>
                                <p class="text-[8px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">Share vacancy details through web or call center.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-1.5 sm:space-x-4 bg-white/40 dark:bg-slate-900/40 p-1.5 sm:p-3.5 rounded-xl sm:rounded-2xl border border-slate-200/50 dark:border-slate-800/50">
                            <span class="h-5 w-5 sm:h-8 sm:w-8 rounded-lg sm:rounded-xl bg-teal-500 text-white font-black flex items-center justify-center text-[9px] sm:text-xs flex-shrink-0">2</span>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-[10px] sm:text-sm">Validate</h3>
                                <p class="text-[8px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">HR team verifies vacancy within 24 hours.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-1.5 sm:space-x-4 bg-white/40 dark:bg-slate-900/40 p-1.5 sm:p-3.5 rounded-xl sm:rounded-2xl border border-slate-200/50 dark:border-slate-800/50">
                            <span class="h-5 w-5 sm:h-8 sm:w-8 rounded-lg sm:rounded-xl bg-teal-500 text-white font-black flex items-center justify-center text-[9px] sm:text-xs flex-shrink-0">3</span>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-[10px] sm:text-sm">Publish</h3>
                                <p class="text-[8px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">Published across Web, Telegram & Call Center 6781.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-1 sm:pt-2">
                        <a href="learn-employer.php" class="inline-flex items-center space-x-1 sm:space-x-2 px-3 py-1.5 sm:px-8 sm:py-3.5 rounded-xl sm:rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-bold text-[9px] sm:text-sm transition-all shadow-lg shadow-teal-600/20">
                            <span>Learn More (Employers)</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<!-- 🌟 SECTION 9: Dynamic FAQ Section (Side-By-Side / Wal Cinaatti on Mobile & PC) 🌟 -->
<section class="py-8 sm:py-16 overflow-hidden">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        
        <div class="text-center mb-6 sm:mb-10">
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white">Frequently Asked Questions</h2>
            <p class="mt-1 sm:mt-2 text-slate-500 dark:text-slate-400 text-xs sm:text-sm">Have questions? We're here to help you get started.</p>
        </div>

        <!-- Filter Pills Toggle -->
        <div class="flex justify-center mb-6 sm:mb-12">
            <div class="inline-flex p-1 sm:p-1.5 rounded-full bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 shadow-inner">
                <button id="faqSeekersBtn" class="px-5 py-2 sm:px-8 sm:py-2.5 rounded-full text-xs font-bold transition-all bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-md">
                    For Job Seekers
                </button>
                <button id="faqCompaniesBtn" class="px-5 py-2 sm:px-8 sm:py-2.5 rounded-full text-xs font-bold transition-all text-slate-600 dark:text-slate-300 hover:text-teal-600">
                    For Companies
                </button>
            </div>
        </div>

        <!-- Main FAQ Grid Layout (100% Side-by-Side / Wal Cinaatti on ALL SCREENS) -->
        <div class="grid grid-cols-12 gap-2 sm:gap-10 items-center">
            
<!-- Dynamic Image Container (Thought Bubble Shifted Up Above Head) -->
            <div id="faqImageContainer" class="col-span-4 sm:col-span-5 order-1 transition-all duration-700 ease-in-out flex justify-center items-center relative py-4 sm:py-8">
                
                <div class="relative w-full max-w-[130px] sm:max-w-sm min-h-[160px] sm:min-h-[320px] lg:min-h-[460px] flex flex-col items-center justify-center">
                    
                    <!-- Background Glow -->
                    <div id="faqGlow" class="absolute inset-0 bg-gradient-to-tr from-teal-500/20 to-emerald-500/20 rounded-full blur-xl sm:blur-3xl pointer-events-none transition-colors duration-500"></div>

                    <!-- 1. FLOATING THOUGHT BUBBLE (Shifted Higher Up Above Character's Head) -->
                    <div class="absolute -top-8 sm:-top-16 right-0 sm:-right-4 z-20 animate-float">
                        <div class="dotted-card border border-teal-500/40 shadow-xl px-2 py-1 sm:px-5 sm:py-3.5 rounded-xl sm:rounded-[2.5rem] flex items-center space-x-1 sm:space-x-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl">
                            <span class="text-xs sm:text-3xl filter drop-shadow-md">🎧</span>
                            <div class="text-left">
                                <span class="text-[6.5px] sm:text-[10px] font-black text-teal-600 dark:text-teal-400 uppercase tracking-widest block whitespace-nowrap">Info+ Fix Everything</span>
                                <span class="text-[8px] sm:text-lg font-black text-slate-900 dark:text-white whitespace-nowrap">Call <span class="text-teal-600 font-black">6781</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. THOUGHT TRAIL DOTS -->
                    <div class="absolute top-0 sm:top-2 left-4 sm:left-20 flex flex-col items-center space-y-0.5 sm:space-y-1 z-10 opacity-90 animate-pulse">
                        <span class="h-1.5 w-1.5 sm:h-3.5 sm:w-3.5 rounded-full bg-teal-500 shadow-md shadow-teal-500/50"></span>
                        <span class="h-1 w-1 sm:h-2.5 sm:w-2.5 rounded-full bg-teal-500/70"></span>
                    </div>

                    <!-- 3. TRANSPARENT CHARACTER IMAGE (Head 100% Unblocked) -->
                    <div class="relative z-10 w-full animate-float flex justify-center items-center my-1 sm:my-4">
                        <img id="faqDynamicImage" 
                             src="jobseeker.png" 
                             onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/woman-working-on-laptop-5353003-4468697.png'" 
                             alt="FAQ Illustration" 
                             class="w-full h-auto max-h-[140px] sm:max-h-[300px] lg:max-h-[420px] object-contain filter drop-shadow-2xl hover:scale-105 transition-all duration-500 transparent-graphic">
                    </div>

                    <!-- 4. BOTTOM FLOATING BADGE -->
                    <div class="absolute -bottom-3 sm:-bottom-8 right-0 sm:right-4 z-20 animate-float-reverse">
                        <div class="dotted-card border border-slate-200/80 dark:border-slate-800 px-1.5 py-0.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-2xl flex items-center space-x-1 sm:space-x-2.5 shadow-md bg-white/95 dark:bg-slate-900/95 backdrop-blur-md">
                            <div class="h-3 w-3 sm:h-6 sm:w-6 rounded-full bg-teal-500 flex items-center justify-center shadow-inner text-white font-black text-[7px] sm:text-xs">
                                ☎️
                            </div>
                            <span class="text-[7px] sm:text-xs font-extrabold text-slate-800 dark:text-slate-200 tracking-wide whitespace-nowrap">24/7 Support</span>
                        </div>
                    </div>

                </div>

            </div>
            <!-- FAQ List Container (67% Width on Mobile) -->
            <div id="faqListContainer" class="col-span-8 sm:col-span-7 order-2 transition-all duration-700 ease-in-out">
                <div id="faqList" class="space-y-2 sm:space-y-6 text-left">
                    <!-- JavaScript dynamically populates cards here -->
                </div>
            </div>

        </div>

    </div>
</section>
<!-- 🌟 SECTION 9.5: Core Services Section (Horizontal Touch Slider on Mobile, 3-Col Grid on PC) 🌟 -->
<section class="py-10 sm:py-20 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Header (All English) -->
        <div class="text-center max-w-3xl mx-auto mb-8 sm:mb-16">
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 mb-3 uppercase tracking-widest">
                🛠️ Primary Deployment
            </span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                Services
            </h2>
            <p class="mt-2.5 sm:mt-3 text-slate-500 dark:text-slate-400 text-xs sm:text-base leading-relaxed">
                Infoplus offers digital career profiling, job application, and subscription-based vacancy announcement notifications to job seekers across Ethiopia.
            </p>
        </div>

        <!-- 3 Services Container (Horizontal Snap Slider on Mobile, 3-Col Grid on PC) -->
        <div id="servicesTrack" class="flex overflow-x-auto sm:grid sm:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 pb-4 pt-1 px-1 snap-x snap-mandatory no-scrollbar sm:overflow-visible scroll-smooth">
            
            <!-- Service 1: Build Career Profile -->
            <div class="w-[85vw] max-w-[330px] sm:w-auto sm:max-w-none flex-shrink-0 snap-center dotted-card bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-8 shadow-sm hover:shadow-2xl hover:border-teal-500/50 transition-all duration-500 flex flex-col justify-between group">
                <div>
                    <!-- Media Container -->
                    <div class="w-full aspect-[4/3] rounded-2xl overflow-hidden mb-5 bg-slate-100 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-center relative shadow-inner group-hover:scale-[1.02] transition-transform duration-500">
                        <video autoplay loop muted playsinline class="w-full h-full object-cover" poster="nofound.png">
                            <source src="videos/service1.mp4" type="video/mp4">
                        </video>
                    </div>

                    <!-- Texts -->
                    <h3 class="text-base sm:text-xl font-black text-slate-900 dark:text-white mb-2 text-left group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                        Build Career Profile
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed text-left">
                        Compose your career profile with verified skill standards and identifiers to showcase your assessed competencies in your sector of expertise.
                    </p>
                </div>

                <div class="pt-5 mt-5 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <a href="learn-seeker.php" class="text-xs sm:text-sm font-black text-teal-600 dark:text-teal-400 hover:text-teal-500 flex items-center gap-1.5 transition-all">
                        <span>Read More</span> 
                        <span class="transform group-hover:translate-x-1 transition-transform">›</span>
                    </a>
                    <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-teal-500/10 text-teal-600 flex items-center justify-center text-[10px] sm:text-xs font-bold">01</span>
                </div>
            </div>

            <!-- Service 2: Job Application -->
            <div class="w-[85vw] max-w-[330px] sm:w-auto sm:max-w-none flex-shrink-0 snap-center dotted-card bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-8 shadow-sm hover:shadow-2xl hover:border-teal-500/50 transition-all duration-500 flex flex-col justify-between group">
                <div>
                    <!-- Media Container -->
                    <div class="w-full aspect-[4/3] rounded-2xl overflow-hidden mb-5 bg-slate-100 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-center relative shadow-inner group-hover:scale-[1.02] transition-transform duration-500">
                        <video autoplay loop muted playsinline class="w-full h-full object-cover" poster="nofound.png">
                            <source src="videos/service2.mp4" type="video/mp4">
                        </video>
                       
                    </div>

                    <!-- Texts -->
                    <h3 class="text-base sm:text-xl font-black text-slate-900 dark:text-white mb-2 text-left group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                        Job Application
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed text-left">
                        Apply for jobs with just a few clicks, and track the progress of your applications through real-time notifications on multiple platforms.
                    </p>
                </div>

                <div class="pt-5 mt-5 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <a href="jobs.php" class="text-xs sm:text-sm font-black text-teal-600 dark:text-teal-400 hover:text-teal-500 flex items-center gap-1.5 transition-all">
                        <span>Read More</span> 
                        <span class="transform group-hover:translate-x-1 transition-transform">›</span>
                    </a>
                    <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-teal-500/10 text-teal-600 flex items-center justify-center text-[10px] sm:text-xs font-bold">02</span>
                </div>
            </div>

            <!-- Service 3: Vacancy Notification -->
            <div class="w-[85vw] max-w-[330px] sm:w-auto sm:max-w-none flex-shrink-0 snap-center dotted-card bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-8 shadow-sm hover:shadow-2xl hover:border-teal-500/50 transition-all duration-500 flex flex-col justify-between group">
                <div>
                    <!-- Media Container -->
                    <div class="w-full aspect-[4/3] rounded-2xl overflow-hidden mb-5 bg-slate-100 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-center relative shadow-inner group-hover:scale-[1.02] transition-transform duration-500">
                        <video autoplay loop muted playsinline class="w-full h-full object-cover" poster="nofound.png">
                            <source src="videos/service3.mp4" type="video/mp4">
                        </video>
                       
                    </div>

                    <!-- Texts -->
                    <h3 class="text-base sm:text-xl font-black text-slate-900 dark:text-white mb-2 text-left group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                        Vacancy Notification
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed text-left">
                        Take advantage of multi-platform vacancy notifications via SMS, Telegram alerts, and Hotline 6781 voice support tailored to your chosen industry.
                    </p>
                </div>

                <div class="pt-5 mt-5 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <a href="about.php" class="text-xs sm:text-sm font-black text-teal-600 dark:text-teal-400 hover:text-teal-500 flex items-center gap-1.5 transition-all">
                        <span>Read More</span> 
                        <span class="transform group-hover:translate-x-1 transition-transform">›</span>
                    </a>
                    <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-teal-500/10 text-teal-600 flex items-center justify-center text-[10px] sm:text-xs font-bold">03</span>
                </div>
            </div>

        </div>

        <!-- Carousel Indicators (HaHuJobs Dots - Clickable & Swipe-Synced) -->
        <div id="serviceDots" class="flex justify-center items-center space-x-2 mt-4 sm:mt-8">
            <button type="button" onclick="scrollToServiceSlide(0)" class="service-dot w-7 h-2 rounded-full bg-teal-500 transition-all duration-300 cursor-pointer"></button>
            <button type="button" onclick="scrollToServiceSlide(1)" class="service-dot w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-700 transition-all duration-300 cursor-pointer"></button>
            <button type="button" onclick="scrollToServiceSlide(2)" class="service-dot w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-700 transition-all duration-300 cursor-pointer"></button>
        </div>

    </div>
</section>
<!-- 🌟 SECTION 10: How It Works (Mobile & PC: Clean Floating Icons with Curved Dashed Line) 🌟 -->
<section class="py-8 sm:py-16 lg:py-20 relative overflow-hidden bg-slate-50/30 dark:bg-slate-950/20 border-t border-slate-200/50 dark:border-slate-800/50">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto mb-6 sm:mb-16">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-extrabold bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 mb-2 uppercase tracking-wider">
                ⚡ Quick Onboarding
            </span>
            <h2 class="text-xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                Be Discovered In <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-500 to-cyan-500">3 Simple Steps</span>
            </h2>
        </div>

        <!-- Steps Container -->
        <div class="relative max-w-5xl mx-auto">
            
            <!-- Curving Dashed Line (Visible on ALL SCREENS - Perfectly aligned to icons) -->
            <div class="absolute top-6 sm:top-12 lg:top-14 left-[16%] right-[16%] h-12 sm:h-24 z-0 pointer-events-none opacity-80 dark:opacity-50">
                <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 1000 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M 50 50 Q 250 -30 450 50 T 950 50" stroke="currentColor" stroke-width="4" stroke-dasharray="12 12" class="text-slate-300 dark:text-slate-600 animate-pulse" />
                </svg>
            </div>

            <!-- 3 Steps Grid: `grid-cols-3` ON ALL SCREENS -->
            <div class="grid grid-cols-3 gap-1.5 sm:gap-6 md:gap-8 text-center relative z-10">
                
                <!-- Step 1: Register -->
                <div class="relative group flex flex-col items-center">
                    <div class="relative z-10 w-full p-1 sm:p-4 md:p-0">
                        <div class="w-10 h-10 sm:w-20 sm:h-20 md:w-28 md:h-28 mx-auto rounded-full bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-teal-500/30 transform group-hover:-translate-y-2 transition-all duration-300 border-2 md:border-4 border-white dark:border-slate-900 flex-shrink-0">
                            <svg class="w-5 h-5 sm:w-10 sm:h-10 md:w-12 md:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 20V10M21 12l-3-3m0 0l-3 3m-9-3v10a2 2 0 002 2h10a2 2 0 002-2v-4M9 7h.01M15 7h.01"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        
                        <h3 class="mt-2 sm:mt-5 text-[11px] sm:text-xl md:text-2xl font-black text-slate-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors leading-tight">
                            <span class="md:hidden">Register</span>
                            <span class="hidden md:inline">Register With Us</span>
                        </h3>
                        <p class="mt-1 sm:mt-2 text-[8px] sm:text-xs md:text-sm text-slate-500 dark:text-slate-400 font-semibold leading-tight sm:leading-relaxed">
                            Apply for Jobs From Anywhere
                        </p>
                    </div>
                </div>

                <!-- Step 2: Add CV -->
                <div class="relative group flex flex-col items-center">
                    <div class="relative z-10 w-full p-1 sm:p-4 md:p-0">
                        <div class="w-10 h-10 sm:w-20 sm:h-20 md:w-28 md:h-28 mx-auto rounded-full bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center shadow-lg shadow-blue-500/30 transform group-hover:-translate-y-2 transition-all duration-300 border-2 md:border-4 border-white dark:border-slate-900 flex-shrink-0">
                            <svg class="w-5 h-5 sm:w-10 sm:h-10 md:w-12 md:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 3v5h5"></path>
                            </svg>
                        </div>
                        
                        <h3 class="mt-2 sm:mt-5 text-[11px] sm:text-xl md:text-2xl font-black text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
                            Add Your CV
                        </h3>
                        <p class="mt-1 sm:mt-2 text-[8px] sm:text-xs md:text-sm text-slate-500 dark:text-slate-400 font-semibold leading-tight sm:leading-relaxed">
                            <span class="md:hidden">Create CV & Get Discovered</span>
                            <span class="hidden md:inline">Create CV Using Our CV Builder Create Your Profile and Be Discovered</span>
                        </p>
                    </div>
                </div>

                <!-- Step 3: Create Alerts -->
                <div class="relative group flex flex-col items-center">
                    <div class="relative z-10 w-full p-1 sm:p-4 md:p-0">
                        <div class="w-10 h-10 sm:w-20 sm:h-20 md:w-28 md:h-28 mx-auto rounded-full bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30 transform group-hover:-translate-y-2 transition-all duration-300 border-2 md:border-4 border-white dark:border-slate-900 flex-shrink-0">
                            <svg class="w-5 h-5 sm:w-10 sm:h-10 md:w-12 md:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 14v6m-3-3h6"></path>
                            </svg>
                        </div>
                        
                        <h3 class="mt-2 sm:mt-5 text-[11px] sm:text-xl md:text-2xl font-black text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors leading-tight">
                            <span class="md:hidden">Job Alerts</span>
                            <span class="hidden md:inline">Create Email Alert</span>
                        </h3>
                        <p class="mt-1 sm:mt-2 text-[8px] sm:text-xs md:text-sm text-slate-500 dark:text-slate-400 font-semibold leading-tight sm:leading-relaxed">
                            <span class="md:hidden">Set Alerts & Get Matches</span>
                            <span class="hidden md:inline">Set Up Job Alerts, Get Right Jobs. Create Alert For Your Job Searches</span>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
<!-- 🌟 UNIVERSAL SHARE MODAL (Official Logos: Telegram, WhatsApp, Facebook, LinkedIn, X) 🌟 -->
<div id="shareModal" onclick="closeShareModal()" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300 cursor-pointer">
    <div class="bg-white dark:bg-slate-900 border border-teal-500/40 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl text-left relative space-y-5" onclick="event.stopPropagation()">
        
        <!-- Modal Header -->
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="font-black text-slate-900 dark:text-white text-base flex items-center gap-2">
                <span>📤</span> <span>Share Vacancy</span>
            </h3>
            <button onclick="closeShareModal()" class="text-slate-400 hover:text-slate-900 dark:hover:text-white font-bold p-1">✕</button>
        </div>

        <p id="shareModalTitle" class="text-xs font-bold text-teal-600 dark:text-teal-400 truncate"></p>

        <!-- Social Share Direct Buttons (Real Official Brand SVGs) -->
        <div class="grid grid-cols-2 gap-3">
            
            <!-- WhatsApp -->
            <a id="shareWaBtn" href="#" target="_blank" class="flex items-center space-x-2.5 p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold text-xs hover:bg-emerald-500 hover:text-white transition-all shadow-sm group">
                <svg class="w-5 h-5 fill-[#25D366] group-hover:fill-white transition-colors" viewBox="0 0 24 24"><path d="M12.031 0C5.385 0 .003 5.382.003 12.029c0 2.127.556 4.198 1.611 6.02L0 24l6.108-1.597c1.748.966 3.737 1.478 5.923 1.478 6.645 0 12.027-5.382 12.027-12.028C24 5.382 18.675 0 12.031 0zm3.844 17.202c-.172.483-.984 1.432-1.996 1.432-1.026 0-2.456-.51-5.111-3.167-2.652-2.656-3.162-4.088-3.162-5.113 0-1.015.952-1.828 1.436-1.998.483-.17.97-.246 1.134.157.164.403.737 1.83.82 2.015.083.187.051.425-.091.604-.142.179-.344.436-.48.55-.138.115-.292.247-.138.513.155.266.69 1.144 1.488 1.84.99.866 1.854 1.137 2.119 1.25.266.115.485.08.625-.08.14-.16.598-.696.757-.936.159-.24.303-.203.55-.107.247.104 1.564.737 1.83 87.083.13.045.247-.282.427z"/></svg>
                <span>WhatsApp</span>
            </a>

            <!-- Telegram -->
            <a id="shareTgBtn" href="#" target="_blank" class="flex items-center space-x-2.5 p-3 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 font-bold text-xs hover:bg-teal-500 hover:text-white transition-all shadow-sm group">
                <svg class="w-5 h-5 fill-[#229ED9] group-hover:fill-white transition-colors" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-1-.65-.35-1 .22-1.58.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.24-5.52 3.64-.52.36-.97.54-1.34.53-.41 0-1.21-.23-1.8-.42-.72-.24-1.3-.37-1.25-.79.03-.22.33-.44.9-.68 3.52-1.53 5.86-2.54 7.03-3.03 3.35-1.39 4.05-1.63 4.5-.14z"/></svg>
                <span>Telegram</span>
            </a>

            <!-- Facebook -->
            <a id="shareFbBtn" href="#" target="_blank" class="flex items-center space-x-2.5 p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-bold text-xs hover:bg-blue-500 hover:text-white transition-all shadow-sm group">
                <svg class="w-5 h-5 fill-[#1877F2] group-hover:fill-white transition-colors" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <span>Facebook</span>
            </a>

            <!-- LinkedIn (NEW) -->
            <a id="shareLiBtn" href="#" target="_blank" class="flex items-center space-x-2.5 p-3 rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20 font-bold text-xs hover:bg-sky-600 hover:text-white transition-all shadow-sm group">
                <svg class="w-5 h-5 fill-[#0A66C2] group-hover:fill-white transition-colors" viewBox="0 0 24 24"><path d="M19 3a2 2 0 0 1 2 2v18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>
                <span>LinkedIn</span>
            </a>

            <!-- Twitter / X -->
            <a id="shareXBtn" href="#" target="_blank" class="col-span-2 flex items-center justify-center space-x-2.5 p-3 rounded-2xl bg-slate-500/10 text-slate-700 dark:text-slate-300 border border-slate-500/20 font-bold text-xs hover:bg-slate-900 hover:text-white transition-all shadow-sm group">
                <svg class="w-5 h-5 fill-slate-900 dark:fill-white group-hover:fill-white transition-colors" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                <span>Twitter / X</span>
            </a>
        </div>

        <!-- Copy Direct Link Input -->
        <div class="pt-2">
            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Direct Job Link</label>
            <div class="flex items-center space-x-2">
                <input type="text" id="shareCopyInput" readonly class="w-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 outline-none">
                <button onclick="copyShareUrl()" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs rounded-xl flex-shrink-0 shadow-md">
                    Copy
                </button>
            </div>
        </div>

    </div>
</div>

</section>
<!-- JavaScript Functions -->
<script>
    // 🌟 MAGIC: Intersection Observer for Auto-View Counting 🌟
document.addEventListener("DOMContentLoaded", function() {
    let observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Card is visible! Wait 4 seconds to confirm user actually looked at it
                let jobId = entry.target.getAttribute('data-job-id');
                entry.target.viewTimeout = setTimeout(() => {
                    fetch('track_view.php?id=' + jobId); // Silent +1 to Database
                    observer.unobserve(entry.target); // Stop tracking once viewed
                }, 4000);
            } else {
                // User scrolled away before 4 seconds, cancel view count
                clearTimeout(entry.target.viewTimeout);
            }
        });
    }, { threshold: 0.7 }); // 70% of card must be visible

    document.querySelectorAll('.job-card-track').forEach(card => {
        observer.observe(card);
    });
});
// 1. FAQ Data Object (Robust & Complete)
const faqData = {
    seekers: [
        {
            q: "How do I register on Infoplus?",
            a: "Registration is simple and fast. Join our website by clicking 'Sign Up', enter your basic details (name, phone, skills, experience), and upload your CV. You can also register offline by calling our 6781 hotline."
        },
        {
            q: "Is Infoplus free to use for job seekers?",
            a: "Yes! Browsing jobs, creating a profile, and applying for standard job openings is 100% free for all job seekers across Ethiopia."
        },
        {
            q: "What happens after I apply for a job?",
            a: "Once you click 'Apply', your profile and CV are sent directly to the employer's dashboard. If your qualifications match their requirements, the employer or our HR team will contact you via Phone or Telegram."
        },
        {
            q: "Is Infoplus legitimate and safe?",
            a: "Absolutely. Infoplus manually verifies every company and vacancy announcement before publishing. We protect candidates from scams and fake job postings."
        },
        {
            q: "Do I need a CV or laptop to get started?",
            a: "No! If you don't have a CV or internet connection, simply call our shortcode 6781. Our professional call center agents will ask you questions and build a digital profile for you over the phone."
        },
        {
            q: "Can I update my profile after registering?",
            a: "Yes, you can log in to your Candidate Dashboard at any time to update your skills, education, or upload a new CV to improve your matching rate."
        }
    ],
    companies: [
        {
            q: "How do I post a job vacancy on Infoplus?",
            a: "Click on 'Post Vacancy' from the navigation menu, fill in your company details, job description, requirements, and submit. Our team will review it shortly."
        },
        {
            q: "How long does verification take for employers?",
            a: "Our HR validation team reviews company details and job authenticity within 12 to 24 hours to ensure quality and legitimacy before publishing to the public."
        },
        {
            q: "How does the 6781 Call Center help my hiring?",
            a: "Many talented Ethiopians do not have regular internet access. Our 6781 agents manually screen and register offline candidates, ensuring you get access to a massive, untapped talent pool."
        },
        {
            q: "What pricing plans are available for companies?",
            a: "We offer flexible plans: A Basic (Free) plan for single postings, a Standard plan (500 Birr) with Telegram & SMS blasts, and custom Enterprise packages for high-volume hiring."
        },
        {
            q: "Is my company data and candidate info secure?",
            a: "Yes. We use enterprise-grade cloud security. Candidate data is strictly used for recruitment purposes and your company portal is protected with modern encryption."
        }
    ]
};

let currentFaqTab = 'seekers';

function renderFAQ() {
    const container = document.getElementById('faqList');
    if (!container) return;
    
    container.style.opacity = '0';
    
    setTimeout(() => {
        container.innerHTML = '';
        const items = faqData[currentFaqTab];
        if (!items) return;
        
        items.forEach((index_item, index) => {
            const itemEl = document.createElement('div');
            itemEl.className = 'dotted-card border border-slate-200/90 dark:border-slate-800/90 rounded-xl sm:rounded-3xl overflow-hidden transition-all duration-300 shadow-sm hover:shadow-xl bg-white/80 dark:bg-slate-900/80';
            
            itemEl.innerHTML = `
                <button onclick="toggleFaq(${index})" class="w-full px-2.5 py-2 sm:px-8 sm:py-6 flex justify-between items-center text-left focus:outline-none group">
                    <span class="font-extrabold text-slate-900 dark:text-white text-[10px] sm:text-lg lg:text-xl leading-snug group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors pr-1 sm:pr-4">${index_item.q}</span>
                    <span id="faqIcon-${index}" class="h-5 w-5 sm:h-10 sm:w-10 rounded-lg sm:rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 font-black flex items-center justify-center text-xs sm:text-xl ml-1 transition-all duration-300 flex-shrink-0 border border-teal-500/20 group-hover:bg-teal-500 group-hover:text-white shadow-sm">+</span>
                </button>
                <div id="faqAnswer-${index}" class="hidden px-2.5 pb-2.5 sm:px-8 sm:pb-7 text-[9px] sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed border-t border-slate-200/60 dark:border-slate-800/60 pt-2 sm:pt-4 mt-0.5">
                    ${index_item.a}
                </div>
            `;
            container.appendChild(itemEl);
        });
        
        container.style.opacity = '1';
    }, 200);
}

function toggleFaq(index) {
    const answer = document.getElementById(`faqAnswer-${index}`);
    const icon = document.getElementById(`faqIcon-${index}`);
    if (!answer || !icon) return;
    
    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        icon.textContent = '×';
        icon.classList.add('bg-rose-500/10', 'text-rose-600');
        icon.classList.remove('bg-teal-500/10', 'text-teal-600');
    } else {
        answer.classList.add('hidden');
        icon.textContent = '+';
        icon.classList.remove('bg-rose-500/10', 'text-rose-600');
        icon.classList.add('bg-teal-500/10', 'text-teal-600');
    }
}

// Event Listeners for FAQ Buttons
document.addEventListener('DOMContentLoaded', () => {
    const seekerBtn = document.getElementById('faqSeekersBtn');
    const companyBtn = document.getElementById('faqCompaniesBtn');
    const faqImgContainer = document.getElementById('faqImageContainer');
    const faqListContainer = document.getElementById('faqListContainer');
    const faqDynamicImg = document.getElementById('faqDynamicImage');
    const faqGlow = document.getElementById('faqGlow');

    if (seekerBtn && companyBtn) {
        seekerBtn.addEventListener('click', () => {
            if(currentFaqTab === 'seekers') return;
            currentFaqTab = 'seekers';
            
            seekerBtn.className = 'px-5 py-2 sm:px-8 sm:py-2.5 rounded-full text-xs font-bold transition-all bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-md';
            companyBtn.className = 'px-5 py-2 sm:px-8 sm:py-2.5 rounded-full text-xs font-bold transition-all text-slate-600 dark:text-slate-300 hover:text-teal-600';
            
            if (faqImgContainer && faqListContainer) {
                faqImgContainer.className = 'col-span-4 sm:col-span-5 order-1 transition-all duration-700 ease-in-out flex justify-center items-center relative py-2 sm:py-8';
                faqListContainer.className = 'col-span-8 sm:col-span-7 order-2 transition-all duration-700 ease-in-out';
            }
            
            if (faqDynamicImg && faqGlow) {
                faqDynamicImg.style.opacity = '0';
                setTimeout(() => {
                    faqDynamicImg.src = 'jobseeker.png'; 
                    faqGlow.className = 'absolute inset-0 bg-gradient-to-tr from-teal-500/20 to-emerald-500/20 rounded-full blur-xl sm:blur-3xl pointer-events-none transition-colors duration-500';
                    faqDynamicImg.style.opacity = '1';
                }, 300);
            }

            renderFAQ();
        });

        companyBtn.addEventListener('click', () => {
            if(currentFaqTab === 'companies') return;
            currentFaqTab = 'companies';
            
            companyBtn.className = 'px-5 py-2 sm:px-8 sm:py-2.5 rounded-full text-xs font-bold transition-all bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md';
            seekerBtn.className = 'px-5 py-2 sm:px-8 sm:py-2.5 rounded-full text-xs font-bold transition-all text-slate-600 dark:text-slate-300 hover:text-teal-600';
            
            if (faqImgContainer && faqListContainer) {
                faqImgContainer.className = 'col-span-4 sm:col-span-5 order-2 transition-all duration-700 ease-in-out flex justify-center items-center relative py-2 sm:py-8';
                faqListContainer.className = 'col-span-8 sm:col-span-7 order-1 transition-all duration-700 ease-in-out';
            }
            
            if (faqDynamicImg && faqGlow) {
                faqDynamicImg.style.opacity = '0';
                setTimeout(() => {
                    faqDynamicImg.src = 'company.png'; 
                    faqGlow.className = 'absolute inset-0 bg-gradient-to-tr from-amber-500/20 to-orange-500/20 rounded-full blur-xl sm:blur-3xl pointer-events-none transition-colors duration-500';
                    faqDynamicImg.style.opacity = '1';
                }, 300);
            }

            renderFAQ();
        });
    }

    // Initial FAQ Render
    renderFAQ();
});

// 2. Auto Slide Image Banner Logic With Dynamic Dots (Hero Section)
let heroSlideIndex = 0;
const slides = document.querySelectorAll('.hero-slide');
const heroDotsContainer = document.getElementById('heroDots');

function createHeroDots() {
    if (!heroDotsContainer || slides.length === 0) return;
    heroDotsContainer.innerHTML = '';
    
    for (let i = 0; i < slides.length; i++) {
        const dot = document.createElement('button');
        dot.setAttribute('aria-label', 'Go to hero slide ' + (i + 1));
        dot.className = `h-2.5 transition-all duration-300 ${i === 0 ? 'w-8 bg-teal-500 rounded-full shadow-md' : 'w-2.5 bg-slate-300 dark:bg-slate-700 hover:bg-teal-400 rounded-full'}`;
        
        dot.addEventListener('click', () => {
            goToHeroSlide(i);
        });
        heroDotsContainer.appendChild(dot);
    }
}

function updateHeroDots() {
    if (!heroDotsContainer) return;
    const dots = heroDotsContainer.children;
    for (let i = 0; i < dots.length; i++) {
        if (i === heroSlideIndex) {
            dots[i].className = 'h-2.5 w-8 bg-teal-500 rounded-full shadow-md transition-all duration-300';
        } else {
            dots[i].className = 'h-2.5 w-2.5 bg-slate-300 dark:bg-slate-700 hover:bg-teal-400 rounded-full transition-all duration-300';
        }
    }
}

function goToHeroSlide(index) {
    if (slides.length === 0) return;
    slides[heroSlideIndex].classList.remove('opacity-100');
    slides[heroSlideIndex].classList.add('opacity-0');
    
    heroSlideIndex = index;
    
    slides[heroSlideIndex].classList.remove('opacity-0');
    slides[heroSlideIndex].classList.add('opacity-100');
    updateHeroDots();
}

function autoSlideHero() {
    if (slides.length === 0) return;
    let nextIndex = (heroSlideIndex + 1) % slides.length;
    goToHeroSlide(nextIndex);
}

if (slides.length > 0) {
    createHeroDots();
    setInterval(autoSlideHero, 4000);
}

// 2. Responsive Auto-sliding Carousel Logic + Mobile Touch Swipe
let jobIndex = 0;
const jobTrack = document.getElementById('jobTrack');
const jobDotsContainer = document.getElementById('jobDots');

function getCardsPerView() {
    if (window.innerWidth < 640) return 1; // Mobile: 1 card per view
    if (window.innerWidth < 1024) return 2; // Tablet: 2 cards per view
    return 3; // Desktop: 3 cards per view
}

function createJobDots() {
    if (!jobTrack || !jobDotsContainer) return;
    jobDotsContainer.innerHTML = '';
    
    const totalCards = jobTrack.children.length;
    const cardsPerView = getCardsPerView();
    const maxScroll = Math.max(1, totalCards - cardsPerView + 1); 
    
    if (totalCards <= cardsPerView) {
        return; // No dots needed if cards fit on screen
    }

    for (let i = 0; i < maxScroll; i++) {
        const dot = document.createElement('button');
        dot.setAttribute('aria-label', 'Go to job slide ' + (i + 1));
        dot.className = `h-2.5 transition-all duration-300 ${i === jobIndex ? 'w-8 bg-amber-400 rounded-full shadow-md' : 'w-2.5 bg-slate-300 dark:bg-slate-700 hover:bg-amber-300 rounded-full'}`;
        
        dot.addEventListener('click', () => {
            jobIndex = i;
            updateJobCarousel();
        });
        jobDotsContainer.appendChild(dot);
    }
}

function updateJobCarousel() {
    if (!jobTrack || jobTrack.children.length === 0) return;
    const firstCard = jobTrack.children[0];
    const cardWidth = firstCard.getBoundingClientRect().width + 24; // 24px gap
    jobTrack.style.transform = `translateX(-${jobIndex * cardWidth}px)`;
    
    if (jobDotsContainer && jobDotsContainer.children.length > 0) {
        const dots = jobDotsContainer.children;
        for (let i = 0; i < dots.length; i++) {
            if (i === jobIndex) {
                dots[i].className = 'h-2.5 w-8 bg-amber-400 rounded-full shadow-md transition-all duration-300';
            } else if (dots[i]) {
                dots[i].className = 'h-2.5 w-2.5 bg-slate-300 dark:bg-slate-700 hover:bg-amber-300 rounded-full transition-all duration-300';
            }
        }
    }
}

function autoSlideJobs() {
    if (!jobTrack || jobTrack.children.length === 0) return;
    const totalCards = jobTrack.children.length;
    const cardsPerView = getCardsPerView();
    const maxScroll = totalCards - cardsPerView;

    if (maxScroll <= 0) {
        jobIndex = 0;
        updateJobCarousel();
        return;
    }

    if (jobIndex >= maxScroll) {
        jobIndex = 0;
    } else {
        jobIndex++;
    }
    updateJobCarousel();
}

// Initialize Carousel & Mobile Touch-Swipe Event
if (jobTrack) {
    createJobDots();
    window.addEventListener('resize', () => {
        createJobDots();
        updateJobCarousel();
    });
    setInterval(autoSlideJobs, 3500);

    // 📱 Mobile Touch-Swipe Support (Quubaan Bilbilaa Irratti Oofuuf)
    let startX = 0;
    let dist = 0;
    
    jobTrack.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    }, { passive: true });

    jobTrack.addEventListener('touchend', (e) => {
        let endX = e.changedTouches[0].clientX;
        dist = startX - endX;
        const totalCards = jobTrack.children.length;
        const maxScroll = Math.max(0, totalCards - getCardsPerView());

        if (Math.abs(dist) > 40) { // 40px swipe threshold
            if (dist > 0 && jobIndex < maxScroll) {
                jobIndex++; // Swipe Left -> Next Card
            } else if (dist < 0 && jobIndex > 0) {
                jobIndex--; // Swipe Right -> Previous Card
            }
            updateJobCarousel();
        }
    }, { passive: true });
}

// 4. Universal Share Modal Logic (Real Official Brand Logos & LinkedIn)
function shareJob(title, path) {
    const fullUrl = window.location.protocol + '//' + window.location.host + (window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/' + path);
    const shareText = encodeURIComponent("Job Vacancy: " + title + " on Infoplus Call Center 6781");
    const encodedUrl = encodeURIComponent(fullUrl);

    // Populate Modal Details
    const modalTitle = document.getElementById('shareModalTitle');
    const copyInput = document.getElementById('shareCopyInput');
    const waBtn = document.getElementById('shareWaBtn');
    const tgBtn = document.getElementById('shareTgBtn');
    const fbBtn = document.getElementById('shareFbBtn');
    const liBtn = document.getElementById('shareLiBtn');
    const xBtn = document.getElementById('shareXBtn');
    const modal = document.getElementById('shareModal');

    if (modalTitle) modalTitle.textContent = title;
    if (copyInput) copyInput.value = fullUrl;

    // Set Direct Social Links
    if (waBtn) waBtn.href = "https://api.whatsapp.com/send?text=" + shareText + "%20" + encodedUrl;
    if (tgBtn) tgBtn.href = "https://t.me/share/url?url=" + encodedUrl + "&text=" + shareText;
    if (fbBtn) fbBtn.href = "https://www.facebook.com/sharer/sharer.php?u=" + encodedUrl;
    if (liBtn) liBtn.href = "https://www.linkedin.com/sharing/share-offsite/?url=" + encodedUrl;
    if (xBtn) xBtn.href = "https://twitter.com/intent/tweet?text=" + shareText + "&url=" + encodedUrl;

    // Open Modal
    if (modal) modal.classList.remove('hidden');
}

function closeShareModal() {
    const modal = document.getElementById('shareModal');
    if (modal) modal.classList.add('hidden');
}

function copyShareUrl() {
    const input = document.getElementById('shareCopyInput');
    if (input) {
        input.select();
        input.setSelectionRange(0, 99999);
        
        try {
            document.execCommand('copy');
            alert("📋 Job link copied to clipboard!");
        } catch (err) {
            alert("Copy link manually: " + input.value);
        }
    }
}
// 🌟 SERVICE SLIDER SCROLL & DOTS SYNC 🌟
function scrollToServiceSlide(index) {
    const track = document.getElementById('servicesTrack');
    if (!track) return;
    const cards = track.children;
    if (cards[index]) {
        cards[index].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const track = document.getElementById('servicesTrack');
    const dots = document.querySelectorAll('.service-dot');
    if (track && dots.length > 0) {
        track.addEventListener('scroll', () => {
            const scrollLeft = track.scrollLeft;
            const cardWidth = track.children[0].offsetWidth + 16;
            const activeIndex = Math.min(2, Math.max(0, Math.round(scrollLeft / cardWidth)));
            
            dots.forEach((dot, idx) => {
                if (idx === activeIndex) {
                    dot.className = 'service-dot w-7 h-2 rounded-full bg-teal-500 transition-all duration-300 cursor-pointer';
                } else {
                    dot.className = 'service-dot w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-700 transition-all duration-300 cursor-pointer';
                }
            });
        });
    }
});
</script>
<?php

include_once __DIR__ . '/includes/footer.php';
?>