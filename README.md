# Ahmed Abu Al-Majid - Professional Services Portal

A comprehensive website and admin dashboard for managing articles, services, messages, and site settings with a focus on Arabic-friendly content management.

## 🌟 Features

### Public Website
- **Responsive Design**: Mobile-friendly layout optimized for all devices
- **Dynamic Articles**: Loads published articles from the database via API
- **Services Showcase**: Display your professional services with rich styling
- **Contact Form**: Integrated contact form with email notifications
- **About Section**: Tell your story with multimedia support
- **Social Integration**: Links to social media platforms

### Admin Dashboard
- **Authentication**: Secure login system for admins only
- **Article Management**: Create, edit, delete, and publish articles
- **Message Management**: View and manage contact form submissions
- **Service Management**: Manage your service offerings
- **Settings Management**: Configure site-wide settings
- **Activity Logging**: Track all admin actions for security and auditing
- **Dashboard Statistics**: Quick overview of site metrics

## 📋 Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache with `.htaccess` support (or equivalent)
- **Extensions**: PDO for MySQL

## 🚀 Installation

### 1. Database Setup

Import the database schema:

```bash
mysql -u your_user -p your_database < database_schema.sql
```

This will create all necessary tables with sample admin user:
- **Username**: `admin`
- **Email**: `admin@example.com`
- **Password**: `Admin@123456`

### 2. Configuration

Edit `config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
define('SITE_URL', 'https://yourdomain.com');
```

### 3. Directory Permissions

Ensure proper permissions for PHP to write logs and cache:

```bash
chmod -R 755 .
chmod -R 777 logs/ (if you have a logs directory)
```

### 4. HTTPS Setup (Recommended)

Enable HTTPS on your server. The code automatically detects HTTPS and adjusts session settings accordingly.

## 📁 Project Structure

```
.
├── admin/                 # Admin dashboard
│   ├── index.php         # Main dashboard controller
│   ├── login.php         # Admin login page
│   ├── ajax/             # AJAX handlers for CRUD operations
│   ├── pages/            # Dashboard pages (articles, messages, etc.)
│   └── errors/           # Error pages (401, 403, 404, 500)
├── api/                  # REST API endpoints
│   ├── articles.php      # Get published articles
│   ├── contact.php       # Handle contact form submissions
│   ├── services.php      # Get services
│   └── settings.php      # Get site settings
├── index.html            # Public homepage
├── config.php            # Configuration & helper functions
├── database_schema.sql   # Database schema
├── verify.php            # Email verification endpoint
├── diagnostic.php        # Diagnostic tools
└── README.md             # This file
```

## 🔑 Admin Access

1. Navigate to `https://yourdomain.com/admin/login.php`
2. Use the default credentials (change password immediately in production)
3. Access dashboard at `https://yourdomain.com/admin/`

### Admin Features

- **Dashboard**: Overview of recent activity and site statistics
- **Messages**: Manage contact form submissions
- **Articles**: Create and manage blog posts/articles
- **Services**: Manage your service offerings
- **Settings**: Configure site-wide email and display settings
- **Activity Log**: Review all admin actions

## 🛣️ API Endpoints

### GET `/api/articles.php`
Returns list of published articles.

**Response:**
```json
{
  "success": true,
  "count": 5,
  "articles": [
    {
      "id": 1,
      "title": "Article Title",
      "slug": "article-title",
      "excerpt": "Short description...",
      "category": "article",
      "badge": "جديد",
      "image_url": "https://...",
      "views": 150,
      "publish_date": "2026-01-15",
      "author": "Admin Name"
    }
  ]
}
```

### POST `/api/contact.php`
Submit a contact form message.

**Parameters:**
- `name` (required): Visitor's full name
- `email` (required): Visitor's email
- `phone` (required): Visitor's phone
- `service` (required): Service of interest
- `subject` (required): Message subject
- `message` (required): Message body

## 🔐 Security Features

- **Input Sanitization**: All user inputs are sanitized using `sanitize()` function
- **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
- **Session Security**: 
  - HttpOnly cookies (no JS access)
  - Secure flag on HTTPS
  - SameSite protection enabled
- **CORS Protection**: API endpoints restrict origins appropriately
- **Activity Logging**: All admin actions are logged with IP and user agent

## 🐛 Troubleshooting

### Admin Login Issues
- Verify database connection in `config.php`
- Check that `admin_users` table exists and has records
- Clear browser cookies and try again

### Articles Not Showing
- Ensure articles are marked as `status = 'published'` in the database
- Check that article `author_id` matches a valid admin user
- Check browser console for API errors

### Email Not Working
- Verify SMTP settings in site settings (admin dashboard)
- Check email from address matches your domain
- Review server error logs for details

### Session/Login Problems
- Ensure PHP has write permission to `/tmp` or the configured session directory
- Check that `session.save_path` is writable
- On local/HTTP, the secure cookie flag has been disabled to prevent session loss

## 📧 Contact & Support

For issues or questions about this project, please contact the site administrator.

## 📄 License

All rights reserved © 2026 Ahmed Abu Al-Majid

---

**Last Updated**: January 29, 2026  
**Version**: 1.0.0
