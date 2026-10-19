-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 19, 2026 at 12:44 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `infoplus_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT '+251947395224',
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin') DEFAULT 'admin',
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `two_factor_method` enum('email','whatsapp','telegram') DEFAULT 'email',
  `backup_codes` text,
  `otp_code` varchar(6) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'logo.png',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `phone`, `password`, `role`, `two_factor_enabled`, `two_factor_method`, `backup_codes`, `otp_code`, `profile_pic`, `created_at`) VALUES
(1, 'SuperAdmin', 'tadesebiruk515@gmail.com', '+251947395224', '$2y$10$wJ9BqTj/N6c5/H3u7hV82.e8/5z3Ea0K4.4e8g1234567890abcdef', 'superadmin', 0, 'email', NULL, NULL, 'uploads/avatars/avatar_1786533183.png', '2026-08-11 08:04:51'),
(4, 'dedefo', 'tadbir795@gmail.com', '0904542589', '$2y$10$8FtHZBaJOUCEuq/m69wb2usX26K1MIbdG6xGbHcfmBiRr4DoxlJOC', 'admin', 1, 'email', NULL, '628325', 'uploads/avatars/admin_avatar_1788254114_4.png', '2026-09-01 08:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

DROP TABLE IF EXISTS `admin_sessions`;
CREATE TABLE IF NOT EXISTS `admin_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `is_trusted` tinyint(1) DEFAULT '1',
  `last_active` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `admin_id`, `device_name`, `ip_address`, `is_trusted`, `last_active`) VALUES
(1, 1, 'Chrome / Windows PC (This Device)', '127.0.0.1', 1, '2026-08-11 08:22:30'),
(2, 1, 'Chrome Mobile / Android Device', '10.250.62.235', 1, '2026-08-11 08:22:30');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('pending','reviewed','accepted','rejected') DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `user_id`, `status`, `applied_at`) VALUES
(4, 23, 57, 'pending', '2026-09-01 05:50:47'),
(5, 26, 58, 'pending', '2026-10-09 08:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
CREATE TABLE IF NOT EXISTS `chats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_type` enum('admin','employer','user') NOT NULL,
  `sender_id` int NOT NULL,
  `receiver_type` enum('admin','employer','user') NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dynamic_contacts`
--

DROP TABLE IF EXISTS `dynamic_contacts`;
CREATE TABLE IF NOT EXISTS `dynamic_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contact_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dynamic_contacts`
--

INSERT INTO `dynamic_contacts` (`id`, `contact_type`, `contact_value`, `url`) VALUES
(1, 'Phone', '6781', NULL),
(2, 'Email', 'infopluscallcenter@gmail.com', NULL),
(3, 'Location', 'Bole plaza', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dynamic_payments`
--

DROP TABLE IF EXISTS `dynamic_payments`;
CREATE TABLE IF NOT EXISTS `dynamic_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_details` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dynamic_payments`
--

INSERT INTO `dynamic_payments` (`id`, `provider_name`, `account_details`) VALUES
(1, 'cbe', '1000643665003'),
(2, 'telebirr', '0947395224');

-- --------------------------------------------------------

--
-- Table structure for table `dynamic_socials`
--

DROP TABLE IF EXISTS `dynamic_socials`;
CREATE TABLE IF NOT EXISTS `dynamic_socials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `platform` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dynamic_socials`
--

INSERT INTO `dynamic_socials` (`id`, `platform`, `url`) VALUES
(1, 'Telegram', 'https://t.me/infopluscallcenter'),
(2, 'TikTok', 'https://www.tiktok.com/@infopluscallcenter'),
(3, 'Facebook', 'https://facebook.com/infoplus'),
(4, 'LinkedIn', 'https://linkedin.com/company/infoplus'),
(5, 'Instagram', 'https://www.instagram.com/infopluscallcenter?igsh=MXhkczU4cWxwZWF6cw=='),
(6, 'YouTube', 'https://youtube.com/@infoplus');

-- --------------------------------------------------------

--
-- Table structure for table `employers`
--

DROP TABLE IF EXISTS `employers`;
CREATE TABLE IF NOT EXISTS `employers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(100) NOT NULL,
  `contact_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `job_credits` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `otp_code` varchar(6) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employers`
--

INSERT INTO `employers` (`id`, `company_name`, `contact_name`, `email`, `phone`, `password`, `logo_url`, `status`, `job_credits`, `created_at`, `otp_code`, `two_factor_enabled`) VALUES
(16, 'Dashen Bank', 'Biruk1', 'ayotubuki69@gmail.com', '+251 25 362 536', '$2y$10$qW8FJjQMchsQ21aT1AXYmuIuKumxHshPMRuOj14VrcuF1B/8EGn8y', NULL, 'approved', 0, '2026-10-09 07:30:16', NULL, 0),
(17, 'Bdkk', 'Hzkk', 'tadbir071@gmail.com', '+251936251213', '$2y$10$X9xAfbXTB7IcHTDfjg8IBei3LQSrfZxGN7MNiVg9GrYr4xrjxsFuC', NULL, 'approved', 0, '2026-10-09 08:08:58', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `employer_payments`
--

DROP TABLE IF EXISTS `employer_payments`;
CREATE TABLE IF NOT EXISTS `employer_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employer_id` int NOT NULL,
  `package_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receipt_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employer_payments`
--

INSERT INTO `employer_payments` (`id`, `employer_id`, `package_name`, `amount`, `payment_method`, `receipt_file`, `note`, `status`, `created_at`) VALUES
(1, 11, 'Info+ Plus — 5 job posts', 'ETB 6,000', 'cbe', 'uploads/payments/1788458589_receipt_6a99b65d97615.jpg', NULL, 'approved', '2026-09-03 18:03:09'),
(2, 11, 'Info+ Plus — 5 job posts', 'ETB 6,000', 'cbe', 'uploads/payments/1788460276_receipt_6a99bcf4e731c.jpg', NULL, 'rejected', '2026-09-03 18:31:16'),
(3, 11, 'Info+ Lite — 1 job post', 'ETB 1,500', 'cbe', 'uploads/payments/1788460429_receipt_6a99bd8d0617b.jpg', NULL, 'rejected', '2026-09-03 18:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `home_slides`
--

DROP TABLE IF EXISTS `home_slides`;
CREATE TABLE IF NOT EXISTS `home_slides` (
  `id` int NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `badge_text` varchar(100) NOT NULL,
  `title_text` varchar(255) NOT NULL,
  `badge_color` varchar(50) DEFAULT 'bg-teal-500',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `home_slides`
--

INSERT INTO `home_slides` (`id`, `image_path`, `badge_text`, `title_text`, `badge_color`, `created_at`) VALUES
(1, 'images/slide1.png', 'Shortcode 6781', 'Apply to Top Jobs via Phone Hotline', 'bg-teal-500', '2026-08-11 10:20:15'),
(2, 'images/slide2.png', 'Enterprise Matchmaking', 'Connect directly with Verified Employers', 'bg-amber-500', '2026-08-11 10:20:15'),
(3, 'images/slide3.png', 'Verified Listings', 'Fast, Secure and Trusted Job Placement', 'bg-emerald-500', '2026-08-11 10:20:15');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employer_id` int DEFAULT NULL,
  `company_name` varchar(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `job_type` enum('Full Time','Part Time','Contract','Internship','Remote') NOT NULL,
  `experience` varchar(30) NOT NULL,
  `location` varchar(100) NOT NULL,
  `salary` varchar(50) DEFAULT 'Negotiable',
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `status` enum('active','expired') DEFAULT 'active',
  `is_top_company` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logo` varchar(255) DEFAULT 'logo.png',
  `deadline` date DEFAULT NULL,
  `career_level` varchar(100) DEFAULT 'Mid Level (2-5 years)',
  `positions` int DEFAULT '1',
  `how_to_apply` text,
  `attachment_file` varchar(255) DEFAULT NULL,
  `views` int DEFAULT '0',
  `last_view_boost_date` date DEFAULT NULL,
  `selected_package` varchar(50) DEFAULT 'free',
  `payment_status` varchar(50) DEFAULT 'approved',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `employer_id`, `company_name`, `title`, `category`, `job_type`, `experience`, `location`, `salary`, `description`, `requirements`, `status`, `is_top_company`, `created_at`, `logo`, `deadline`, `career_level`, `positions`, `how_to_apply`, `attachment_file`, `views`, `last_view_boost_date`, `selected_package`, `payment_status`) VALUES
(11, NULL, 'coca cola', 'driver\'s', 'Sales and Marketing', 'Full Time', 'Entry Level (Fresh Graduate)', 'addis abeba', '30,000', '**Infoplus**\r\n\r\n**ኃላፊ**\r\n\r\n**Deadline:** 2026-06-30\r\n**Experience:** 4+ yrs\r\n\r\nA supervisory role requiring over four years of relevant experience and strong presentation abilities, based in Addis Ababa.\r\n\r\n* Provide presentations as required\r\n* Leverage 4+ years of experience in automotive/maintenance contexts\r\n* Operate within Addis Ababa workplace environment\r\n* Call: 6781', '4+ years of experience in automotive/maintenance contexts', 'active', 1, '2026-08-12 09:35:16', 'uploads/logos/1786527316_logo_profile.png', '2026-10-26', 'Entry Level (Fresh Graduate)', 22, 'call 6781', 'uploads/attachments/1786527316_attach_udacity_certs.pdf', 253, '2026-10-19', 'free', 'approved'),
(12, NULL, 'Hiwot clinic', 'ክሊኒካል ነርስ (Clinical Nurse)', 'Health Care', 'Full Time', 'Junior Level (1-3 years)', 'addis abeba', '50,000', 'ክሊኒካል ነርስ (Clinical Nurse)\r\nየስራ ልምድ\r\nቦታ፡ 2 (ሴቶች)\r\nበBSc ዲግሪ እና 2 ዓመት የስራ ልምድ ያላቸው\r\nበLevel 4 የተመረቁ/የተመዘገቡ COC ያላቸው\r\nአድራሻ መስጠት እና ስራውን መጀመር የሚችሉ\r\nየደመወዝ በስምምነት', 'በBSc ዲግሪ እና 2 ዓመት የስራ ልምድ ያላቸው\r\nበLevel 4 የተመረቁ/የተመዘገቡ COC ያላቸው', 'active', 0, '2026-08-12 09:43:21', 'uploads/logos/1786527801_logo_profile7.png', '2026-10-23', 'Junior Level (1-3 years)', 5, 'called 6781', 'uploads/attachments/1786527801_attach_project3.jpg', 252, '2026-10-19', 'free', 'approved'),
(15, NULL, 'Dashn Bank', 'የአካውንቲንግ ሰራተኛ', 'Accounting and Finance', 'Full Time', 'Senior Accountant 2 ዓመት የስራ ልም', 'addis abeba', 'በስምምነት', 'የአካውንቲንግ ሰራተኛ\r\nየስራ ልምድ\r\nቦታ፡ 2 (ሴቶች)\r\nበAccounting and Finance BSc Degree\r\nSenior Accountant 2 ዓመት የስራ ልምድ\r\nከላይ በላይ የሆነ ልምድ ያለው\r\nአድራሻ መስጠት እና ስራውን መጀመር የሚችሉ\r\nየደመወዝ በስምምነት', 'Senior Accountant 2 ዓመት የስራ ልምድ', 'active', 1, '2026-08-12 10:17:47', 'uploads/logos/1786529867_logo_aaa.png', '2026-10-19', 'Senior Accountant 2 ዓመት የስራ ልምድ', 5, 'call 6781', 'uploads/attachments/1786529867_attach_Document2.pdf', 232, '2026-10-19', 'free', 'approved'),
(16, NULL, 'FT FURNITURE', 'የፈርኒቸር ሽያጭ (Sale\'s)', 'Sales and Marketing', 'Full Time', 'Entry Level (Fresh Graduate)', 'addis abeba', 'በስምምነት', '✅ የስራ መደብ 1: የፈርኒቸር ሽያጭ (Sale\'s)\r\n\r\n▪ የስራ ልምድ: 0 አመት / የስራ ልምድ ላላቸው ቅድሚያ እንሰጣለን\r\n\r\n• ጾታ: ወንድ / ሴት\r\n\r\n• ዕድሜ: 18+\r\n\r\n✅ የስራ መደብ 2. ካሸር (Casher)\r\n\r\n▪ የስራ ልምድ: 0 አመት\r\n\r\n• ዕድሜ: ሴት\r\n\r\n✅ የስራ መደብ 3. ፋይናንስ (Finance)\r\n\r\n▪ የስራ ልምድ: ሁለት ዓመት እና ከዚያ በላይ (2+)\r\n\r\n• ዕድሜ: ሴት\r\n\r\n✅ የስራ መደብ 4. ፈርኒቸር ዲዛይነር (Furniture Designer)\r\n\r\n▪ የስራ ልምድ: አንድ ዓመት እና ከዚያ በላይ (1+)\r\n\r\n• ዕድሜ: ወንድ ለማመልከት ላለው ሙያ call 6781', 'ሁለት ዓመት እና ከዚያ በላይ (2+', 'active', 1, '2026-08-12 22:36:07', 'logo.png', '2026-10-31', 'Entry Level (Fresh Graduate)', 10, 'CALL 6781', NULL, 296, '2026-10-19', 'free', 'approved'),
(18, NULL, 'Dashen Bank', 'Branch Business Relationship Manager-RMSME', 'Banking & Finance', 'Full Time', 'Senior level', 'South Addis Ababa District Branches', 'Negotiable', 'Dashen Bank South Addis Ababa District is pleased to announce a vacancy for the position of Branch Business Relationship Manager-RMSME. The job holder will be responsible for planning, organizing, leading, and controlling business development activities within the branch team. The role focuses on attracting, deepening, and managing financial relationships to achieve deposit and loan growth, drive business growth in the Retail & MSME market segment, and promote the Bank\'s products and services to enhance value-added relationships with existing customers.', '- BA Degree in Accounting, Marketing Management, Business Administration, Banking, Finance or related Business field.\r\n- Minimum of six (6) years of relevant experience.\r\n- In-depth understanding of Retail & MSME banking.\r\n- Knowledge and experience in modern sales and marketing practices in the financial services industry.\r\n- Strong negotiation skills to prospect and close business.\r\n- Strong networking skills for customer acquisition and retention.\r\n- Extensive banking background, including credit, risk management, and compliance requirements.\r\n- Ability to engage and influence senior-level leaders on key business priorities and initiatives.\r\n- Project management skills.\r\n- Strong leadership, coaching, customer insight, partnership engagement, and service excellence capabilities.\r\n- Ability to identify, assess, and mitigate business risks.\r\n- Digital savviness and ability to leverage digital technologies.\r\n- Strong emotional intelligence and decisiveness.\r\n- Excellent collaboration, communication, adaptability, and relationship-building skills.\r\n- Customer-focused mindset and ability to understand customer needs.\r\n- Ability to use data to drive work and make informed decisions.\r\n- Professionalism, integrity, respect, transparency, and commitment to continuous improvement.\r\n- Ability to support diversity, inclusion, and belonging.', 'active', 1, '2026-08-12 23:13:22', 'uploads/logos/1786576401_logo_Screenshot 2026-08-12 140754.png', '2026-09-17', 'Senior level', 1, 'Employees who fulfill the above requirements are invited to apply for this position from August 10, 2026 to August 19, 2026.\r\n\r\nTo apply, click the link below:\r\nhttps://career55.sapsf.eu/sfcareer/jobreqcareer?jobId=2197&company=dashenbank\r\n\r\nFor any enquiry or support, contact: 0114707920.\r\n\r\nCareer Site: Career Opportunities\r\nTelegram: https://t.me/DashenBankEthiopia\r\nLinkedIn: https://www.linkedin.com/company/Dashen-bank-official-page\r\nFacebook: https://www.facebook.com/DashenBankOfficial', 'uploads/attachments/1786576401_attach_login.png', 186, '2026-10-19', 'free', 'approved'),
(19, NULL, 'Dashen Bank', 'Branch Business Relationship Manager-IFB', 'Banking & Finance', 'Full Time', 'Senior', 'Branches Under Hawassa District', 'Negotiable', 'The Branch Business Relationship Manager - IFB will plan, organize, lead, and control business development activities within the branch team in the assigned region. The job holder will be responsible for attracting, deepening, and managing financial relationships to achieve deposit, Interest Free Financing and Investment growth goals, drive business growth for the Bank in the IFB market segment, and promote and sell other products and services of the Bank to enhance value-added relationships with existing customers.', '- Bachelor\'s degree in Business Administration, Banking, Finance or a related field.\r\n- Diploma/Certificate in Islamic Banking, Finance or a related field is an added advantage.\r\n- Minimum of six (6) years of relevant experience.\r\n- Demonstrated in-depth understanding of Interest Free Banking (IFB) operations and IFB deposit, investment and financing products.\r\n- Good knowledge of IFB modes of finance and investments, Shari\'ah principles and associated risks.\r\n- Knowledge and experience in modern sales and marketing practices in the financial services industry.\r\n- Strong networking skills to enhance customer acquisition and retention.\r\n- Extensive banking background, including understanding of credit, risk management and compliance requirements.\r\n- Project management skills.\r\n- Strong result-driven leadership and coaching skills.\r\n- Ability to understand and anticipate customer needs, preferences and behaviours.\r\n- Ability to build and maintain effective partnerships with stakeholders.\r\n- Strong commitment to service excellence and exceptional customer experience.\r\n- Ability to identify, assess and mitigate potential risks.\r\n- Digital savviness and ability to leverage digital technologies to improve efficiency and growth.\r\n- Strong emotional intelligence and ability to influence and inspire others.\r\n- Strong decisiveness and ability to prioritize resources and make effective investment decisions.\r\n- Excellent collaboration and communication skills.\r\n- Commitment to continuous improvement and achieving organizational goals.\r\n- Ability to adapt quickly to changes and new demands.\r\n- Ability to build sustainable relationships with stakeholders.\r\n- Customer-focused mindset and ability to understand customer perspectives and experiences.\r\n- Ability to use data to drive work and make informed decisions.\r\n- Professionalism, competence, integrity, respect, authenticity and transparency.\r\n- Ability to embrace diversity, respect unique identities and foster belonging.', 'active', 1, '2026-08-12 23:24:06', 'uploads/logos/1786577046_logo_Screenshot 2026-08-12 140754.png', '2026-09-10', 'Senior', 1, 'Employees who fulfill the above requirements are invited to apply on the ERP System from August 10, 2026 to August 19, 2026.\r\n\r\nTo apply for this job posting, click the link below:\r\nhttps://career55.sapsf.eu/sfcareer/jobreqcareer?jobId=2019&company=dashenbank\r\n\r\nFor any enquiry or support request:\r\n+251 46 212 6535\r\n+251 96 909 0387\r\n\r\nCareer Site: Career Opportunities\r\n\r\nTelegram: https://t.me/DashenBankEthiopia\r\n\r\nLinkedIn: https://www.linkedin.com/company/Dashen-bank-official-page\r\n\r\nFacebook: https://www.facebook.com/DashenBankOfficial', 'uploads/attachments/1786577046_attach_login.png', 243, '2026-10-19', 'free', 'approved'),
(20, NULL, 'ZX Steel Manufacturing', 'Finance Manager', 'Accounting & Finance', 'Full Time', 'Senior', 'Ethiopia', 'Negotiable', 'We are seeking a highly competent and results-oriented Finance Manager to oversee the company\'s financial operations and ensure compliance with Ethiopian financial regulations and international accounting standards. The Finance Manager will be responsible for financial planning, budgeting, financial reporting, taxation, internal controls, regulatory compliance, and supporting strategic business decisions. Preference will be given to candidates with extensive experience in the manufacturing industry, particularly steel production or mineral ore import and export businesses.', '- Lead and manage the company\'s overall finance and accounting functions.\r\n- Prepare accurate monthly, quarterly, and annual financial statements in accordance with Ethiopian Financial Reporting Standards (EFRS/IFRS).\r\n- Develop, monitor, and control annual budgets, cash flow forecasts, and financial plans.\r\n- Ensure compliance with Ethiopian tax laws, including VAT, withholding tax, corporate income tax, payroll taxes, and other statutory obligations.\r\n- Oversee tax planning and coordinate timely tax filings with relevant government authorities.\r\n- Ensure compliance with Customs Valuation Directive No. 1080/2025 and other customs-related financial regulations applicable to import and export operations.\r\n- Supervise accounting records, reconciliations, and financial reports.\r\n- Establish and strengthen internal financial controls, policies, and procedures.\r\n- Coordinate internal and external audits and implement audit recommendations.\r\n- Manage relationships with banks, auditors, tax authorities, customs officials, and other regulatory bodies.\r\n- Analyze financial performance and provide strategic recommendations to senior management.\r\n- Monitor working capital, inventory valuation, and cost control, particularly within manufacturing operations.\r\n- Lead, mentor, and develop the finance team.\r\n- Must hold a valid Practicing CPA License issued by the Ethiopian Institute of Certified Public Accountants (ETiCPA), with active registration and official attestation signing authority.\r\n- Bachelor\'s degree in Accounting, Finance, or a related field.\r\n- Minimum of 6 years of progressive professional experience in finance and accounting, with substantial manufacturing-sector experience.\r\n- Minimum of 3 years of experience in a Finance Manager position.\r\n- Strong knowledge of EFRS/IFRS, Ethiopian tax laws, and financial regulations.\r\n- Comprehensive understanding of Customs Valuation Directive No. 1080/2025 and import/export customs procedures.\r\n- Experience in steel manufacturing, metal processing, or mineral ore import and export is highly preferred.\r\n- Master\'s degree in Accounting, Finance, Business Administration (MBA), or a related field is an added advantage.\r\n- Experience with ERP systems and advanced financial reporting software.\r\n- Experience in financial planning and analysis, budgeting, treasury management, and manufacturing cost accounting.\r\n- Strong financial analysis and strategic planning skills.\r\n- Excellent leadership and team management abilities.\r\n- High integrity, professionalism, and ethical conduct.\r\n- Strong analytical and problem-solving skills.\r\n- Excellent communication and interpersonal skills.\r\n- Ability to work under pressure and meet strict reporting deadlines.\r\n- Strong proficiency in Microsoft Excel and financial management software.\r\n- Sound decision-making and organizational skills.', 'active', 1, '2026-08-12 23:27:08', 'uploads/logos/1786577228_logo_Screenshot 2026-08-12 142511.png', '2026-11-28', 'Senior', 1, 'Interested and qualified applicants are invited to submit their updated CV, cover letter, and supporting credentials via email to:\r\n\r\nZxsteelmanufacturing@gmail.com\r\n\r\nPlease indicate \"Finance Manager\" in the subject line of your email application.\r\n\r\nOnly shortlisted candidates will be contacted.', NULL, 207, '2026-10-19', 'free', 'approved'),
(21, NULL, 'EEC', 'Roller Operator', 'Construction', 'Full Time', 'Entry Level (Fresh Graduate)', 'addis abeba', 'Negotiable', 'The Roller Operator is responsible for safely and efficiently operating road roller machinery to compact and strengthen soil, gravel, sand, asphalt, and other construction materials. The operator will conduct basic machine inspections before and after work, report mechanical or technical problems to the supervisor, maintain machine cleanliness and routine maintenance, follow workplace health, safety and environmental procedures, and work collaboratively with other operators and construction workers to complete assigned tasks on time.', '- Completed 8th grade education.\r\n- Must have a valid Roller Operator license.\r\n- Minimum of four (4) years of relevant work experience.\r\n- Experience in construction or road projects is preferred.\r\n- Safely and efficiently operate road roller machinery.\r\n- Properly compact and strengthen soil, gravel, sand, asphalt and other construction materials.\r\n- Conduct basic inspection of the machine before and after operation.\r\n- Report machine problems or technical defects to the supervisor promptly.\r\n- Follow workplace safety, health, safety and environmental protection procedures.\r\n- Maintain machine cleanliness and perform routine maintenance.\r\n- Work cooperatively with other operators and construction workers.\r\n- Complete assigned work on time according to the work plan and instructions.\r\n- Use appropriate personal protective equipment and follow construction safety procedures.', 'active', 0, '2026-08-12 23:58:50', 'uploads/logos/1786579130_logo_Screenshot 2026-08-12 145702.png', '2026-11-26', 'Entry Level (Fresh Graduate)', 1, 'Click the \"Apply\" button to submit your application.', NULL, 208, '2026-10-19', 'free', 'approved'),
(22, NULL, 'MULMUL Bread Factory', 'Data Entry and Data Analyst', 'Information Technology', 'Full Time', 'Entry Level (Fresh Graduate)', 'Addis Ababa', '15,000 ETB Net', 'We are seeking a qualified and detail-oriented Data Entry and Data Analyst to enter, verify, organize, maintain, and analyze paper and digital information using databases, Microsoft Excel, SQL, Power BI, Tableau, and related data analysis tools. The successful candidate will be responsible for maintaining data accuracy and confidentiality, updating and reporting data regularly, analyzing collected data using appropriate statistical methods, and presenting analytical results through reports, charts, graphs, and dashboards to support management decision-making.', '- Bachelor\'s degree in Accounting and Finance, Statistics, Mathematics, Economics, Computer Science, or a related field.\r\n- 1–2 years of relevant work experience.\r\n- Enter paper and digital information into databases and Microsoft Excel.\r\n- Check the accuracy, completeness, and consistency of entered data and correct errors.\r\n- Organize, record, and securely maintain data files.\r\n- Maintain the confidentiality of information.\r\n- Use data entry systems, Microsoft Excel, and other relevant software.\r\n- Regularly update registered data and prepare reports.\r\n- Check data quality and identify errors.\r\n- Analyze collected data using statistical and appropriate analytical methods.\r\n- Use Excel, SQL, Power BI, Tableau, and similar data analysis software.\r\n- Present analytical results through reports, charts, graphs, and dashboards.\r\n- Identify data trends, problems, and opportunities.\r\n- Present data insights and findings to management.\r\n- Good computer skills.\r\n- Fast and accurate data entry skills.\r\n- Good knowledge of Excel, SQL, and database systems.\r\n- Strong data analysis and problem-solving skills.\r\n- Good report preparation and communication skills.\r\n- Ability to maintain data entry requirements and follow data processing procedures.\r\n- Ability to use databases and Microsoft Office applications.', 'active', 1, '2026-08-13 00:08:45', 'uploads/logos/1786579725_logo_slide5.png', '2026-11-27', 'Entry Level (Fresh Graduate)', 10, 'Applicants can submit their educational and work experience credentials by email to:\r\n\r\nmulmulhr@gmail.com\r\n\r\nApplicants can also apply in person at the Mulmul Bread Factory near the Abahawa office or at the main office located near 22 Dembel Hospital.\r\n\r\nPreference will be given to applicants who can provide a guarantor and whose residence is close to Lebu Meberat.\r\n\r\nFor more information, please call:\r\n+251 11 462 4401', NULL, 202, '2026-10-19', 'free', 'approved'),
(23, NULL, 'Technology Solutions Company', 'Software Engineer', 'Information Technology & Software Engineering', 'Full Time', 'Junior / Entry Level', 'addis abeba', 'Negotiable', 'We are seeking a motivated and technically skilled Software Engineer to join our technology team. The successful candidate will be responsible for designing, developing, testing, implementing, and maintaining software applications and digital solutions. The position requires strong problem-solving abilities, programming skills, teamwork, and the ability to develop reliable and user-friendly software systems.\r\n\r\nKey Responsibilities\r\nDesign, develop, test, and maintain software applications.\r\nWrite clean, efficient, maintainable, and well-documented code.\r\nAnalyze business and technical requirements and translate them into software solutions.\r\nDevelop and maintain web and mobile applications.\r\nDesign and integrate application programming interfaces (APIs).\r\nWork with databases and ensure efficient data management.\r\nIdentify, troubleshoot, and resolve software errors and technical problems.\r\nConduct software testing and debugging to ensure application quality and reliability.\r\nCollaborate with software developers, system analysts, designers, and other team members.\r\nParticipate in system design, development, deployment, and maintenance activities.\r\nMonitor application performance and implement improvements when required.\r\nMaintain technical documentation for software applications and systems.\r\nFollow software development best practices, security standards, and coding guidelines.\r\nStay updated with emerging technologies and recommend appropriate technical solutions.', 'Required Qualifications\r\n\r\nBachelor\'s degree in Computer Science, Computer Science and Engineering, Software Engineering, Information Technology, or a related field.\r\n1–2 years of relevant professional experience is preferred.\r\nStrong knowledge of at least one programming language such as Java, Python, JavaScript, C++, or C#.\r\nKnowledge of web development technologies including HTML, CSS, and JavaScript.\r\nUnderstanding of databases and SQL.\r\nKnowledge of REST APIs and software integration.\r\nBasic understanding of software development life cycle (SDLC).\r\nAbility to use Git and other version-control tools.\r\nStrong analytical and problem-solving skills.\r\nGood communication and teamwork skills.\r\nAbility to learn new technologies quickly.\r\nPreferred Qualifications\r\nExperience developing web or mobile applications.\r\nExperience with React, React Native, Node.js, Django, Spring Boot, or similar frameworks.\r\nExperience working with MySQL, PostgreSQL, MongoDB, or other database systems.\r\nKnowledge of cloud technologies is an added advantage.\r\nExperience with software testing and debugging.\r\nExperience working on academic, personal, or professional software projects.\r\nRequired Competencies\r\nSoftware development and programming\r\nProblem solving and analytical thinking\r\nDatabase management\r\nAPI development and integration\r\nSoftware testing and debugging\r\nSystem design\r\nTeamwork and collaboration\r\nCommunication skills\r\nTime management\r\nContinuous learning and innovation', 'active', 0, '2026-08-26 08:56:57', 'logo.png', '2028-11-30', 'Junior / Entry Level', 100, 'Interested and qualified applicants are invited to submit their updated CV, academic credentials, and a brief cover letter through the company\'s official recruitment platform or email.\r\n\r\nPlease indicate \"Software Engineer\" in the subject line of your application.\r\n\r\nOnly shortlisted candidates will be contacted.', NULL, 253, '2026-10-19', 'free', 'approved'),
(24, NULL, 'coca cola', 'accounting', 'Accounting and Finance', 'Full Time', 'Junior / Entry Level', 'addis abeba', '5000', 'qwewwwwwwwwwwww', 'uyyyyyyyyyyyyv', 'active', 0, '2026-09-03 13:16:28', 'uploads/logos/1788441388_logo_6a99732c0e5cd.png', '2027-03-30', 'Junior / Entry Level', 10, 'call 6081', NULL, 84, '2026-10-19', 'free', 'approved'),
(25, 11, 'nnnnnnnn', 'yguj', 'Business Development', 'Part Time', 'Junior Level (1-3 years)', 'Ethiopia', '15,000 ETB Net', 'o8ukituuk', 'iyiiygiojpo;', 'active', 0, '2026-09-03 18:37:38', 'uploads/logos/1788460658_joblogo_6a99be722f4c2.jpg', '2027-01-31', 'Junior Level (1-3 years)', 1, 't7iyi7yyiui', NULL, 51, '2026-10-19', 'free', 'approved'),
(26, 11, 'nnnnnnnn', 'jkjj', 'Business Development', 'Contract', 'Entry Level (Fresh Graduate)', 'o;\'ljkl', '15,000 ETB Net', 'ipjljlkj', 'puipioipopoi', 'active', 0, '2026-09-03 19:17:44', 'uploads/logos/1788463064_joblogo_6a99c7d81f6e0.jpg', '2027-01-30', 'Entry Level (Fresh Graduate)', 10, 'yfuyyiuiu', NULL, 65, '2026-10-19', 'free', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_type` enum('admin','employer','user') NOT NULL,
  `sender_email` varchar(100) NOT NULL,
  `receiver_email` varchar(100) DEFAULT 'general',
  `message` text,
  `file_url` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_edited` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_type`, `sender_email`, `receiver_email`, `message`, `file_url`, `file_type`, `created_at`, `is_edited`) VALUES
(34, 'admin', 'tadesebiruk515@gmail.com', 'tadbir795@gmail.com', 'akkam abbo', NULL, NULL, '2026-08-13 10:58:11', 1),
(41, 'admin', 'tadesebiruk515@gmail.com', 'tadbir795@gmail.com', 'Hi', NULL, NULL, '2026-09-01 05:55:48', 0),
(42, 'admin', 'tadesebiruk515@gmail.com', 'ayotubuki69@gmail.com', 'Hi', NULL, NULL, '2026-10-09 07:33:02', 0),
(43, 'admin', 'tadesebiruk515@gmail.com', 'biruke4739@gmail.com', 'Hi', NULL, NULL, '2026-10-09 07:33:15', 0),
(44, 'admin', 'tadesebiruk515@gmail.com', 'ayotubuki69@gmail.com', 'Hello 👋', NULL, NULL, '2026-10-09 07:58:31', 0),
(45, 'admin', 'tadesebiruk515@gmail.com', 'ayotubuki69@gmail.com', 'Hello 👋', NULL, NULL, '2026-10-09 07:58:39', 0);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'smtp_email', 'infopluscallcenter@gmail.com'),
(2, 'smtp_app_password', 'abcd efgh ijkl mnop'),
(3, 'hotline_number', '6781'),
(4, 'is_hot_jobs_enabled', '1'),
(5, 'smtp_host', 'smtp.gmail.com'),
(6, 'smtp_port', '587'),
(7, 'developer_team', 'Wabisoftware Developers Team');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('cbe_account', '1000123456789'),
('chapa_account', 'chapa_sec_key_123'),
('developer_link', 'https://sofomartechnologies.com/'),
('developer_team', 'Wabisoftware Developers Team'),
('facebook_link', 'https://facebook.com/infoplus'),
('footer_bg_theme', 'amber'),
('footer_email', 'infopluscallcenter@gmail.com'),
('footer_location', 'Abado Ena Tafou 20/21'),
('footer_map_url', 'https://maps.app.goo.gl/kpcbeiGKaKDenLue6?g_st=atm'),
('footer_phone', '6781'),
('linkedin_link', 'https://linkedin.com/company/infoplus'),
('telebirr_account', '0911223344'),
('telegram_link', 'https://t.me/infoplus'),
('tiktok_link', 'https://www.tiktok.com/@infopluscallcenter'),
('twitter_link', 'https://x.com/infoplus'),
('youtube_link', 'https://youtube.com/@infoplus');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `cv_path` varchar(255) DEFAULT NULL,
  `skills` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `marital_status` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'Ethiopia',
  `city` varchar(50) DEFAULT NULL,
  `subcity` varchar(50) DEFAULT NULL,
  `woreda` varchar(50) DEFAULT NULL,
  `area` varchar(50) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `work_position` varchar(100) DEFAULT NULL,
  `years_experience` varchar(30) DEFAULT NULL,
  `skills_list` text,
  `onboarding_completed` tinyint(1) DEFAULT '0',
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `bio` text,
  `education_level` varchar(100) DEFAULT NULL,
  `institution` varchar(150) DEFAULT NULL,
  `field_of_study` varchar(150) DEFAULT NULL,
  `graduation_year` varchar(10) DEFAULT NULL,
  `expected_salary` varchar(50) DEFAULT NULL,
  `languages` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `reference_contact` text,
  `previous_company` varchar(150) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `middle_name`, `email`, `phone`, `password`, `otp_code`, `is_verified`, `cv_path`, `skills`, `created_at`, `marital_status`, `birth_date`, `gender`, `country`, `city`, `subcity`, `woreda`, `area`, `alternate_phone`, `work_position`, `years_experience`, `skills_list`, `onboarding_completed`, `two_factor_enabled`, `bio`, `education_level`, `institution`, `field_of_study`, `graduation_year`, `expected_salary`, `languages`, `portfolio_url`, `reference_contact`, `previous_company`, `profile_pic`) VALUES
(57, 'Kenbon', 'Tadele', NULL, 'enokosuruqo29@gmail.com', '+2519362514', '$2y$10$hfQIvne0TbYjjcs53WHJJOECVQoQIq2LTSqhjfj6voWxs1pBmtW8a', NULL, 1, 'uploads/cvs/1788241745_856_cv.pdf', NULL, '2026-09-01 05:49:05', 'Single', '2003-09-01', 'Male', 'Ethiopia', 'Adama', 'Sheget', '04', NULL, '', 'Engineering', '0-1 Year', 'Mechanic', 1, 0, 'I\'m engineering', 'Bachelor Degree', 'Astu', 'Engineering', '2020', '30000', 'Amharic, oromoo, English', '', '', '', 'uploads/avatars/1788241770_avatar_57.jpg'),
(58, 'Kena', 'Kebede', NULL, 'biruktadesse2222@gmail.com', '+251988552233', '$2y$10$/xK6sf7JSMBSA7BEUKYuKuBSNMCCgnM/xemNQT.88rRu69ZptEJu2', NULL, 1, NULL, NULL, '2026-10-09 08:06:45', 'Single', '0000-00-00', 'Male', 'Ethiopia', 'Addis Ababa', 'Bole', '18', NULL, '', 'Geography', '3-5 Years', 'Jdkk', 1, 0, 'Bsjdk', 'Bachelor Degree', 'Adama', 'Geography', '2025', '30000', 'Bzj', 'bnzksk', 'Nznn', 'Nsj', NULL);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
