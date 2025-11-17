# Group Members Application

A Laravel 11 application that allows users to search Google Workspace groups and view a flattened list of all members, including those from nested groups.

## Features

- **Google OAuth Authentication**: Users sign in with their Google account
- **Authorization Check**: Only users in the database can access the application
- **Admin User Management**: Admin users can create, edit, and delete users via a web interface
- **Recursive Group Expansion**: Automatically expands nested groups to show all members
- **Duplicate Removal**: Removes duplicate users and shows which groups they belong to
- **Pagination**: Results are paginated (50 per page) for better performance
- **Client-Side Search**: Search across all members by name, email, title, department, or source group
- **Session Caching**: Results are cached in session to avoid re-fetching on pagination
- **Domain Auto-Append**: Optionally configure EMAIL_DOMAIN to allow users to search without typing the full email
- **CSV Export**: Download group member lists as CSV files
- **User Information**: Displays name, email, source group, title, and department

## Requirements

- Ubuntu Server (20.04 LTS or later recommended)
- PHP 8.2 or higher
- Apache 2.4+ with SSL support
- MySQL 8.0+ or MariaDB 10.6+
- Composer
- **Domain name** with DNS pointing to your server (required for SSL)
- Google Workspace account with admin access
- Google OAuth credentials
- Google Service Account with domain-wide delegation

**Important:** This application **requires SSL/HTTPS** and must run on port 443. You must have a valid domain name configured before installation.

## Server Installation (Ubuntu)

This guide assumes you're starting with a fresh Ubuntu server installation.

### 1. Update System Packages

```bash
sudo apt update
sudo apt upgrade -y
```

### 2. Install Apache

```bash
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2
```

Verify Apache is running:
```bash
sudo systemctl status apache2
```

### 3. Install MySQL

```bash
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql
```

Secure MySQL installation (follow prompts):
```bash
sudo mysql_secure_installation
```

Create a database and user for the application:
```bash
sudo mysql -u root -p
```

In the MySQL prompt:
```sql
CREATE DATABASE groupmembers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'groupmembers'@'localhost' IDENTIFIED BY 'your_strong_password_here';
GRANT ALL PRIVILEGES ON groupmembers.* TO 'groupmembers'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Install PHP 8.2 and Required Extensions

```bash
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-fpm php8.2-intl -y
```

Verify PHP installation:
```bash
php -v
```

### 5. Install Composer

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

Verify Composer installation:
```bash
composer --version
```

### 6. Install Git

```bash
sudo apt install git -y
```

### 7. Enable Required Apache Modules

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo systemctl restart apache2
```

### 8. Set Up SSL with Let's Encrypt

**Note:** This application requires SSL/HTTPS and must run on port 443. You must have a domain name configured and pointing to your server's IP address.

Install Certbot:

```bash
sudo apt install certbot python3-certbot-apache -y
```

Obtain SSL certificate (replace `your-domain.com` with your actual domain):

```bash
sudo certbot certonly --standalone -d your-domain.com -d www.your-domain.com
```

Follow the prompts to complete SSL certificate generation. You'll need to provide an email address and agree to the terms of service.

### 9. Configure Apache for Laravel with SSL

Create a new Apache virtual host configuration for HTTPS (port 443):

```bash
sudo nano /etc/apache2/sites-available/groupmembers-ssl.conf
```

Add the following configuration (replace `your-domain.com` with your actual domain):

```apache
<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/groupmembers/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf

    <Directory /var/www/groupmembers/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/groupmembers_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/groupmembers_ssl_access.log combined
</VirtualHost>
```

Create HTTP to HTTPS redirect configuration:

```bash
sudo nano /etc/apache2/sites-available/groupmembers.conf
```

Add the following to redirect all HTTP traffic to HTTPS:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>
```

Enable the sites and disable the default site:

```bash
sudo a2ensite groupmembers.conf
sudo a2ensite groupmembers-ssl.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

Verify SSL is working by checking Apache status:

```bash
sudo systemctl status apache2
sudo apache2ctl -S
```

### 10. Clone the Application

```bash
cd /var/www
sudo git clone https://github.com/childrda/GroupMembers.git groupmembers
sudo chown -R www-data:www-data /var/www/groupmembers
cd groupmembers
```

### 11. Install PHP Dependencies

```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### 12. Configure Environment

```bash
sudo -u www-data cp example.env .env
sudo nano .env
```

Update the following in `.env`:
- `APP_NAME` - Your application name
- `APP_URL` - Your domain URL **must use HTTPS** (e.g., `https://your-domain.com`)
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=groupmembers`
- `DB_USERNAME=groupmembers`
- `DB_PASSWORD=your_strong_password_here`

**Important:** The `APP_URL` must use `https://` as the application requires SSL.

### 13. Generate Application Key

```bash
sudo -u www-data php artisan key:generate
```

### 14. Set File Permissions

```bash
sudo chown -R www-data:www-data /var/www/groupmembers
sudo chmod -R 755 /var/www/groupmembers
sudo chmod -R 775 /var/www/groupmembers/storage
sudo chmod -R 775 /var/www/groupmembers/bootstrap/cache
```

### 15. Run Database Migrations

```bash
sudo -u www-data php artisan migrate
```

### 16. Set Up SSL Certificate Auto-Renewal

Let's Encrypt certificates expire every 90 days. Set up automatic renewal:

```bash
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

Test the renewal process:

```bash
sudo certbot renew --dry-run
```

This ensures your SSL certificate will be automatically renewed before expiration.

## Application Installation

If you've already completed the server installation above, you can skip to step 3. Otherwise, follow these steps:

1. **Clone the repository:**
```bash
git clone https://github.com/childrda/GroupMembers.git
cd GroupMembers
```

2. **Install dependencies:**
```bash
composer install
```

3. **Copy the environment file:**
```bash
cp example.env .env
```

4. **Generate application key:**
```bash
php artisan key:generate
```

5. **Run migrations:**
```bash
php artisan migrate
```

## Configuration

### 1. Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google+ API
4. Create OAuth 2.0 credentials (Web application)
5. Add authorized redirect URI: `http://your-domain/auth/google/callback`
6. Update your `.env` file:
```
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://your-domain/auth/google/callback
```

### 2. Google Service Account Setup

1. **Enable Admin SDK API:**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Navigate to **APIs & Services** → **Library**
   - Search for "Admin SDK API"
   - Click on it and press **Enable**
   - Wait a few minutes for the API to propagate

2. In Google Cloud Console, create a Service Account
3. Enable Domain-Wide Delegation
4. Download the JSON key file
5. Place the JSON file at: `storage/app/google-credentials.json`
6. In Google Admin Console, authorize the service account with these scopes:
   - `https://www.googleapis.com/auth/admin.directory.group.readonly`
   - `https://www.googleapis.com/auth/admin.directory.group.member.readonly`
   - `https://www.googleapis.com/auth/admin.directory.user.readonly`
7. Update your `.env` file:
```
GOOGLE_ADMIN_EMAIL=admin@yourdomain.com
EMAIL_DOMAIN=yourdomain.com
```

**Note:** `EMAIL_DOMAIN` is optional. If set, users can search for groups by entering just the group name (e.g., "all-elementary-staff") instead of the full email address.

### 3. User Authorization

Add authorized users to the `users` table. You can do this via MySQL:

```sql
mysql -u groupmembers -p groupmembers
```

```sql
INSERT INTO users (name, email, email_verified_at, created_at, updated_at)
VALUES ('John Doe', 'john@yourdomain.com', NOW(), NOW(), NOW());
```

Or use the admin interface (if you have an admin user) or create an admin user directly:

```sql
INSERT INTO users (name, email, email_verified_at, isadmin, created_at, updated_at)
VALUES ('Admin User', 'admin@yourdomain.com', NOW(), 1, NOW(), NOW());
```

**Note:** The first admin user must be created via SQL. After that, admin users can manage other users through the web interface at `/admin/users`.

## Usage

### Production (Apache)

If you've set up Apache as described above, navigate to your domain using HTTPS:
- `https://your-domain.com`

**Note:** HTTP traffic will automatically redirect to HTTPS. The application only accepts connections on port 443 (HTTPS).

### Development Server

For local development:
```bash
php artisan serve
```

Then navigate to `http://localhost:8000`

### Using the Application

1. Navigate to your application URL
2. Click "Sign in with Google"
3. If your email is in the database, you'll be redirected to the dashboard
4. **Admin Users:** Access the "Admin" link in the navigation to manage users
5. Go to "Groups" to search for a Google Workspace group
6. Enter a group email (e.g., `all-elementary-staff@lcps.k12.va.us`) or just the group name if `EMAIL_DOMAIN` is configured
7. View the flattened member list with pagination
8. Use the search box to filter members across all results
9. Navigate between pages using pagination controls
10. Download results as CSV if needed

### Admin User Management

Admin users (those with `isadmin = true`) can:
- View all users at `/admin/users`
- Create new users
- Edit existing users (including admin status)
- Delete users (cannot delete themselves)
- Access is protected by middleware - only admin users can access these routes

## API Scopes

The application requires the following Google Directory API scopes:
- `https://www.googleapis.com/auth/admin.directory.group.readonly`
- `https://www.googleapis.com/auth/admin.directory.group.member.readonly`
- `https://www.googleapis.com/auth/admin.directory.user.readonly`

## Security Notes

- The service account JSON file should be kept secure and never committed to version control
- Only authorized users (those in the database) can access the application
- The application uses Laravel's built-in session-based authentication
- Admin routes are protected by middleware - only users with `isadmin = true` can access them
- Keep your `.env` file secure and never commit it to version control
- Use strong passwords for your MySQL database user
- Regularly update your system packages: `sudo apt update && sudo apt upgrade`
- Consider setting up a firewall (UFW) to restrict access to necessary ports only
- **SSL/HTTPS is mandatory** - The application only accepts secure connections on port 443
- Keep SSL certificates up to date - Let's Encrypt certificates auto-renew every 90 days
- Ensure your domain's DNS is properly configured before setting up SSL

## Troubleshooting

### Apache Issues

**"403 Forbidden" error:**
- Check file permissions: `sudo chown -R www-data:www-data /var/www/groupmembers`
- Verify Apache can read the directory: `sudo chmod -R 755 /var/www/groupmembers`
- Ensure `mod_rewrite` is enabled: `sudo a2enmod rewrite && sudo systemctl restart apache2`

**"500 Internal Server Error":**
- Check Apache error logs: `sudo tail -f /var/log/apache2/error.log`
- Check Laravel logs: `tail -f /var/www/groupmembers/storage/logs/laravel.log`
- Verify file permissions on `storage` and `bootstrap/cache` directories

**SSL Certificate Issues:**
- Verify SSL module is enabled: `sudo a2enmod ssl && sudo systemctl restart apache2`
- Check certificate paths in virtual host config match your domain
- Verify certificate files exist: `sudo ls -la /etc/letsencrypt/live/your-domain.com/`
- Test SSL configuration: `sudo apache2ctl configtest`
- Check if port 443 is open: `sudo ufw status` (if firewall is enabled)
- Ensure DNS is properly configured and pointing to your server IP

### Database Connection Issues

**"SQLSTATE[HY000] [2002] No such file or directory":**
- Ensure MySQL is running: `sudo systemctl status mysql`
- Check your `.env` file has correct database credentials
- Verify database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### "Google service account credentials file not found"
- Ensure the JSON file is placed at `storage/app/google-credentials.json`
- Check file permissions: `sudo chmod 600 storage/app/google-credentials.json`
- Verify ownership: `sudo chown www-data:www-data storage/app/google-credentials.json`

### "Your account is not authorized"
- Add your email to the `users` table in the database
- Verify the email matches exactly (case-sensitive)

### Permission Issues

**"The stream or file could not be opened":**
- Fix storage permissions: `sudo chmod -R 775 storage bootstrap/cache`
- Fix ownership: `sudo chown -R www-data:www-data storage bootstrap/cache`

### API Errors

**"Admin SDK API has not been used" or "SERVICE_DISABLED"**
- Enable the Admin SDK API in Google Cloud Console:
  1. Go to APIs & Services → Library
  2. Search for "Admin SDK API"
  3. Click Enable
  4. Wait a few minutes for propagation

**"invalid_grant" or "Invalid email or User ID"**
- Verify domain-wide delegation is enabled for the service account
- Check that all required scopes are authorized in Google Admin Console
- Ensure the admin email in `.env` matches a valid admin account
- Verify the email exists in your Google Workspace domain

## Technology Stack

- **Framework**: Laravel 11
- **PHP**: 8.2+
- **Authentication**: Laravel Socialite (Google OAuth)
- **Google APIs**: Google API Client Library
- **Frontend**: Tailwind CSS (via CDN)

## Project Structure

```
group-members/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   └── AdminUserController.php
│   │   │   ├── Auth/
│   │   │   │   └── GoogleAuthController.php
│   │   │   ├── DashboardController.php
│   │   │   └── GroupController.php
│   │   └── Middleware/
│   │       ├── AdminMiddleware.php
│   │       └── CheckAuthorizedUser.php
│   ├── Models/
│   │   └── User.php
│   └── Services/
│       └── GoogleDirectoryService.php
├── config/
│   └── services.php (Google OAuth & Service Account config)
├── database/
│   └── migrations/
│       ├── 0001_01_01_000000_create_users_table.php
│       └── 2024_01_01_000003_add_isadmin_to_users_table.php
├── resources/
│   └── views/
│       ├── admin/
│       │   └── users/ (index, create, edit)
│       ├── auth/ (login, unauthorized)
│       ├── groups/ (index, results)
│       └── layouts/ (app.blade.php)
└── routes/
    └── web.php
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
