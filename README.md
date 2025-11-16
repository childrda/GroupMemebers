# Group Members Application

A Laravel 11 application that allows users to search Google Workspace groups and view a flattened list of all members, including those from nested groups.

## Features

- **Google OAuth Authentication**: Users sign in with their Google account
- **Authorization Check**: Only users in the database can access the application
- **Recursive Group Expansion**: Automatically expands nested groups to show all members
- **Duplicate Removal**: Removes duplicate users and shows which groups they belong to
- **Pagination**: Results are paginated (50 per page) for better performance
- **Client-Side Search**: Search across all members by name, email, title, department, or source group
- **Session Caching**: Results are cached in session to avoid re-fetching on pagination
- **Domain Auto-Append**: Optionally configure EMAIL_DOMAIN to allow users to search without typing the full email
- **CSV Export**: Download group member lists as CSV files
- **User Information**: Displays name, email, source group, title, and department

## Requirements

- PHP 8.2 or higher
- Composer
- Google Workspace account with admin access
- Google OAuth credentials
- Google Service Account with domain-wide delegation

## Installation

1. Clone the repository and install dependencies:
```bash
composer install
```

2. Copy the environment file:
```bash
cp example.env .env
```

3. Generate application key:
```bash
php artisan key:generate
```

4. Run migrations:
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

Add authorized users to the `users` table:
```sql
INSERT INTO users (name, email, email_verified_at, created_at, updated_at)
VALUES ('John Doe', 'john@yourdomain.com', NOW(), NOW(), NOW());
```

Or use a seeder:
```php
php artisan make:seeder UserSeeder
```

## Usage

1. Start the development server:
```bash
php artisan serve
```

2. Navigate to `http://localhost:8000`
3. Click "Sign in with Google"
4. If your email is in the database, you'll be redirected to the dashboard
5. Go to "Groups" to search for a Google Workspace group
6. Enter a group email (e.g., `all-elementary-staff@lcps.k12.va.us`) or just the group name if `EMAIL_DOMAIN` is configured
7. View the flattened member list with pagination
8. Use the search box to filter members across all results
9. Navigate between pages using pagination controls
10. Download results as CSV if needed

## API Scopes

The application requires the following Google Directory API scopes:
- `https://www.googleapis.com/auth/admin.directory.group.readonly`
- `https://www.googleapis.com/auth/admin.directory.group.member.readonly`
- `https://www.googleapis.com/auth/admin.directory.user.readonly`

## Security Notes

- The service account JSON file should be kept secure and never committed to version control
- Only authorized users (those in the database) can access the application
- The application uses Laravel's built-in session-based authentication

## Troubleshooting

### "Google service account credentials file not found"
- Ensure the JSON file is placed at `storage/app/google-credentials.json`
- Check file permissions

### "Your account is not authorized"
- Add your email to the `users` table in the database

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
│   │   │   ├── Auth/GoogleAuthController.php
│   │   │   ├── DashboardController.php
│   │   │   └── GroupController.php
│   │   └── Middleware/
│   │       └── CheckAuthorizedUser.php
│   └── Services/
│       └── GoogleDirectoryService.php
├── config/
│   └── services.php (Google OAuth & Service Account config)
├── database/
│   └── migrations/ (Users table)
├── resources/
│   └── views/
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
