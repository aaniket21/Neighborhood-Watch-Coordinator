# Neighborhood Watch Coordinator

A modern PHP-based web application for community-driven crime reporting, local alerts, and neighborhood engagement.

## Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Screenshots](#screenshots)
- [Tech Stack](#tech-stack)
- [Setup & Installation](#setup--installation)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Security Notes](#security-notes)
- [License](#license)

---

## Overview
Neighborhood Watch Coordinator is a web platform that empowers residents to:
- Report crimes and suspicious activities in their area.
- View and verify crime reports on an interactive map.
- Receive local news, notifications, and safety alerts.
- Chat with an AI-powered assistant for guidance and help.
- Administer and moderate community safety efforts.

The system supports both regular users and admin roles, with a modern, responsive UI and chatbot integration.

---

## Features
- **User Registration & Login:** Secure authentication for residents and admins.
- **Dashboard:** Personalized dashboard showing user reports, stats, and nearby incidents.
- **Crime Reporting:** Submit detailed reports (title, description, location, type, etc.) with geolocation.
- **Admin Panel:** Manage users, moderate and verify reports, view stats.
- **Interactive Map:** Visualize crime locations using Leaflet.js.
- **Notifications:** Receive local news and safety alerts.
- **AI Chatbot:** Ask questions and get instant answers via Gemini API.
- **Responsive Design:** Mobile-friendly, modern look using Bootstrap and custom CSS.
- **Security:** Password hashing, session management, input validation.

---

## Screenshots
> _Add screenshots of the dashboard, report form, admin panel, and chatbot UI here._

---

## Tech Stack
- **Backend:** PHP 7+ (Procedural)
- **Database:** MySQL (auto-setup via `config.php`)
- **Frontend:** HTML5, CSS3, Bootstrap 5, Animate.css, FontAwesome, Leaflet.js
- **AI Integration:** Gemini API (Google Generative Language)

---

## Setup & Installation
1. **Clone or copy the repository:**
   ```
   git clone <repo-url>
   ```
2. **Move the project to your web root:**
   - For XAMPP: `c:/xampp/htdocs/chatbot/`
3. **Database Setup:**
   - No manual setup needed. On first run, `config.php` will create the MySQL database, tables, and a default admin user (`admin`/`admin123`).
   - Default DB credentials: `root` / (no password). Change in `config.php` if needed.
4. **Gemini API Key:**
   - Replace the placeholder key in `chatbot_api.php` with your actual Gemini API key for chatbot functionality.
5. **Start XAMPP/Apache and visit:**
   - [http://localhost/chatbot/](http://localhost/chatbot/)

---

## Usage
- **Register** a new user or log in as admin (`admin`/`admin123`).
- **Dashboard:** View your reports, stats, and map of incidents.
- **Report Crime:** Submit new reports with address and geolocation.
- **Admin Panel:** (Admins only) Verify/reject reports, manage users.
- **Chatbot:** Click the chat icon to ask questions or get help.

---

## Project Structure
```
├── admin.php            # Admin dashboard
├── chatbot.php          # Chatbot widget UI
├── chatbot_api.php      # Gemini API backend
├── config.php           # DB setup and connection
├── dashboard.php        # User dashboard
├── index.php            # Login page
├── logout.php           # Logout handler
├── navbar.php           # Navigation bar
├── news.php             # News and alerts
├── notifications.php    # User notifications
├── profile.php          # User profile
├── register.php         # Registration page
├── report_crime.php     # Crime reporting form
├── settings.php         # User settings
├── style.css            # Custom styles
├── includes/            # (Reserved for future PHP includes)
└── README.md            # Project documentation
```

---

## Security Notes
- Change the default admin password after first login.
- Never commit your real Gemini API key to public repos.
- Always use HTTPS in production.

---

## License
MIT License. See [LICENSE](LICENSE) for details.
