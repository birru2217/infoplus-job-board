<?php
// apply_action.php
session_start();
require_once __DIR__ . '/config.php';

$job_id = (int)($_POST['job_id'] ?? $_GET['job_id'] ?? 0);

if ($job_id <= 0) {
    header("Location: jobs.php");
    exit;
}

// 🌟 YOO USER-NI ACCOUNT HIN QABNE / LOGIN HIN GOONE
if (!isset($_SESSION['user_id'])) {
    // ID Hojii kanaa Session keessatti SAVE gochuu!
    $_SESSION['apply_redirect_job_id'] = $job_id;
    
    // Gara Signup geessuu
    header("Location: signup.php?type=seeker");
    exit;
}

// 🌟 YOO USER-NI LOGGED IN TA'E (APPLICATION PROCESS)
$user_id = $_SESSION['user_id'];

if (isset($pdo) && $pdo !== null) {
    try {
        // 1. Dursee apply godhee jiraachuun isaa check gochuu (Duplicates prevention)
        $check = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
        $check->execute([$job_id, $user_id]);
        
        if (!$check->fetch()) {
            // 2. Application haaraa Database 'applications' table keessatti insert gochuu
            $stmt = $pdo->prepare("INSERT INTO applications (job_id, user_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$job_id, $user_id]);
        }
        
        // Success Message
        $_SESSION['app_success_msg'] = "Application Submitted Successfully! The employer has received your profile and CV.";
        
        // Gara Job Details page deebisuu
        header("Location: job-details.php?id=" . $job_id . "&status=applied");
        exit;

    } catch (PDOException $e) {
        $_SESSION['app_error_msg'] = "Database Error: " . $e->getMessage();
        header("Location: job-details.php?id=" . $job_id);
        exit;
    }
}
?>