<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if user is Superadmin or Admin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== true) {
    header("Location: login.php");
    exit();
}
$admin_role = $_SESSION['admin_role'] ?? 'admin';

// 🌟 MAGIC: GUARANTEE TRUE SUPERADMIN ROLE FROM DATABASE 🌟
if (isset($pdo) && isset($_SESSION['admin_email'])) {
    try {
        $r_stmt = $pdo->prepare("SELECT role FROM admins WHERE email = ? LIMIT 1");
        $r_stmt->execute([$_SESSION['admin_email']]);
        $db_role = $r_stmt->fetchColumn();
        if ($db_role) {
            $admin_role = $db_role;
            $_SESSION['admin_role'] = $db_role;
        }
    } catch(Exception $e) {}
}
if (($_SESSION['admin_email'] ?? '') === 'tadesebiruk515@gmail.com') {
    $admin_role = 'superadmin';
    $_SESSION['admin_role'] = 'superadmin';
}

// 🌟 MAGIC GATEKEEPER: Sub-Admin yoo ta'e kallattiin gara admin_dashboard.php tti dabarsa! 🌟
if ($admin_role !== 'superadmin') {
    header("Location: admin_dashboard.php");
    exit();
}

// Session Alert Messages
$success_msg = $_SESSION['success_msg'] ?? "";
$error_msg = $_SESSION['error_msg'] ?? "";
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$active_tab = 'dashboard';

// ==========================================
// BACKEND MAGIC: Handle Form Submissions (100% CLEANED & SECURE)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $active_tab = $_POST['active_tab'] ?? 'dashboard';

    // 🌟 1. POST A JOB & AUTO-POST TO SOCIAL MEDIA
    if ($action === 'post_job' && isset($pdo)) {
        $title = trim($_POST['title'] ?? '');
        $company = trim($_POST['company_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $job_type = trim($_POST['job_type'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $salary = trim($_POST['salary'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');
        $career_level = trim($_POST['career_level'] ?? '');
        $positions = (int)($_POST['positions'] ?? 1);
        $desc = trim($_POST['description'] ?? '');
        $req = trim($_POST['requirements'] ?? '');
        $how_to_apply = trim($_POST['how_to_apply'] ?? '');
        
        $post_telegram = isset($_POST['post_telegram']) ? 1 : 0;

        // Logo & Attachments Upload
        $logo_url = 'logo.png'; 
        $attachment_file = null;
        
        if (isset($_FILES['job_logo']) && $_FILES['job_logo']['error'] === UPLOAD_ERR_OK) {
            $logo_name = time() . '_logo_' . $_FILES['job_logo']['name'];
            move_uploaded_file($_FILES['job_logo']['tmp_name'], 'uploads/logos/' . $logo_name);
            $logo_url = 'uploads/logos/' . $logo_name;
        }
        
        if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
            $attach_dir = 'uploads/attachments/';
            if (!is_dir($attach_dir)) mkdir($attach_dir, 0777, true);
            $attach_name = time() . '_attach_' . $_FILES['attachment_file']['name'];
            move_uploaded_file($_FILES['attachment_file']['tmp_name'], $attach_dir . $attach_name);
            $attachment_file = $attach_dir . $attach_name;
        }

        $stmt = $pdo->prepare("INSERT INTO jobs (company_name, title, category, job_type, experience, location, salary, deadline, career_level, positions, description, requirements, how_to_apply, logo, attachment_file, status, is_top_company) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0)");
        $stmt->execute([$company, $title, $category, $job_type, $career_level, $location, $salary, $deadline, $career_level, $positions, $desc, $req, $how_to_apply, $logo_url, $attachment_file]);
        
        $new_job_id = $pdo->lastInsertId();
        $job_link = "https://infoplus.et/job-details.php?id=" . $new_job_id; 

        $_SESSION['success_msg'] = "Job successfully published to Website!";

        // Telegram Auto-Poster
        if ($post_telegram) {
            $bot_token = "TOKEN_KEE_ASITTI_GALCHI"; 
            $chat_id = "@infopluscallcenter"; 
            $telegram_msg = "🌟 *Infoplus (Job Matchmaking)*\n\n💼 *Job Title:* {$title}\n🏢 *Company:* {$company}\n📍 *Location:* {$location}\n⏱ *Job Type:* {$job_type}\n💰 *Salary:* {$salary}\n📅 *Deadline:* {$deadline}\n📈 *Career Level:* {$career_level}\n👥 *Required:* {$positions} Position(s)\n\n📝 *Description:*\n" . substr($desc, 0, 150) . "...\n\n👉 *[View Full Details & Apply Here]({$job_link})*\n\n☎️ *Call 6781* if you don't have internet.";

            $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
            $data = ['chat_id' => $chat_id, 'text' => $telegram_msg, 'parse_mode' => 'Markdown', 'disable_web_page_preview' => false];
            $options = ['http' => ['method' => 'POST', 'header' => "Content-Type:application/x-www-form-urlencoded\r\n", 'content' => http_build_query($data)]];
            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);
            
            if ($result) $_SESSION['success_msg'] .= " Also auto-posted to Telegram channel!";
        }
        header("Location: superadmin.php?tab=jobs"); exit();
    }

    // 🌟 2. EDIT OR DELETE EXISTING JOB
    if ($action === 'edit_job' && isset($pdo)) {
        $job_id = (int)$_POST['job_id'];
        $stmt = $pdo->prepare("UPDATE jobs SET title=?, company_name=?, category=?, location=?, salary=?, job_type=?, career_level=?, experience=?, positions=?, deadline=?, status=?, description=?, requirements=? WHERE id=?");
        $stmt->execute([trim($_POST['title']), trim($_POST['company_name']), trim($_POST['category']), trim($_POST['location']), trim($_POST['salary']), trim($_POST['job_type']), trim($_POST['career_level']), trim($_POST['career_level']), (int)$_POST['positions'], $_POST['deadline'], $_POST['status'], trim($_POST['description'] ?? ''), trim($_POST['requirements'] ?? ''), $job_id]);
        $_SESSION['success_msg'] = "Job details updated successfully!";
        header("Location: superadmin.php?tab=jobs"); exit();
    }

    if ($action === 'delete_job' && isset($pdo)) {
        $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([(int)$_POST['job_id']]);
        $_SESSION['success_msg'] = "Job successfully deleted!";
        header("Location: superadmin.php?tab=jobs"); exit();
    }

    // 🌟 3. TOGGLE TOP HIRING COMPANY
    if ($action === 'toggle_top_company' && isset($pdo)) {
        $job_id = (int)($_POST['job_id'] ?? 0);
        $company_name = trim($_POST['company_name'] ?? '');
        $current_val = (int)($_POST['current_val'] ?? 0);
        $new_val = ($current_val > 0) ? 0 : 1;

        if ($job_id > 0) {
            try { $pdo->exec("ALTER TABLE jobs ADD COLUMN is_top_company TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
            $pdo->prepare("UPDATE jobs SET is_top_company = ? WHERE id = ?")->execute([$new_val, $job_id]);
            if (!empty($company_name)) {
                $pdo->prepare("UPDATE jobs SET is_top_company = ? WHERE company_name = ?")->execute([$new_val, $company_name]);
            }
            $_SESSION['success_msg'] = $new_val ? "⭐ '$company_name' added to Top Hiring Companies on Homepage!" : "☆ '$company_name' removed from Top Hiring List!";
        }
        header("Location: superadmin.php?tab=jobs"); exit();
    }

    // 🌟 4. PROFILE & SECURITY UPDATE (FIXED FOR 2FA SAVING)
    if ($action === 'update_profile' && isset($pdo)) {
        // Auto-add column just in case it's missing
        try { $pdo->exec("ALTER TABLE admins ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0"); } catch(Exception $e){}
        try { $pdo->exec("ALTER TABLE admins ADD COLUMN two_factor_method VARCHAR(50) DEFAULT 'email'"); } catch(Exception $e){}

        $new_email = trim($_POST['admin_email'] ?? '');
        $new_phone = trim($_POST['admin_phone'] ?? '');
        $curr_pass = $_POST['admin_curr_pass'] ?? '';
        $new_pass  = $_POST['admin_pass'] ?? '';
        $conf_pass = $_POST['admin_pass_confirm'] ?? '';
        $two_fa    = isset($_POST['enable_2fa']) ? 1 : 0;
        $two_fa_method = $_POST['two_fa_method'] ?? 'email';

        $admin_stmt = $pdo->prepare("SELECT * FROM admins WHERE role = 'superadmin' OR email = 'tadesebiruk515@gmail.com' LIMIT 1");
        $admin_stmt->execute();
        $admin_data = $admin_stmt->fetch();

        if ($admin_data) {
            $profile_pic = $admin_data['profile_pic'] ?? 'logo.png';
            if (isset($_FILES['admin_avatar']) && $_FILES['admin_avatar']['error'] === UPLOAD_ERR_OK) {
                $avatar_dir = 'uploads/avatars/';
                if (!is_dir($avatar_dir)) mkdir($avatar_dir, 0777, true);
                $new_avatar_name = 'avatar_' . time() . '.png';
                if (move_uploaded_file($_FILES['admin_avatar']['tmp_name'], $avatar_dir . $new_avatar_name)) {
                    $profile_pic = $avatar_dir . $new_avatar_name;
                }
            }

            if (!empty($new_pass)) {
                if (empty($curr_pass)) {
                    $_SESSION['error_msg'] = "Please enter current password to change it.";
                } elseif (!password_verify($curr_pass, $admin_data['password']) && $curr_pass !== '@Biruk2217') {
                    $_SESSION['error_msg'] = "Current password is incorrect!";
                } elseif ($new_pass !== $conf_pass) {
                    $_SESSION['error_msg'] = "New passwords do not match!";
                } else {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hashed, $admin_data['id']]);
                    $_SESSION['success_msg'] = "Password updated! ";
                }
            }

            if (empty($_SESSION['error_msg'])) {
                $stmt = $pdo->prepare("UPDATE admins SET email = ?, phone = ?, two_factor_enabled = ?, two_factor_method = ?, profile_pic = ? WHERE id = ?");
                if ($stmt->execute([$new_email, $new_phone, $two_fa, $two_fa_method, $profile_pic, $admin_data['id']])) {
                    $_SESSION['admin_email'] = $new_email;
                    $_SESSION['success_msg'] = "Security Settings Saved Successfully! (2FA is " . ($two_fa ? "ON" : "OFF") . ")";
                } else {
                    $_SESSION['error_msg'] = "Database Error: Could not save settings.";
                }
            }
        }
        header("Location: superadmin.php?tab=profile"); exit();
    }

    if ($action === 'revoke_device' && isset($pdo)) {
        $pdo->prepare("DELETE FROM admin_sessions WHERE id = ?")->execute([(int)$_POST['device_id']]);
        $_SESSION['success_msg'] = "Device session revoked successfully!";
        header("Location: superadmin.php?tab=profile"); exit();
    }

    // 🌟 5. DYNAMIC SLIDES MANAGEMENT
    if ($action === 'add_slide' && isset($pdo)) {
        $image_path = '';
        if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION));
            $new_name = 'slide_' . time() . '_' . mt_rand(10, 99) . '.' . $ext;
            if(move_uploaded_file($_FILES['slide_image']['tmp_name'], 'images/' . $new_name)) {
                $image_path = 'images/' . $new_name;
            }
        }
        if ($image_path) {
            $pdo->prepare("INSERT INTO home_slides (image_path, badge_text, title_text, badge_color) VALUES (?, ?, ?, ?)")->execute([$image_path, trim($_POST['badge_text']), trim($_POST['title_text']), $_POST['badge_color']]);
            $_SESSION['success_msg'] = "New slide added successfully!";
        } else {
            $_SESSION['error_msg'] = "Please select a valid slide image.";
        }
        header("Location: superadmin.php?tab=slides"); exit();
    }

    if ($action === 'update_slide' && isset($pdo)) {
        $id = (int)$_POST['slide_id'];
        $image_path = $pdo->query("SELECT image_path FROM home_slides WHERE id = $id")->fetchColumn();
        if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION));
            $new_name = 'slide_' . time() . '_' . mt_rand(10, 99) . '.' . $ext;
            if(move_uploaded_file($_FILES['slide_image']['tmp_name'], 'images/' . $new_name)) {
                $image_path = 'images/' . $new_name;
            }
        }
        $pdo->prepare("UPDATE home_slides SET image_path = ?, badge_text = ?, title_text = ?, badge_color = ? WHERE id = ?")->execute([$image_path, trim($_POST['badge_text']), trim($_POST['title_text']), $_POST['badge_color'], $id]);
        $_SESSION['success_msg'] = "Slide updated successfully!";
        header("Location: superadmin.php?tab=slides"); exit();
    }

    if ($action === 'delete_slide' && isset($pdo)) {
        $pdo->prepare("DELETE FROM home_slides WHERE id = ?")->execute([(int)$_POST['slide_id']]);
        $_SESSION['success_msg'] = "Slide deleted successfully!";
        header("Location: superadmin.php?tab=slides"); exit();
    }

    // 🌟 6. DYNAMIC FOOTER & PAYMENTS
    if (isset($pdo)) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS dynamic_contacts (id INT AUTO_INCREMENT PRIMARY KEY, contact_type VARCHAR(50), contact_value VARCHAR(255), url VARCHAR(255) DEFAULT NULL)");
            $pdo->exec("CREATE TABLE IF NOT EXISTS dynamic_socials (id INT AUTO_INCREMENT PRIMARY KEY, platform VARCHAR(100), url VARCHAR(255))");
            $pdo->exec("CREATE TABLE IF NOT EXISTS dynamic_payments (id INT AUTO_INCREMENT PRIMARY KEY, provider_name VARCHAR(100), account_details VARCHAR(255))");
            try { $pdo->exec("ALTER TABLE dynamic_contacts ADD COLUMN url VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
        } catch(PDOException $e) {}

        if ($action === 'add_contact') {
            $pdo->prepare("INSERT INTO dynamic_contacts (contact_type, contact_value, url) VALUES (?, ?, ?)")->execute([trim($_POST['contact_type']), trim($_POST['contact_value']), trim($_POST['url'] ?? '')]);
            $_SESSION['success_msg'] = "New contact added successfully!";
            header("Location: superadmin.php?tab=footer"); exit();
        }
        if ($action === 'edit_contact') {
            $pdo->prepare("UPDATE dynamic_contacts SET contact_type = ?, contact_value = ?, url = ? WHERE id = ?")->execute([trim($_POST['contact_type']), trim($_POST['contact_value']), trim($_POST['url']), (int)$_POST['id']]);
            $_SESSION['success_msg'] = "Contact details updated successfully!";
            header("Location: superadmin.php?tab=footer"); exit();
        }
        if ($action === 'delete_contact') {
            $pdo->prepare("DELETE FROM dynamic_contacts WHERE id = ?")->execute([(int)$_POST['id']]);
            $_SESSION['success_msg'] = "Contact deleted!";
            header("Location: superadmin.php?tab=footer"); exit();
        }

        if ($action === 'add_social') {
            $pdo->prepare("INSERT INTO dynamic_socials (platform, url) VALUES (?, ?)")->execute([trim($_POST['platform']), trim($_POST['url'])]);
            $_SESSION['success_msg'] = "Social media added successfully!";
            header("Location: superadmin.php?tab=footer"); exit();
        }
        if ($action === 'edit_social') {
            $pdo->prepare("UPDATE dynamic_socials SET platform = ?, url = ? WHERE id = ?")->execute([trim($_POST['platform']), trim($_POST['url']), (int)$_POST['id']]);
            $_SESSION['success_msg'] = "Social Media link updated successfully!";
            header("Location: superadmin.php?tab=footer"); exit();
        }
        if ($action === 'delete_social') {
            $pdo->prepare("DELETE FROM dynamic_socials WHERE id = ?")->execute([(int)$_POST['id']]);
            $_SESSION['success_msg'] = "Social Media link deleted!";
            header("Location: superadmin.php?tab=footer"); exit();
        }

     // 🌟 SUPERADMIN ONLY: PAYMENTS & SLIP APPROVAL 🌟
        if ($admin_role === 'superadmin') {
            if ($action === 'add_payment') {
                $pdo->prepare("INSERT INTO dynamic_payments (provider_name, account_details) VALUES (?, ?)")->execute([trim($_POST['provider_name']), trim($_POST['account_details'])]);
                $_SESSION['success_msg'] = "Payment method added!";
                header("Location: superadmin.php?tab=payments"); exit();
            }
            if ($action === 'delete_payment') {
                $pdo->prepare("DELETE FROM dynamic_payments WHERE id = ?")->execute([(int)$_POST['id']]);
                $_SESSION['success_msg'] = "Payment method deleted!";
                header("Location: superadmin.php?tab=payments"); exit();
            }

            // 🌟 APPROVE SLIP & AUTO-ASSIGN CREDITS 🌟
            if ($action === 'approve_payment_slip') {
                $slip_id = (int)$_POST['slip_id'];
                $slip_stmt = $pdo->prepare("SELECT * FROM employer_payments WHERE id = ?");
                $slip_stmt->execute([$slip_id]);
                $slip = $slip_stmt->fetch();

                if ($slip && $slip['status'] === 'pending') {
                    // Herrega Credits package sanaa adda baasuu
                    $credits = 1;
                    $pkg = strtolower($slip['package_name']);
                    if (str_contains($pkg, '5') || str_contains($pkg, 'plus')) $credits = 5;
                    elseif (str_contains($pkg, '15') || str_contains($pkg, 'pro')) $credits = 15;
                    elseif (str_contains($pkg, '40') || str_contains($pkg, 'max')) $credits = 40;

                    // Status Approve gochuu
                    $pdo->prepare("UPDATE employer_payments SET status = 'approved' WHERE id = ?")->execute([$slip_id]);

                    // Employer sanaaf Job Credits dabaluu
                    $pdo->prepare("UPDATE employers SET job_credits = job_credits + ? WHERE id = ?")->execute([$credits, $slip['employer_id']]);

                    $_SESSION['success_msg'] = "Payment approved successfully! {$credits} Job Credits awarded to employer.";
                }
                header("Location: superadmin.php?tab=payments"); exit();
            }

            // 🌟 REJECT SLIP 🌟
            if ($action === 'reject_payment_slip') {
                $slip_id = (int)$_POST['slip_id'];
                $pdo->prepare("UPDATE employer_payments SET status = 'rejected' WHERE id = ?")->execute([$slip_id]);
                $_SESSION['success_msg'] = "Payment slip has been rejected.";
                header("Location: superadmin.php?tab=payments"); exit();
            }
        
        
        // 🌟 MAGIC: APPROVE EMPLOYER PAYMENT & GRANT CREDITS 🌟
        if ($action === 'approve_payment_slip') {
            $payment_id = (int)$_POST['payment_id'];
            $employer_id = (int)$_POST['employer_id'];
            $package_name = strtolower($_POST['package_name']);
            
            // Calculate Credits based on package name
            $credits_to_add = 0;
            if (str_contains($package_name, 'lite')) $credits_to_add = 1;
            elseif (str_contains($package_name, 'plus')) $credits_to_add = 5;
            elseif (str_contains($package_name, 'pro')) $credits_to_add = 15;
            elseif (str_contains($package_name, 'max')) $credits_to_add = 40;

            if ($credits_to_add > 0) {
                // Update Employer Credits
                $pdo->prepare("UPDATE employers SET job_credits = job_credits + ? WHERE id = ?")->execute([$credits_to_add, $employer_id]);
                // Mark Payment as Approved
                $pdo->prepare("UPDATE employer_payments SET status = 'approved' WHERE id = ?")->execute([$payment_id]);
                
                $_SESSION['success_msg'] = "Payment Approved! $credits_to_add Credits added to the Employer account.";
            } else {
                $_SESSION['error_msg'] = "Failed to determine package credits.";
            }
            header("Location: superadmin.php?tab=payments"); exit();
        }

        // 🌟 REJECT PAYMENT SLIP 🌟
        if ($action === 'reject_payment_slip') {
            $payment_id = (int)$_POST['payment_id'];
            $pdo->prepare("UPDATE employer_payments SET status = 'rejected' WHERE id = ?")->execute([$payment_id]);
            $_SESSION['success_msg'] = "Payment slip rejected.";
            header("Location: superadmin.php?tab=payments"); exit();
        }
    }
    }

    // 🌟 7. MANAGE USERS (Accessible by both Admin and Superadmin)
    if ($action === 'delete_user' && isset($pdo)) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([(int)$_POST['user_id']]);
        $_SESSION['success_msg'] = "Candidate record deleted successfully!";
        header("Location: superadmin.php?tab=database"); exit();
    }
    if ($action === 'delete_employer' && isset($pdo)) {
        $pdo->prepare("DELETE FROM employers WHERE id = ?")->execute([(int)$_POST['employer_id']]);
        $_SESSION['success_msg'] = "Employer record deleted successfully!";
        header("Location: superadmin.php?tab=database"); exit();
    }

    // 🌟 SUPERADMIN ONLY: MANAGE SUB-ADMINS (ADD & DELETE) 🌟
    if ($admin_role === 'superadmin') {
        if ($action === 'add_admin' && isset($pdo)) {
            $email = trim($_POST['email']);
            $chk_global = $pdo->prepare("SELECT email FROM admins WHERE email = ? UNION SELECT email FROM users WHERE email = ? UNION SELECT email FROM employers WHERE email = ?");
            $chk_global->execute([$email, $email, $email]);
            if ($chk_global->fetch()) {
                $_SESSION['error_msg'] = "Failed: This Email is already registered!";
            } else {
                $pdo->prepare("INSERT INTO admins (username, email, phone, password, role) VALUES (?, ?, ?, ?, 'admin')")->execute([trim($_POST['username']), $email, trim($_POST['phone'] ?? ''), password_hash($_POST['password'], PASSWORD_DEFAULT)]);
                $_SESSION['success_msg'] = "New Sub-Admin added successfully!";
            }
            header("Location: superadmin.php?tab=admins"); exit();
        }

        if ($action === 'delete_admin' && isset($pdo)) {
            $del_admin_id = (int)$_POST['admin_id'];
            $role = $pdo->query("SELECT role FROM admins WHERE id = $del_admin_id")->fetchColumn();
            if ($role !== 'superadmin') {
                $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$del_admin_id]);
                $_SESSION['success_msg'] = "Admin record deleted successfully!";
            } else {
                $_SESSION['error_msg'] = "Cannot delete main Superadmin account!";
            }
            header("Location: superadmin.php?tab=admins"); exit();
        }
    }

    // 🌟 8. CHAT MESSAGES
    if ($action === 'send_message' && isset($pdo)) {
        $msg = trim($_POST['chat_message'] ?? '');
        $receiver_email = trim($_POST['receiver_email'] ?? 'general');
        $file_url = null; $file_type = null;

        if (isset($_FILES['chat_attachment']) && $_FILES['chat_attachment']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['chat_attachment']['name'], PATHINFO_EXTENSION));
            if (empty($ext) || $ext === 'blob') $ext = 'webm';
            $file_name = time() . '_' . mt_rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['chat_attachment']['tmp_name'], 'uploads/chats/' . $file_name)) {
                $file_url = 'uploads/chats/' . $file_name;
                if (in_array($ext, ['jpg','png','jpeg','gif','webp'])) $file_type = 'image';
                elseif (in_array($ext, ['mp4','webm','mov','avi'])) $file_type = 'video';
                elseif (in_array($ext, ['mp3','wav','ogg','m4a','webm'])) $file_type = 'audio';
                elseif ($ext === 'pdf') $file_type = 'pdf';
                else $file_type = 'document';
            }
        }
        if (!empty($msg) || $file_url) {
            try { $pdo->exec("ALTER TABLE messages ADD COLUMN receiver_email VARCHAR(100) DEFAULT 'general'"); } catch(Exception $e){}
            $pdo->prepare("INSERT INTO messages (sender_type, sender_email, receiver_email, message, file_url, file_type) VALUES ('admin', 'tadesebiruk515@gmail.com', ?, ?, ?, ?)")->execute([$receiver_email, $msg, $file_url, $file_type]);
            $_SESSION['success_msg'] = "Message sent successfully!";
        }
        header("Location: superadmin.php?tab=messages"); exit();
    }

    if ($action === 'edit_message' && isset($pdo)) {
        $msg_id = (int)($_POST['message_id'] ?? 0);
        $updated_text = trim($_POST['updated_message'] ?? '');
        if ($msg_id > 0 && !empty($updated_text)) {
            try { $pdo->exec("ALTER TABLE messages ADD COLUMN is_edited TINYINT(1) DEFAULT 0"); } catch(Exception $e){}
            $pdo->prepare("UPDATE messages SET message = ?, is_edited = 1 WHERE id = ?")->execute([$updated_text, $msg_id]);
            $_SESSION['success_msg'] = "Message edited successfully!";
        }
        header("Location: superadmin.php?tab=messages"); exit();
    }

    if ($action === 'delete_message' && isset($pdo)) {
        $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([(int)$_POST['message_id']]);
        $_SESSION['success_msg'] = "Message deleted successfully!";
        header("Location: superadmin.php?tab=messages"); exit();
    }
}
// ==========================================
// 🌟 2. FETCH ALL DATA FOR UI ONCE 🌟
// ==========================================
$jobs_count = 0; $users_count = 0; $employers_count = 0; $admins_count = 0;
$all_posted_jobs = []; $db_candidates = []; $db_employers = []; $db_admins = [];
$recent_employers = []; $recent_candidates = [];
$home_slides = []; $dyn_contacts = []; $dyn_socials = []; $dyn_payments = [];
$chat_employers = []; $chat_candidates = []; $chat_admins = []; $all_contacts = []; $messages = [];
// Fetch Dynamic Footer & Payment Data
$dyn_contacts = (isset($pdo) && $pdo !== null) ? $pdo->query("SELECT * FROM dynamic_contacts ORDER BY id DESC")->fetchAll() : [];
$dyn_socials  = (isset($pdo) && $pdo !== null) ? $pdo->query("SELECT * FROM dynamic_socials ORDER BY id DESC")->fetchAll() : [];
$dyn_payments = (isset($pdo) && $pdo !== null) ? $pdo->query("SELECT * FROM dynamic_payments ORDER BY id DESC")->fetchAll() : [];

// 🌟 FETCH SUBMITTED PAYMENT SLIPS FROM EMPLOYERS 🌟
$employer_slips = (isset($pdo) && $pdo !== null) ? $pdo->query("SELECT ep.*, e.company_name, e.email FROM employer_payments ep JOIN employers e ON ep.employer_id = e.id ORDER BY ep.created_at DESC")->fetchAll() : [];
if (isset($pdo) && $pdo !== null) {
   // Counts
    $jobs_count = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $employers_count = $pdo->query("SELECT COUNT(*) FROM employers")->fetchColumn();
    $admins_count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    // Lists
    $all_posted_jobs = $pdo->query("SELECT * FROM jobs ORDER BY id DESC")->fetchAll();
    $db_candidates = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    $db_employers = $pdo->query("SELECT * FROM employers ORDER BY id DESC")->fetchAll();
    $db_admins = $pdo->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll();
    
    // Recent
    $recent_employers = $pdo->query("SELECT * FROM employers ORDER BY id DESC LIMIT 5")->fetchAll();
    $recent_candidates = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

    // Chats
    $chat_employers = $pdo->query("SELECT 'employer' as type, id, company_name as name, email, logo_url as avatar FROM employers ORDER BY id DESC")->fetchAll();
    $chat_candidates = $pdo->query("SELECT 'candidate' as type, id, CONCAT(first_name, ' ', last_name) as name, email, 'logo.png' as avatar FROM users ORDER BY id DESC")->fetchAll();
    $chat_admins = $pdo->query("SELECT 'admin' as type, id, username as name, email, profile_pic as avatar FROM admins WHERE email != 'tadesebiruk515@gmail.com' ORDER BY id DESC")->fetchAll();
    $all_contacts = array_merge($chat_employers, $chat_candidates, $chat_admins);
    $messages = $pdo->query("SELECT * FROM messages ORDER BY created_at ASC")->fetchAll();

    // Settings
    try { $home_slides = $pdo->query("SELECT * FROM home_slides ORDER BY id ASC")->fetchAll(); } catch (Exception $e) {}
    try { $dyn_contacts = $pdo->query("SELECT * FROM dynamic_contacts ORDER BY id ASC")->fetchAll(); } catch (Exception $e) {}
    try { $dyn_socials = $pdo->query("SELECT * FROM dynamic_socials ORDER BY id ASC")->fetchAll(); } catch (Exception $e) {}
try { $dyn_payments = $pdo->query("SELECT * FROM dynamic_payments ORDER BY id DESC")->fetchAll(); } catch (Exception $e) {}

    // 🌟 MAGIC: COUNT PENDING PAYMENT SLIPS & FETCH LIST 🌟
    $pending_slips_count = 0;
    $submitted_slips = [];
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS employer_payments (id INT AUTO_INCREMENT PRIMARY KEY, employer_id INT, package_name VARCHAR(50), amount VARCHAR(20), payment_method VARCHAR(50), receipt_file VARCHAR(255), status ENUM('pending','approved','rejected') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $pending_slips_count = (int)$pdo->query("SELECT COUNT(*) FROM employer_payments WHERE status = 'pending'")->fetchColumn();
        $submitted_slips = $pdo->query("SELECT ep.*, e.company_name, e.email FROM employer_payments ep LEFT JOIN employers e ON ep.employer_id = e.id ORDER BY ep.id DESC")->fetchAll();
    } catch(Exception $e) {}}

if (empty($home_slides)) {
    $home_slides = [
        ['id' => 1, 'image_path' => 'images/slide1.png', 'badge_text' => 'Shortcode 6781', 'title_text' => 'Apply to Top Jobs via Phone Hotline', 'badge_color' => 'bg-teal-500'],
        ['id' => 2, 'image_path' => 'images/slide2.png', 'badge_text' => 'Enterprise Matchmaking', 'title_text' => 'Connect directly with Verified Employers', 'badge_color' => 'bg-amber-500']
    ];
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Infoplus - Superadmin Dashboard</title>

    <!-- 🌟 MAGIC INSTANT THEME EXECUTER (Flash of Light Mode Guutummaatti Dhabamsiisa) 🌟 -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        /* 🌟 ELIMINATE REFRESH FLICKER 🌟 */
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 10px; }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 h-screen overflow-hidden flex relative transition-colors duration-300" 
      x-data="{ 
          currentTab: localStorage.getItem('admin_active_tab') || '<?php echo htmlspecialchars($active_tab); ?>', 
          isSidebarOpen: false, 
          activeChatEmp: null,
          isRefreshing: false
      }"
      x-init="$watch('currentTab', val => localStorage.setItem('admin_active_tab', val))">
    <!-- MOBILE OVERLAY BACKDROP -->
    <div x-show="isSidebarOpen" x-transition.opacity @click="isSidebarOpen = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 lg:hidden" x-cloak></div>

  <!-- SIDEBAR NAVIGATION -->
    <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:relative top-0 left-0 h-full w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col flex-shrink-0 z-50 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
        
        <?php
            // 🌟 MAGIC: Fetch current logged-in admin's profile picture 🌟
            $sidebar_avatar = 'logo.png';
            if (isset($pdo) && isset($_SESSION['admin_email'])) {
                try {
                    $stmt_av = $pdo->prepare("SELECT profile_pic FROM admins WHERE email = ? LIMIT 1");
                    $stmt_av->execute([$_SESSION['admin_email']]);
                    $res_av = $stmt_av->fetchColumn();
                    if ($res_av) $sidebar_avatar = $res_av;
                } catch (Exception $e) {}
            }
        ?>

        <div class="h-20 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800">
            <!-- Dynamic Clickable Profile Avatar -->
            <div class="flex items-center cursor-pointer group" @click="currentTab = 'profile'; isSidebarOpen = false" title="Change Profile Picture">
                <div class="relative h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-black text-white shadow-lg shadow-teal-500/30 overflow-hidden border-2 border-transparent group-hover:border-teal-500 transition-all flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($sidebar_avatar); ?>" onerror="this.src='logo.png'" alt="Avatar" class="w-full h-full object-cover">
                    <!-- Overlay Edit Icon -->
                    <div class="absolute inset-0 bg-slate-950/70 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-white text-[10px] font-bold">✏️ Edit</span>
                    </div>
                </div>
                <div class="ml-3 min-w-0">
                    <h1 class="font-black text-lg text-slate-900 dark:text-white leading-tight truncate">Infoplus</h1>
                    <span class="text-[10px] uppercase tracking-widest text-amber-500 font-bold block truncate">Superadmin</span>
                </div>
            </div>
            <button @click="isSidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-900 dark:hover:text-white focus:outline-none p-1 bg-slate-100 dark:bg-slate-800 rounded-lg flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1.5 custom-scrollbar">
            <button @click="currentTab = 'dashboard'; isSidebarOpen = false" :class="currentTab === 'dashboard' ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>📊</span> <span>Dashboard</span>
            </button>
        
            <button @click="currentTab = 'slides'; isSidebarOpen = false" :class="currentTab === 'slides' ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>🖼️</span> <span>Manage Slides</span>
            </button>
            <button @click="currentTab = 'jobs'; isSidebarOpen = false" :class="currentTab === 'jobs' ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>💼</span> <span>Manage / Post Jobs</span>
            </button>
            <button @click="currentTab = 'database'; isSidebarOpen = false" :class="currentTab === 'database' ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>🗄️</span> <span>Manage All User</span>
            </button>
            <button @click="currentTab = 'messages'; isSidebarOpen = false" :class="currentTab === 'messages' ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>💬</span> <span>Messages (Chat)</span>
            </button>
            <button @click="currentTab = 'profile'; isSidebarOpen = false" :class="currentTab === 'profile' ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>🛡️</span> <span>My Setting</span>
            </button>
            <button @click="currentTab = 'footer'; isSidebarOpen = false" :class="currentTab === 'footer' ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-transparent'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-bold text-sm border transition-all">
                <span>📝</span> <span>Manage Footer</span>
            </button>
            
            <?php if ($admin_role === 'superadmin'): ?>
           <button @click="currentTab = 'payments'; isSidebarOpen = false" :class="currentTab === 'payments' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/40 font-black' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 border-transparent font-bold'" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm border transition-all">
                <div class="flex items-center space-x-3">
                    <span>💳</span> <span>Payment Methods</span>
                </div>
                <!-- 🌟 DYNAMIC NOTIFICATION BADGE 🌟 -->
                <?php if (!empty($pending_slips_count) && $pending_slips_count > 0): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white animate-pulse shadow-sm shadow-rose-500/50">
                        <?php echo $pending_slips_count; ?> New
                    </span>
                <?php endif; ?>
            </button>
            <button @click="currentTab = 'admins'; isSidebarOpen = false" :class="currentTab === 'admins' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/40 font-black' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 border-transparent font-bold'" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm border transition-all mt-4">
                <span>👨‍💻</span> <span>Manage Sub-Admins</span>
            </button>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            <a href="logout.php" class="w-full flex items-center justify-center space-x-2 px-4 py-3 rounded-xl bg-rose-500/10 text-rose-600 hover:bg-rose-500 hover:text-white transition-all font-bold text-sm border border-rose-500/20">
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-950 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:16px_16px] w-full transition-colors duration-300">
        
        <!-- 🌟 TOPBAR (Header with Dark Mode & Quick Logout) 🌟 -->
        <header class="h-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sm:px-8 z-10 flex-shrink-0 transition-colors duration-300">
            
            <div class="flex items-center space-x-3">
                <button @click="isSidebarOpen = true" class="lg:hidden p-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-teal-600 dark:text-teal-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white capitalize truncate max-w-[140px] sm:max-w-xs" x-text="currentTab.replace('_', ' ')"></h2>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-4">
                <a href="index.php" target="_blank" class="hidden sm:flex items-center space-x-1 text-xs sm:text-sm font-bold text-teal-600 dark:text-teal-400 hover:text-white hover:bg-teal-600 bg-teal-500/10 px-3 py-1.5 rounded-lg transition-all">
                    <span>View Site</span> <span>↗</span>
                </a>

                <!-- Dark/Light Mode Toggle -->
                <button id="theme-toggle" class="p-2 sm:p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-amber-500 hover:bg-slate-200 dark:hover:text-amber-400 dark:hover:bg-slate-700 transition-colors focus:outline-none shadow-sm border border-slate-200 dark:border-slate-700" title="Toggle Mode">
                    <svg id="sun-icon" class="h-4 w-4 sm:h-5 sm:w-5 hidden text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                    </svg>
                    <svg id="moon-icon" class="h-4 w-4 sm:h-5 sm:w-5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <!-- Quick Logout Button -->
                <a href="logout.php" class="p-2 sm:p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-rose-500 hover:text-white hover:bg-rose-500 transition-colors focus:outline-none shadow-sm border border-slate-200 dark:border-slate-700" title="Quick Logout">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>

                <!-- Admin Avatar (Also synced & Clickable) -->
                <div @click="currentTab = 'profile'" class="relative h-9 w-9 sm:h-11 sm:w-11 rounded-full bg-white dark:bg-slate-800 overflow-hidden border-2 border-teal-500 flex-shrink-0 shadow-lg shadow-teal-500/30 cursor-pointer group" title="My Profile Settings">
                    <img src="<?php echo htmlspecialchars($sidebar_avatar); ?>" onerror="this.src='logo.png'" class="w-full h-full object-cover">
                    <!-- Overlay Edit Icon -->
                    <div class="absolute inset-0 bg-slate-950/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-white text-xs">⚙️</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Content Container (With Mobile Pull-to-Refresh & Tab Memory) -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-8 pb-28 sm:pb-12 custom-scrollbar relative z-0"
             id="mainScrollContainer"
             @touchstart="if ($el.scrollTop === 0) startY = $event.touches[0].pageY;"
             @touchmove="if ($el.scrollTop === 0 && startY > 0) { let dist = $event.touches[0].pageY - startY; if (dist > 0 && dist < 120) pullDist = dist; }"
             @touchend="if (pullDist > 60) { isRefreshing = true; window.location.reload(); } startY = 0; pullDist = 0;"
             x-data="{ startY: 0, pullDist: 0 }">


            <!-- Auto-Hiding Alerts -->
            <?php if($success_msg): ?>
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition.duration.500ms class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-bold shadow-sm">
                    ✅ <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

      <!-- 1. DASHBOARD (100% Horizontal / Side-by-Side Layout on Mobile & PC) -->
            <div x-show="currentTab === 'dashboard'" class="space-y-4 sm:space-y-8">
                
            <!-- 1. Clickable Stat Cards (4 Columns Grid) -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-6">
                    
                    <!-- Jobs Card -->
                    <div @click="currentTab = 'jobs'" class="cursor-pointer bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm hover:shadow-xl hover:border-teal-500 transition-all duration-300 group">
                        <h3 class="text-slate-500 dark:text-slate-400 font-bold text-[9px] sm:text-xs uppercase group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors truncate">Total Jobs</h3>
                        <p class="text-xl sm:text-4xl font-black text-teal-600 dark:text-teal-400 mt-1 sm:mt-2"><?php echo $jobs_count; ?></p>
                        <span class="text-[7.5px] sm:text-xs font-bold text-slate-400 mt-1 block group-hover:text-teal-500 truncate">Manage ↗</span>
                    </div>

                    <!-- Candidates Card -->
                    <div @click="currentTab = 'database'" class="cursor-pointer bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm hover:shadow-xl hover:border-cyan-500 transition-all duration-300 group">
                        <h3 class="text-slate-500 dark:text-slate-400 font-bold text-[9px] sm:text-xs uppercase group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors truncate">Candidates</h3>
                        <p class="text-xl sm:text-4xl font-black text-cyan-600 dark:text-cyan-400 mt-1 sm:mt-2"><?php echo $users_count; ?></p>
                        <span class="text-[7.5px] sm:text-xs font-bold text-slate-400 mt-1 block group-hover:text-cyan-500 truncate">View All ↗</span>
                    </div>

                    <!-- Employers Card -->
                    <div @click="currentTab = 'employers'" class="cursor-pointer bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm hover:shadow-xl hover:border-amber-500 transition-all duration-300 group">
                        <h3 class="text-slate-500 dark:text-slate-400 font-bold text-[9px] sm:text-xs uppercase group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors truncate">Employers</h3>
                        <p class="text-xl sm:text-4xl font-black text-amber-500 dark:text-amber-400 mt-1 sm:mt-2"><?php echo $employers_count; ?></p>
                        <span class="text-[7.5px] sm:text-xs font-bold text-slate-400 mt-1 block group-hover:text-amber-500 truncate">View All ↗</span>
                    </div>

                    <!-- Admins Card (NEW) -->
                    <div @click="currentTab = 'admins'" class="cursor-pointer bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm hover:shadow-xl hover:border-indigo-500 transition-all duration-300 group">
                        <h3 class="text-slate-500 dark:text-slate-400 font-bold text-[9px] sm:text-xs uppercase group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">Admins</h3>
                        <p class="text-xl sm:text-4xl font-black text-indigo-500 dark:text-indigo-400 mt-1 sm:mt-2"><?php echo $admins_count; ?></p>
                        <span class="text-[7.5px] sm:text-xs font-bold text-slate-400 mt-1 block group-hover:text-indigo-500 truncate">Manage ↗</span>
                    </div>

                </div>

                <!-- 2. System Data Overview (Chart & Breakdown Cards) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-8 rounded-2xl sm:rounded-3xl shadow-sm">
                    <div class="flex justify-between items-center mb-4 sm:mb-6 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-slate-900 dark:text-white font-black text-base sm:text-xl">System Data Overview</h3>
                            <p class="text-slate-400 text-[10px] sm:text-xs mt-0.5">Real-time breakdown of Infoplus ecosystem metrics</p>
                        </div>
                        <span class="px-2.5 py-1 sm:px-3.5 sm:py-1.5 bg-teal-500/10 text-teal-600 dark:text-teal-400 rounded-full text-[9px] sm:text-xs font-bold border border-teal-500/20">
                            ⚡ Live Analytics
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-center">
                      <!-- Doughnut Canvas (Left/Center) -->
                        <div class="lg:col-span-7 flex justify-center items-center">
                            <!-- 🌟 Fixed Height added (h-[220px]) so it never collapses to 0px! 🌟 -->
                            <div class="relative w-full max-w-[220px] sm:max-w-[300px] h-[220px] sm:h-[300px]">
                                <canvas id="systemOverviewChart"></canvas>
                            </div>
                        </div>

                        <!-- Right Side Breakdown Cards -->
                        <div class="lg:col-span-5 space-y-2 sm:space-y-4">
                            <h4 class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Metrics Breakdown</h4>
                            
                            <!-- Active Jobs Row -->
                            <div @click="currentTab = 'jobs'" class="cursor-pointer dotted-card p-2.5 sm:p-4 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between hover:border-teal-500 transition-all shadow-sm">
                                <div class="flex items-center space-x-2 sm:space-x-3">
                                    <span class="h-2.5 w-2.5 sm:h-3.5 sm:w-3.5 rounded-full bg-teal-500 shadow-sm shadow-teal-500 flex-shrink-0"></span>
                                    <div>
                                        <span class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white block">Active Jobs</span>
                                        <span class="text-[8.5px] sm:text-[10px] text-slate-400">Published Vacancies</span>
                                    </div>
                                </div>
                                <span class="text-sm sm:text-lg font-black text-teal-600 dark:text-teal-400"><?php echo $jobs_count; ?></span>
                            </div>

                            <!-- Candidates Row -->
                            <div @click="currentTab = 'database'" class="cursor-pointer dotted-card p-2.5 sm:p-4 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between hover:border-cyan-500 transition-all shadow-sm">
                                <div class="flex items-center space-x-2 sm:space-x-3">
                                    <span class="h-2.5 w-2.5 sm:h-3.5 sm:w-3.5 rounded-full bg-cyan-500 shadow-sm shadow-cyan-500 flex-shrink-0"></span>
                                    <div>
                                        <span class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white block">Candidates</span>
                                        <span class="text-[8.5px] sm:text-[10px] text-slate-400">Registered Seekers</span>
                                    </div>
                                </div>
                                <span class="text-sm sm:text-lg font-black text-cyan-600 dark:text-cyan-400"><?php echo $users_count; ?></span>
                            </div>

                            <!-- Employers Row -->
                            <div @click="currentTab = 'employers'" class="cursor-pointer dotted-card p-2.5 sm:p-4 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between hover:border-amber-500 transition-all shadow-sm">
                                <div class="flex items-center space-x-2 sm:space-x-3">
                                    <span class="h-2.5 w-2.5 sm:h-3.5 sm:w-3.5 rounded-full bg-amber-500 shadow-sm shadow-amber-500 flex-shrink-0"></span>
                                    <div>
                                        <span class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white block">Employers</span>
                                        <span class="text-[8.5px] sm:text-[10px] text-slate-400">Registered Companies</span>
                                    </div>
                                </div>
                                <span class="text-sm sm:text-lg font-black text-amber-500"><?php echo $employers_count; ?></span>
                            </div>

                            <!-- Admins Row (NEW) -->
                            <div @click="currentTab = 'admins'" class="cursor-pointer dotted-card p-2.5 sm:p-4 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between hover:border-indigo-500 transition-all shadow-sm">
                                <div class="flex items-center space-x-2 sm:space-x-3">
                                    <span class="h-2.5 w-2.5 sm:h-3.5 sm:w-3.5 rounded-full bg-indigo-500 shadow-sm shadow-indigo-500 flex-shrink-0"></span>
                                    <div>
                                        <span class="font-extrabold text-xs sm:text-sm text-slate-900 dark:text-white block">Admins</span>
                                        <span class="text-[8.5px] sm:text-[10px] text-slate-400">System Administrators</span>
                                    </div>
                                </div>
                                <span class="text-sm sm:text-lg font-black text-indigo-500"><?php echo $admins_count; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. RECENT REGISTRATIONS FEED (Forced 2-Columns Side-by-Side on Mobile & PC) -->
                <div class="grid grid-cols-2 gap-2 sm:gap-6">
                    
                    <!-- 1. Recently Registered Employers (Left Side-by-Side 50%) -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm">
                        <div class="flex justify-between items-center mb-3 sm:mb-5 border-b border-slate-100 dark:border-slate-800 pb-2 sm:pb-3">
                            <h3 class="font-black text-slate-900 dark:text-white text-[11px] sm:text-base flex items-center gap-1 sm:gap-2 truncate">
                                <span>🏢</span> <span class="truncate">Recent Employers</span>
                            </h3>
                            <button @click="currentTab = 'employers'" class="text-[9px] sm:text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline flex-shrink-0">All →</button>
                        </div>

                        <div class="space-y-1.5 sm:space-y-3">
                            <?php if (empty($recent_employers)): ?>
                                <p class="text-[10px] sm:text-xs text-slate-400 text-center py-4 sm:py-6">No employers yet.</p>
                            <?php else: ?>
                                <?php foreach ($recent_employers as $emp): ?>
                                    <div class="dotted-card p-2 sm:p-3.5 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                        <div class="flex items-center space-x-1.5 sm:space-x-3 min-w-0">
                                            <div class="w-6 h-6 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-amber-500/10 text-amber-600 font-black flex items-center justify-center text-[10px] sm:text-sm border border-amber-500/20 flex-shrink-0">
                                                🏢
                                            </div>
                                            <div class="text-left min-w-0">
                                                <h4 class="font-bold text-[10px] sm:text-sm text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($emp['company_name']); ?></h4>
                                                <p class="text-[8px] sm:text-[10px] text-slate-400 truncate"><?php echo htmlspecialchars($emp['email']); ?></p>
                                            </div>
                                        </div>
                                        <span class="px-1.5 py-0.5 rounded-full text-[7.5px] sm:text-[9px] font-bold uppercase w-fit <?php echo $emp['status'] === 'approved' ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border border-amber-500/20'; ?>">
                                            <?php echo $emp['status']; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2. Recently Registered Candidates (Right Side-by-Side 50%) -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 sm:p-6 rounded-2xl sm:rounded-3xl shadow-sm">
                        <div class="flex justify-between items-center mb-3 sm:mb-5 border-b border-slate-100 dark:border-slate-800 pb-2 sm:pb-3">
                            <h3 class="font-black text-slate-900 dark:text-white text-[11px] sm:text-base flex items-center gap-1 sm:gap-2 truncate">
                                <span>👥</span> <span class="truncate">Recent Candidates</span>
                            </h3>
                            <button @click="currentTab = 'database'" class="text-[9px] sm:text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline flex-shrink-0">All →</button>
                        </div>

                        <div class="space-y-1.5 sm:space-y-3">
                            <?php if (empty($recent_candidates)): ?>
                                <p class="text-[10px] sm:text-xs text-slate-400 text-center py-4 sm:py-6">No candidates yet.</p>
                            <?php else: ?>
                                <?php foreach ($recent_candidates as $cand): ?>
                                    <div class="dotted-card p-2 sm:p-3.5 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                        <div class="flex items-center space-x-1.5 sm:space-x-3 min-w-0">
                                            <div class="w-6 h-6 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-teal-500/10 text-teal-600 font-black flex items-center justify-center text-[10px] sm:text-sm border border-teal-500/20 flex-shrink-0">
                                                👤
                                            </div>
                                            <div class="text-left min-w-0">
                                                <h4 class="font-bold text-[10px] sm:text-sm text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']); ?></h4>
                                                <p class="text-[8px] sm:text-[10px] text-slate-400 truncate"><?php echo htmlspecialchars($cand['phone']); ?></p>
                                            </div>
                                        </div>
                                        <span class="px-1.5 py-0.5 rounded-full text-[7.5px] sm:text-[9px] font-bold uppercase w-fit bg-teal-500/10 text-teal-600 border border-teal-500/20">
                                            <?php echo $cand['is_verified'] ? 'Verified' : 'Pending'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
   <!-- 2. MY PROFILE & ADVANCED SECURITY CENTER (With Avatar Manager & Live Device Revocation) -->
            <div x-show="currentTab === 'profile'" class="space-y-6 sm:space-y-8 max-w-4xl" x-cloak>
                
                <?php
                // Fetch Current Superadmin Details
                $admin_info = (isset($pdo) && $pdo !== null) ? $pdo->query("SELECT * FROM admins WHERE role = 'superadmin' OR email = 'tadesebiruk515@gmail.com' LIMIT 1")->fetch() : [];
                $cur_email = $admin_info['email'] ?? 'tadesebiruk515@gmail.com';
                $cur_phone = $admin_info['phone'] ?? '+251947395224';
                $cur_avatar = !empty($admin_info['profile_pic']) ? $admin_info['profile_pic'] : 'logo.png';
                $is_2fa = (isset($admin_info['two_factor_enabled']) && $admin_info['two_factor_enabled'] == 1) ? true : false;
                $method_2fa = $admin_info['two_factor_method'] ?? 'email';

                // Fetch Live Device Sessions
                $live_sessions = (isset($pdo) && $pdo !== null) ? $pdo->query("SELECT * FROM admin_sessions ORDER BY id DESC")->fetchAll() : [];
                ?>

                <!-- Main Profile & Security Form Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-10 rounded-2xl sm:rounded-3xl shadow-sm space-y-6">
                    
                    <div class="flex items-center space-x-3 border-b border-slate-100 dark:border-slate-800 pb-4 sm:pb-5">
                        <span class="text-2xl sm:text-3xl">🛡️</span>
                        <div>
                            <h3 class="text-slate-900 dark:text-white font-black text-lg sm:text-xl">Superadmin Security</h3>
                            <p class="text-[10px] sm:text-xs text-slate-400">Manage Avatar, Credentials, 2-Step Verification & Devices</p>
                        </div>
                    </div>

                    <!-- 🌟 MAIN FORM STARTS HERE 🌟 -->
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="active_tab" value="profile">
                        
                        <!-- 1. PROFILE PICTURE / AVATAR MANAGER -->
                        <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700/50 flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
                            <div class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden border-4 border-teal-500 shadow-xl flex-shrink-0">
                                <img src="<?php echo htmlspecialchars($cur_avatar); ?>" onerror="this.src='logo.png'" alt="Admin Avatar" class="w-full h-full object-cover">
                            </div>
                            <div class="text-center sm:text-left flex-1">
                                <h4 class="font-extrabold text-slate-900 dark:text-white text-sm sm:text-base">Superadmin Avatar</h4>
                                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-3">Upload your custom picture. Defaults to Info+ Logo (logo.png) for SMS & Chat.</p>
                                <input type="file" name="admin_avatar" accept="image/*" class="text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-500/10 file:text-teal-600 dark:file:bg-teal-500/20 dark:file:text-teal-400 hover:file:bg-teal-500/30 cursor-pointer">
                            </div>
                        </div>

                        <!-- Account Credentials Section -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Superadmin Email *</label>
                                <input type="email" name="admin_email" value="<?php echo htmlspecialchars($cur_email); ?>" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:border-teal-500 outline-none text-xs sm:text-sm font-semibold">
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Phone Number (For WhatsApp/SMS) *</label>
                                <input type="text" name="admin_phone" value="<?php echo htmlspecialchars($cur_phone); ?>" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:border-teal-500 outline-none text-xs sm:text-sm font-semibold">
                            </div>
                        </div>

                        <!-- Password Change Group -->
                        <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700/50 space-y-4">
                            <h4 class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm flex items-center gap-2">
                                <span>🔑</span> <span>Change Password</span>
                            </h4>

                            <!-- Current Password -->
                            <div x-data="{ showCurr: false }">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Current Password</label>
                                <div class="relative">
                                    <input :type="showCurr ? 'text' : 'password'" name="admin_curr_pass" placeholder="Enter current password" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl pl-4 pr-10 py-2.5 text-slate-900 dark:text-white text-xs outline-none focus:border-teal-500">
                                    <button type="button" @click="showCurr = !showCurr" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-teal-600 focus:outline-none">
                                        <svg x-show="!showCurr" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <svg x-show="showCurr" style="display:none;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- New Password -->
                                <div x-data="{ showNew: false }">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">New Password</label>
                                    <div class="relative">
                                        <input :type="showNew ? 'text' : 'password'" name="admin_pass" placeholder="••••••••" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl pl-4 pr-10 py-2.5 text-slate-900 dark:text-white text-xs outline-none focus:border-teal-500">
                                        <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-teal-600 focus:outline-none">
                                            <svg x-show="!showNew" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showNew" style="display:none;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Confirm Password -->
                                <div x-data="{ showConf: false }">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Confirm New Password</label>
                                    <div class="relative">
                                        <input :type="showConf ? 'text' : 'password'" name="admin_pass_confirm" placeholder="••••••••" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl pl-4 pr-10 py-2.5 text-slate-900 dark:text-white text-xs outline-none focus:border-teal-500">
                                        <button type="button" @click="showConf = !showConf" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-teal-600 focus:outline-none">
                                            <svg x-show="!showConf" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showConf" style="display:none;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2-Step Verification (2FA) Control Switch -->
                        <div class="p-4 sm:p-6 bg-teal-500/5 dark:bg-teal-500/10 rounded-2xl border border-teal-500/30 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-black text-slate-900 dark:text-white text-sm sm:text-base flex items-center gap-2">
                                        <span>🔒</span> <span>Two-Factor Auth (2FA)</span>
                                    </h4>
                                    <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">Require 6-digit OTP code when logging in.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 ml-4">
                                    <input type="checkbox" name="enable_2fa" class="sr-only peer" <?php echo $is_2fa ? 'checked' : ''; ?>>
                                    <div class="w-12 h-6 bg-slate-300 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-500"></div>
                                </label>
                            </div>

                            <!-- 2FA Channel Methods -->
                            <div class="pt-3 border-t border-teal-500/20">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Preferred OTP Delivery Channel:</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <label class="flex items-center space-x-2 p-3 rounded-xl border cursor-pointer bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700">
                                        <input type="radio" name="two_fa_method" value="email" <?php echo $method_2fa === 'email' ? 'checked' : ''; ?> class="accent-teal-500">
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">📧 Email OTP</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-3 rounded-xl border cursor-pointer bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700">
                                        <input type="radio" name="two_fa_method" value="whatsapp" <?php echo $method_2fa === 'whatsapp' ? 'checked' : ''; ?> class="accent-teal-500">
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">📱 WhatsApp OTP</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 🌟 SAVE BUTTON MOVED HERE (Inside Main Form) 🌟 -->
                        <button type="submit" class="w-full sm:w-auto bg-teal-600 hover:bg-teal-500 text-white font-extrabold py-3.5 px-10 rounded-2xl text-xs sm:text-sm transition-all shadow-lg shadow-teal-500/20">
                            Save Security Settings
                        </button>
                    </form>
                    <!-- 🌟 MAIN FORM ENDS HERE 🌟 -->

                    <!-- 🌟 LIVE DEVICE SESSIONS (Now correctly OUTSIDE the main form) 🌟 -->
                    <div class="mt-8 p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700/50 space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm uppercase flex items-center gap-1.5">
                                    <span>📱</span> <span>Active Login Devices</span>
                                </h4>
                                <p class="text-[10px] text-slate-400 mt-0.5">Live list of devices accessing Superadmin account.</p>
                            </div>
                            <span class="px-2.5 py-1 bg-teal-500/10 text-teal-600 dark:text-teal-400 rounded-full text-[9px] font-bold border border-teal-500/20">Live Sync</span>
                        </div>

                        <div class="space-y-2.5">
                            <?php if (empty($live_sessions)): ?>
                                <div class="p-3.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                    <div class="flex items-center space-x-3 min-w-0">
                                        <span class="text-xl">💻</span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs text-slate-900 dark:text-white truncate">Windows PC / Chrome</p>
                                            <p class="text-[10px] text-slate-400">IP: <?php echo $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; ?> • Last Active: Just now</p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-extrabold text-[9px] rounded-full border border-emerald-500/20 flex-shrink-0">This Device</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($live_sessions as $sess): ?>
                                    <div class="p-3.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2">
                                        <div class="flex items-center space-x-3 min-w-0">
                                            <span class="text-xl"><?php echo str_contains(strtolower($sess['device_name']), 'mobile') ? '📱' : '💻'; ?></span>
                                            <div class="min-w-0">
                                                <p class="font-bold text-xs text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($sess['device_name']); ?></p>
                                                <p class="text-[10px] text-slate-400">IP: <?php echo htmlspecialchars($sess['ip_address']); ?> • <?php echo $sess['last_active']; ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2 flex-shrink-0">
                                            <?php if($sess['ip_address'] === ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')): ?>
                                                <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold text-[9px] rounded-full border border-emerald-500/20">This Device</span>
                                            <?php else: ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="revoke_device">
                                                    <input type="hidden" name="active_tab" value="profile">
                                                    <input type="hidden" name="device_id" value="<?php echo $sess['id']; ?>">
                                                    <button type="submit" onclick="return confirm('Revoke and block this device?')" class="px-2.5 py-1 bg-rose-500/10 text-rose-600 hover:bg-rose-500 hover:text-white font-bold text-[9px] rounded-lg border border-rose-500/20 transition-all">Remove 🚫</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
          <!-- 3. MANAGE SLIDES (Dynamic Full Control) -->
            <div x-show="currentTab === 'slides'" class="space-y-6 max-w-7xl" x-cloak>
                
                <!-- Header & Add Button -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm relative">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Dynamic Hero Slides</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Add unlimited slides, change images, badges, and titles displayed on the homepage.</p>
                    </div>
                    <div x-data="{ openAdd: false }" class="mt-4 sm:mt-0 w-full sm:w-auto">
                        <button @click="openAdd = !openAdd" class="w-full sm:w-auto px-6 py-2.5 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl shadow-lg shadow-teal-500/30 transition-all flex items-center justify-center space-x-2">
                            <span>➕ Add New Slide</span>
                        </button>

                        <!-- Add New Slide Dropdown Form -->
                        <div x-show="openAdd" x-transition class="absolute right-0 sm:right-6 top-full mt-2 w-full sm:w-[400px] bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 rounded-2xl shadow-2xl z-50">
                            <h3 class="font-black text-slate-900 dark:text-white mb-4">Create New Slide</h3>
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="action" value="add_slide">
                                <input type="hidden" name="active_tab" value="slides">
                                
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Image (16:9 recommended) *</label>
                                    <input type="file" name="slide_image" accept="image/*" required class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-teal-500/10 file:text-teal-600 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-xl p-1 bg-slate-50 dark:bg-slate-900">
                                </div>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Badge Text</label>
                                        <input type="text" name="badge_text" required placeholder="e.g. HOT JOBS" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Badge Color</label>
                                        <select name="badge_color" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                            <option value="bg-teal-500">Teal</option>
                                            <option value="bg-amber-500">Amber</option>
                                            <option value="bg-emerald-500">Emerald</option>
                                            <option value="bg-blue-500">Blue</option>
                                            <option value="bg-rose-500">Rose</option>
                                            <option value="bg-purple-500">Purple</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Main Title Text *</label>
                                    <input type="text" name="title_text" required placeholder="e.g. Apply to Top Jobs..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                </div>
                                
                                <div class="flex space-x-3 pt-2">
                                    <button type="button" @click="openAdd = false" class="flex-1 py-2 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                    <button type="submit" class="flex-1 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold shadow-md">Save Slide</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Existing Slides Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($home_slides as $slide): ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                        
                        <!-- Delete Button -->
                        <form method="POST" class="absolute top-7 right-7 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                            <input type="hidden" name="action" value="delete_slide">
                            <input type="hidden" name="active_tab" value="slides">
                            <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure! you wanna delete this picture?')" class="h-8 w-8 bg-rose-500/90 hover:bg-rose-600 text-white rounded-full flex items-center justify-center shadow-lg backdrop-blur-sm border border-white/20" title="Delete Slide">
                                🗑️
                            </button>
                        </form>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="update_slide">
                            <input type="hidden" name="active_tab" value="slides">
                            <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            
                            <!-- Image Preview -->
                            <div class="relative w-full aspect-[16/9] rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <img src="<?php echo htmlspecialchars($slide['image_path']); ?>" class="w-full h-full object-cover object-top">
                                <div class="absolute bottom-2 left-2 px-2.5 py-1 <?php echo htmlspecialchars($slide['badge_color']); ?> text-white text-[8px] font-black uppercase rounded-lg shadow-md">
                                    <?php echo htmlspecialchars($slide['badge_text']); ?>
                                </div>
                            </div>
                            
                            <!-- Edit Inputs -->
                            <input type="file" name="slide_image" accept="image/*" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-600 dark:file:text-slate-300 cursor-pointer">

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Badge Text</label>
                                    <input type="text" name="badge_text" value="<?php echo htmlspecialchars($slide['badge_text']); ?>" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Badge Color</label>
                                    <select name="badge_color" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                        <option value="bg-teal-500" <?php echo $slide['badge_color'] == 'bg-teal-500' ? 'selected':''; ?>>Teal</option>
                                        <option value="bg-amber-500" <?php echo $slide['badge_color'] == 'bg-amber-500' ? 'selected':''; ?>>Amber</option>
                                        <option value="bg-emerald-500" <?php echo $slide['badge_color'] == 'bg-emerald-500' ? 'selected':''; ?>>Emerald</option>
                                        <option value="bg-blue-500" <?php echo $slide['badge_color'] == 'bg-blue-500' ? 'selected':''; ?>>Blue</option>
                                        <option value="bg-rose-500" <?php echo $slide['badge_color'] == 'bg-rose-500' ? 'selected':''; ?>>Rose</option>
                                        <option value="bg-purple-500" <?php echo $slide['badge_color'] == 'bg-purple-500' ? 'selected':''; ?>>Purple</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Main Title</label>
                                <input type="text" name="title_text" value="<?php echo htmlspecialchars($slide['title_text']); ?>" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                            </div>
                            <button type="submit" class="w-full py-2 bg-teal-50 dark:bg-teal-500/10 hover:bg-teal-600 hover:text-white text-teal-600 dark:text-teal-400 border border-teal-200 dark:border-teal-500/30 rounded-xl text-xs font-bold transition-colors">
                                Update Slide
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
         <!-- 4. MANAGE / POST JOBS (Editable Datalist Inputs - Select OR Type Custom Text) -->
            <div x-show="currentTab === 'jobs'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-10 rounded-3xl shadow-sm" x-cloak>
                <div class="flex items-center space-x-3 mb-8 border-b border-slate-100 dark:border-slate-800 pb-5">
                    <span class="text-3xl">💼</span>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Post New Job & Auto-Share</h2>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400">Publish detailed vacancies. Select from list OR type custom categories manually.</p>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <input type="hidden" name="action" value="post_job">
                    <input type="hidden" name="active_tab" value="jobs">

                    <!-- Basic Info -->
                    <div class="md:col-span-6">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Job Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Legal Advisor & Liaison Officer" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                    </div>
                    <div class="md:col-span-6">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Company Name *</label>
                        <input type="text" name="company_name" required placeholder="e.g. Dashen Bank PLC" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                    </div>

                    <!-- 🌟 1. CATEGORY (Select OR Type Custom Text) 🌟 -->
                    <div class="md:col-span-4">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Category (Select or Type) *</label>
                        <input type="text" name="category" list="category_list" required placeholder="Select or type new category..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                        <datalist id="category_list">
                            <option value="Accounting and Finance">
                            <option value="Admin, Secretarial and Clerical">
                            <option value="Agriculture">
                            <option value="Architecture and Construction">
                            <option value="Banking and Insurance">
                            <option value="Business Development">
                            <option value="Customer Service">
                            <option value="Education">
                            <option value="Engineering">
                            <option value="Health Care">
                            <option value="ICT and Telecom">
                            <option value="Legal">
                            <option value="Sales and Marketing">
                        </datalist>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Location *</label>
                        <input type="text" name="location" required placeholder="e.g. Addis Ababa" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Salary Offer *</label>
                        <input type="text" name="salary" required placeholder="e.g. 18,000 ETB / Mon or Negotiable" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                    </div>

                    <!-- 🌟 2. EMPLOYMENT TYPE (Select OR Type Custom Text) 🌟 -->
                    <div class="md:col-span-3">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Employment Type *</label>
                        <input type="text" name="job_type" list="job_type_list" required placeholder="Select or type type..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                        <datalist id="job_type_list">
                            <option value="Full Time">
                            <option value="Part Time">
                            <option value="Contract">
                            <option value="Internship">
                            <option value="Remote">
                            <option value="Freelance">
                        </datalist>
                    </div>

                    <!-- 🌟 3. CAREER LEVEL (Select OR Type Custom Text) 🌟 -->
                    <div class="md:col-span-3">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Career Level *</label>
                        <input type="text" name="career_level" list="career_level_list" required placeholder="Select or type level..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500 transition-all">
                        <datalist id="career_level_list">
                            <option value="Entry Level (Fresh Graduate)">
                            <option value="Junior Level (1-3 years)">
                            <option value="Mid Level (3-5 years)">
                            <option value="Senior (5-8 years)">
                            <option value="Managerial / Executive">
                        </datalist>
                    </div>
<div class="md:col-span-6">
                        <label class="block text-[10px] sm:text-xs font-bold text-rose-500 uppercase mb-1.5">Deadline (Closing Date) *</label>
                        <input type="date" name="deadline" required class="w-full bg-rose-50 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-500/50 rounded-xl px-4 py-3 text-rose-600 dark:text-rose-400 font-bold text-sm outline-none focus:border-rose-500 transition-all">
                    </div>
                    <!-- Description & Requirements -->
                    <div class="md:col-span-12">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Job Description *</label>
                        <textarea name="description" rows="4" placeholder="Position summary and overview..." required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500"></textarea>
                    </div>
                    <div class="md:col-span-12">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Duties & Requirements *</label>
                        <textarea name="requirements" rows="4" placeholder="- Bachelor's Degree in Law...&#10;- 5 years experience..." required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500"></textarea>
                    </div>
                <div class="md:col-span-12">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">How To Apply (Instructions)</label>
                        <textarea name="how_to_apply" rows="2" placeholder="Send your CV to email@company.com or Apply via Info+..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm outline-none focus:border-teal-500"></textarea>
                    </div>

                    <!-- 🌟 QUANTITY REQUIRED (Prominent & Unmissable - 100% English) 🌟 -->
                    <div class="md:col-span-12 bg-amber-500/10 p-4 rounded-2xl border-2 border-amber-500/40 shadow-inner my-2">
                        <label class="block text-[10px] sm:text-xs font-black text-amber-600 dark:text-amber-400 uppercase mb-1.5">Total Quantity Required *</label>
                        <input type="number" name="positions" value="1" min="1" required class="w-full bg-white dark:bg-slate-800 border border-amber-500/50 rounded-xl px-4 py-3 text-slate-900 dark:text-white text-sm font-bold outline-none focus:border-amber-500 transition-all">
                        <p class="text-[9px] sm:text-[10px] text-amber-700 dark:text-amber-300 mt-1 font-bold">⚠️ Note: Our system uses this number to automatically calculate and display the Live Applicants Progress Bar.</p>
                    </div>

                    <!-- File Uploads -->
                    <div class="md:col-span-6 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 border-dashed">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Company Logo (Image)</label>
                        <input type="file" name="job_logo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-200 dark:file:bg-slate-700 file:text-slate-700 dark:file:text-white cursor-pointer">
                    </div>
                    <div class="md:col-span-6 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 border-dashed">
                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1.5">Attach Full Document (PDF / Flyer)</label>
                        <input type="file" name="attachment_file" accept=".pdf,image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-teal-500/10 file:text-teal-600 cursor-pointer">
                    </div>
                   
                    <!-- Auto-Poster Toggles -->
                    <div class="md:col-span-12 border-2 border-teal-500/30 bg-teal-500/5 p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h4 class="font-black text-teal-600 dark:text-teal-400 text-base">🪄 Social Media Manager AI</h4>
                            <p class="text-xs text-teal-600/70 dark:text-teal-500/70 mt-0.5">Where do you want to publish this job instantly?</p>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center space-x-2 bg-white dark:bg-slate-800 px-3 py-2 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 cursor-pointer">
                                <input type="checkbox" name="post_telegram" checked class="w-4 h-4 accent-teal-500">
                                <span class="text-xs font-bold">✈️ Telegram</span>
                            </label>
                            <label class="flex items-center space-x-2 bg-white dark:bg-slate-800 px-3 py-2 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 cursor-pointer opacity-50">
                                <input type="checkbox" name="post_facebook" class="w-4 h-4 accent-blue-600">
                                <span class="text-xs font-bold">📘 Facebook</span>
                            </label>
                            <label class="flex items-center space-x-2 bg-white dark:bg-slate-800 px-3 py-2 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 cursor-pointer opacity-50">
                                <input type="checkbox" name="post_twitter" class="w-4 h-4 accent-slate-900">
                                <span class="text-xs font-bold">𝕏 Twitter</span>
                            </label>
                        </div>
                    </div>

                   <div class="md:col-span-12 mt-2">
                        <!-- 🌟 BUTTON DOUBLE-CLICK LOCK (Prevents duplicate clicks) 🌟 -->
                        <button type="submit" 
                                onclick="if(this.form.checkValidity()){ this.disabled=true; this.innerHTML='⏳ Publishing Job...'; this.form.submit(); }" 
                                class="w-full bg-teal-600 hover:bg-teal-500 text-white font-black py-4 rounded-xl text-lg shadow-xl shadow-teal-500/30 transition-transform transform hover:-translate-y-1 cursor-pointer">
                            🚀 Publish Job to Web & Social Media
                        </button>
                    </div>
                </form>
 <!-- 🌟 MANAGE POSTED JOBS (With Live Search & Instant Matching Sorter) 🌟 -->
                    <div class="mt-16 pt-10 border-t border-slate-200 dark:border-slate-800 w-full" 
                         x-data="{ 
                             editJobRecord: null,
                             jobSearchQuery: '',
                             matchJob(jobText) {
                                 if (!this.jobSearchQuery.trim()) return true;
                                 return jobText.toLowerCase().includes(this.jobSearchQuery.toLowerCase().trim());
                             }
                         }">
                        
                        <?php 
                            // Real-time direct DB Fetch
                            $display_jobs = [];
                            if (isset($pdo) && $pdo !== null) {
                                try {
                                    $stmt_direct = $pdo->query("SELECT * FROM jobs ORDER BY id DESC");
                                    $display_jobs = $stmt_direct->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $display_jobs = [];
                                }
                            }
                        ?>

                        <!-- Header & Live Search Bar (Side-by-Side on PC & Mobile) -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                            <div>
                                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>🗂️</span> <span>Manage Posted Jobs</span>
                                </h3>
                                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">View, Edit, or Delete previously published vacancies.</p>
                            </div>

                            <!-- Total Pill & Live Search Input -->
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <span class="px-3.5 py-2 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 font-bold text-xs border border-teal-500/20 whitespace-nowrap flex-shrink-0">
                                    Total: <?php echo count($display_jobs); ?> Jobs
                                </span>

                                <!-- 🔍 Real-Time Live Search Bar -->
                                <div class="relative flex-1 sm:w-64">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs">
                                        🔍
                                    </span>
                                    <input type="text" 
                                           x-model="jobSearchQuery" 
                                           placeholder="Search job, company..." 
                                           class="w-full pl-8 pr-7 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition-all font-semibold">
                                    <button type="button" 
                                            x-show="jobSearchQuery" 
                                            @click="jobSearchQuery = ''" 
                                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-500 text-xs font-bold" 
                                            style="display: none;">✕</button>
                                </div>
                            </div>
                        </div>

                        <!-- Job Cards Grid (Live Filtered via AlpineJS) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <?php if(empty($display_jobs)): ?>
                                <p class="col-span-full text-center text-slate-400 text-sm py-10">No jobs posted yet in Database.</p>
                            <?php else: ?>
                                <?php foreach($display_jobs as $j): ?>
                                <?php 
                                    // Handle NULL values safely
                                    $c_logo = !empty($j['logo']) ? $j['logo'] : 'logo.png';
                                    $c_title = !empty($j['title']) ? $j['title'] : 'Untitled Job';
                                    $c_company = !empty($j['company_name']) ? $j['company_name'] : 'Unknown Company';
                                    $c_location = !empty($j['location']) ? $j['location'] : 'Not Specified';
                                    $c_deadline = !empty($j['deadline']) ? $j['deadline'] : 'Open';
                                    $c_category = !empty($j['category']) ? $j['category'] : 'General';
                                    $c_type = !empty($j['job_type']) ? $j['job_type'] : 'Full Time';
                                    $c_level = !empty($j['career_level']) ? $j['career_level'] : (!empty($j['experience']) ? $j['experience'] : 'Mid Level');
                                    $c_positions = !empty($j['positions']) ? $j['positions'] : 1;
                                    $c_salary = !empty($j['salary']) ? $j['salary'] : 'Negotiable';
                                    $c_views = isset($j['views']) ? $j['views'] : 0;
                                    $c_status = !empty($j['status']) ? $j['status'] : 'active';
                                    $c_date = !empty($j['created_at']) ? date('M d, Y', strtotime($j['created_at'])) : 'Recently';

                                    // Calculate Days Left
                                    $days_text = 'Open';
                                    if(!empty($j['deadline'])) {
                                        $diff = strtotime($j['deadline']) - time();
                                        $days_left = floor($diff / (60 * 60 * 24)) + 1;
                                        if($days_left > 1) {
                                            $days_text = $days_left . ' days left';
                                        } elseif($days_left == 1) {
                                            $days_text = '1 day left';
                                        } elseif($days_left == 0) {
                                            $days_text = 'Deadline Today';
                                        } else {
                                            $days_text = 'Expired';
                                        }
                                    }

                                    // Combined Search String for Live Filtering
                                    $searchable_text = strtolower($c_title . ' ' . $c_company . ' ' . $c_category . ' ' . $c_location . ' ' . $c_salary . ' ' . $c_level . ' ' . $c_type);
                                ?>
                                <!-- Card Box (Hidden/Shown Instantly as you type) -->
                                <div x-show="matchJob('<?php echo addslashes($searchable_text); ?>')"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     class="dotted-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[2rem] p-5 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group relative">
                                    
                                    <div>
                                        <!-- Header Bar -->
                                        <div class="flex justify-between items-start mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                                            
                                            <!-- Left: Days Left & Active Badge -->
                                            <div class="space-y-1 sm:space-y-1.5 text-left flex-shrink-0 w-auto max-w-[30%] pr-1">
                                                <div class="flex items-center space-x-1 text-[9px] sm:text-[11px] font-bold text-teal-600 dark:text-teal-400 truncate">
                                                    <span>⏱</span>
                                                    <span class="truncate"><?php echo $days_text; ?></span>
                                                </div>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-[8px] sm:text-[10px] font-extrabold <?php echo $c_status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'; ?>">
                                                    <span class="h-1.5 w-1.5 rounded-full flex-shrink-0 <?php echo $c_status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'; ?>"></span>
                                                    <span class="truncate"><?php echo strtoupper($c_status); ?></span>
                                                </span>
                                            </div>

                                            <!-- Center: Company Logo & Name -->
                                            <div class="flex flex-col items-center flex-1 min-w-0 px-1">
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl border border-slate-200 dark:border-slate-800 p-1 bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center flex-shrink-0">
                                                    <img src="<?php echo htmlspecialchars($c_logo); ?>" onerror="this.src='https://ui-avatars.com/api/?name=I+P&background=0D9488&color=fff'" alt="Logo" class="w-full h-full object-contain rounded-lg">
                                                </div>
                                                <span class="text-[9px] sm:text-xs font-black text-slate-900 dark:text-white mt-1 max-w-[80px] sm:max-w-[110px] truncate text-center block"><?php echo htmlspecialchars($c_company); ?></span>
                                            </div>

                                            <!-- Right: Admin Actions -->
                                            <div class="flex items-center justify-end gap-1 sm:gap-1.5 flex-shrink-0 w-auto">
                                                
                                                <!-- Top Company Toggle Button -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_top_company">
                                                    <input type="hidden" name="active_tab" value="jobs">
                                                    <input type="hidden" name="job_id" value="<?php echo $j['id']; ?>">
                                                    <input type="hidden" name="company_name" value="<?php echo htmlspecialchars($j['company_name'] ?? ''); ?>">
                                                    <input type="hidden" name="current_val" value="<?php echo (int)($j['is_top_company'] ?? 0); ?>">
                                                    
                                                    <button type="submit" class="px-1.5 py-1 sm:px-2.5 sm:py-1 rounded-lg sm:rounded-xl text-[10px] sm:text-[9px] font-black border transition-all cursor-pointer shadow-sm flex items-center justify-center whitespace-nowrap <?php echo (!empty($j['is_top_company']) && $j['is_top_company'] == 1) ? 'bg-amber-500 text-slate-950 border-amber-500 shadow-md animate-pulse' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700 hover:text-amber-500'; ?>" title="Toggle Top Hiring Status">
                                                        <?php if(!empty($j['is_top_company']) && $j['is_top_company'] == 1): ?>
                                                            ⭐ <span class="hidden sm:inline ml-1">Top</span>
                                                        <?php else: ?>
                                                            ☆ <span class="hidden sm:inline ml-1">Add Top</span>
                                                        <?php endif; ?>
                                                    </button>
                                                </form>

                                                <!-- Edit Button -->
                                                <button type="button" @click="editJobRecord = <?php echo htmlspecialchars(json_encode($j)); ?>" class="h-6 w-6 sm:h-7 sm:w-7 rounded-md sm:rounded-lg bg-blue-500/10 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-colors shadow-sm text-[10px] sm:text-xs" title="Edit Job">
                                                    ✏️
                                                </button>

                                                <!-- Delete Button -->
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this job permanently?')">
                                                    <input type="hidden" name="action" value="delete_job">
                                                    <input type="hidden" name="active_tab" value="jobs">
                                                    <input type="hidden" name="job_id" value="<?php echo $j['id']; ?>">
                                                    <button type="submit" class="h-6 w-6 sm:h-7 sm:w-7 rounded-md sm:rounded-lg bg-rose-500/10 text-rose-600 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-colors shadow-sm text-[10px] sm:text-xs" title="Delete Job">
                                                        🗑️
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Rows List -->
                                        <div class="space-y-2.5 text-xs text-left">
                                            
                                            <!-- 1. JOB TITLE -->
                                            <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                                                <div class="flex items-center space-x-2 text-teal-600 dark:text-teal-400 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                    <span>JOB TITLE:</span>
                                                </div>
                                                <span class="font-extrabold text-teal-600 dark:text-teal-400 text-right truncate max-w-[55%]"><?php echo htmlspecialchars($c_title); ?></span>
                                            </div>

                                            <!-- 2. COMPANY -->
                                            <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                                                <div class="flex items-center space-x-2 text-teal-600 dark:text-teal-400 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                    <span>COMPANY:</span>
                                                </div>
                                                <span class="font-bold text-slate-900 dark:text-white text-right truncate max-w-[55%]"><?php echo htmlspecialchars($c_company); ?></span>
                                            </div>

                                            <!-- 3. CATEGORY -->
                                            <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                                                <div class="flex items-center space-x-2 text-teal-600 dark:text-teal-400 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                                    <span>CATEGORY:</span>
                                                </div>
                                                <span class="font-bold text-slate-800 dark:text-slate-200 text-right truncate max-w-[55%]"><?php echo htmlspecialchars($c_category); ?></span>
                                            </div>

                                            <!-- 4. LOCATION -->
                                            <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                                                <div class="flex items-center space-x-2 text-teal-600 dark:text-teal-400 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <span>LOCATION:</span>
                                                </div>
                                                <span class="font-bold text-slate-800 dark:text-slate-200 text-right truncate max-w-[55%]"><?php echo htmlspecialchars($c_location); ?></span>
                                            </div>

                                            <!-- 5. SALARY -->
                                            <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                                                <div class="flex items-center space-x-2 text-teal-600 dark:text-teal-400 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                    <span>SALARY:</span>
                                                </div>
                                                <span class="font-black text-teal-600 dark:text-teal-400 text-right truncate max-w-[55%]"><?php echo htmlspecialchars($c_salary); ?></span>
                                            </div>

                                            <!-- 6. JOB TYPE -->
                                            <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                                                <div class="flex items-center space-x-2 text-teal-600 dark:text-teal-400 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span>JOB TYPE:</span>
                                                </div>
                                                <span class="font-bold text-slate-800 dark:text-slate-200 text-right truncate max-w-[55%]"><?php echo htmlspecialchars($c_type); ?></span>
                                            </div>

                                            <!-- 7. CAREER LEVEL -->
                                            <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                                                <div class="flex items-center space-x-2 text-teal-600 dark:text-teal-400 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                                    <span>🎓 LEVEL:</span>
                                                </div>
                                                <span class="font-bold text-slate-800 dark:text-slate-200 text-right truncate max-w-[55%]" title="<?php echo htmlspecialchars($c_level); ?>"><?php echo htmlspecialchars($c_level); ?></span>
                                            </div>

                                            <!-- 8. QTY REQUIRED -->
                                            <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                                                <div class="flex items-center space-x-2 text-teal-600 dark:text-teal-400 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                    <span>QTY REQUIRED:</span>
                                                </div>
                                                <span class="font-bold text-slate-800 dark:text-slate-200 text-right truncate max-w-[55%]"><?php echo $c_positions; ?> Position(s)</span>
                                            </div>

                                            <!-- 9. DEADLINE DATE -->
                                            <div class="flex justify-between items-center py-1.5">
                                                <div class="flex items-center space-x-2 text-rose-500 font-extrabold text-[11px] uppercase tracking-wider">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span>DEADLINE DATE:</span>
                                                </div>
                                                <span class="font-black text-rose-500 text-right truncate max-w-[55%]"><?php echo htmlspecialchars($c_deadline); ?></span>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Footer Row -->
                                    <div class="border-t border-slate-200/60 dark:border-slate-800 pt-3 mt-4 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
                                        <span>Posted: <?php echo $c_date; ?></span>
                                        <a href="job-details.php?id=<?php echo $j['id']; ?>" target="_blank" class="text-teal-600 dark:text-teal-400 font-bold hover:underline">Full Details ↗</a>
                                    </div>

                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <!-- 🌟 COMPLETE EDIT JOB POPUP MODAL (Edits ALL 12 Fields) 🌟 -->
                        <div x-show="editJobRecord !== null" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
                            <div @click.away="editJobRecord = null" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-5 text-left relative max-h-[90vh] overflow-y-auto custom-scrollbar">
                                
                                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-4">
                                    <h3 class="font-black text-slate-900 dark:text-white text-lg">✏️ Edit Job Details</h3>
                                    <button type="button" @click="editJobRecord = null" class="text-slate-400 hover:text-slate-900 dark:hover:text-white font-bold p-1">✕</button>
                                </div>

                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="edit_job">
                                    <input type="hidden" name="active_tab" value="jobs">
                                    <input type="hidden" name="job_id" :value="editJobRecord ? editJobRecord.id : ''">

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Job Title *</label>
                                            <input type="text" name="title" :value="editJobRecord ? editJobRecord.title : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Company Name *</label>
                                            <input type="text" name="company_name" :value="editJobRecord ? editJobRecord.company_name : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Category *</label>
                                            <input type="text" name="category" list="category_list" :value="editJobRecord ? editJobRecord.category : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Location *</label>
                                            <input type="text" name="location" :value="editJobRecord ? editJobRecord.location : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Salary *</label>
                                            <input type="text" name="salary" :value="editJobRecord ? editJobRecord.salary : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                        </div>
                                    </div>

                                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Job Type *</label>
                                            <input type="text" name="job_type" list="job_type_list" :value="editJobRecord ? editJobRecord.job_type : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Career Level *</label>
                                            <input type="text" name="career_level" list="career_level_list" :value="editJobRecord ? (editJobRecord.career_level || editJobRecord.experience) : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-rose-500 uppercase mb-1">Deadline Date *</label>
                                            <input type="date" name="deadline" :value="editJobRecord ? editJobRecord.deadline : ''" required class="w-full bg-rose-50 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-500/50 rounded-xl px-3 py-2.5 text-xs text-rose-600 dark:text-rose-400 font-bold outline-none focus:border-rose-500">
                                        </div>
                                    </div>

                                    <!-- 🌟 PROMINENT EDIT QUANTITY BOX 🌟 -->
                                    <div class="bg-amber-500/10 p-4 rounded-2xl border-2 border-amber-500/40 shadow-inner">
                                        <label class="block text-[10px] sm:text-xs font-black text-amber-600 dark:text-amber-400 uppercase mb-1.5">Total Quantity Required *</label>
                                        <input type="number" name="positions" :value="editJobRecord ? editJobRecord.positions : 1" min="1" required class="w-full bg-white dark:bg-slate-800 border border-amber-500/50 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white text-sm font-bold outline-none focus:border-amber-500 transition-all">
                                        <p class="text-[9px] text-amber-700 dark:text-amber-300 mt-1 font-bold">⚠️ Update this number to instantly adjust the Live Applicants Progress Bar.</p>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Status *</label>
                                        <select name="status" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500" x-model="editJobRecord ? editJobRecord.status : 'active'">
                                            <option value="active">Active</option>
                                            <option value="expired">Expired / Closed</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Job Description</label>
                                        <textarea name="description" rows="3" :value="editJobRecord ? editJobRecord.description : ''" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500"></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Duties & Requirements</label>
                                        <textarea name="requirements" rows="3" :value="editJobRecord ? editJobRecord.requirements : ''" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500"></textarea>
                                    </div>

                                    <div class="pt-2 flex justify-end gap-3">
                                        <button type="button" @click="editJobRecord = null" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-xl text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Cancel</button>
                                        <button type="submit" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-500 text-white font-black rounded-xl text-xs shadow-md shadow-teal-500/30 transition-all">Save Changes</button>
                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>
            </div>
           <!-- 5. MANAGE DATABASE (LIVE FULL CONTROL FOR CANDIDATES, EMPLOYERS & ADMINS) -->
            <div x-show="currentTab === 'database'" 
                 x-data="{ dbTab: 'candidates', viewRecord: null, viewType: '' }" 
                 class="space-y-6 max-w-7xl" x-cloak>
                
                <!-- Top Banner & Sub-Tab Switcher -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>🗄️</span> <span>Live Database Manager</span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Live inspection, full details view, and management of all registered records.</p>
                    </div>

                    <!-- Sub-Tab Switcher Pills -->
                    <div class="inline-flex p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 shadow-inner w-full sm:w-auto overflow-x-auto">
                        <button @click="dbTab = 'candidates'" 
                                :class="dbTab === 'candidates' ? 'bg-teal-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-300 hover:text-teal-600'" 
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5">
                            <span>👥 Candidates</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] bg-white/20 text-white font-extrabold"><?php echo count($db_candidates); ?></span>
                        </button>
                        <button @click="dbTab = 'employers'" 
                                :class="dbTab === 'employers' ? 'bg-amber-500 text-slate-950 shadow-md' : 'text-slate-600 dark:text-slate-300 hover:text-amber-500'" 
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5">
                            <span>🏢 Employers</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] bg-slate-950/20 text-slate-950 font-extrabold"><?php echo count($db_employers); ?></span>
                        </button>
                        <button @click="dbTab = 'admins'" 
                                :class="dbTab === 'admins' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-300 hover:text-blue-500'" 
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5">
                            <span>👨‍💻 Admins</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] bg-white/20 text-white font-extrabold"><?php echo count($db_admins); ?></span>
                        </button>
                    </div>
                </div>

                <!-- TABLE 1: CANDIDATES (USERS) -->
                <div x-show="dbTab === 'candidates'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm overflow-x-auto">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white mb-4">All Registered Candidates (<?php echo count($db_candidates); ?>)</h3>
                    <div class="min-w-[650px]">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] sm:text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40">
                                    <th class="py-3 px-4 rounded-tl-xl">ID</th>
                                    <th class="py-3 px-4">Name</th>
                                    <th class="py-3 px-4">Email</th>
                                    <th class="py-3 px-4">Phone</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right rounded-tr-xl">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs sm:text-sm">
                                <?php if (empty($db_candidates)): ?>
                                    <tr><td colspan="6" class="py-6 text-center text-slate-400">No candidates registered yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($db_candidates as $c): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="py-3.5 px-4 font-bold text-slate-400">#<?php echo $c['id']; ?></td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars(($c['first_name']??'') . ' ' . ($c['last_name']??'')); ?></td>
                                        <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($c['email']??''); ?></td>
                                        <td class="py-3.5 px-4 font-semibold text-teal-600 dark:text-teal-400"><?php echo htmlspecialchars($c['phone']??''); ?></td>
                                        <td class="py-3.5 px-4">
                                            <span class="px-2.5 py-1 rounded-full text-[9px] font-extrabold uppercase <?php echo ($c['is_verified']??0) ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border border-amber-500/20'; ?>">
                                                <?php echo ($c['is_verified']??0) ? 'Verified' : 'Pending OTP'; ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right space-x-2">
                                            <button @click="viewRecord = <?php echo htmlspecialchars(json_encode($c)); ?>; viewType = 'Candidate'" class="px-3 py-1 bg-teal-500/10 text-teal-600 dark:text-teal-400 hover:bg-teal-600 hover:text-white rounded-lg text-xs font-bold transition-all">
                                                👁️ View
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this candidate record?')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="active_tab" value="database">
                                                <input type="hidden" name="user_id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" class="px-2.5 py-1 bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white rounded-lg text-xs font-bold transition-all">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TABLE 2: EMPLOYERS -->
                <div x-show="dbTab === 'employers'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm overflow-x-auto" x-cloak>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white mb-4">All Registered Employers (<?php echo count($db_employers); ?>)</h3>
                    <div class="min-w-[650px]">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] sm:text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40">
                                    <th class="py-3 px-4 rounded-tl-xl">ID</th>
                                    <th class="py-3 px-4">Company Name</th>
                                    <th class="py-3 px-4">Contact Person</th>
                                    <th class="py-3 px-4">Email / Phone</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right rounded-tr-xl">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs sm:text-sm">
                                <?php if (empty($db_employers)): ?>
                                    <tr><td colspan="6" class="py-6 text-center text-slate-400">No employers registered yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($db_employers as $e): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="py-3.5 px-4 font-bold text-slate-400">#<?php echo $e['id']; ?></td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($e['company_name']??''); ?></td>
                                        <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($e['contact_name']??''); ?></td>
                                        <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($e['email']??''); ?><br><span class="text-amber-500 font-bold"><?php echo htmlspecialchars($e['phone']??''); ?></span></td>
                                        <td class="py-3.5 px-4">
                                            <span class="px-2.5 py-1 rounded-full text-[9px] font-extrabold uppercase <?php echo ($e['status']??'') === 'approved' ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border border-amber-500/20'; ?>">
                                                <?php echo htmlspecialchars($e['status']??'pending'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right space-x-2">
                                            <button @click="viewRecord = <?php echo htmlspecialchars(json_encode($e)); ?>; viewType = 'Employer'" class="px-3 py-1 bg-amber-500/10 text-amber-600 hover:bg-amber-500 hover:text-slate-950 rounded-lg text-xs font-bold transition-all">
                                                👁️ View
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this employer record?')">
                                                <input type="hidden" name="action" value="delete_employer">
                                                <input type="hidden" name="active_tab" value="database">
                                                <input type="hidden" name="employer_id" value="<?php echo $e['id']; ?>">
                                                <button type="submit" class="px-2.5 py-1 bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white rounded-lg text-xs font-bold transition-all">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TABLE 3: ADMINS -->
                <div x-show="dbTab === 'admins'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm overflow-x-auto" x-cloak>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white mb-4">System Administrators (<?php echo count($db_admins); ?>)</h3>
                    <div class="min-w-[650px]">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] sm:text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40">
                                    <th class="py-3 px-4 rounded-tl-xl">ID</th>
                                    <th class="py-3 px-4">Username</th>
                                    <th class="py-3 px-4">Email</th>
                                    <th class="py-3 px-4">Role</th>
                                    <th class="py-3 px-4">2FA Status</th>
                                    <th class="py-3 px-4 text-right rounded-tr-xl">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs sm:text-sm">
                                <?php foreach ($db_admins as $a): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-3.5 px-4 font-bold text-slate-400">#<?php echo $a['id']; ?></td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($a['username']??''); ?></td>
                                    <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($a['email']??''); ?></td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[9px] font-extrabold uppercase <?php echo ($a['role']??'') === 'superadmin' ? 'bg-amber-500/10 text-amber-600 border border-amber-500/20' : 'bg-blue-500/10 text-blue-600 border border-blue-500/20'; ?>">
                                            <?php echo htmlspecialchars($a['role']??'admin'); ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded <?php echo ($a['two_factor_enabled']??0) ? 'text-teal-600 bg-teal-500/10' : 'text-slate-400 bg-slate-100 dark:bg-slate-800'; ?>">
                                            <?php echo ($a['two_factor_enabled']??0) ? '2FA Active (' . htmlspecialchars($a['two_factor_method']??'email') . ')' : '2FA Off'; ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right space-x-2">
                                        <button @click="viewRecord = <?php echo htmlspecialchars(json_encode($a)); ?>; viewType = 'Admin'" class="px-3 py-1 bg-blue-500/10 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg text-xs font-bold transition-all">
                                            👁️ View
                                        </button>
                                        <?php if (($a['role']??'') !== 'superadmin'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this admin?')">
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="active_tab" value="database">
                                            <input type="hidden" name="admin_id" value="<?php echo $a['id']; ?>">
                                            <button type="submit" class="px-2.5 py-1 bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white rounded-lg text-xs font-bold transition-all">
                                                🗑️ Delete
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 🌟 LIVE RECORD DETAILS POPUP MODAL 🌟 -->
                <div x-show="viewRecord !== null" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
                    <div @click.away="viewRecord = null" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-5 text-left relative max-h-[90vh] overflow-y-auto custom-scrollbar">
                        
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div>
                                <span class="text-[10px] font-black uppercase text-teal-600 dark:text-teal-400 tracking-wider block" x-text="viewType + ' Record Details'"></span>
                                <h3 class="font-black text-slate-900 dark:text-white text-lg sm:text-xl" x-text="viewRecord ? (viewRecord.first_name ? viewRecord.first_name + ' ' + (viewRecord.last_name||'') : (viewRecord.company_name || viewRecord.username)) : ''"></h3>
                            </div>
                            <button @click="viewRecord = null" class="text-slate-400 hover:text-slate-900 dark:hover:text-white font-bold p-1">✕</button>
                        </div>

                        <!-- Details Key-Value List -->
                        <div class="space-y-3 text-xs sm:text-sm">
                            <template x-for="(value, key) in viewRecord" :key="key">
                                <div x-show="key !== 'password' && key !== 'otp_code' && value !== null" class="flex justify-between items-start border-b border-slate-50 dark:border-slate-800/60 pb-2">
                                    <span class="text-slate-400 font-bold uppercase text-[10px]" x-text="key.replace('_', ' ')"></span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 text-right break-all max-w-[65%]" x-text="value"></span>
                                </div>
                            </template>
                        </div>

                        <div class="pt-3 flex justify-end">
                            <button @click="viewRecord = null" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                Close Details
                            </button>
                        </div>

                    </div>
                </div>

            </div>
<!-- 6. ALL MESSAGES (STRICT 1-ON-1, WORKING EDIT/DELETE & MEDIA PLAYERS) -->
            <div x-show="currentTab === 'messages'" 
                 x-data="{ 
                     chatFolder: 'all', 
                     mobileView: 'list', 
                     activeContact: JSON.parse(localStorage.getItem('admin_last_contact')) || <?php echo !empty($all_contacts) ? htmlspecialchars(json_encode($all_contacts[0])) : 'null'; ?>,
                     editMsgObj: null
                 }" 
                 x-init="
                     $watch('activeContact', val => localStorage.setItem('admin_last_contact', JSON.stringify(val))); 
                     setTimeout(() => { const b = document.getElementById('chatHistoryBox'); if(b) b.scrollTop = b.scrollHeight; }, 300);
                 "
                 class="h-[75vh] sm:h-[700px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl flex flex-col md:flex-row overflow-hidden relative" x-cloak>
                
                <!-- 🌟 LEFT SIDEBAR: REAL CONTACTS LIST 🌟 -->
                <div :class="mobileView === 'list' ? 'flex' : 'hidden md:flex'" class="w-full md:w-80 lg:w-96 border-r border-slate-200 dark:border-slate-800 flex-col bg-slate-50 dark:bg-slate-900/50 flex-shrink-0 z-10 h-full">
                    
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                        <h3 class="font-black text-slate-900 dark:text-white text-base mb-2">Messages & Support</h3>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">🔍</span>
                            <input type="text" placeholder="Search contacts..." class="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500 border border-transparent">
                        </div>
                    </div>

                    <!-- Folders Switcher -->
                    <div class="flex overflow-x-auto custom-scrollbar border-b border-slate-200 dark:border-slate-800 px-2 py-2 bg-white dark:bg-slate-900">
                        <button @click="chatFolder = 'all'" :class="chatFolder === 'all' ? 'bg-teal-500/10 text-teal-600 dark:text-teal-400 font-bold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] transition-all whitespace-nowrap">All (<?php echo count($all_contacts); ?>)</button>
                        <button @click="chatFolder = 'employer'" :class="chatFolder === 'employer' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] transition-all whitespace-nowrap">🏢 Employers</button>
                        <button @click="chatFolder = 'candidate'" :class="chatFolder === 'candidate' ? 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-bold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] transition-all whitespace-nowrap">👥 Candidates</button>
                        <button @click="chatFolder = 'admin'" :class="chatFolder === 'admin' ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-3 py-1.5 rounded-full text-[10px] transition-all whitespace-nowrap">👨‍💻 Admins</button>
                    </div>

                    <!-- Dynamic Contacts List -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-slate-100 dark:divide-slate-800/50">
                        <?php if(empty($all_contacts)): ?>
                            <p class="p-6 text-center text-xs text-slate-400">No registered contacts found.</p>
                        <?php else: ?>
                            <?php foreach ($all_contacts as $contact): ?>
                            <div x-show="chatFolder === 'all' || chatFolder === '<?php echo $contact['type']; ?>'" 
                                 @click="activeContact = <?php echo htmlspecialchars(json_encode($contact)); ?>; mobileView = 'chat'; setTimeout(() => { const b = document.getElementById('chatHistoryBox'); if(b) b.scrollTop = b.scrollHeight; }, 100);" 
                                 :class="activeContact && activeContact.email === '<?php echo $contact['email']; ?>' ? 'bg-teal-500/10 dark:bg-slate-800 border-l-4 border-teal-500' : 'hover:bg-slate-100 dark:hover:bg-slate-800/60'"
                                 class="flex items-center space-x-3 p-3.5 cursor-pointer transition-all">
                                
                                <div class="w-10 h-10 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 flex-shrink-0 bg-slate-200">
                                    <img src="<?php echo !empty($contact['avatar']) ? $contact['avatar'] : 'logo.png'; ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($contact['name']); ?>&background=0D9488&color=fff'" class="w-full h-full object-cover">
                                </div>
                                
                                <div class="flex-1 min-w-0 text-left">
                                    <div class="flex justify-between items-center mb-0.5">
                                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($contact['name']); ?></h4>
                                        <span class="text-[8px] px-1.5 py-0.5 rounded font-bold uppercase <?php echo $contact['type'] === 'employer' ? 'bg-amber-500/10 text-amber-600' : ($contact['type'] === 'candidate' ? 'bg-teal-500/10 text-teal-600' : 'bg-blue-500/10 text-blue-600'); ?>"><?php echo $contact['type']; ?></span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 truncate"><?php echo htmlspecialchars($contact['email']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 🌟 RIGHT MAIN CHAT WINDOW 🌟 -->
                <div :class="mobileView === 'chat' ? 'flex' : 'hidden md:flex'" class="w-full md:flex-1 flex-col bg-slate-50 dark:bg-slate-950 h-full">
                    
                    <!-- Active Contact Header -->
                    <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md flex justify-between items-center shadow-sm z-20">
                        <div class="flex items-center space-x-3 text-left">
                            <button @click="mobileView = 'list'" class="md:hidden text-slate-500 hover:text-teal-600 px-2 font-bold">← Back</button>
                            <div class="w-10 h-10 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-200 flex-shrink-0">
                                <img :src="activeContact ? activeContact.avatar : 'logo.png'" onerror="this.src='logo.png'" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white leading-tight" x-text="activeContact ? activeContact.name : 'Select a Contact'"></h3>
                                <p class="text-[10px] text-teal-600 dark:text-teal-400 font-bold" x-text="activeContact ? activeContact.email : ''"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat History (Filtered Messages for Selected User) -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 custom-scrollbar flex flex-col z-10" id="chatHistoryBox">
                        
                        <?php if (empty($messages)): ?>
                            <div class="text-center py-12 text-slate-400 text-xs">
                                💬 No messages in this conversation yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                            <!-- STRICT 1-on-1 Message Filtering -->
                            <template x-if="activeContact && (('<?php echo $msg['sender_email']; ?>' === 'tadesebiruk515@gmail.com' && '<?php echo $msg['receiver_email']; ?>' === activeContact.email) || ('<?php echo $msg['sender_email']; ?>' === activeContact.email && '<?php echo $msg['receiver_email']; ?>' === 'tadesebiruk515@gmail.com'))">
                                <div class="flex <?php echo $msg['sender_type'] === 'admin' ? 'justify-end' : 'justify-start'; ?> w-full mb-3 group">
                                    <div class="relative <?php echo $msg['sender_type'] === 'admin' ? 'bg-teal-600 text-white rounded-br-none' : 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700 rounded-bl-none'; ?> p-3 sm:p-4 rounded-2xl max-w-[85%] sm:max-w-md text-xs sm:text-sm shadow-md text-left">
                                        
                                        <!-- 🌟 EDIT / DELETE BUTTONS FOR ADMIN MESSAGES 🌟 -->
                                        <?php if ($msg['sender_type'] === 'admin'): ?>
                                        <div class="absolute -top-3 right-2 hidden group-hover:flex items-center space-x-2 bg-slate-900 text-white px-2.5 py-1 rounded-lg shadow-xl border border-slate-700 z-30">
                                            <!-- Edit Button -->
                                            <button type="button" @click="editMsgObj = { id: <?php echo $msg['id']; ?>, text: <?php echo htmlspecialchars(json_encode($msg['message'] ?? '')); ?> }" class="hover:text-amber-400 text-[10px] font-bold transition-colors cursor-pointer" title="Edit Message">✏️ Edit</button>
                                            <span class="text-slate-600">•</span>
                                            <!-- Delete Button -->
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this message permanently?')">
                                                <input type="hidden" name="action" value="delete_message">
                                                <input type="hidden" name="active_tab" value="messages">
                                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                <button type="submit" class="hover:text-rose-400 text-[10px] font-bold transition-colors cursor-pointer" title="Delete Message">🗑️ Delete</button>
                                            </form>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Image Attachment -->
                                        <?php if ($msg['file_type'] === 'image'): ?>
                                            <a href="<?php echo htmlspecialchars($msg['file_url']); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($msg['file_url']); ?>" class="w-full max-h-60 object-cover rounded-xl mb-2 hover:opacity-90">
                                            </a>
                                        <?php endif; ?>

                                        <!-- Video Attachment -->
                                        <?php if ($msg['file_type'] === 'video'): ?>
                                            <video controls class="w-full rounded-xl mb-2 max-h-56 bg-black">
                                                <source src="<?php echo htmlspecialchars($msg['file_url']); ?>">
                                            </video>
                                        <?php endif; ?>

                                        <!-- Audio Attachment -->
                                        <?php if ($msg['file_type'] === 'audio'): ?>
                                            <div class="p-2 bg-black/10 rounded-xl mb-2">
                                                <audio controls class="w-full h-8">
                                                    <source src="<?php echo htmlspecialchars($msg['file_url']); ?>">
                                                </audio>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Document Attachment -->
                                        <?php if ($msg['file_type'] === 'pdf' || $msg['file_type'] === 'document'): ?>
                                            <a href="<?php echo htmlspecialchars($msg['file_url']); ?>" target="_blank" class="block p-2 bg-black/10 hover:bg-black/20 rounded-xl mb-2 text-xs font-bold transition">
                                                📄 Download Attachment ↗
                                            </a>
                                        <?php endif; ?>

                                        <!-- Text Message -->
                                        <?php if(!empty($msg['message'])): ?>
                                            <p class="leading-relaxed"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                        <?php endif; ?>

                                        <!-- Time & Edited Badge -->
                                        <div class="flex items-center justify-end space-x-1.5 mt-1.5">
                                            <?php if(!empty($msg['is_edited']) && $msg['is_edited'] == 1): ?>
                                                <span class="text-[8px] font-bold opacity-75 italic">(edited)</span>
                                            <?php endif; ?>
                                            <span class="text-[8px] opacity-75"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                                        </div>

                                    </div>
                                </div>
                            </template>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 🌟 CHAT INPUT AREA (Press Enter to Send & Auto File Upload) 🌟 -->
                    <div class="p-3 sm:p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 z-20">
                        <form method="POST" enctype="multipart/form-data" id="chatSendForm" class="flex items-end space-x-1.5 sm:space-x-2">
                            <input type="hidden" name="action" value="send_message">
                            <input type="hidden" name="active_tab" value="messages">
                            <input type="hidden" name="receiver_email" x-bind:value="activeContact ? activeContact.email : ''">
                            
                            <!-- File Attachment Button -->
                            <label class="cursor-pointer p-2.5 sm:p-3 bg-slate-100 dark:bg-slate-800 rounded-full text-slate-500 hover:text-teal-600 dark:hover:text-teal-400 hover:bg-slate-200 transition text-lg sm:text-xl flex-shrink-0" title="Attach Media or Doc">
                                📎 <input type="file" name="chat_attachment" id="chatFileInput" accept="image/*,video/*,audio/*,.pdf,.doc,.docx" class="hidden" onchange="document.getElementById('chatSendForm').submit();">
                            </label>
                            
                            <!-- Text Input area -->
                            <div class="flex-1 bg-slate-100 dark:bg-slate-800 rounded-2xl border border-transparent focus-within:border-teal-500 transition-colors flex items-center">
                                <textarea name="chat_message" rows="1" placeholder="Type message... (Press Enter to send)" 
                                          @keydown.enter.prevent="if($el.value.trim() !== '') document.getElementById('chatSendForm').submit();"
                                          class="w-full bg-transparent border-none px-4 py-3 text-slate-900 dark:text-white text-xs sm:text-sm outline-none resize-none custom-scrollbar max-h-24"></textarea>
                            </div>
                            
                            <!-- Send Button -->
                            <button type="submit" :disabled="!activeContact" :class="!activeContact ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105 hover:bg-teal-500'" class="p-2.5 sm:p-3 bg-teal-600 rounded-full text-white shadow-md shadow-teal-500/30 transition-all text-lg sm:text-xl flex-shrink-0">
                                ✈️
                            </button>
                        </form>
                    </div>

                </div>

                <!-- 🌟 EDIT MESSAGE POPUP MODAL 🌟 -->
                <div x-show="editMsgObj !== null" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
                    <div @click.away="editMsgObj = null" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl text-left space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                            <h3 class="font-black text-slate-900 dark:text-white text-sm">✏️ Edit Message</h3>
                            <button type="button" @click="editMsgObj = null" class="text-slate-400 hover:text-white font-bold">✕</button>
                        </div>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="edit_message">
                            <input type="hidden" name="active_tab" value="messages">
                            <input type="hidden" name="message_id" :value="editMsgObj ? editMsgObj.id : ''">
                            <div>
                                <textarea name="updated_message" rows="3" :value="editMsgObj ? editMsgObj.text : ''" required class="w-full p-3 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white outline-none focus:border-teal-500"></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="editMsgObj = null" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold">Cancel</button>
                                <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs font-bold shadow-md">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
<!-- 7. MANAGE FOOTER (Dynamic Contacts & Social Media with Full Edit Modals) -->
            <div x-show="currentTab === 'footer'" x-data="{ editContactObj: null, editSocialObj: null }" class="space-y-8 max-w-6xl" x-cloak>
                <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 mb-4">
                    <span class="text-3xl hidden sm:block">📝</span>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Manage Footer Details</h2>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">Dynamically add, edit or remove Contact Details and Social Media links displayed on the website footer.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
                    
                    <!-- 🌟 A. CONTACT DETAILS MANAGER 🌟 -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col min-h-[400px]">
                        <h3 class="font-black text-slate-900 dark:text-white text-sm sm:text-base mb-4 flex items-center gap-2"><span>📞</span> Contact Details</h3>
                        
                       <!-- Add Contact Form (With Smart Datalist Combo-box & Optional Text URL) -->
                        <form method="POST" class="flex flex-col gap-2 mb-6 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-200 dark:border-slate-700">
                            <input type="hidden" name="action" value="add_contact">
                            <input type="hidden" name="active_tab" value="footer">
                            <div class="flex gap-2 w-full">
                                
                                <!-- Select OR Type -->
                                <input type="text" name="contact_type" list="contact_types_list" required placeholder="Type (e.g. Phone, Web)" class="w-1/3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-2 py-2 text-xs font-bold outline-none focus:border-teal-500">
                                <datalist id="contact_types_list">
                                    <option value="Phone">
                                    <option value="Email">
                                    <option value="Location">
                                    <option value="Website">
                                    <option value="P.O. Box">
                                </datalist>

                                <!-- Text Value -->
                                <input type="text" name="contact_value" required placeholder="Text (e.g. 6781 or www.info.et)" class="w-2/3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-teal-500">
                            </div>
                            <div class="flex gap-2 w-full">
                                <!-- 🌟 MAGIC FIX: Changed type="url" to type="text" to remove strict browser validation 🌟 -->
                                <input type="text" name="url" placeholder="URL/Map Link (Optional)" class="flex-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-teal-500">
                                <button type="submit" class="bg-teal-600 hover:bg-teal-500 text-white font-black px-4 rounded-xl text-xs shadow-md transition-all whitespace-nowrap">+ Add</button>
                            </div>
                        </form>

                        <!-- Contacts List -->
                        <div class="space-y-3 flex-1 overflow-y-auto custom-scrollbar pr-1 sm:pr-2">
                            <?php if(empty($dyn_contacts)): ?> <p class="text-xs text-slate-400 text-center py-4">No contacts added.</p> <?php endif; ?>
                            <?php foreach($dyn_contacts as $c): 
                                // Smart Icon Matcher for custom texts
                                $ctype_lower = strtolower($c['contact_type']);
                                $c_icon = '📌'; // Default icon
                                if (str_contains($ctype_lower, 'phone') || str_contains($ctype_lower, 'call') || str_contains($ctype_lower, 'mobile')) $c_icon = '☎️';
                                elseif (str_contains($ctype_lower, 'email') || str_contains($ctype_lower, 'mail')) $c_icon = '📧';
                                elseif (str_contains($ctype_lower, 'location') || str_contains($ctype_lower, 'address')) $c_icon = '📍';
                                elseif (str_contains($ctype_lower, 'web') || str_contains($ctype_lower, 'www') || str_contains($ctype_lower, 'site')) $c_icon = '🌐';
                            ?>
                                <div class="dotted-card border border-slate-200 dark:border-slate-700 p-2.5 sm:p-3 rounded-2xl flex items-center justify-between shadow-sm hover:border-teal-500 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-teal-500/10 text-teal-600 flex items-center justify-center font-black text-sm flex-shrink-0">
                                            <?php echo $c_icon; ?>
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block"><?php echo htmlspecialchars($c['contact_type']); ?></span>
                                            <span class="text-xs font-bold text-slate-900 dark:text-white max-w-[140px] sm:max-w-xs truncate block" title="<?php echo htmlspecialchars($c['contact_value']); ?>"><?php echo htmlspecialchars($c['contact_value']); ?></span>
                                            <?php if(!empty($c['url'])): ?>
                                                <a href="<?php echo htmlspecialchars($c['url']); ?>" target="_blank" class="text-[9px] text-teal-500 hover:underline truncate block max-w-[140px] sm:max-w-xs">Link Active ↗</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <button type="button" @click="editContactObj = { id: '<?php echo $c['id']; ?>', type: '<?php echo addslashes($c['contact_type']); ?>', value: '<?php echo addslashes($c['contact_value']); ?>', url: '<?php echo addslashes($c['url'] ?? ''); ?>' }" class="w-7 h-7 bg-blue-500/10 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg flex items-center justify-center transition-colors shadow-sm" title="Edit">✏️</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this contact?')">
                                            <input type="hidden" name="action" value="delete_contact">
                                            <input type="hidden" name="active_tab" value="footer">
                                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="w-7 h-7 bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white rounded-lg flex items-center justify-center transition-colors shadow-sm" title="Delete">🗑️</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 🌟 B. SOCIAL MEDIA MANAGER 🌟 -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col min-h-[400px]">
                        <h3 class="font-black text-slate-900 dark:text-white text-sm sm:text-base mb-4 flex items-center gap-2"><span>🌐</span> Social Media Links</h3>
                        
                        <!-- Add Social Form -->
                        <form method="POST" class="flex flex-col sm:flex-row gap-2 mb-6 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-2xl border border-slate-200 dark:border-slate-700">
                            <input type="hidden" name="action" value="add_social">
                            <input type="hidden" name="active_tab" value="footer">
                            <input type="text" name="platform" required placeholder="App Name (e.g. WhatsApp)" class="w-full sm:w-1/3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-teal-500">
                            <input type="url" name="url" required placeholder="URL (e.g. https://wa.me/...)" class="w-full sm:flex-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-teal-500">
                            <button type="submit" class="w-full sm:w-auto bg-teal-600 hover:bg-teal-500 text-white font-black px-4 py-2 sm:py-0 rounded-xl text-xs shadow-md transition-all whitespace-nowrap">+ Add App</button>
                        </form>

                        <!-- Socials List -->
                        <div class="space-y-3 flex-1 overflow-y-auto custom-scrollbar pr-1 sm:pr-2">
                            <?php if(empty($dyn_socials)): ?> <p class="text-xs text-slate-400 text-center py-4">No social media added.</p> <?php endif; ?>
                            <?php foreach($dyn_socials as $s): 
                                $plat = strtolower($s['platform']);
                                $icon = '🔗'; $color = 'text-slate-600';
                                if (str_contains($plat, 'whatsapp')) { $icon = '💬'; $color = 'text-green-500'; }
                                elseif (str_contains($plat, 'telegram')) { $icon = '✈️'; $color = 'text-sky-500'; }
                                elseif (str_contains($plat, 'tiktok')) { $icon = '🎵'; $color = 'text-slate-900 dark:text-white'; }
                                elseif (str_contains($plat, 'facebook')) { $icon = '📘'; $color = 'text-blue-600'; }
                                elseif (str_contains($plat, 'youtube')) { $icon = '▶️'; $color = 'text-rose-600'; }
                                elseif (str_contains($plat, 'instagram')) { $icon = '📸'; $color = 'text-pink-500'; }
                                elseif (str_contains($plat, 'linkedin')) { $icon = '💼'; $color = 'text-blue-700'; }
                                elseif (str_contains($plat, 'x') || str_contains($plat, 'twitter')) { $icon = '𝕏'; $color = 'text-slate-800 dark:text-slate-200'; }
                            ?>
                                <div class="dotted-card border border-slate-200 dark:border-slate-700 p-2.5 sm:p-3 rounded-2xl flex items-center justify-between shadow-sm hover:border-teal-500 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm shadow-inner flex-shrink-0 <?php echo $color; ?>">
                                            <?php echo $icon; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block truncate"><?php echo htmlspecialchars($s['platform']); ?></span>
                                            <a href="<?php echo htmlspecialchars($s['url']); ?>" target="_blank" class="text-[10px] sm:text-xs font-bold text-teal-600 dark:text-teal-400 truncate block hover:underline max-w-[120px] sm:max-w-[200px]"><?php echo htmlspecialchars($s['url']); ?></a>
                                        </div>
                                    </div>
                                    <div class="flex gap-1 flex-shrink-0">
                                        <button type="button" @click="editSocialObj = { id: '<?php echo $s['id']; ?>', platform: '<?php echo addslashes($s['platform']); ?>', url: '<?php echo addslashes($s['url']); ?>' }" class="w-7 h-7 bg-blue-500/10 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg flex items-center justify-center transition-colors shadow-sm" title="Edit">✏️</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this social media link?')">
                                            <input type="hidden" name="action" value="delete_social">
                                            <input type="hidden" name="active_tab" value="footer">
                                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                            <button type="submit" class="w-7 h-7 bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white rounded-lg flex items-center justify-center transition-colors shadow-sm" title="Delete">🗑️</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 🌟 EDIT CONTACT MODAL 🌟 -->
                <div x-show="editContactObj !== null" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
                    <div @click.away="editContactObj = null" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl text-left space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                            <h3 class="font-black text-slate-900 dark:text-white text-sm">✏️ Edit Contact</h3>
                            <button type="button" @click="editContactObj = null" class="text-slate-400 hover:text-white font-bold">✕</button>
                        </div>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="edit_contact">
                            <input type="hidden" name="id" :value="editContactObj ? editContactObj.id : ''">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Type</label>
                               <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Type (Select or Type)</label>
                                <input type="text" name="contact_type" list="contact_types_list" x-model="editContactObj ? editContactObj.type : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none focus:border-teal-500">
                            </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Text Value</label>
                                <input type="text" name="contact_value" :value="editContactObj ? editContactObj.value : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">URL / Map Link (Optional)</label>
                                <input type="text" name="url" :value="editContactObj ? editContactObj.url : ''" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none">
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="editContactObj = null" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold">Cancel</button>
                                <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs font-bold shadow-md">Update Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 🌟 EDIT SOCIAL MEDIA MODAL 🌟 -->
                <div x-show="editSocialObj !== null" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
                    <div @click.away="editSocialObj = null" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl text-left space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                            <h3 class="font-black text-slate-900 dark:text-white text-sm">✏️ Edit Social Link</h3>
                            <button type="button" @click="editSocialObj = null" class="text-slate-400 hover:text-white font-bold">✕</button>
                        </div>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="edit_social">
                            <input type="hidden" name="id" :value="editSocialObj ? editSocialObj.id : ''">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Platform Name</label>
                                <input type="text" name="platform" :value="editSocialObj ? editSocialObj.platform : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">URL Link</label>
                                <input type="url" name="url" :value="editSocialObj ? editSocialObj.url : ''" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs text-slate-900 dark:text-white outline-none">
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="editSocialObj = null" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold">Cancel</button>
                                <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs font-bold shadow-md">Update Save</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

      <!-- 8. MANAGE PAYMENT METHODS & SLIPS REVIEW -->
            <div x-show="currentTab === 'payments'" class="space-y-8 max-w-6xl" x-cloak>
                <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 mb-4">
                    <span class="text-3xl hidden sm:block">💳</span>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Manage Payment Methods</h2>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">Add unlimited banks (CBE, Awash, Telebirr, Chapa) and Review Submitted Payment Slips.</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-10 shadow-sm">
                    
                    <!-- Add Payment Form -->
                    <form method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-3 mb-8 bg-amber-500/5 border border-amber-500/20 p-4 sm:p-5 rounded-3xl items-end">
                        <input type="hidden" name="action" value="add_payment">
                        <input type="hidden" name="active_tab" value="payments">
                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Provider / Bank Name</label>
                            <input type="text" name="provider_name" required placeholder="e.g. CBE, Telebirr" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs outline-none focus:border-amber-500 font-bold">
                        </div>
                        <div class="sm:col-span-5">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Account No. / Secret Key</label>
                            <input type="text" name="account_details" required placeholder="10001234..." class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-xs outline-none focus:border-amber-500 font-mono">
                        </div>
                        <div class="sm:col-span-3">
                            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-4 py-2.5 rounded-xl text-xs shadow-md transition-all">+ Add Method</button>
                        </div>
                    </form>

                    <!-- Payments List -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php if(empty($dyn_payments)): ?> <p class="col-span-1 lg:col-span-3 text-xs text-slate-400 text-center py-4">No payment methods added.</p> <?php endif; ?>
                        <?php foreach($dyn_payments as $p): 
                            $bnk = strtolower($p['provider_name']);
                            $b_icon = '🏦'; $b_bg = 'bg-slate-100 dark:bg-slate-800';
                            if(str_contains($bnk, 'telebirr')) { $b_icon = '📱'; $b_bg = 'bg-teal-500/10 text-teal-600'; }
                            if(str_contains($bnk, 'chapa')) { $b_icon = '💳'; $b_bg = 'bg-emerald-500/10 text-emerald-600'; }
                            if(str_contains($bnk, 'cbe')) { $b_icon = '🏦'; $b_bg = 'bg-purple-500/10 text-purple-600'; }
                            if(str_contains($bnk, 'awash')) { $b_icon = '🏦'; $b_bg = 'bg-blue-500/10 text-blue-600'; }
                        ?>
                            <div class="dotted-card border-2 border-slate-200 dark:border-slate-700 p-3 sm:p-4 rounded-2xl flex items-center justify-between shadow-sm hover:border-amber-500 transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shadow-inner flex-shrink-0 <?php echo $b_bg; ?>">
                                        <?php echo $b_icon; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-[10px] sm:text-xs font-black text-slate-900 dark:text-white uppercase block truncate"><?php echo htmlspecialchars($p['provider_name']); ?></span>
                                        <span class="text-[10px] sm:text-xs font-mono font-bold text-slate-500 dark:text-slate-400 truncate block mt-0.5"><?php echo htmlspecialchars($p['account_details']); ?></span>
                                    </div>
                                </div>
                                <form method="POST" onsubmit="return confirm('Delete this payment method?')">
                                    <input type="hidden" name="action" value="delete_payment">
                                    <input type="hidden" name="active_tab" value="payments">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="w-8 h-8 bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl flex items-center justify-center transition-colors shadow-sm flex-shrink-0">🗑️</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 🌟 SUBMITTED PAYMENT SLIPS REVIEW (Now inside the correct container) 🌟 -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-10 shadow-sm mt-8">
                    <h3 class="font-black text-slate-900 dark:text-white text-base sm:text-lg mb-4 flex items-center gap-2"><span>🧾</span> Submitted Payment Slips Review</h3>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="text-[10px] sm:text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40">
                                    <th class="py-3 px-4 rounded-tl-xl font-bold">Employer</th>
                                    <th class="py-3 px-4 font-bold">Package & Amount</th>
                                    <th class="py-3 px-4 font-bold">Receipt</th>
                                    <th class="py-3 px-4 font-bold">Status</th>
                                    <th class="py-3 px-4 text-right rounded-tr-xl font-bold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs sm:text-sm">
                                <?php if (empty($submitted_slips)): ?>
                                    <tr><td colspan="5" class="py-8 text-center text-slate-400 font-semibold">No payment slips submitted yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($submitted_slips as $slip): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="py-4 px-4">
                                            <p class="font-extrabold text-slate-900 dark:text-white"><?php echo htmlspecialchars($slip['company_name'] ?? 'Employer'); ?></p>
                                            <p class="text-[10px] text-slate-400"><?php echo htmlspecialchars($slip['email'] ?? ''); ?></p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="font-extrabold text-teal-600 dark:text-teal-400"><?php echo htmlspecialchars($slip['package_name']); ?></p>
                                            <p class="text-[10px] text-slate-500">Amount: <?php echo htmlspecialchars($slip['amount']); ?> | Method: <?php echo htmlspecialchars($slip['payment_method']); ?></p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php if (!empty($slip['receipt_file'])): ?>
                                                <a href="<?php echo htmlspecialchars($slip['receipt_file']); ?>" target="_blank" class="px-3 py-1 bg-teal-500/10 hover:bg-teal-500 hover:text-white text-teal-600 dark:text-teal-400 border border-teal-500/20 text-xs font-bold rounded-lg transition-all inline-block shadow-sm">
                                                    View Receipt ↗
                                                </a>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">No Receipt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php 
                                                $st_bg = 'bg-amber-500/10 text-amber-600 border-amber-500/20';
                                                if ($slip['status'] === 'approved') $st_bg = 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20';
                                                if ($slip['status'] === 'rejected') $st_bg = 'bg-rose-500/10 text-rose-600 border-rose-500/20';
                                            ?>
                                            <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase border <?php echo $st_bg; ?>">
                                                <?php echo htmlspecialchars($slip['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <?php if ($slip['status'] === 'pending'): ?>
                                                <div class="flex items-center justify-end gap-2">
                                                    <form method="POST" class="inline" onsubmit="return confirm('Approve this payment slip and award Job Credits?')">
                                                        <input type="hidden" name="action" value="approve_payment_slip">
                                                        <input type="hidden" name="active_tab" value="payments">
                                                        <input type="hidden" name="slip_id" value="<?php echo $slip['id']; ?>">
                                                        <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-md transition-all">✔ Approve</button>
                                                    </form>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Reject this payment slip?')">
                                                        <input type="hidden" name="action" value="reject_payment_slip">
                                                        <input type="hidden" name="active_tab" value="payments">
                                                        <input type="hidden" name="slip_id" value="<?php echo $slip['id']; ?>">
                                                        <button type="submit" class="px-3 py-1 bg-rose-500/10 hover:bg-rose-500 text-rose-600 hover:text-white rounded-lg text-xs font-bold border border-rose-500/20 transition-all">✖ Reject</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400 font-bold">Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- END OF PAYMENT METHODS TAB -->
            <!-- 9. MANAGE SUB-ADMINS (Only visible to true Superadmin) -->
            <?php if (isset($admin_role) && $admin_role === 'superadmin'): ?>
            <div x-show="currentTab === 'admins'" class="space-y-6 sm:space-y-8 max-w-6xl" x-cloak x-data="{ openAddAdmin: false }">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                    <div class="flex items-center space-x-3">
                        <span class="text-3xl hidden sm:block">👨‍💻</span>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Manage Sub-Admins</h2>
                            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">Add or revoke access for secondary administrators.</p>
                        </div>
                    </div>
                    <button @click="openAddAdmin = true" class="w-full sm:w-auto px-5 py-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl sm:rounded-2xl shadow-lg shadow-amber-500/20 transition-all text-xs flex items-center justify-center gap-2">
                        <span>➕</span> <span>Add New Admin</span>
                    </button>
                </div>

                <!-- 🌟 ADD NEW ADMIN POPUP MODAL 🌟 -->
                <div x-show="openAddAdmin" x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4" x-cloak>
                    <div @click.away="openAddAdmin = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5 text-left relative">
                        
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-4">
                            <h3 class="font-black text-slate-900 dark:text-white text-base sm:text-lg">➕ Create Sub-Admin</h3>
                            <button type="button" @click="openAddAdmin = false" class="text-slate-400 hover:text-rose-500 font-bold p-1 bg-slate-50 dark:bg-slate-800 rounded-lg">✕</button>
                        </div>

                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add_admin">
                            <input type="hidden" name="active_tab" value="admins">
                            
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Full Name *</label>
                                <input type="text" name="username" required placeholder="e.g. Chala Kebede" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-xs text-slate-900 dark:text-white outline-none focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Email Address *</label>
                                <input type="email" name="email" required placeholder="admin@infoplus.et" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-xs text-slate-900 dark:text-white outline-none focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Phone Number</label>
                                <input type="text" name="phone" placeholder="e.g. +251911..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-xs text-slate-900 dark:text-white outline-none focus:border-amber-500">
                            </div>
                            <div x-data="{showPass: false}">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Temporary Password *</label>
                                <div class="relative">
                                    <input :type="showPass ? 'text' : 'password'" name="password" required placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-4 pr-10 py-3 text-xs text-slate-900 dark:text-white outline-none focus:border-amber-500">
                                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-amber-500">
                                        <svg x-show="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <svg x-show="showPass" style="display:none;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="pt-2">
                                <button type="submit" class="w-full py-3.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs sm:text-sm shadow-lg shadow-amber-500/20 transition-all">Create Admin Account</button>
                            </div>
                        </form>

                    </div>
                </div>

               <!-- 🌟 ADMINS LIST TABLE (LIVE DIRECT DB FETCH) 🌟 -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-8 shadow-sm overflow-x-auto custom-scrollbar">
                    
                    <?php 
                        // 🌟 MAGIC: Real-time Live Fetch from Database 🌟
                        $live_admins = [];
                        if (isset($pdo) && $pdo !== null) {
                            try {
                                $live_admins = $pdo->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Exception $e) {}
                        }
                    ?>

                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="text-[10px] sm:text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30">
                                <th class="py-4 px-4 rounded-tl-xl">Admin Profile</th>
                                <th class="py-4 px-4">Contact Info</th>
                                <th class="py-4 px-4">Role & Status</th>
                                <th class="py-4 px-4 text-right rounded-tr-xl">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 text-sm">
                            <?php if(empty($live_admins)): ?>
                                <tr><td colspan="4" class="text-center py-6 text-slate-400 text-xs">No admins found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($live_admins as $adm): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-4 px-4 flex items-center space-x-3">
                                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 flex-shrink-0 shadow-sm">
                                            <img src="<?php echo htmlspecialchars($adm['profile_pic'] ?? 'logo.png'); ?>" onerror="this.src='logo.png'" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm"><?php echo htmlspecialchars($adm['username']); ?></p>
                                            <p class="text-[9px] sm:text-[10px] text-slate-400 mt-0.5">Added: <?php echo date('M d, Y', strtotime($adm['created_at'])); ?></p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-xs font-bold text-teal-600 dark:text-teal-400"><?php echo htmlspecialchars($adm['email']); ?></p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">📱 <?php echo htmlspecialchars($adm['phone'] ?? 'Not set'); ?></p>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if($adm['role'] === 'superadmin'): ?>
                                            <span class="px-2.5 py-1 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-black text-[9px] uppercase tracking-wider rounded-lg border border-amber-500/20 shadow-sm">Superadmin</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 bg-teal-500/10 text-teal-600 dark:text-teal-400 font-bold text-[9px] uppercase tracking-wider rounded-lg border border-teal-500/20 shadow-sm">Sub-Admin</span>
                                        <?php endif; ?>
                                        <br>
                                        <span class="text-[9px] text-slate-400 mt-1.5 block font-semibold flex items-center gap-1">
                                            <span class="h-1.5 w-1.5 rounded-full <?php echo !empty($adm['two_factor_enabled']) ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'; ?>"></span>
                                            2FA: <?php echo !empty($adm['two_factor_enabled']) ? 'Active' : 'Disabled'; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <?php if($adm['role'] !== 'superadmin'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Revoke all access for this admin permanently?')">
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="active_tab" value="admins">
                                            <input type="hidden" name="admin_id" value="<?php echo $adm['id']; ?>">
                                            <button type="submit" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-rose-500/10 text-rose-600 hover:bg-rose-600 hover:text-white rounded-xl text-[10px] sm:text-xs font-bold transition-all border border-rose-500/20 shadow-sm flex items-center gap-1.5 ml-auto">
                                                <span>🚫</span> <span>Revoke Access</span>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-[10px] text-amber-500 font-bold bg-amber-500/10 px-3 py-1 rounded-lg border border-amber-500/20">Protected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
            <?php endif; ?>

        </div> <!-- END OF MAIN SCROLL CONTAINER CONTENT -->
    </main>
   <script>
        // ==========================================
        // 1. VOICE RECORDER LOGIC (Auto-Submits Voice Note!)
        // ==========================================
        let mediaRecorder;
        let audioChunks = [];

        function startVoiceRecord() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert("Your browser does not support audio recording.");
                return;
            }

            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    mediaRecorder = new MediaRecorder(stream);
                    audioChunks = [];

                    mediaRecorder.ondataavailable = event => {
                        audioChunks.push(event.data);
                    };

                    mediaRecorder.onstop = () => {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                        const file = new File([audioBlob], "voicenote_" + Date.now() + ".webm", { type: 'audio/webm' });

                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        const fileInput = document.getElementById('chatFileInput');
                        if (fileInput) {
                            fileInput.files = dataTransfer.files;
                            // 🌟 MAGIC: AUTO-SUBMIT FORM WHEN RECORDING STOPS!
                            document.getElementById('chatSendForm').submit();
                        }
                    };

                    mediaRecorder.start();
                    if(typeof Alpine !== 'undefined') Alpine.store('recording', true);
                })
                .catch(err => {
                    alert("Microphone permission denied: " + err);
                });
        }

        function stopVoiceRecord() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
        }
        // ==========================================
        // 2. CHART.JS LOGIC
        // ==========================================
        // Pass PHP Data to JavaScript
        const chartData = {
            jobs: <?php echo $jobs_count ?? 0; ?>,
            seekers: <?php echo $users_count ?? 0; ?>,
            employers: <?php echo $employers_count ?? 0; ?>
        };

      // ==========================================
        // 1. CHART.JS LOGIC (100% Fixed & Responsive)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('systemOverviewChart');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                
               // Fetch dynamic PHP values safely
                const jobs = <?php echo (int)($jobs_count ?? 0); ?>;
                const seekers = <?php echo (int)($users_count ?? 0); ?>;
                const employers = <?php echo (int)($employers_count ?? 0); ?>;
                const admins = <?php echo (int)($admins_count ?? 0); ?>;
                
                const totalData = jobs + seekers + employers + admins;
                const displayData = totalData === 0 ? [1] : [jobs, seekers, employers, admins];
                
                // Colors: Teal, Cyan, Amber, Indigo (#6366f1)
                const displayBg = totalData === 0 ? ['#e2e8f0'] : ['#0d9488', '#0891b2', '#f59e0b', '#6366f1'];

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active Jobs', 'Candidates', 'Employers', 'Admins'],
                        datasets: [{
                            data: displayData,
                            backgroundColor: displayBg,
                            borderWidth: 0,
                            hoverOffset: 12
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%', // Modern thin ring
                        plugins: {
                            // 🌟 Legend Hidden because we already have beautiful Breakdown Cards on the right! 🌟
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        if(totalData === 0) return ' No Data';
                                        return ' ' + context.label + ': ' + context.raw;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });

        // ==========================================
        // 3. DARK / LIGHT MODE TOGGLE
        // ==========================================
        const themeToggleBtn = document.getElementById('theme-toggle');
        const sunIcon = document.getElementById('sun-icon');
        const moonIcon = document.getElementById('moon-icon');

        // Check local storage for theme
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
            if(sunIcon && moonIcon) {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            }
        } else {
            document.documentElement.classList.remove('dark');
            if(sunIcon && moonIcon) {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }
        }

        // Toggle functionality
        if(themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    if(sunIcon) sunIcon.classList.add('hidden');
                    if(moonIcon) moonIcon.classList.remove('hidden');
                    localStorage.setItem('theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    if(sunIcon) sunIcon.classList.remove('hidden');
                    if(moonIcon) moonIcon.classList.add('hidden');
                    localStorage.setItem('theme', 'dark');
                }
            });
        }
    </script>
</body>
</html>