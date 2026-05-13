# Queue Management System
## Manpower Agency Edition v2.0

A professional queue management system designed for local manpower/staffing agencies with dynamic counter redistribution, authentication, and self-service kiosk capabilities.

### Features

#### Queue Management
- **Dual Window System**: Window 1 for Insurance & Benefits, Window 2 for ID & ATM Renewals
- **Dynamic Counter Redistribution**: Automatic workload redistribution when a counter goes offline
- **Real-time Public Display**: Large-screen display with YouTube/video integration
- **Queue Number Generation**: Automatic numbering with I (Insurance/Benefits) and R (Renewals) prefixes

#### Security
- **User Authentication**: Login system with session management
- **Role-based Access**: Admin, Supervisor, and Staff roles
- **Session Tokens**: Secure 8-hour session tokens
- **Audit Logging**: All authentication events logged

#### Reporting & Analytics
- **Daily Reports**: Comprehensive statistics by date range
- **Service Breakdown**: Performance by service type
- **Hourly Distribution**: Peak hour analysis

#### Customer Experience
- **Self-Service Kiosk**: Touch-screen self check-in terminal
- **Public Display**: Large-screen display with real-time updates
- **Announcement System**: Custom and preset announcements
- **Video Integration**: YouTube/URL video playback

### System Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- Modern web browser

### Installation

1. **Create the database**:
```sql
CREATE DATABASE queuing_system;
```

2. **Import the schema**:
```bash
mysql -u root -p queuing_system < database\Schema_v2.sql
```

3. **Update database credentials** in `config.php`:
```php
private $host = "localhost";
private $db_name = "queuing_system";
private $username = "root";
private $password = "your_password";
```

4. **Default Login Credentials**:
   - Username: `admin`
   - Password: `admin123` (change immediately after first login!)

### File Structure

```
├── database/
│   └── Schema_v2.sql       # Complete database schema
├── api/
│   ├── add_customer.php
│   ├── call_customer.php
│   ├── complete_customer.php
│   ├── cancel_customer.php
│   ├── get_queue.php
│   ├── get_stats.php
│   ├── get_display_data.php
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── session.php
│   ├── counter/
│   │   ├── toggle_status.php
│   │   ├── get_assignments.php
│   │   ├── override_assignment.php
│   │   └── get_redistribution_logs.php
│   ├── announcement/
│   │   ├── index.php
│   │   └── get_active.php
│   ├── reports/
│   │   └── daily.php
│   └── settings/
│       └── index.php
├── js/
│   ├── main.js
│   └── display.js
├── css/
│   └── display.css
├── index.php               # Admin Dashboard
├── login.php               # Login Page
├── display.php             # Public Display Screen
├── settings.php           # Display Settings
├── reports.php            # Reports & Analytics
├── kiosk.php               # Self-Service Kiosk
├── config.php              # Database & Auth Config
└── README.md
```

### Service Types

| Service | Queue Prefix | Window | Color |
|---------|--------------|--------|-------|
| Insurance (UCBP) | I | Window 1 | Blue |
| Benefits (SSS) | I | Window 1 | Green |
| ID Renewal | R | Window 2 | Purple |
| ATM Renewal | R | Window 2 | Orange |

### Page Access

| Page | Purpose | Access |
|------|---------|--------|
| `index.php` | Admin Dashboard | Authenticated |
| `display.php` | Public Display | Public |
| `settings.php` | Display Settings | Admin only |
| `reports.php` | Reports | Authenticated |
| `kiosk.php` | Self Check-In | Public |

### How Counter Redistribution Works

1. **When a counter goes offline**:
   - Services from that counter are automatically assigned to the available counter
   - All waiting customers are reassigned to the available counter
   - An announcement is displayed on the public screen
   - The event is logged for audit purposes

2. **When a counter comes back online**:
   - Original service assignments are restored
   - New customers go to the primary counter

### Support

For issues or questions, contact the system administrator.