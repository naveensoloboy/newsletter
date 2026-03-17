# 📰 Online Newsletter Management System — PHP + MongoDB

## Project Structure
```
newsletter_php/
├── backend/
│   ├── api/
│   │   ├── auth.php          ← Login, Register, Me
│   │   ├── newsletters.php   ← Newsletter CRUD + PDF
│   │   ├── articles.php      ← Article CRUD + Image Upload
│   │   ├── admin.php         ← Admin: approve/reject/publish/users
│   │   ├── public.php        ← Public: read newsletters, subscribe
│   │   └── health.php        ← Health check
│   ├── models/
│   │   ├── Database.php
│   │   ├── UserModel.php
│   │   ├── NewsletterModel.php
│   │   ├── ArticleModel.php
│   │   └── SubscriberModel.php
│   ├── utils/
│   │   ├── Auth.php          ← JWT helpers
│   │   ├── Email.php         ← PHPMailer email
│   │   └── Response.php      ← JSON response helper
│   ├── config/
│   │   └── config.php        ← All settings here
│   ├── uploads/              ← Uploaded images stored here
│   ├── bootstrap.php         ← Autoload all files
│   ├── seed.php              ← Creates default admin
│   ├── composer.json
│   └── .htaccess
└── frontend/
    ├── public/
    │   ├── index.html        ← Newsletter archive
    │   ├── newsletter.html   ← Read newsletter
    │   ├── search.html       ← Search & filter
    │   ├── login.html        ← Login / Register
    │   ├── css/style.css
    │   └── js/api.js         ← PHP-compatible API helper
    ├── faculty/
    │   ├── dashboard.html
    │   ├── create-article.html
    │   └── my-articles.html
    └── admin/
        ├── dashboard.html
        ├── review.html
        ├── publish.html
        └── users.html
```

---

## ✅ Step-by-Step Setup

### Step 1 — Install XAMPP
Download from: https://www.apachefriends.org/download.html
- Choose Windows 64-bit
- Install to `C:\xampp`
- During install, select: ✅ Apache, ✅ PHP, ✅ phpMyAdmin (optional)

### Step 2 — Install MongoDB on Windows
Download Community Server from: https://www.mongodb.com/try/download/community
- Version: 7.x, Platform: Windows, Package: MSI
- Install with default settings
- ✅ Check "Install MongoDB as a Service" — it auto-starts

Verify: Open Command Prompt → type `mongosh` → should open MongoDB shell

### Step 3 — Install PHP MongoDB Extension
1. Download the MongoDB PHP driver:
   - Go to: https://pecl.php.net/package/mongodb
   - Download the `.dll` for your PHP version (check `C:\xampp\php\php.exe --version`)
   - Example: `php_mongodb-1.19.0-8.2-ts-x64.zip`
2. Extract and copy `php_mongodb.dll` to `C:\xampp\php\ext\`
3. Open `C:\xampp\php\php.ini` and add this line:
   ```
   extension=mongodb
   ```
4. Restart Apache in XAMPP Control Panel

### Step 4 — Place Project in XAMPP
Copy the entire `newsletter_php` folder to:
```
C:\xampp\htdocs\newsletter_php\
```

Your folder structure should be:
```
C:\xampp\htdocs\newsletter_php\
├── backend\
└── frontend\
```

### Step 5 — Install Composer Dependencies
1. Download Composer from: https://getcomposer.org/Composer-Setup.exe
2. Run the installer (it auto-detects PHP)
3. Open Command Prompt:
```bash
cd C:\xampp\htdocs\newsletter_php\backend
composer install
```
This installs:
- `mongodb/mongodb` — PHP MongoDB library
- `firebase/php-jwt` — JWT tokens
- `phpmailer/phpmailer` — Email sending

### Step 6 — Configure the System
Edit `backend/config/config.php`:

```php
define('MONGO_URI',     'mongodb://localhost:27017');
define('MONGO_DB',      'newsletter_db');
define('JWT_SECRET',    'your-long-random-secret-here');

// For Gmail emails:
define('SMTP_USER',     'youremail@gmail.com');
define('SMTP_PASSWORD', 'your-16-char-app-password');
define('FROM_EMAIL',    'youremail@gmail.com');

// Match your XAMPP port (usually 80):
define('FRONTEND_URL',  'http://localhost/newsletter_php');
define('UPLOAD_URL',    'http://localhost/newsletter_php/backend/uploads/');
```

### Step 7 — Enable Apache mod_headers (for CORS)
1. Open `C:\xampp\apache\conf\httpd.conf`
2. Find and uncomment (remove `#`):
   ```
   LoadModule headers_module modules/mod_headers.so
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Restart Apache

### Step 8 — Seed the Database (Create Admin)
Open Command Prompt:
```bash
cd C:\xampp\htdocs\newsletter_php\backend
php seed.php
```
Output:
```
✅ Admin created!
   Email   : admin@college.edu
   Password: Admin@123
```

### Step 9 — Test Backend Health
Open browser: http://localhost/newsletter_php/backend/api/health.php

Expected response:
```json
{
  "status": "ok",
  "message": "Newsletter Management System (PHP) running",
  "php": "8.x.x",
  "mongodb": "connected"
}
```

### Step 10 — Open Frontend
Open VS Code → Open Folder → select `newsletter_php`
Right-click `frontend/public/index.html` → **Open with Live Server**

Or open directly in browser:
```
http://localhost/newsletter_php/frontend/public/index.html
```

**Login:** admin@college.edu / Admin@123

---

## 🔧 Troubleshooting

| Problem | Fix |
|---------|-----|
| `Class 'MongoDB\Client' not found` | Check `php_mongodb.dll` in `C:\xampp\php\ext\` and `extension=mongodb` in `php.ini` |
| `composer: command not found` | Restart Command Prompt after installing Composer |
| CORS error in browser | Enable `mod_headers` in `httpd.conf` and restart Apache |
| 500 Internal Server Error | Check `C:\xampp\apache\logs\error.log` |
| MongoDB not connecting | Run `net start MongoDB` in Command Prompt as Administrator |
| Images not loading | Check `uploads/` folder exists and is writable (`chmod 755` on Linux) |
| Email not sending | Check Gmail App Password in `config.php`; emails fail silently |
| `vendor/autoload.php` not found | Run `composer install` inside `backend/` folder |

---

## 🌐 API Endpoints Reference

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `api/auth.php?action=login` | POST | No | Login |
| `api/auth.php?action=register` | POST | No | Register faculty |
| `api/auth.php?action=me` | GET | Yes | Get current user |
| `api/newsletters.php?action=list` | GET | Faculty/Admin | List newsletters |
| `api/newsletters.php?action=create` | POST | Faculty/Admin | Create newsletter |
| `api/newsletters.php?action=pdf&id=X` | GET | No | Download PDF |
| `api/articles.php?action=create` | POST | Faculty/Admin | Create article |
| `api/articles.php?action=submit&id=X` | POST | Faculty | Submit for review |
| `api/articles.php?action=upload-image` | POST | Faculty/Admin | Upload image |
| `api/admin.php?action=stats` | GET | Admin | Dashboard stats |
| `api/admin.php?action=approve-article&id=X` | POST | Admin | Approve article |
| `api/admin.php?action=publish-newsletter&id=X` | POST | Admin | Publish newsletter |
| `api/public.php?action=newsletters` | GET | No | Get published newsletters |
| `api/public.php?action=subscribe` | POST | No | Subscribe |

---

## Default Credentials
- **Admin:** admin@college.edu / Admin@123
- Faculty users register from the login page
