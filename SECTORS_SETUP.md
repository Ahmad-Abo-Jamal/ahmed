# Sectors & Brands Management - Setup Guide

## ✅ What's Been Implemented

You now have complete **database control** for sectors and brands management with admin dashboard integration!

---

## 📁 Files Created/Updated

### Database
- **database_sectors_upgrade.sql** - SQL script to create sectors and brands tables with sample data

### API Endpoints
- **api/sectors.php** - Public API to fetch sectors and brands (used by frontend)
- **admin/ajax/sectors.php** - Admin API to manage sectors (Create, Read, Update, Delete)
- **admin/ajax/brands.php** - Admin API to manage brands (Create, Read, Update, Delete)

### Admin Dashboard
- **admin/pages/sectors.php** - Full admin interface for managing sectors and brands
- **admin/index.php** - Updated with new menu item "القطاعات و العلامات"

### Frontend
- **index.html** - Updated to dynamically load sectors/brands from API (removed hardcoded data)
- **api/sectors.php** integration for real-time updates

---

## 🚀 Quick Setup Steps

### Step 1: Import Database Tables

Run this SQL script in your database:

```bash
mysql -u your_user -p your_database < database_sectors_upgrade.sql
```

Or import manually through phpMyAdmin:
1. Copy content from `database_sectors_upgrade.sql`
2. Go to phpMyAdmin
3. Select your database
4. Go to "SQL" tab
5. Paste and execute

### Step 2: Access Admin Dashboard

1. Log in to admin: `http://yoursite.com/admin/`
2. You'll see new menu item: **"القطاعات و العلامات"**
3. Click to manage sectors and brands

---

## 🎮 Admin Dashboard Features

### Managing Sectors

✅ **Add Sector**
- Name (Arabic & English)
- Icon (FontAwesome class, e.g., `fa-university`)
- Description
- Status (Active/Inactive)
- Auto ordering

✅ **Edit Sector**
- Modify all sector details
- Activity logging

✅ **Delete Sector**
- Auto-deletes all associated brands
- Maintains data integrity

### Managing Brands

✅ **Add Brand**
- Assign to sector
- Name (Arabic & English)
- Category (Arabic & English)
- Description (Arabic & English)
- Icon (FontAwesome class)
- Logo URL (optional)
- Color Gradient (Primary & Secondary)
- Status (Active/Inactive)

✅ **Edit Brand**
- Update all brand information
- Change sector assignment
- Modify colors and styling

✅ **Delete Brand**
- Remove individual brands
- No impact on sector

### Features

- **Tab-based Navigation**: Switch between sectors to see/manage brands
- **Visual Preview**: See brand logo with colors before saving
- **Bulk Organization**: Groups brands by sector
- **Activity Logging**: All changes tracked for audit trail

---

## 🌐 Frontend Integration

### How It Works

1. **Initial Load**: Frontend fetches from `/api/sectors.php`
2. **Dynamic Rendering**: Sectors and brands are generated from database
3. **No Cache Issues**: Changes in admin appear immediately on frontend
4. **Animations**: All brand cards animate as configured

### Example API Response

```json
{
  "success": true,
  "sectors": [
    {
      "id": 1,
      "name": "Finance",
      "name_ar": "المالية والبنوك",
      "icon": "fa-university",
      "display_order": 1,
      "brands": [
        {
          "id": 1,
          "name": "Al-Ahli Bank",
          "name_ar": "البنك الأهلي",
          "category_ar": "البنوك والخدمات المالية",
          "description_ar": "...",
          "icon": "fa-landmark",
          "logo_color": "#1a365d",
          "logo_color_secondary": "#2c5aa0"
        }
      ]
    }
  ]
}
```

---

## 🛠️ Sample Data Included

The upgrade script includes sample data:

**6 Sectors:**
1. المالية والبنوك (Finance & Banking)
2. التكنولوجيا (Technology)
3. التجزئة (Retail)
4. الصحة (Healthcare)
5. الطاقة (Energy)
6. الإعلام (Media)

**18 Sample Brands:**
- 3 brands per sector with realistic data
- Colorful gradients for each brand
- Full Arabic & English naming

---

## 📝 Database Schema

### Sectors Table
```sql
CREATE TABLE sectors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    icon VARCHAR(100) DEFAULT 'fa-briefcase',
    description TEXT,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### Brands Table
```sql
CREATE TABLE brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sector_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    category VARCHAR(255),
    category_ar VARCHAR(255),
    description TEXT,
    description_ar TEXT,
    icon VARCHAR(100) DEFAULT 'fa-star',
    logo_url VARCHAR(500),
    logo_color VARCHAR(7) DEFAULT '#08137b',
    logo_color_secondary VARCHAR(7) DEFAULT '#4f09a7',
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE CASCADE
)
```

---

## 🔄 Workflow

### Adding a New Sector & Brands

1. **Admin Login** → Dashboard → "القطاعات و العلامات"
2. **Click "إضافة قطاع"** → Fill form → Save
3. **Switch to sector tab** → Click "إضافة علامة جديدة"
4. **Fill brand details** → Save
5. **Website Updates Automatically** ✨

### Real-time Updates

- Changes in admin appear **instantly** on frontend
- No caching issues
- No manual refresh needed

---

## 🎨 Customization

### Change Brand Colors

1. Open admin dashboard
2. Go to sectors
3. Edit brand
4. Change logo colors using color picker
5. Save

### Change Sector Icons

Use any [FontAwesome 6.4.0](https://fontawesome.com/icons) icon:
- Finance: `fa-university`, `fa-landmark`, `fa-piggy-bank`
- Technology: `fa-code`, `fa-robot`, `fa-cloud`
- Retail: `fa-store`, `fa-shopping-cart`, `fa-gift`
- And many more!

---

## 📊 Activity Logging

All actions are logged in `activity_log` table:
- Admin who made changes
- What was changed
- When it changed
- IP address of admin

---

## ❓ Troubleshooting

### Frontend not showing sectors?
✅ Check `/api/sectors.php` returns valid JSON
✅ Database tables created with correct schema
✅ Sectors have `status = 'active'`

### Admin page not showing?
✅ Verify menu item added in admin/index.php
✅ File `admin/pages/sectors.php` exists
✅ You're logged in as admin

### Brands not appearing?
✅ Check selected sector exists
✅ Verify brands have `status = 'active'`
✅ Sector SectorId matches in brands table

---

## 🎯 Next Steps

1. ✅ Run database upgrade script
2. ✅ Test admin dashboard
3. ✅ Add your company's sectors
4. ✅ Add your brands per sector
5. ✅ Customize colors and icons
6. ✅ Test frontend displays correctly

---

## 📞 Support

All features are fully functional. The system is:
- ✅ Database-driven
- ✅ Admin-controlled
- ✅ Real-time updated
- ✅ Fully animated
- ✅ Mobile responsive

Enjoy! 🎉
