# Deployment Guide - Sharma Salon & Spa System

This guide explains how to deploy the Sharma Salon & Spa system from your local XAMPP environment to a live web server.

## Pre-Deployment Checklist

- [ ] Backup your local database
- [ ] Test all features locally
- [ ] Choose a hosting provider with PHP 8.0+ and MySQL support
- [ ] Obtain domain name (if needed)
- [ ] Prepare FTP/SFTP credentials

## Step 1: Prepare Database Export

1. **Export Database via phpMyAdmin**
   - Open `http://localhost/phpmyadmin`
   - Select `sharma_salon` database
   - Click "Export" tab
   - Choose "Quick" export method
   - Format: SQL
   - Click "Go" to download `sharma_salon.sql`

2. **Optional: Clean Test Data**
   - If you want a fresh start, you can:
     - Keep only the structure
     - Delete test tokens, customers, and bookings
     - Keep: services, settings, loyalty_milestones, admin users

## Step 2: Update Configuration for Production

### 2.1 Update Database Configuration

Edit `config/database.php`:

```php
private $host = "your_db_host";        // Usually 'localhost'
private $db_name = "your_db_name";     // Your hosting DB name
private $username = "your_db_user";     // Your hosting DB username
private $password = "your_db_password"; // Your hosting DB password
```

### 2.2 Security Settings (Optional but Recommended)

**Option A: Create a separate config for production**
```php
// config/database.prod.php
// Copy database.php and update with production credentials
```

**Option B: Use environment variables**
```php
private $host = getenv('DB_HOST') ?: "localhost";
private $db_name = getenv('DB_NAME') ?: "sharma_salon";
// etc.
```

## Step 3: Upload Files to Server

### Using FTP/SFTP Client (FileZilla, WinSCP, etc.)

1. **Connect to your server**
   - Host: Your server address
   - Username: FTP username
   - Password: FTP password
   - Port: 21 (FTP) or 22 (SFTP)

2. **Upload Files**
   - Upload entire `salon` folder to `public_html/` or `www/` directory
   - Recommended path: `public_html/salon/` or just `public_html/` if using root

3. **Set Correct Permissions**
   - Folders: `755` (rwxr-xr-x)
   - Files: `644` (rw-r--r--)
   - Special: `assets/uploads/` should be `775` (writable)

## Step 4: Import Database on Server

### Using cPanel / phpMyAdmin

1. **Log in to cPanel**
2. **Open phpMyAdmin**
3. **Create Database**
   - Go to "Databases"
   - Create new database (e.g., `username_salon`)
   - Note the full database name

4. **Create Database User**
   - Create new MySQL user
   - Set a strong password
   - Add user to database with ALL PRIVILEGES

5. **Import SQL File**
   - Select your database in phpMyAdmin
   - Click "Import" tab
   - Choose file: `sharma_salon.sql`
   - Click "Go"

## Step 5: Update Application Settings

### 5.1 Admin Settings

Log in to admin panel and update:
- Shop name and address
- Contact phone numbers (Call & WhatsApp)
- Email address
- Google Maps embed code

### 5.2 Change Default Admin Password

**IMPORTANT**: Change the default admin password immediately!

1. Log in with default credentials
2. Use `reset_admin.php` or create a new admin user
3. Delete or disable the default account

## Step 6: SSL Certificate (HTTPS)

For security and Web Push notifications:

1. **Get SSL Certificate**
   - Many hosts offer free Let's Encrypt SSL
   - Or use cPanel "SSL/TLS Status"

2. **Force HTTPS** (Add to `.htaccess`):
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Update Settings**
   - Verify all links use HTTPS
   - Update any hardcoded URLs

## Step 7: Cron Job for Notifications

Set up a cron job to run notification processing:

### cPanel Cron Jobs

1. Go to cPanel > Cron Jobs
2. Add new cron job:
   - **Minute**: `*/2` (every 2 minutes)
   - **Hour**: `*`
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `*`
   - **Command**: 
     ```
     /usr/bin/php /home/username/public_html/salon/cron/process_notifications.php
     ```

**Note**: Replace `/home/username/public_html/` with your actual server path.

## Step 8: Post-Deployment Testing

### Test Checklist

- [ ] Homepage loads correctly
- [ ] Customer can book a token
- [ ] Admin can log in
- [ ] Token management works
- [ ] Services display with images
- [ ] Real-time SSE updates work
- [ ] Notifications trigger (if HTTPS)
- [ ] Settings page updates correctly
- [ ] Contact page displays map
- [ ] All links work (no localhost references)

## Step 9: Performance Optimization (Optional)

### Enable PHP OPcache

Check if OPcache is enabled (most hosts enable by default):
```php
<?php phpinfo(); ?>
```

### Add Database Indexes

If not done during setup:
```bash
mysql -u username -p database_name < sql/add_indexes.sql
```

### Enable Gzip Compression

Add to `.htaccess`:
```apache
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

## Common Issues & Solutions

### Issue: Database Connection Error
**Solution**: Double-check credentials in `config/database.php`

### Issue: Images Not Displaying
**Solution**: 
- Check `assets/uploads/` folder permissions (775)
- Verify images were uploaded correctly
- Check file paths in database

### Issue: SSE Not Working
**Solution**:
- Check server supports SSE (most do)
- Verify Apache/Nginx isn't buffering output
- Contact host if issues persist

### Issue: CSRF Token Errors
**Solution**:
- Ensure sessions are working on server
- Check PHP session configuration
- Verify `session_start()` is called

### Issue: Permissions Denied Errors
**Solution**:
- Set correct file permissions
- Check folder ownership
- Contact hosting support if needed

## Backup Strategy

### Regular Backups

1. **Database Backups** (Weekly)
   - Use phpMyAdmin export
   - Or set up automated cPanel backups

2. **File Backups** (Monthly)
   - Download entire `salon` folder
   - Or use hosting backup tools

3. **Store Safely**
   - Keep backups in multiple locations
   - Cloud storage (Google Drive, Dropbox)
   - Local external drive

## Monitoring & Maintenance

- Monitor error logs regularly
- Check disk space usage
- Update PHP if security updates available
- Review customer feedback
- Test all features after any server changes

## Getting Help

If you encounter issues:
1. Check server error logs
2. Enable PHP error reporting temporarily
3. Contact your hosting provider support
4. Refer to README.md for common issues

---

**Congratulations!** Your Sharma Salon & Spa system is now live! 🎉
