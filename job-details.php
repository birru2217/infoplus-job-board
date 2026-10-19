<?php
include_once __DIR__ . '/includes/header.php';

// 🌟 MAGIC VIEWS ENGINE FORMULA (Initial +30 & Daily +50 Views) 🌟
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

// Fetch Job ID from URL
$job_id = (int)($_GET['id'] ?? 0);
$job = null;

// 🌟 GUARANTEED REAL DATABASE FETCH 🌟
if ($job_id > 0 && isset($pdo) && $pdo !== null) {
    try {
        @$pdo->exec("ALTER TABLE jobs ADD COLUMN views INT DEFAULT 0");
        $pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?")->execute([$job_id]);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        $fetched_job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetched_job) {
            $job = $fetched_job;
        }
    } catch (Exception $e) { $job = null; }
}
// 🌟 MAGIC: Real Click View Tracker 🌟
    if (!isset($_SESSION['viewed_jobs'])) {
        $_SESSION['viewed_jobs'] = [];
    }
    if (!in_array($job_id, $_SESSION['viewed_jobs'])) {
        $pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?")->execute([$job_id]);
        $_SESSION['viewed_jobs'][] = $job_id;
    }
    // 🌟 MAGIC: Fetch Real Applications Count for this Job 🌟
    $app_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
    $app_count_stmt->execute([$job_id]);
    $total_applied = (int)$app_count_stmt->fetchColumn();
    
    $total_required = (int)($job['positions'] ?? 1);
    if($total_required <= 0) $total_required = 1; // Fallback
    
    // Calculate Progress Percentage
    $progress_percent = min(100, round(($total_applied / $total_required) * 100));
// Fallback Mock Data ONLY if DB Record really does not exist
if (!$job) {
    $job = [
        'id' => $job_id ?: 1,
        'company_name' => 'Info+ Partner Enterprise',
        'title' => 'Professional Job Vacancy',
        'category' => 'General Professional',
        'job_type' => 'Full Time',
        'experience' => '1-3 Years',
        'career_level' => 'Junior Level (1-3 years)',
        'location' => 'Addis Ababa, Ethiopia',
        'salary' => 'Negotiable',
        'deadline' => date('Y-m-d', strtotime('+12 days')),
        'positions' => 1,
        'description' => 'Detailed position summary for this vacancy announcement. Please review the duties and requirements below to apply.',
        'requirements' => "• Relevant Bachelor's Degree or Diploma from a recognized institution.\n• Strong problem solving and team collaboration skills.",
        'how_to_apply' => 'Interested applicants can click the Quick Apply button below or call shortcode 6781.',
        'logo' => 'logo.png',
        'attachment_file' => null,
        'views' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Extract Data Safely
$c_title = !empty($job['title']) ? $job['title'] : 'Job Vacancy';
$c_company = !empty($job['company_name']) ? $job['company_name'] : 'Info+ Verified Partner';
$company_logo = !empty($job['logo']) ? $job['logo'] : 'logo.png';
$c_location = !empty($job['location']) ? $job['location'] : 'Ethiopia';
$c_salary = !empty($job['salary']) ? $job['salary'] : 'Negotiable';
$c_type = !empty($job['job_type']) ? $job['job_type'] : 'Full Time';
$c_category = !empty($job['category']) ? $job['category'] : 'General';
$career_lvl = !empty($job['career_level']) ? $job['career_level'] : (!empty($job['experience']) ? $job['experience'] : 'Mid Level');
$positions_num = !empty($job['positions']) ? (int)$job['positions'] : 1;

// 🌟 MAGIC: LIVE APPLICANTS COUNT FROM DATABASE 🌟
$total_applicants = 0;
if ($job_id > 0 && isset($pdo) && $pdo !== null) {
    try {
        $app_stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
        $app_stmt->execute([$job_id]);
        $total_applicants = (int)$app_stmt->fetchColumn();
    } catch (Exception $e) {}
}

$deadline_raw = $job['deadline'] ?? '';
$deadline_str = !empty($deadline_raw) ? date('M d, Y', strtotime($deadline_raw)) : 'Open until filled';
$posted_date = !empty($job['created_at']) ? date('M d, Y', strtotime($job['created_at'])) : 'Recently Posted';

// Dynamic Skills
$skills_tags = [];
if (!empty($job['requirements'])) {
    $lines = explode("\n", $job['requirements']);
    foreach ($lines as $line) {
        $clean = trim(str_replace(['•', '-', '*', '1.', '2.', '3.'], '', $line));
        if (!empty($clean) && strlen($clean) > 3 && strlen($clean) < 45) {
            $skills_tags[] = $clean;
        }
    }
}
if (count($skills_tags) < 2) {
    $skills_tags = [$c_category, $c_type, 'Communication', 'Problem Solving', 'Field Expertise'];
}
$skills_tags = array_slice($skills_tags, 0, 6);
?>

<div class="py-8 sm:py-12 relative overflow-hidden" x-data="{ activeTab: 'description', showApplyModal: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center space-x-2 text-xs font-bold text-slate-400 mb-6 text-left">
            <a href="index.php" class="hover:text-teal-600 transition-colors">Home</a>
            <span>/</span>
            <a href="jobs.php" class="hover:text-teal-600 transition-colors">Jobs</a>
            <span>/</span>
            <span class="text-teal-600 dark:text-teal-400 truncate max-w-[220px]"><?php echo htmlspecialchars($c_title); ?></span>
        </nav>

        <!-- Success Alert Notification -->
        <?php if (isset($_SESSION['app_success_msg'])): ?>
            <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm font-black flex justify-between items-center shadow-lg animate-bounce">
                <span>🎉 <?php echo $_SESSION['app_success_msg']; unset($_SESSION['app_success_msg']); ?></span>
                <button @click="show = false" class="text-emerald-600 font-bold ml-4">✕</button>
            </div>
        <?php endif; ?>

    <!-- 🌟 HERO OVERVIEW CARD (Left Aligned Pills & Deadline Beside Salary) 🌟 -->
        <div class="dotted-card bg-white/90 dark:bg-slate-900/90 backdrop-blur-2xl border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 sm:p-10 shadow-xl mb-8">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                
                <!-- Left Section: Logo, Title, and Far-Left Aligned Metrics -->
                <div class="flex flex-col w-full text-left space-y-3">
                    
                    <!-- Row 1: Logo + Title & Badges -->
                    <div class="flex flex-row items-start space-x-3 sm:space-x-6 w-full">
                        <!-- Company Logo -->
                        <div class="w-14 h-14 sm:w-24 sm:h-24 rounded-2xl border-2 border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 p-1.5 shadow-md flex-shrink-0 flex items-center justify-center">
                            <img src="<?php echo htmlspecialchars($company_logo); ?>" onerror="this.src='logo.png'" alt="Company Logo" class="w-full h-full object-contain rounded-xl">
                        </div>

                        <!-- Title & Badges -->
                        <div class="space-y-1 flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-wider bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20">
                                    🔥 URGENT HIRING
                                </span>
                                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/50 dark:border-slate-700/50">
                                    📍 <?php echo htmlspecialchars($c_location); ?>
                                </span>
                            </div>

                            <h1 class="text-base sm:text-3xl lg:text-4xl font-black text-slate-900 dark:text-white leading-snug truncate sm:whitespace-normal">
                                <?php echo htmlspecialchars($c_title); ?>
                            </h1>
                            <p class="text-xs sm:text-base font-extrabold text-slate-500 dark:text-slate-400 truncate">
                                🏢 <?php echo htmlspecialchars($c_company); ?> <span class="text-teal-500 font-bold">✔ Verified</span>
                            </p>
                        </div>
                    </div>

                    <!-- Row 2: Metrics Pills (Starts from FAR LEFT under Logo, Deadline beside Salary!) -->
                    <div class="w-full pt-2 sm:pt-3 border-t border-slate-100 dark:border-slate-800/80">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-3 text-[10px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 w-full justify-start">
                            <span class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 px-2.5 py-1.5 rounded-xl flex items-center gap-1 border border-slate-200/60 dark:border-slate-700/60 shadow-sm">
                                💼 Job Type: <?php echo htmlspecialchars($c_type); ?>
                            </span>
                            <span class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 px-2.5 py-1.5 rounded-xl flex items-center gap-1 border border-slate-200/60 dark:border-slate-700/60 shadow-sm">
                                👥 :<?php echo $positions_num; ?> Position(s)
                            </span>
                            <span class="bg-teal-500/10 text-teal-600 dark:text-teal-400 px-2.5 py-1.5 rounded-xl flex items-center gap-1 border border-teal-500/20 font-extrabold shadow-sm">
                                💵 Salary: <?php echo htmlspecialchars($c_salary); ?>
                            </span>
                            <span class="bg-rose-500/10 text-rose-600 dark:text-rose-400 px-2.5 py-1.5 rounded-xl flex items-center gap-1 border border-rose-500/20 font-extrabold shadow-sm">
                                ⏳ Deadline: <?php echo $deadline_str; ?>
                            </span>
                        </div>
                    </div>

                </div>

                <!-- Right: Action Buttons -->
                <div class="flex flex-row lg:flex-col sm:flex-row items-center gap-3 w-full lg:w-auto flex-shrink-0 pt-4 lg:pt-0 border-t lg:border-t-0 border-slate-200 dark:border-slate-800">
                    <button @click="showApplyModal = true" class="flex-1 lg:w-full px-8 py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-sm sm:text-base transition-all shadow-xl shadow-teal-600/30 flex items-center justify-center space-x-2 transform hover:-translate-y-0.5 cursor-pointer">
                        <span>⚡ Quick Apply</span>
                    </button>
                    
                    <button onclick="shareJob('<?php echo addslashes($c_title); ?>', 'job-details.php?id=<?php echo $job['id']; ?>')" class="px-5 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-bold transition-all flex items-center justify-center space-x-2">
                        <span>📤</span> <span class="hidden sm:inline">Share</span>
                    </button>
                </div>

            </div>
        </div>
        <!-- 🌟 MAIN CONTENT GRID (Body Left + Sidebar Right) 🌟 -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 text-left">
            
            <!-- LEFT COLUMN (8/12 - Detailed Description, Duties & Requirements) -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- Content Tabs Container -->
                <div class="dotted-card bg-white/90 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-8">
                    
                    <!-- Tabs Navigation -->
                    <div class="flex border-b border-slate-200 dark:border-slate-800 space-x-6 pb-2 overflow-x-auto">
                        <button @click="activeTab = 'description'" :class="activeTab === 'description' ? 'text-teal-600 dark:text-teal-400 border-b-2 border-teal-500 font-black' : 'text-slate-400 font-bold hover:text-slate-600'" class="pb-3 text-xs sm:text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                            About Job
                        </button>
                        <button @click="activeTab = 'duties'" :class="activeTab === 'duties' ? 'text-teal-600 dark:text-teal-400 border-b-2 border-teal-500 font-black' : 'text-slate-400 font-bold hover:text-slate-600'" class="pb-3 text-xs sm:text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                            Requirement
                        </button>
                        <button @click="activeTab = 'apply'" :class="activeTab === 'apply' ? 'text-teal-600 dark:text-teal-400 border-b-2 border-teal-500 font-black' : 'text-slate-400 font-bold hover:text-slate-600'" class="pb-3 text-xs sm:text-sm uppercase tracking-wider transition-all whitespace-nowrap">
                            How to Apply
                        </button>
                    </div>

     <!-- TAB 1: OVERVIEW (Official Document Style with Labeled Breakdown & Synthesized Flowing Post) -->
                    <div x-show="activeTab === 'description'" class="space-y-6">
                        
                        <!-- Official Paper Document Container -->
                        <div class="relative dotted-card bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-xl overflow-hidden text-left space-y-8">
                            
                            <!-- Document Watermark Accent -->
                            <div class="absolute -right-12 -bottom-12 opacity-10 dark:opacity-20 pointer-events-none transform rotate-12">
                                <img src="logo.png" class="w-72 h-72 sm:w-80 sm:h-80 object-contain">
                            </div>

                            <!-- 1. Document Header (Company Logo Left + Official 6781 Stamp Right) -->
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b-2 border-slate-100 dark:border-slate-800 pb-6 relative z-10">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white p-1.5 flex-shrink-0 shadow-sm flex items-center justify-center">
                                        <img src="<?php echo htmlspecialchars($company_logo); ?>" onerror="this.src='logo.png'" class="w-full h-full object-contain rounded-xl">
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 dark:text-white text-base sm:text-lg leading-tight"><?php echo htmlspecialchars($c_company); ?></h4>
                                        <span class="text-[10px] sm:text-xs font-extrabold text-teal-600 dark:text-teal-400 uppercase tracking-widest block mt-0.5">Official Recruitment Announcement</span>
                                    </div>
                                </div>

                                <div class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-full bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/30 text-xs font-black uppercase tracking-wider">
                                    <span>✔</span> <span>6781 Verified Vacancy</span>
                                </div>
                            </div>

                            <!-- 2. Position Summary Section (Explicitly Labeled Line-by-Line) -->
                            <div class="space-y-4 relative z-10">
                                <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>📄</span> <span>Position Summary & Key Details</span>
                                </h3>
                                
                                <!-- Structured Labeled Box (Kuta Kutaadhaan Spaced-Out List) -->
                                <div class="bg-slate-50/80 dark:bg-slate-800/40 p-5 sm:p-7 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 space-y-3.5 text-xs sm:text-sm">
                                    
                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5 gap-1">
                                        <span class="font-bold text-slate-400 uppercase text-[10px] sm:text-xs">📌 Job Title:</span>
                                        <span class="font-black text-teal-600 dark:text-teal-400 text-left sm:text-right text-sm sm:text-base"><?php echo htmlspecialchars($c_title); ?></span>
                                    </div>

                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5 gap-1">
                                        <span class="font-bold text-slate-400 uppercase text-[10px] sm:text-xs">🏢 Hiring Organization:</span>
                                        <span class="font-extrabold text-slate-900 dark:text-white text-left sm:text-right"><?php echo htmlspecialchars($c_company); ?></span>
                                    </div>

                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5 gap-1">
                                        <span class="font-bold text-slate-400 uppercase text-[10px] sm:text-xs">📈 Required Experience / Level:</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-left sm:text-right"><?php echo htmlspecialchars($career_lvl); ?></span>
                                    </div>

                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5 gap-1">
                                        <span class="font-bold text-slate-400 uppercase text-[10px] sm:text-xs">👥 Quantity Required:</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-left sm:text-right"><?php echo $positions_num; ?> Position(s)</span>
                                    </div>

                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5 gap-1">
                                        <span class="font-bold text-slate-400 uppercase text-[10px] sm:text-xs">💵 Compensation / Salary:</span>
                                        <span class="font-black text-emerald-600 dark:text-emerald-400 text-left sm:text-right"><?php echo htmlspecialchars($c_salary); ?></span>
                                    </div>

                                    <div class="flex flex-col sm:flex-row justify-between sm:items-center border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5 gap-1">
                                        <span class="font-bold text-slate-400 uppercase text-[10px] sm:text-xs">📍 Work Location:</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-left sm:text-right"><?php echo htmlspecialchars($c_location); ?></span>
                                    </div>
                                    
                                    <!-- Superadmin Raw Overview Text (Spaced Out) -->
                                    <?php if (!empty($job['description'])): ?>
                                    <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                                        <span class="font-bold text-slate-400 uppercase text-[10px] block mb-1">Additional Overview Details:</span>
                                        <p class="text-slate-700 dark:text-slate-300 font-medium leading-relaxed whitespace-pre-line">
                                            <?php echo htmlspecialchars($job['description']); ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>

                            <!-- 3. Magic Synthesized Complete Announcement Paragraph (Ergaa Continuous Post) -->
                            <div class="p-5 rounded-2xl bg-teal-500/5 dark:bg-teal-500/10 border-2 border-teal-500/20 space-y-2.5 relative z-10">
                                <h4 class="font-black text-xs uppercase tracking-widest text-teal-600 dark:text-teal-400 flex items-center gap-2">
                                    <span>✨</span> <span>Synthesized Full Job Announcement</span>
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-200 leading-relaxed font-medium">
                                    <strong class="text-teal-600 dark:text-teal-400"><?php echo htmlspecialchars($c_company); ?></strong> is currently seeking qualified and ambitious candidates for the position of <strong class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($c_title); ?></strong> located in <strong><?php echo htmlspecialchars($c_location); ?></strong>. This is a <strong><?php echo htmlspecialchars($c_type); ?></strong> opportunity requiring <strong><?php echo htmlspecialchars($career_lvl); ?></strong> qualification. A total of <strong><?php echo $positions_num; ?> candidate(s)</strong> will be selected with a salary offer of <strong><?php echo htmlspecialchars($c_salary); ?></strong>. The application deadline for this vacancy is <strong><?php echo $deadline_str; ?></strong>.
                                </p>
                            </div>

                            <!-- 4. Official Infoplus Stamp (Chapha 3D) at Bottom Right -->
                            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800 relative z-10">
                                <div class="flex items-center space-x-3 p-3 px-5 rounded-full border-2 border-teal-500/40 bg-teal-500/10 text-teal-700 dark:text-teal-300 shadow-xl transform -rotate-2 hover:rotate-0 transition-transform">
                                    <div class="w-10 h-10 rounded-full bg-teal-600 text-white flex items-center justify-center font-black text-sm border-2 border-white shadow-md">
                                        I+
                                    </div>
                                    <div class="text-left">
                                        <span class="text-[9px] font-black uppercase tracking-widest block text-teal-600 dark:text-teal-400">INFOPLUS CALL CENTER 6781</span>
                                        <span class="text-xs font-black text-slate-900 dark:text-white block">OFFICIAL VERIFIED DOCUMENT ✔</span>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
              <!-- TAB 2: DUTIES & REQUIREMENTS (Official Document Style & Magic Checklist) -->
                    <div x-show="activeTab === 'duties'" class="space-y-6" x-cloak>
                        
                        <div class="relative dotted-card bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-xl overflow-hidden text-left space-y-6">
                            
                            <!-- Document Header -->
                            <div class="flex justify-between items-center border-b-2 border-slate-100 dark:border-slate-800 pb-4">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">📋</span>
                                    <div>
                                        <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">Duties, Requirements & Qualifications</h3>
                                        <p class="text-[10px] sm:text-xs text-slate-400">Official criteria for <?php echo htmlspecialchars($c_title); ?></p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-teal-500/10 text-teal-600 dark:text-teal-400 font-extrabold text-[10px] uppercase rounded-full border border-teal-500/20">
                                    Mandatory Criteria
                                </span>
                            </div>

                            <!-- Superadmin Requirements Body -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Primary Qualifications & Requirements:</h4>
                                <div class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed sm:leading-loose whitespace-pre-line bg-slate-50/80 dark:bg-slate-800/40 p-5 sm:p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/60 font-medium">
                                    <?php echo !empty($job['requirements']) ? htmlspecialchars($job['requirements']) : "• Relevant qualification or diploma in the specified field.\n• Proven work experience suited for " . htmlspecialchars($c_title) . ".\n• Strong problem solving and team collaboration abilities."; ?>
                                </div>
                            </div>

                            <!-- Magic Key Qualification Checklist -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div class="p-4 rounded-2xl bg-teal-500/5 border border-teal-500/20 space-y-1">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-teal-600 dark:text-teal-400 block">Required Experience</span>
                                    <span class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($career_lvl); ?></span>
                                </div>
                                <div class="p-4 rounded-2xl bg-emerald-500/5 border border-emerald-500/20 space-y-1">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 block">Language Proficiency</span>
                                    <span class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white block">One of -->Amharic/Afaan Oromoo/English</span>
                                </div>
                            </div>

                            <!-- Candidate Notice -->
                            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center space-x-3 text-amber-800 dark:text-amber-300 text-xs font-medium">
                                <span class="text-xl flex-shrink-0">💡</span>
                                <p class="leading-relaxed">
                                    Make sure your candidate profile and CV clearly highlight the skills required above before submitting your application.
                                </p>
                            </div>

                        </div>

                    </div>

                    <!-- TAB 3: HOW TO APPLY (Persuasive Guide & Hotline 6781 Integration) -->
                    <div x-show="activeTab === 'apply'" class="space-y-6" x-cloak>
                        
                        <div class="relative dotted-card bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-xl overflow-hidden text-left space-y-6">
                            
                            <!-- Header -->
                            <div class="flex justify-between items-center border-b-2 border-slate-100 dark:border-slate-800 pb-4">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">🚀</span>
                                    <div>
                                        <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">How To Apply for This Vacancy</h3>
                                        <p class="text-[10px] sm:text-xs text-slate-400">Official application channels for <?php echo htmlspecialchars($c_title); ?></p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-extrabold text-[10px] uppercase rounded-full border border-amber-500/20">
                                    Multiple Options
                                </span>
                            </div>

                            <!-- Employer Instructions Box -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Employer Instructions:</h4>
                                <div class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50/80 dark:bg-slate-800/40 p-5 sm:p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/60 font-medium">
                                    <?php echo !empty($job['how_to_apply']) ? nl2br(htmlspecialchars($job['how_to_apply'])) : "Interested applicants should submit their candidate profile online using the Quick Apply button below, or call our Call Center hotline 6781 for agent-assisted registration."; ?>
                                </div>
                            </div>

                            <!-- 🌟 2 EASY WAYS TO APPLY 🌟 -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                
                                <!-- Way 1: Online Quick Apply -->
                                <div class="p-5 rounded-2xl bg-teal-500/10 border-2 border-teal-500/30 space-y-3 flex flex-col justify-between">
                                    <div>
                                        <span class="px-2.5 py-1 rounded-full bg-teal-600 text-white text-[9px] font-black uppercase inline-block mb-2">Option 1: Web Portal</span>
                                        <h4 class="font-extrabold text-slate-900 dark:text-white text-sm">1-Click Online Quick Apply</h4>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                                            Submit your saved Infoplus profile and CV directly to <?php echo htmlspecialchars($c_company); ?> instantly.
                                        </p>
                                    </div>
                                    <button @click="showApplyModal = true" class="w-full py-3 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-black text-xs shadow-md transition-all cursor-pointer mt-3">
                                        Quick Apply Online Now →
                                    </button>
                                </div>

                                <!-- Way 2: Hotline 6781 -->
                                <div class="p-5 rounded-2xl bg-amber-500/10 border-2 border-amber-500/30 space-y-3 flex flex-col justify-between">
                                    <div>
                                        <span class="px-2.5 py-1 rounded-full bg-amber-500 text-slate-950 text-[9px] font-black uppercase inline-block mb-2">Option 2: Call Center</span>
                                        <h4 class="font-extrabold text-slate-900 dark:text-white text-sm">Phone Registration via 6781</h4>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                                            Don't have internet or a laptop? Call shortcode <strong>6781</strong>. Our agents will register you and apply on your behalf over the phone!
                                        </p>
                                    </div>
                                    <a href="tel:6781" class="w-full py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs text-center shadow-md transition-all block mt-3">
                                        ☎️ Call Hotline 6781 Directly
                                    </a>
                                </div>

                            </div>

                            <!-- Persuasive Call Center Banner -->
                            <div class="p-5 rounded-2xl bg-gradient-to-r from-teal-500/10 via-emerald-500/10 to-amber-500/10 border border-teal-500/30 flex flex-col sm:flex-row items-center justify-between gap-4 text-left">
                                <div class="space-y-1">
                                    <h4 class="font-black text-slate-900 dark:text-white text-sm sm:text-base flex items-center gap-2">
                                        <span>🎧</span> <span>Need Assistance Applying?</span>
                                    </h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                        Our 6781 Call Center team is available 24/7 to help you build your CV, update your profile, or answer any questions about this vacancy at <strong><?php echo htmlspecialchars($c_company); ?></strong>.
                                    </p>
                                </div>
                                <a href="tel:6781" class="px-6 py-3 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-xs shadow-lg flex-shrink-0 whitespace-nowrap">
                                    ☎️ Call 6781 Free Support
                                </a>
                            </div>

                        </div>

                    </div>

                    <!-- Document Attachment (PDF/Flyer Download if exists) -->
                    <?php if (!empty($job['attachment_file'])): ?>
                    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="text-2xl">📄</span>
                            <div>
                                <h4 class="font-bold text-xs text-amber-700 dark:text-amber-400">Official Vacancy Attachment Document</h4>
                                <p class="text-[10px] text-slate-500">Download PDF or view flyer provided by <?php echo htmlspecialchars($c_company); ?></p>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars($job['attachment_file']); ?>" target="_blank" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded-xl shadow-md transition-all">
                            View File ↗
                        </a>
                    </div>
                    <?php endif; ?>

                </div>

            </div>

           <!-- RIGHT SIDEBAR (4/12 - Magic Overview Summary & Dynamic Views) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- 🌟 MAGIC JOB OVERVIEW SUMMARY CARD 🌟 -->
                <div class="dotted-card bg-white/95 dark:bg-slate-900/95 border-2 border-teal-500/30 rounded-3xl p-6 shadow-xl space-y-5 text-left relative overflow-hidden">
                    
                    <!-- Top Header with Live Pulse -->
                    <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="font-black text-slate-900 dark:text-white text-base flex items-center gap-2">
                            <span>📊</span> <span>Job Overview Summary</span>
                        </h3>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-teal-500 animate-pulse"></span> Live
                        </span>
                    </div>

                    <!-- Key Metric Rows -->
                    <div class="space-y-3 text-xs font-semibold text-slate-600 dark:text-slate-300">
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800/60 pb-2">
                            <span class="text-slate-400 font-bold">Category:</span>
                            <span class="font-extrabold text-slate-900 dark:text-white text-right max-w-[60%] truncate"><?php echo htmlspecialchars($c_category); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800/60 pb-2">
                            <span class="text-slate-400 font-bold">Job Type:</span>
                            <span class="font-extrabold text-teal-600 dark:text-teal-400 text-right"><?php echo htmlspecialchars($c_type); ?></span>
                        </div>
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800/60 pb-2">
                            <span class="text-slate-400 font-bold">Career Level:</span>
                            <span class="font-extrabold text-slate-900 dark:text-white text-right max-w-[60%] truncate" title="<?php echo htmlspecialchars($career_lvl); ?>"><?php echo htmlspecialchars($career_lvl); ?></span>
                        </div>
                        <!-- Posted Date -->
                                <div class="flex justify-between items-center py-2.5">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Posted Date:</span>
                                    <span class="text-xs font-black text-slate-900 dark:text-white text-right"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                                </div>
                            </div>

                            <!-- 🌟 REAL-TIME APPLICATION PROGRESS TRACKER 🌟 -->
                            <div class="mt-5 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-sm relative overflow-hidden">
                                <!-- Background accent -->
                                <div class="absolute -right-4 -top-4 w-16 h-16 bg-teal-500/10 rounded-full blur-xl pointer-events-none"></div>

                                <div class="flex justify-between items-center text-[10px] sm:text-xs font-bold mb-2 relative z-10">
                                    <span class="text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        Live Applicants
                                    </span>
                                    <span class="text-teal-600 dark:text-teal-400 font-black bg-teal-500/10 px-2 py-0.5 rounded-md border border-teal-500/20">
                                        <?php echo $total_applied; ?> / <?php echo $total_required; ?> Filled
                                    </span>
                                </div>
                                
                                <!-- Progress Bar Container -->
                                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 mb-1.5 relative z-10 overflow-hidden shadow-inner">
                                    <!-- Dynamic Progress Fill -->
                                    <div class="bg-gradient-to-r from-teal-400 to-emerald-500 h-2 rounded-full transition-all duration-1000 ease-out relative" style="width: <?php echo $progress_percent; ?>%">
                                        <div class="absolute inset-0 bg-white/20 w-full animate-[pulse_2s_ease-in-out_infinite]"></div>
                                    </div>
                                </div>
                                
                                <!-- Status Message -->
                                <div class="relative z-10">
                                    <?php if($total_applied >= $total_required): ?>
                                        <p class="text-[9px] text-rose-500 mt-1 font-bold flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span> Limit Reached. Apply ASAP!</p>
                                    <?php else: ?>
                                        <p class="text-[9px] text-slate-400 mt-1 font-semibold flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Accepting applications now.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- END OF PROGRESS TRACKER -->

                            <!-- AI OVERVIEW HIGHLIGHTS -->
                    </div>
                </div>

            </div>
        </div>

        <!-- 🌟 BOTTOM 3D ILLUSTRATION SECTION (`signup.png` with 3D Float) 🌟 -->
        <div class="mt-16 dotted-card bg-gradient-to-r from-teal-600/90 via-emerald-600/90 to-teal-700/90 text-white rounded-[2.5rem] p-8 sm:p-12 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10 text-left">
                <div class="lg:col-span-8 space-y-4">
                    <span class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-white/20 text-white border border-white/30 inline-block">
                        🚀 Take the Next Career Step
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-black text-white leading-tight">
                        Ready to Apply for <span class="text-amber-300"><?php echo htmlspecialchars($c_title); ?></span>?
                    </h2>
                    <p class="text-xs sm:text-sm text-white/90 leading-relaxed max-w-2xl font-medium">
                        Create your candidate profile on Infoplus to enjoy 1-click job applications, automatic CV matching, and instant job alerts directly to your phone.
                    </p>
                    <div class="pt-2 flex flex-wrap gap-4">
                        <button @click="showApplyModal = true" class="px-8 py-3.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm shadow-xl transition-all transform hover:-translate-y-0.5 cursor-pointer">
                            Apply for this Job Now →
                        </button>
                        <a href="signup.php?type=seeker" class="px-6 py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white border border-white/30 font-bold text-sm transition-all">
                            Create Free Profile
                        </a>
                    </div>
                </div>

                <!-- 3D Graphic Image (`signup.png` with Float Animation) -->
                <div class="lg:col-span-4 flex justify-center">
                    <div class="relative w-full max-w-[220px] sm:max-w-[280px] animate-float">
                        <img src="signup.png" onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/job-registration-5353004-4468698.png'" alt="Candidate Registration 3D" class="w-full h-auto object-contain filter drop-shadow-2xl mix-blend-multiply dark:mix-blend-normal">
                    </div>
                </div>
            </div>
        </div>

        <!-- 🌟 QUICK APPLY MODAL (Connects to apply_action.php) 🌟 -->
        <div x-show="showApplyModal" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
            <div @click.away="showApplyModal = false" class="bg-white dark:bg-slate-900 border border-teal-500/40 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl text-left relative space-y-6">
                
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-4">
                    <h3 class="font-black text-slate-900 dark:text-white text-lg">⚡ Quick Application</h3>
                    <button @click="showApplyModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white font-bold">✕</button>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="space-y-4">
                        <div class="p-4 rounded-2xl bg-teal-500/10 border border-teal-500/20">
                            <p class="text-xs font-bold text-teal-600 dark:text-teal-400">Applying as:</p>
                            <p class="text-sm font-black text-slate-900 dark:text-white mt-0.5"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Candidate'); ?></p>
                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                        </div>
                        <p class="text-xs text-slate-500">Your saved Infoplus CV and candidate profile will be submitted directly to <strong><?php echo htmlspecialchars($c_company); ?></strong>.</p>
                        
                        <form action="apply_action.php" method="POST">
                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                            <button type="submit" class="w-full py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 text-white font-black text-sm shadow-lg shadow-teal-500/30 transition-all cursor-pointer">
                                Confirm & Submit Application →
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="space-y-4 text-center">
                        <div class="w-16 h-16 bg-teal-500/10 text-teal-500 rounded-full flex items-center justify-center mx-auto text-3xl">
                            💼
                        </div>
                        <h4 class="font-black text-slate-900 dark:text-white text-base">Candidate Profile Required</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            To apply for <strong><?php echo htmlspecialchars($c_title); ?></strong>, please sign up or log in. We will automatically submit your application once verified!
                        </p>
                        <div class="flex gap-3 pt-2">
                            <a href="apply_action.php?job_id=<?php echo $job['id']; ?>" class="flex-1 py-3.5 bg-teal-600 hover:bg-teal-500 text-white rounded-2xl font-black text-xs text-center shadow-lg shadow-teal-500/20">
                                Sign Up & Apply →
                            </a>
                            <a href="login.php" class="flex-1 py-3.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-2xl font-bold text-xs text-center">
                                Log In
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php
include_once __DIR__ . '/includes/footer.php';
?>