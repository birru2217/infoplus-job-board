<div align="center">
  <img src="logo.png" alt="Infoplus Logo" width="120" height="120">
  
  # 🚀 Infoplus - Enterprise Job Board & Matchmaking Platform
  
  **Bridging the Gap Between Digital Recruitment and Offline Accessibility in Ethiopia.**

  <a href="http://infoplus.ifree.page/" target="_blank">
    <img src="https://img.shields.io/badge/🌍_Live_Demo_Website-0D9488?style=for-the-badge&logoColor=white" alt="Live Demo">
  </a>

  <br><br>

  ![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
  ![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
  ![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
  ![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)
</div>

---

## 🎥 Project Video Demo

> **Note:** Click the video below to see Infoplus in action, showcasing the Candidate CV Builder, Employer PalmJobs-style Dashboard, and Superadmin controls.

*(IDDOO KANA VIIDIYOO KEE HARKISII (DRAG & DROP) GITHUB IRRATTI DARBI)*

---

## 📖 About The Project

**Infoplus** is an advanced, enterprise-grade job matchmaking and recruitment platform explicitly tailored for the Ethiopian labor market. It solves a critical problem: connecting highly skilled individuals who have internet access, alongside those who do not, to verified employers. 

This platform seamlessly integrates a modern, highly interactive **Web Portal** with a dedicated **Offline Call Center (Shortcode 6781)**. Users can build their professional profiles via a dynamic 6-step UI wizard online, or call the hotline to have agents digitally construct their profiles on their behalf.

---

## 🔄 How The Ecosystem Works

1. **Dual-Entry Registration:** Candidates register online via the Web Portal OR call `6781` to have an agent create their profile.
2. **Employer Verification:** Enterprises register and are manually vetted by the Infoplus HR team to prevent scams.
3. **Smart Matchmaking:** Employers post jobs; the AI matches job requirements with candidate skills and locations.
4. **Multi-Channel Distribution:** Jobs are posted on the site, sent via Telegram Bot, and available via the Call Center.
5. **Hiring:** Employers manage applications using a Kanban-style dashboard, chat with candidates, and hire.

---

## ✨ Comprehensive Features by User Role

### 1. 👥 Job Seekers (Candidates)
- **6-Step Interactive CV Builder:** A beautiful, Alpine.js-powered wizard that collects Personal Info, Education, Career Objectives, Skills, Experience, and Resume Uploads with live validation.
- **Smart Search Engine:** Employs fuzzy matching and a synonym dictionary (e.g., searching "IT" brings up "Software Engineering" or "Data Entry").
- **1-Click Quick Apply:** Candidates can instantly apply to verified jobs using their saved digital CVs.
- **Live Application Tracking:** A dedicated dashboard to track application statuses (`Pending`, `Reviewed`, `Accepted`, `Rejected`).
- **AI Job Recommendations:** The system automatically suggests "Hot Jobs" based on the user's location, skills, and career level.

### 2. 🏢 Employers (Enterprise HR)
- **Modern PalmJobs-Style UI:** A clean, emerald-themed dashboard for managing recruitment at a glance.
- **Kanban Applicant Management:** Employers can review candidates, download PDF resumes, and Accept/Reject applicants directly from the UI.
- **Multi-Channel Job Broadcasting:** 
  - Posts are instantly published to the Web Portal.
  - **Telegram Auto-Poster:** Automatically formats and broadcasts the job vacancy to the official Infoplus Telegram channel via Bot API.
- **Direct Real-Time Chat:** Employers can chat 1-on-1 with candidates or system support, including support for file attachments and Voice Notes.

### 3. 👨‍💻 System Admins & Superadmins
- **Strict Role-Based Access Control (RBAC):** Distinct dashboards for Superadmins (Full Access) and Sub-Admins (Restricted Access).
- **Live Ecosystem Analytics:** Real-time Chart.js doughnut charts tracking total jobs, active employers, and registered candidates.
- **Dynamic Content Management:** Manage Homepage Hero Slides, Footer Contacts, and Social Media Links without touching code.
- **Advanced Device Security:** Admins can view live active sessions (IP & Device name) and remotely revoke/block untrusted devices.

---

## 🛠️ Tech Stack & Technologies Used

| Category | Technology |
| :--- | :--- |
| **Backend** | Pure Vanilla PHP 8+ (Object-Oriented/PDO) |
| **Database** | MySQL (Relational DB) |
| **Frontend Styling** | HTML5, Tailwind CSS (via CDN) |
| **Frontend Reactivity** | Alpine.js (via CDN) |
| **Design Language** | Modern Glassmorphism, App-like Mobile-First UI |
| **Data Visualization** | Chart.js |
| **Integrations** | PHPMailer (SMTP), WhatsApp API (UltraMsg), Telegram Bot API |

---

## 🔒 Security Implementations

- **Smart Phone Parsing:** Normalizes local Ethiopian numbers (`09...`, `07...`) to international formats (`+2519...`).
- **Global Unique Identity:** Prevents users from registering as an Employer if their email/phone is already used as a Candidate or Admin.
- **Two-Factor Authentication (2FA):** Configurable 6-digit OTP delivery via Email or WhatsApp.
- **Secure Password Hashing:** Uses PHP's native `BCRYPT` algorithm.
- **SQL Injection Prevention:** 100% usage of PDO Prepared Statements.

---

## 📸 Screenshots

*(You can drag and drop screenshots of your Dashboard, CV Builder, and Homepage here to make the README even better!)*

---

## ⚙️ Installation & Local Setup Guide

Follow these steps to run the Infoplus platform on your local machine (XAMPP/WAMP/MAMP):

### 1. Clone the Repository
```bash
git clone https://github.com/YourUsername/infoplus.git
cd infoplus
