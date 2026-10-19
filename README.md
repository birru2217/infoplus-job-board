<div align="center">
  <img src="logo.png" alt="Infoplus Logo" width="120" height="120">
  
  # 🚀 Infoplus - Enterprise Job Board & Matchmaking Platform
  
  **Bridging the Gap Between Digital Recruitment and Offline Accessibility in Ethiopia.**

  ![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
  ![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
  ![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
  ![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)
</div>

---

## 📖 About The Project

**Infoplus** is an advanced, enterprise-grade job matchmaking and recruitment platform explicitly tailored for the Ethiopian labor market. It solves a critical problem: connecting highly skilled individuals who have internet access, alongside those who do not, to verified employers. 

This platform seamlessly integrates a modern, highly interactive **Web Portal** with a dedicated **Offline Call Center (Shortcode 6781)**. Users can build their professional profiles via a dynamic 6-step UI wizard online, or call the hotline to have agents digitally construct their profiles on their behalf.

---

## ✨ Comprehensive Features by User Role

### 1. 👥 Job Seekers (Candidates)
- **6-Step Interactive CV Builder:** A beautiful, Alpine.js-powered wizard that collects Personal Info, Education, Career Objectives, Skills, Experience, and Resume Uploads with live validation.
- **Smart Search Engine:** Employs fuzzy matching and a synonym dictionary (e.g., searching "IT" brings up "Software Engineering" or "Data Entry").
- **1-Click Quick Apply:** Candidates can instantly apply to verified jobs using their saved digital CVs.
- **Live Application Tracking:** A dedicated dashboard to track application statuses (`Pending`, `Reviewed`, `Accepted`, `Rejected`).
- **AI Job Recommendations:** The system automatically suggests "Hot Jobs" based on the user's location, skills, and career level.
- **Single Sign-On (SSO):** Seamless integration with Google and LinkedIn OAuth.

### 2. 🏢 Employers (Enterprise HR)
- **Modern PalmJobs-Style UI:** A clean, emerald-themed dashboard for managing recruitment at a glance.
- **Kanban Applicant Management:** Employers can review candidates, download PDF resumes, and Accept/Reject applicants directly from the UI.
- **Multi-Channel Job Broadcasting:** 
  - Posts are instantly published to the Web Portal.
  - **Telegram Auto-Poster:** Automatically formats and broadcasts the job vacancy to the official Infoplus Telegram channel via Bot API.
- **Dynamic Billing & Plans:** Integrated interfaces for package management and payment slips (CBE, Telebirr, Chapa).
- **Direct Real-Time Chat:** Employers can chat 1-on-1 with candidates or system support, including support for file attachments and Voice Notes.

### 3. 👨‍💻 System Admins & Superadmins
- **Strict Role-Based Access Control (RBAC):** Distinct dashboards for Superadmins (Full Access) and Sub-Admins (Restricted Access).
- **Live Ecosystem Analytics:** Real-time Chart.js doughnut charts tracking total jobs, active employers, and registered candidates.
- **Dynamic Content Management:** 
  - Manage Homepage Hero Slides (Images, Texts, Badge Colors).
  - Dynamically manage Footer Contacts and Social Media Links.
- **Advanced Device Security:** Admins can view live active sessions (IP & Device name) and remotely revoke/block untrusted devices.
- **Database Management:** Full CRUD operations to inspect, edit, or remove candidate and employer records.

---

## 🛠️ Tech Stack & Technologies Used

### Backend & Database
- **Core Language:** Pure Vanilla PHP 8+ (No heavy frameworks, ensuring maximum speed).
- **Database:** MySQL via PDO (PHP Data Objects) for complete protection against SQL Injections.
- **Architecture:** Procedural & Object-Oriented blend with PRG (Post/Redirect/Get) patterns to prevent duplicate form submissions.

### Frontend UI / UX
- **Styling:** Tailwind CSS (via CDN) utilizing custom Glassmorphism (backdrop-blur), translucent dotted-mesh cards, and floating 3D illustrations.
- **Reactivity:** Alpine.js for lightweight, instant DOM manipulations, tab switching, and live form validation without page reloads.
- **Data Visualization:** Chart.js for Admin and Employer analytics.
- **Theming:** Seamless Light/Dark Mode synchronized via `localStorage` and PHP Cookies to prevent rendering flickers.

### APIs & Integrations
- **Mailing:** PHPMailer for OTP delivery.
- **Social Media:** Telegram Bot API for auto-posting jobs.
- **Authentication:** Google OAuth 2.0 and LinkedIn OpenID.
- **Security:** UltraMsg WhatsApp API integration for 2-Factor Authentication (2FA).

---

## 🔒 Security Implementations
- **Smart Phone Parsing:** Normalizes local Ethiopian numbers (`09...`, `07...`) to international formats (`+2519...`).
- **Global Unique Identity:** Prevents users from registering as an Employer if their email/phone is already used as a Candidate or Admin.
- **Two-Factor Authentication (2FA):** Configurable 6-digit OTP delivery via Email or WhatsApp.
- **Password Hashing:** Uses PHP's native `BCRYPT` algorithm.

---

## ⚙️ Installation & Local Setup Guide

Follow these steps to run the Infoplus platform on your local machine (XAMPP/WAMP/MAMP):

### 1. Clone the Repository
```bash
git clone https://github.com/YourUsername/infoplus.git
cd infoplus
