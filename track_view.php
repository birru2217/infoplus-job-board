<?php
// track_view.php - Dhoksaatti view lakkaa'a (AJAX)
session_start();
require_once __DIR__ . '/config.php';

if (isset($_GET['id']) && isset($pdo)) {
    $job_id = (int)$_GET['id'];
    
    // User-ni tokko hojii tokkoof al-tokko qofa akka lakkaa'amuuf Session fayyadamna
    if (!isset($_SESSION['viewed_jobs'])) {
        $_SESSION['viewed_jobs'] = [];
    }
    
    if (!in_array($job_id, $_SESSION['viewed_jobs'])) {
        $stmt = $pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?");
        $stmt->execute([$job_id]);
        $_SESSION['viewed_jobs'][] = $job_id;
        echo "View Added";
    } else {
        echo "Already Viewed";
    }
}
?>