# 🚀 Setup & Installation Guide

## Quick Start (3 Steps)

### Step 1: Initialize the Database
Visit this URL in your browser:
```
http://localhost/setup.php
```

This will:
- Create all necessary database tables
- Add sample data (services, articles, sectors, brands)
- Create admin account (Username: `admin`, Password: `admin123`)

### Step 2: Verify Everything
Visit the diagnostic page:
```
http://localhost/diagnostic.php
```

This will show:
- ✓ PHP version and server info
- ✓ Database connection status
- ✓ All required tables
- ✓ API endpoint status

### Step 3: Access Your Site
- **Public Website**: http://localhost (or your domain)
- **Admin Panel**: http://localhost/admin/login.php
- **Admin Credentials**: 
  - Username: `admin`
  - Password: `admin123`

---

## Detailed Setup Guide

### Prerequisites
- PHP 7.4+ with MySQL support
- MySQL 5.7 or later
- Web Server (Apache/Nginx)

### Database Configuration

Edit `config.php` and set your database credentials:

```php
define('DB_HOST', 'your_host');      // Usually 'localhost'
define('DB_USER', 'your_user');      // MySQL username
define('DB_PASS', 'your_password');  // MySQL password
define('DB_NAME', 'your_database');  // Database name
```

### Directory Structure

```
/
├── api/                          # Public API endpoints
│   ├── articles.php
│   ├── contact.php
│   ├── sectors.php
│   ├── services.php
│   └── settings.php
├── admin/                         # Admin panel
│   ├── index.php
│   ├── login.php
│   ├── pages/                    # Admin pages
│   └── ajax/                     # Admin AJAX handlers
├── static/                        # Static files
│   ├── css/
│   ├── js/
│   └── images/
├── setup.php                      # Database initialization
├── diagnostic.php                 # System diagnostics
├── config.php                     # Configuration file
├── index.html                     # Public homepage
└── ...other public pages          # Articles, courses, etc.
```

---

## Manual Setup (if automatic setup fails)

### 1. Create Database Tables

```sql
-- Run the SQL file
mysql -u your_user -p your_database < database_complete_schema.sql
```

### 2. Create Admin Account

```sql
INSERT INTO admin_users (username, email, password_hash, full_name, role, status)
VALUES ('admin', 'admin@example.com', '$2y$10$...', 'Administrator', 'admin', 'active');
```

### 3. Add Sample Services

```sql
INSERT INTO services (title, description, icon, status) VALUES
('Service 1', 'Description', 'fa-icon', 'active'),
('Service 2', 'Description', 'fa-icon', 'active');
```

---

## Troubleshooting

### Database Connection Error
- ✓ Check `config.php` has correct credentials
- ✓ Ensure MySQL is running
- ✓ Verify database exists and user has permissions
- ✓ Check firewall/hosting doesn't block MySQL

### "Table doesn't exist" Error
- ✓ Run `setup.php` in browser to create tables
- ✓ Or manually run SQL schema file
- ✓ Check database name in `config.php`

### API Returns Empty Data
- ✓ Verify database tables were created
- ✓ Add sample data using admin panel
- ✓ Check API endpoints (e.g., `/api/services.php`)
- ✓ Review browser console for errors

### Admin Panel Won't Load
- ✓ Check `admin/index.php` exists
- ✓ Verify session configuration in `config.php`
- ✓ Clear browser cookies and try again
- ✓ Check error logs for PHP errors

### Frontend Doesn't Load Content
- ✓ Open browser console (F12)
- ✓ Check for "Failed to load resource" errors
- ✓ Verify API endpoint URLs in `index.html`
- ✓ Make sure database has sample data

---

## Adding Content

### Via Admin Panel
1. Login at `/admin/login.php`
2. Navigate to the respective section:
   - **Services**: Add/edit services
   - **Articles**: Create and publish articles
   - **Sectors & Brands**: Manage industry sectors
   - **Settings**: Update site configuration

### Via Direct Database Insert

#### Add a Service
```sql
INSERT INTO services (title, description, icon, status, display_order)
VALUES ('New Service', 'Service description', 'fa-star', 'active', 1);
```

#### Add an Article
```sql
INSERT INTO articles (title, slug, excerpt, content, category, author_id, status, publish_date)
VALUES ('Article Title', 'article-slug', 'Excerpt...', 'Content...', 'news', 1, 'published', NOW());
```

#### Add a Sector with Brands
```sql
INSERT INTO sectors (name, name_ar, icon, status)
VALUES ('Technology', 'التكنولوجيا', 'fa-laptop', 'active');

INSERT INTO brands (sector_id, name, name_ar, category, description, icon, status)
VALUES (1, 'Brand Name', 'اسم العلامة', 'Category', 'Description', 'fa-star', 'active');
```

---

## API Reference

### Get Services
**URL**: `/api/services.php`
**Method**: GET
**Response**:
```json
{
  "success": true,
  "count": 6,
  "services": [
    {
      "id": 1,
      "title": "Service Name",
      "description": "Description",
      "icon": "fa-chart-line",
      "price_min": 0,
      "price_max": 0,
      "duration": null
    }
  ]
}
```

### Get Articles
**URL**: `/api/articles.php`
**Method**: GET
**Response**:
```json
{
  "success": true,
  "count": 3,
  "articles": [
    {
      "id": 1,
      "title": "Article Title",
      "slug": "article-slug",
      "excerpt": "...",
      "category": "news",
      "badge": "Latest",
      "image_url": null,
      "publish_date": "2024-01-01",
      "author": "Author Name",
      "word_count": 500,
      "reading_time": 3
    }
  ]
}
```

### Get Settings
**URL**: `/api/settings.php`
**Method**: GET
**Response**:
```json
{
  "success": true,
  "settings": [
    {
      "setting_key": "site_email",
      "setting_value": "info@example.com"
    },
    {
      "setting_key": "site_phone",
      "setting_value": "+966..."
    }
  ]
}
```

### Get Sectors & Brands
**URL**: `/api/sectors.php`
**Method**: GET
**Response**:
```json
{
  "success": true,
  "sectors": [
    {
      "id": 1,
      "name": "Finance",
      "name_ar": "المالية",
      "icon": "fa-university",
      "brands": [
        {
          "id": 1,
          "name": "Brand Name",
          "name_ar": "اسم العلامة",
          "category": "Banking",
          "description": "Description",
          "icon": "fa-building",
          "logo_color": "#08137b"
        }
      ]
    }
  ]
}
```

### Submit Contact Form
**URL**: `/api/contact.php`
**Method**: POST
**Fields**: name, email, phone, service, subject, message
**Response**:
```json
{
  "success": true,
  "message": "تم إرسال رسالتك بنجاح!"
}
```

---

## File Permissions

Ensure these directories are writable:
```bash
chmod 755 /
chmod 755 admin/
chmod 755 api/
```

For file uploads (if enabled):
```bash
chmod 777 uploads/
chmod 777 admin/uploads/
```

---

## Security Recommendations

1. **Change Admin Password**: Login and update in settings
2. **Change Database Credentials**: Edit `config.php` with strong password
3. **Use HTTPS**: Always use SSL/TLS in production
4. **Disable Setup Page**: After setup, delete or protect `setup.php`
5. **Regular Backups**: Backup database and files regularly

---

## Support & Help

### Check System Status
Visit: `/diagnostic.php`

### View Error Logs
PHP errors are usually logged to:
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- cPanel: `/home/user/public_html/error_log`

### Common Fixes
1. Clear browser cache (Ctrl+Shift+Delete)
2. Restart your web server
3. Check file permissions (755 for directories, 644 for files)
4. Verify database connection with `config.php`

---

## What's Included

✅ Dynamic public website with Arabic support
✅ Admin dashboard for content management
✅ Services management
✅ Articles/blog system
✅ Sectors & brands showcase
✅ Contact form
✅ Newsletter subscription
✅ Email notifications
✅ Activity logging
✅ Responsive design
✅ SEO-friendly structure

---

**Last Updated**: February 2024  
**Version**: 1.0.0

