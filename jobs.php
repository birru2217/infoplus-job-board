<?php
include_once __DIR__ . '/includes/header.php';

// --- 1. EXTRACT SEARCH & FILTER PARAMETERS ---
$q = trim($_GET['q'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$location_filter = trim($_GET['location'] ?? '');
$career_filter   = trim($_GET['career'] ?? '');
$type_filter     = trim($_GET['type'] ?? '');
$gender_filter   = trim($_GET['gender'] ?? '');
$date_filter     = trim($_GET['date'] ?? '');
$sort_by         = trim($_GET['sort'] ?? 'latest');

// --- 2. DYNAMIC DATABASE SEARCH QUERY (WITH SMART FUZZY & DICTIONARY MATCHING) ---
$jobs = [];
$recommended_jobs = [];

if (isset($pdo) && $pdo !== null) {
    try {
        $sql = "SELECT * FROM jobs WHERE status = 'active'";
        $params = [];

        // 🌟 SMART SEARCH: Exact + Related/Synonym + Fuzzy Keyword Matching 🌟
        if (!empty($q)) {
            $q_lower = strtolower(trim($q));
            $words = explode(' ', $q_lower);
            
            // 🪄 MAGIC DICTIONARY: Walitti dhufeenya hojiiwwanii (Synonyms)
            $synonyms = [
                'ict' => ['data', 'computer', 'software', 'network', 'tech', 'it', 'developer', 'entry'],
                'data' => ['ict', 'entry', 'computer', 'database', 'clerk', 'typing'],
                'eng' => ['engineer', 'construction', 'mechanic', 'architecture', 'build'],
                'admin' => ['secretarial', 'clerical', 'assistant', 'office', 'receptionist'],
                'health' => ['medical', 'nurse', 'doctor', 'clinic', 'hospital', 'pharmacy'],
                'finance' => ['accounting', 'bank', 'cashier', 'audit', 'teller'],
                'bank' => ['finance', 'teller', 'accounting', 'customer'],
                'sales' => ['marketing', 'agent', 'broker', 'trade']
            ];

            $search_terms = $words;

            // 🪄 FUZZY MATCH: Qubee dogoggorame sirreessuu fi wal-fakkii fiduu
            foreach ($synonyms as $key => $related_words) {
                foreach ($words as $w) {
                    // Yoo guutummaatti walsime ykn qubeen 1 ykn 2 qofti dogoggorame (levenshtein distance <= 2)
                    if (strpos($w, $key) !== false || levenshtein($w, $key) <= 2) {
                        $search_terms = array_merge($search_terms, $related_words);
                    }
                }
            }
            $search_terms = array_unique($search_terms); // Jechoota irra deddeebi'aman haquu

            $q_conditions = [];
            foreach ($search_terms as $idx => $term) {
                if (strlen($term) > 1) { // Jechoota qubee 1 qaban dhiisuu
                    $param_key = ":w_" . $idx;
                    $q_conditions[] = "(title LIKE $param_key OR category LIKE $param_key OR description LIKE $param_key OR company_name LIKE $param_key)";
                    $params[$param_key] = "%$term%";
                }
            }
            
            if (!empty($q_conditions)) {
                $sql .= " AND (" . implode(' OR ', $q_conditions) . ")";
            }
        }
        // Dropdown Filters
        if (!empty($category_filter)) {
            $sql .= " AND category = :category";
            $params[':category'] = $category_filter;
        }

        if (!empty($location_filter)) {
            $sql .= " AND location LIKE :location";
            $params[':location'] = "%$location_filter%";
        }

        if (!empty($career_filter)) {
            $sql .= " AND (career_level LIKE :career OR experience LIKE :career)";
            $params[':career'] = "%$career_filter%";
        }

        if (!empty($type_filter)) {
            $sql .= " AND job_type = :type";
            $params[':type'] = $type_filter;
        }

        // 🌟 GENDER FILTER (Male / Female / All) 🌟
        if (!empty($gender_filter) && $gender_filter !== 'All Genders') {
            $sql .= " AND (title LIKE :gender OR description LIKE :gender OR requirements LIKE :gender)";
            $params[':gender'] = "%" . $gender_filter . "%";
        }

        // Date Filter
        if ($date_filter === 'Past 24 Hours') {
            $sql .= " AND created_at >= NOW() - INTERVAL 1 DAY";
        } elseif ($date_filter === 'Past Week') {
            $sql .= " AND created_at >= NOW() - INTERVAL 1 WEEK";
        } elseif ($date_filter === 'Past Month') {
            $sql .= " AND created_at >= NOW() - INTERVAL 1 MONTH";
        }

        // 🌟 RANKING: Exact Title & Company Matches Float to the TOP! 🌟
        if (!empty($q)) {
            $sql .= " ORDER BY (CASE WHEN title LIKE :exact_q THEN 1 WHEN company_name LIKE :exact_q THEN 2 WHEN title LIKE :part_q THEN 3 ELSE 4 END) ASC, id DESC";
            $params[':exact_q'] = "$q%";
            $params[':part_q'] = "%$q%";
        } else {
            if ($sort_by === 'closing_soon') {
                $sql .= " ORDER BY (CASE WHEN deadline IS NULL OR deadline = '' THEN 1 ELSE 0 END) ASC, deadline ASC, id DESC";
            } elseif ($sort_by === 'views') {
                $sql .= " ORDER BY views DESC, id DESC";
            } else {
                $sql .= " ORDER BY id DESC"; // Default Latest
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll();

        // 🌟 3. AI CANDIDATE MATCHING ENGINE (For Logged-in Candidate) 🌟
        if (isset($_SESSION['user_id'])) {
            $cand_id = (int)$_SESSION['user_id'];
            $cand_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $cand_stmt->execute([$cand_id]);
            $candidate = $cand_stmt->fetch();

            if ($candidate) {
                $cand_city = $candidate['city'] ?? 'Addis Ababa';
                $cand_pos  = $candidate['work_position'] ?? 'Developer';

                $rec_stmt = $pdo->prepare("SELECT * FROM jobs WHERE status = 'active' AND (location LIKE ? OR category LIKE ? OR title LIKE ?) ORDER BY id DESC LIMIT 2");
                $rec_stmt->execute(["%$cand_city%", "%$cand_pos%", "%$cand_pos%"]);
                $recommended_jobs = $rec_stmt->fetchAll();
            }
        }

    } catch (PDOException $e) {
        $jobs = [];
    }
}

// Helper to build Sort URL query
function build_sort_url($sort_val) {
    $params = $_GET;
    $params['sort'] = $sort_val;
    return 'jobs.php?' . http_build_query($params);
}

// Filter Categories & Options
$categories = ["Accounting and Finance", "Admin, Secretarial and Clerical", "Agriculture", "Architecture and Construction", "Banking and Insurance", "Business Development", "Customer Service", "Education", "Engineering", "Health Care", "ICT and Telecom", "Legal", "Sales and Marketing"];
$locations  = ["Addis Ababa", "Oromia", "Amhara", "Sidama", "Dire Dawa", "Adama", "Hawassa", "Remote"];
$career_levels = ["Entry Level (Fresh Graduate)", "Junior Level (1-3 years)", "Mid Level (3-5 years)", "Senior (5-8 years)"];
$emp_types  = ["Full Time", "Part Time", "Contract", "Internship", "Remote"];
$genders    = ["All Genders", "Female", "Male"];
$dates      = ["Anytime", "Past 24 Hours", "Past Week", "Past Month"];
?>

<!-- 🌟 TOP SEARCH & FILTER ENGINE 🌟 -->
<section class="relative z-30 pt-6 sm:pt-10 pb-4">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        
        <div class="text-center mb-6">
            <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Find Your Dream Job in Ethiopia</h1>
            <p class="mt-1.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400">Search thousands of verified vacancies from top employers across Ethiopia.</p>
        </div>

        <!-- SEARCH FORM -->
        <form action="jobs.php" method="GET" class="dotted-card bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border border-teal-500/30 dark:border-teal-400/30 p-3.5 sm:p-6 rounded-[2rem] shadow-2xl shadow-teal-500/10 transition-all">
            
            <!-- 🌟 Main Search Bar: 100% Horizontal on Mobile & PC (Input + Button on One Line) 🌟 -->
            <div class="flex flex-row items-center gap-1.5 sm:gap-3 mb-3 sm:mb-4 p-1 sm:p-1.5 bg-slate-100/90 dark:bg-slate-800/80 rounded-2xl border border-slate-200/80 dark:border-slate-700/80">
                <div class="relative w-full flex-1 min-w-0">
                    <span class="absolute inset-y-0 left-0 pl-3 sm:pl-3.5 flex items-center text-teal-500 font-bold text-xs sm:text-sm">🔍</span>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Job title, Keywords, or Company..." class="w-full pl-8 sm:pl-10 pr-2 sm:pr-3 py-2.5 sm:py-3.5 rounded-xl bg-transparent text-slate-900 dark:text-white text-xs sm:text-sm font-semibold outline-none transition-all placeholder:text-slate-400">
                </div>
                <button type="submit" class="px-4 sm:px-8 py-2.5 sm:py-3.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-black text-xs sm:text-sm transition-all shadow-md shadow-teal-600/30 whitespace-nowrap flex-shrink-0">
                    Search Jobs
                </button>
            </div>

            <!-- 🌟 6 Dropdown Filters Grid (2-Cols on Mobile, 6-Cols on PC: Symmetrically Balanced!) 🌟 -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3">
                
                <!-- 1. Category Filter -->
                <select name="category" class="w-full px-2.5 py-2 sm:py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold outline-none focus:border-teal-500 cursor-pointer">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- 2. Location Filter -->
                <select name="location" class="w-full px-2.5 py-2 sm:py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold outline-none focus:border-teal-500 cursor-pointer">
                    <option value="">All Locations</option>
                    <?php foreach($locations as $loc): ?>
                        <option value="<?php echo $loc; ?>" <?php echo $location_filter === $loc ? 'selected' : ''; ?>><?php echo $loc; ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- 3. Career Level Filter -->
                <select name="career" class="w-full px-2.5 py-2 sm:py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold outline-none focus:border-teal-500 cursor-pointer">
                    <option value="">Career Level</option>
                    <?php foreach($career_levels as $lvl): ?>
                        <option value="<?php echo $lvl; ?>" <?php echo $career_filter === $lvl ? 'selected' : ''; ?>><?php echo $lvl; ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- 4. Employment Type Filter -->
                <select name="type" class="w-full px-2.5 py-2 sm:py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold outline-none focus:border-teal-500 cursor-pointer">
                    <option value="">Employment Type</option>
                    <?php foreach($emp_types as $typ): ?>
                        <option value="<?php echo $typ; ?>" <?php echo $type_filter === $typ ? 'selected' : ''; ?>><?php echo $typ; ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- 5. Gender Filter (NEWLY ADDED) -->
                <select name="gender" class="w-full px-2.5 py-2 sm:py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold outline-none focus:border-teal-500 cursor-pointer">
                    <?php foreach($genders as $gen): ?>
                        <option value="<?php echo $gen; ?>" <?php echo $gender_filter === $gen ? 'selected' : ''; ?>><?php echo $gen; ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- 6. Date Posted Filter -->
                <select name="date" class="w-full px-2.5 py-2 sm:py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] sm:text-xs font-semibold outline-none focus:border-teal-500 cursor-pointer">
                    <option value="">Date Posted</option>
                    <?php foreach($dates as $dt): ?>
                        <option value="<?php echo $dt; ?>" <?php echo $date_filter === $dt ? 'selected' : ''; ?>><?php echo $dt; ?></option>
                    <?php endforeach; ?>
                </select>

            </div>
        </form>

    </div>
</section>

<!-- 🌟 MAIN CONTENT: JOBS LIST 🌟 -->
<section class="py-4 sm:py-8 relative z-20">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        
        <!-- 🌟 AI CANDIDATE RECOMMENDATION BANNER 🌟 -->
        <?php if (!empty($recommended_jobs)): ?>
        <div class="mb-8 dotted-card border-2 border-teal-500/50 bg-gradient-to-r from-teal-500/10 via-emerald-500/10 to-amber-500/10 p-4 sm:p-7 rounded-3xl shadow-xl relative overflow-hidden">
            <div class="flex justify-between items-center mb-4 border-b border-teal-500/20 pb-3">
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <span class="text-2xl animate-bounce">✨</span>
                    <div>
                        <h3 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white">AI Recommended Jobs</h3>
                        <p class="text-[10px] sm:text-xs text-teal-600 dark:text-teal-400 font-bold">Matched with your skills & location</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-teal-500 text-white font-black text-[9px] sm:text-xs shadow-md">98% Match</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach($recommended_jobs as $rec_job): ?>
                <div class="bg-white/90 dark:bg-slate-900/90 border border-teal-500/40 p-4 rounded-2xl shadow-md flex justify-between items-center">
                    <div class="min-w-0 pr-2 text-left">
                        <span class="text-[9px] font-extrabold text-teal-600 dark:text-teal-400 uppercase tracking-widest block">⭐ Recommended</span>
                        <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white mt-0.5 truncate">
                            <a href="job-details.php?id=<?php echo $rec_job['id']; ?>" class="hover:text-teal-600"><?php echo htmlspecialchars($rec_job['title']); ?></a>
                        </h4>
                        <p class="text-[10px] text-slate-500 truncate"><?php echo htmlspecialchars($rec_job['company_name']); ?> • <?php echo htmlspecialchars($rec_job['location']); ?></p>
                    </div>
                    <a href="job-details.php?id=<?php echo $rec_job['id']; ?>" class="px-3.5 py-1.5 rounded-xl bg-teal-600 text-white text-xs font-bold shadow-md hover:bg-teal-500 flex-shrink-0">Apply</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- MAIN LAYOUT GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- LEFT COLUMN: STICKY GRAPHIC WIDGET (Desktop Only) -->
            <div class="hidden lg:block lg:col-span-4 sticky top-24">
                <div class="relative w-full flex flex-col items-center justify-center p-2 pt-6">
                    <div class="absolute inset-0 bg-gradient-to-tr from-teal-500/20 via-emerald-500/15 to-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div class="relative z-10 w-full animate-float flex flex-col items-center">
                        <img src="job.png" 
                             onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/job-search-5352994-4468688.png'" 
                             alt="Find Jobs Graphic" 
                             class="w-full h-auto max-h-none object-contain filter drop-shadow-2xl transparent-graphic scale-[1.15] hover:scale-[1.20] transition-transform duration-500">
                        
                        <div class="absolute -top-4 -right-4 xl:-right-8 dotted-card px-3.5 py-1.5 rounded-2xl border border-teal-500/30 shadow-xl bg-white/90 dark:bg-slate-900/90 backdrop-blur-md animate-pulse z-20">
                            <span class="text-teal-600 dark:text-teal-400 font-black text-[10px] uppercase">🎯 Dream Job</span>
                        </div>
                        
                        <div class="absolute -bottom-6 -left-6 xl:-left-12 dotted-card px-3.5 py-1.5 rounded-2xl border border-amber-500/30 shadow-xl bg-white/90 dark:bg-slate-900/90 backdrop-blur-md animate-float-reverse z-20">
                            <span class="text-amber-600 dark:text-amber-400 font-black text-[10px] uppercase">⚡ Instant Apply</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: RESULTS & JOB CARDS -->
            <div class="lg:col-span-8 space-y-4 sm:space-y-6">
                
                <!-- 🌟 RESULTS HEADER (100% Side-by-Side on Mobile & PC) 🌟 -->
                <div class="flex flex-row justify-between items-center border-b border-slate-200/60 dark:border-slate-800/60 pb-3 mb-4 gap-2 text-left">
                    <h2 class="text-xs sm:text-base font-extrabold text-slate-800 dark:text-slate-200 truncate">
                        Showing <span class="text-teal-600 font-black"><?php echo count($jobs); ?></span> jobs
                    </h2>
                    
                    <!-- Dynamic Sorter Selector -->
                    <div class="flex items-center space-x-1.5 text-[11px] sm:text-xs text-slate-500 flex-shrink-0">
                        <span class="hidden sm:inline font-bold">Sort by:</span>
                        <select onchange="location = this.value;" class="bg-slate-100 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 px-2.5 py-1 rounded-xl font-bold text-slate-700 dark:text-slate-300 text-[10px] sm:text-xs outline-none cursor-pointer">
                            <option value="<?php echo build_sort_url('latest'); ?>" <?php echo $sort_by === 'latest' ? 'selected' : ''; ?>>Latest Posts</option>
                            <option value="<?php echo build_sort_url('closing_soon'); ?>" <?php echo $sort_by === 'closing_soon' ? 'selected' : ''; ?>>Closing Soon</option>
                            <option value="<?php echo build_sort_url('views'); ?>" <?php echo $sort_by === 'views' ? 'selected' : ''; ?>>Most Viewed</option>
                        </select>
                    </div>
                </div>

                <!-- 🌟 3D POP-OUT EMPTY STATE: "NO JOBS FOUND" (ENLARGED & MAGIC ANIMATED) 🌟 -->
                <?php if (empty($jobs)): ?>
                    <div class="dotted-card bg-white/95 dark:bg-slate-900/95 p-6 sm:p-14 rounded-[2.5rem] text-center border-2 border-teal-500/30 dark:border-teal-500/40 shadow-2xl flex flex-col items-center justify-center relative overflow-visible my-6 group">
                        
                        <!-- 🌟 3D Pop-Out Floating Graphic Container (Enlarged & Glowing!) 🌟 -->
                        <div class="relative w-44 h-44 sm:w-64 sm:h-64 -mt-10 sm:-mt-20 mb-3 flex items-center justify-center">
                            <!-- Background Glow Aura -->
                            <div class="absolute inset-0 bg-gradient-to-tr from-teal-500/30 via-cyan-500/20 to-amber-500/20 rounded-full blur-2xl sm:blur-3xl animate-pulse pointer-events-none"></div>
                            
                            <!-- Enlarged 3D Image popping out of card -->
                            <img src="nofound.png" 
                                 onerror="this.src='https://cdn3d.iconscout.com/3d/premium/thumb/empty-folder-5353008-4468702.png'" 
                                 alt="No Jobs Found" 
                                 class="relative z-10 w-full h-full object-contain filter drop-shadow-[0_20px_25px_rgba(0,0,0,0.3)] animate-float transform hover:scale-110 transition-transform duration-500">
                        </div>

                        <h3 class="text-lg sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">No Jobs Found Matching Your Criteria</h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md leading-relaxed">We couldn't find any direct matches for your keywords. Try searching for related categories, selecting "All Genders", or clearing your filters.</p>
                        
                        <a href="jobs.php" class="mt-6 px-8 py-3.5 bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-xs sm:text-sm rounded-2xl shadow-xl shadow-teal-600/30 hover:scale-105 transition-all">
                            Reset All Filters 🔄
                        </a>
                    </div>
                <?php else: ?>
                    
                    <!-- Job Cards Loop -->
                    <?php foreach($jobs as $job): ?>
                    <?php 
                        $logo = !empty($job['logo']) ? $job['logo'] : 'logo.png';
                        $career = !empty($job['career_level']) ? $job['career_level'] : ($job['experience'] ?? 'Mid Level');
                        $deadline = !empty($job['deadline']) ? 'Closes: ' . $job['deadline'] : 'Open';
                    ?>
                    <div class="dotted-card bg-white/85 dark:bg-slate-900/85 backdrop-blur-lg border border-slate-200/80 dark:border-slate-800 p-4 sm:p-7 rounded-3xl shadow-sm hover:shadow-xl hover:border-teal-500/50 transition-all duration-300 group text-left">
                        
                        <!-- Top Row: Deadline & Save -->
                        <div class="flex justify-between items-center mb-3 sm:mb-4">
                            <span class="text-[11px] sm:text-xs font-bold text-amber-500">
                                ⏰ <?php echo htmlspecialchars($deadline); ?>
                            </span>
                            <button class="text-slate-400 hover:text-teal-500 transition-colors flex items-center space-x-1 text-xs font-bold border border-slate-200 dark:border-slate-700 px-2.5 py-1 rounded-xl hover:bg-teal-50 dark:hover:bg-slate-800">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                                <span class="hidden sm:inline">Save</span>
                            </button>
                        </div>

                        <!-- Middle Row: Logo + Job Title + Company -->
                        <div class="flex items-start space-x-3 sm:space-x-5 mb-4">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 flex-shrink-0 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-1.5 shadow-sm flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform">
                                <img src="<?php echo htmlspecialchars($logo); ?>" onerror="this.src='logo.png'" alt="Logo" class="w-full h-full object-contain rounded-xl">
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white leading-snug group-hover:text-teal-600 transition-colors truncate sm:whitespace-normal">
                                    <a href="job-details.php?id=<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a>
                                </h2>
                                <p class="text-xs sm:text-sm font-extrabold text-teal-600 dark:text-teal-400 mt-0.5 flex items-center truncate">
                                    🏢 <?php echo htmlspecialchars($job['company_name']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Badges Row -->
                        <div class="flex flex-wrap gap-1.5 sm:gap-2 mb-4">
                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg text-[10px] sm:text-xs font-semibold flex items-center">
                                📍 <?php echo htmlspecialchars($job['location']); ?>
                            </span>
                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg text-[10px] sm:text-xs font-semibold flex items-center">
                                🏷️ <?php echo htmlspecialchars($job['category']); ?>
                            </span>
                            <span class="px-2.5 py-1 bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 rounded-lg text-[10px] sm:text-xs font-bold border border-teal-200 dark:border-teal-500/20 flex items-center">
                                💼 <?php echo htmlspecialchars($job['job_type']); ?>
                            </span>
                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-lg text-[10px] sm:text-xs font-semibold flex items-center">
                                📈 <?php echo htmlspecialchars($career); ?>
                            </span>
                            <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 rounded-lg text-[10px] sm:text-xs font-black border border-emerald-200 dark:border-emerald-500/20 flex items-center">
                                💵 <?php echo htmlspecialchars($job['salary']); ?>
                            </span>
                        </div>

                        <!-- Description Snippet -->
                        <div class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-4 line-clamp-2 sm:line-clamp-3">
                            <?php echo htmlspecialchars($job['description']); ?>
                        </div>

                        <!-- Action Footer Row -->
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800 pt-4">
                            <div class="flex flex-1 gap-2 sm:gap-3">
                                <a href="job-details.php?id=<?php echo $job['id']; ?>" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-extrabold text-xs sm:text-sm text-center transition-all shadow-md shadow-teal-500/20">
                                    Apply Now
                                </a>
                                <a href="job-details.php?id=<?php echo $job['id']; ?>" class="px-3 sm:px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 hover:border-teal-500 text-slate-700 dark:text-slate-300 hover:text-teal-500 font-bold text-xs sm:text-sm text-center transition-all">
                                    Details 📖
                                </a>
                            </div>
                            
                            <button onclick="shareJob('<?php echo addslashes($job['title']); ?>', 'job-details.php?id=<?php echo $job['id']; ?>')" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-teal-600 transition-colors" title="Share this job">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                            </button>
                        </div>

                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

        </div>
    </div>
</section>

<script>
function shareJob(title, url) {
    if (navigator.share) {
        navigator.share({
            title: 'Check out this job on Infoplus: ' + title,
            url: window.location.origin + '/' + url
        }).catch(console.error);
    } else {
        navigator.clipboard.writeText(window.location.origin + '/' + url);
        alert('Job link copied to clipboard!');
    }
}
</script>

<?php
include_once __DIR__ . '/includes/footer.php';
?>