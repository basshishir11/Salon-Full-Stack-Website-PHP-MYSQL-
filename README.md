# Sharma Salon & Spa - Token & Booking Management System

A comprehensive web-based token and booking management system for Sharma Salon & Spa with real-time queue updates, loyalty rewards, referral tracking, and customer notifications.

## Features

### Customer Features
- **Gender-Based Token Booking**: Separate queues for Men and Women services
- **Service Selection**: Browse and select multiple services with prices and duration
- **Real-Time Queue Tracking**: Monitor token status and queue position
- **Browser Notifications**: Get notified 15 minutes before turn and when it's your turn
- **Loyalty Rewards**: Earn rewards at milestones (5, 7, 10, 12 visits)
- **Referral System**: Share unique referral codes and track successful referrals
- **Contact Page**: View salon information, map location, and direct contact options

### Admin Features
- **Dashboard**: Overview of daily stats, revenue, and queue status
- **Token Management**: Real-time queue with SSE (Server-Sent Events) for live updates
- **Service Management**: Add, edit, delete, and toggle services with image upload
- **Customer Management**: View customer list and visit history
- **Revenue Analytics**: Daily/weekly/monthly revenue charts and service popularity
- **Loyalty Management**: View and manage pending/claimed rewards
- **Referral Tracking**: Monitor referral statistics and top referrers
- **Settings**: Configure shop information, contact details, and notification preferences

## Technology Stack

- **Frontend**: HTML5, CSS3 (Vanilla), JavaScript (ES6+), Bootstrap 5.3 (minimal)
- **Backend**: PHP 8.0+
- **Database**: MySQL / MariaDB
- **Real-Time**: Server-Sent Events (SSE)
- **Charts**: Chart.js
- **Icons**: FontAwesome 6

## Installation

### Prerequisites
- XAMPP (Apache 2.4+ and MariaDB 10.4+) or equivalent
- PHP 8.0 or higher
- Modern web browser (Chrome, Firefox, Edge recommended)

### Setup Steps

1. **Clone/Copy Files**
   ```
   Place the `salon` folder in `c:/xampp/htdocs/`
   ```

2. **Configure Database**
   - Edit `config/database.php` if needed (default settings work with XAMPP)
   - Database credentials:
     - Host: `localhost`
     - Database: `sharma_salon`
     - Username: `root`
     - Password: `` (empty)

3. **Initialize Database**
   - Navigate to: `http://localhost/salon/setup.php`
   - This will:
     - Create the `sharma_salon` database
     - Create all required tables
     - Insert default settings
     - Seed sample services
     - Seed loyalty milestones
     - Create default admin account

4. **Default Admin Login**
   - URL: `http://localhost/salon/pages/admin/login.php`

5. **Optional: Add Database Indexes** (For Performance)
   - Via phpMyAdmin, run: `sql/add_indexes.sql`

## Usage

### Customer Flow
1. Visit `http://localhost/salon/index.php`
2. Click "Book Token"
3. Select gender (Men/Women)
4. Choose services
5. Enter name, phone, and optional referral code
6. Receive token number and track status

### Admin Flow
1. Login at `/pages/admin/login.php`
2. View dashboard for overview
3. Manage tokens in real-time queues
4. Update service offerings
5. Review analytics and reports
6. Configure settings as needed

### Notification System
For the notification system to work:
1. Customer must allow browser notifications when booking
2. Admin should periodically run: `php cron/process_notifications.php`
3. For production, schedule this via Windows Task Scheduler (every 1-2 minutes)

## Key URLs

### Customer Panel
- Home: `http://localhost/salon/index.php`
- Services: `http://localhost/salon/pages/services.php`
- Rewards: `http://localhost/salon/pages/rewards_info.php`
- Contact: `http://localhost/salon/pages/contact.php`

### Admin Panel
- Login: `http://localhost/salon/pages/admin/login.php`
- Dashboard: `http://localhost/salon/pages/admin/dashboard.php`
- Manage Tokens: `http://localhost/salon/pages/admin/manage_tokens.php`
- Manage Services: `http://localhost/salon/pages/admin/manage_services.php`
- Revenue Analytics: `http://localhost/salon/pages/admin/revenue.php`
- Customers: `http://localhost/salon/pages/admin/manage_customers.php`
- Loyalty: `http://localhost/salon/pages/admin/loyalty.php`
- Referrals: `http://localhost/salon/pages/admin/referrals.php`
- Settings: `http://localhost/salon/pages/admin/settings.php`

## Security Features

- **CSRF Protection**: All forms are protected with CSRF tokens
- **XSS Protection**: All user inputs are sanitized using `htmlspecialchars()`
- **SQL Injection Protection**: PDO prepared statements throughout
- **Session Management**: Secure session handling for admin authentication
- **Password Security**: Passwords are hashed using `password_hash()` with bcrypt

## Troubleshooting

### Database Connection Issues
- Check XAMPP Apache and MySQL are running
- Verify database credentials in `config/database.php`
- Re-run `setup.php` if needed

### SSE Not Working (Live Updates)
- Check browser console for errors
- Ensure session is active (admin logged in)
- Try refreshing the page

### Notifications Not Appearing
- Browser must allow notifications
- Check `cron/process_notifications.php` is running
- Verify token is in "Waiting" status

## Project Structure

```
salon/
├── ajax/              # AJAX endpoints
├── assets/
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript files
│   └── uploads/      # Uploaded service images
├── config/           # Database configuration
├── cron/             # Background scripts
├── includes/         # Reusable PHP components
├── pages/            # HTML pages
│   ├── admin/        # Admin panel pages
│   └── ...           # Customer pages
├── sql/              # Database schemas and seeds
└── index.php         # Landing page
```

## Credits

Developed for **Sharma Salon & Spa**

---

For deployment instructions, see [DEPLOY.md](DEPLOY.md)
