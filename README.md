# 🏛️ LGU3 Management System

A modern web-based management system for Local Government Unit operations with advanced calendar participation tracking and citizen engagement analytics.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

## ✨ Features

### 🎨 Modern UI/UX
- **Glassmorphism Design** - Premium frosted glass effects
- **Particle Backgrounds** - Dynamic animated backgrounds
- **Crazy Box Loading Animation** - Engaging loading states
- **Smooth Animations** - Fade-in, slide-up, and floating effects
- **Responsive Design** - Works on all devices

### 📅 Calendar & Event Management
- **Multi-User Tagging** - Tag multiple citizens per event with beautiful pill UI
- **Join/Decline System** - Citizens can respond to event invitations
- **Real-time Notifications** - Instant alerts for event responses
- **Event Types** - Training, Meetings, Work schedules, Tasks

### 📊 Analytics & Participation Tracking
- **Engagement Dashboard** - Real-time participation metrics
- **Join/Decline Rates** - Visual progress bars with percentages
- **Pending Responses** - Track who hasn't responded yet
- **Admin Overview** - Comprehensive statistics at a glance

### 👥 User Management
- **Role-Based Access** - Admin and User roles
- **Citizen Profiles** - Complete user information management
- **Activity Tracking** - Monitor user sessions and activity
- **Account Status Control** - Enable/disable user accounts

### 📚 Additional Features
- **Learning Materials** - Upload and manage PDF documents
- **Program Management** - Create and assign training programs
- **Application Tracking** - Monitor citizen applications
- **Edit Requests** - Approval system for profile changes

## 🚀 Installation Guide

### Prerequisites
- **XAMPP** or **WAMP** (Apache + MySQL + PHP)
- **PHP 7.4+**
- **MySQL 5.7+**
- Modern web browser

### Step 1: Clone the Repository
```bash
git clone https://github.com/khenthub20/lgu3.git
cd lgu3
```

### Step 2: Move to Web Server Directory
```bash
# For XAMPP
cp -r lgu3 C:/xampp/htdocs/

# For WAMP
cp -r lgu3 C:/wamp64/www/
```

### Step 3: Create Database
1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Create a new database named: `lgu3_db`
3. Import the schema:
   - Click on `lgu3_db` database
   - Go to "Import" tab
   - Choose `schema.sql` file
   - Click "Go"

### Step 4: Configure Database Connection
Edit `db_connect.php` if needed (default settings work for XAMPP/WAMP):
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lgu3_db";
```

### Step 5: Run Migration Scripts
Open your browser and run these URLs in order:

1. **Update User Schema**: http://localhost/lgu3/update_schema_user.php
2. **Update Livelihood Schema**: http://localhost/lgu3/update_schema_livelihood.php
3. **Migrate Tags System**: http://localhost/lgu3/migrate_tags.php
4. **Seed Programs** (Optional): http://localhost/lgu3/seed_programs.php
5. **Seed Calendar** (Optional): http://localhost/lgu3/seed_calendar.php

### Step 6: Access the Application
- **Main Login**: http://localhost/lgu3/
- **Signup**: http://localhost/lgu3/signup.php

## 👤 Default Admin Account

After installation, create an admin account by:
1. Sign up normally at: http://localhost/lgu3/signup.php
2. Manually update the user role in database:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
   ```

## 📁 Project Structure

```
lgu3/
├── index.php              # Login page
├── signup.php             # User registration
├── admin_dashboard.php    # Admin control panel
├── user_dashboard.php     # Citizen dashboard
├── api.php                # Backend API endpoints
├── db_connect.php         # Database configuration
├── style.css              # Global styles
├── script.js              # JavaScript utilities
├── schema.sql             # Database schema
├── uploads/               # User uploaded files
└── README.md              # This file
```

## 🔧 API Endpoints

### Public Endpoints
- `POST /api.php?action=login` - User authentication
- `POST /api.php?action=signup` - User registration

### User Endpoints
- `GET /api.php?action=get_calendar` - Fetch calendar events
- `POST /api.php?action=respond_to_event` - Join/Decline events
- `GET /api.php?action=notifications` - Get user notifications

### Admin Endpoints
- `GET /api.php?action=stats` - Dashboard statistics
- `GET /api.php?action=get_calendar_stats` - Participation analytics
- `POST /api.php?action=add_calendar_event` - Create events
- `POST /api.php?action=delete_calendar_event` - Remove events
- `GET /api.php?action=all_citizens` - List all users
- `POST /api.php?action=toggle_user_status` - Enable/disable accounts

## 🎨 Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Icons**: Feather Icons
- **Fonts**: Google Fonts (Outfit, Inter)
- **Animations**: CSS3 Keyframes

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/AmazingFeature`
3. Commit your changes: `git commit -m 'Add some AmazingFeature'`
4. Push to the branch: `git push origin feature/AmazingFeature`
5. Open a Pull Request

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 👨‍💻 Developer

Created with ❤️ by **khenthub20**

## 🐛 Known Issues

- Browser authentication popup may appear on first `git push`
- Particle animations may slow down on older devices
- File uploads limited to server's `upload_max_filesize` setting

## 🔮 Future Enhancements

- [ ] SMS notifications for events
- [ ] Mobile app version
- [ ] Advanced reporting with charts
- [ ] Export data to Excel/PDF
- [ ] Multi-language support
- [ ] Dark/Light theme toggle

## 📞 Support

For issues and questions:
- **GitHub Issues**: https://github.com/khenthub20/lgu3/issues
- **Email**: Contact through GitHub profile

---

**⭐ If you find this project useful, please give it a star!**
