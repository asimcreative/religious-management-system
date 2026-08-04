# RAMS — Installation Guide

## Requirements

- PHP 8.3+
- MySQL 8.0+
- Redis 6+
- Node.js 20+
- Composer 2+

---

## 1. Clone and Install

```bash
git clone <repo-url> rams
cd rams
composer install
npm install
```

---

## 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="RAMS"
APP_ENV=production
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rams
DB_USERNAME=rams_user
DB_PASSWORD=strong_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_DRIVER=redis
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@rams.example.com
MAIL_FROM_NAME="RAMS"

SANCTUM_TOKEN_EXPIRATION=43200
SANCTUM_TOKEN_PREFIX=rams_
```

---

## 3. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

The seeder creates:
- A default Super Admin company
- The Super Admin role
- A default admin user (see DatabaseSeeder for credentials)

---

## 4. Build Frontend Assets

```bash
npm run build
```

---

## 5. Start Horizon (Queue Worker)

```bash
php artisan horizon
```

For production, run Horizon as a supervised process (systemd or Supervisor).

Example Supervisor config (`/etc/supervisor/conf.d/horizon.conf`):

```ini
[program:rams-horizon]
process_name=%(program_name)s
command=php /var/www/rams/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/rams/storage/logs/horizon.log
```

---

## 6. Schedule (Cron)

Add to server cron:

```bash
* * * * * cd /var/www/rams && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs:
- `logs:purge` — daily at 02:00 (data retention cleanup)
- `horizon:snapshot` — every 5 minutes (Horizon metrics)

---

## 7. Web Server

### Nginx example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/rams/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## 8. Permissions

```bash
chmod -R 755 /var/www/rams/storage
chmod -R 755 /var/www/rams/bootstrap/cache
chown -R www-data:www-data /var/www/rams
```

---

## 9. Verify Installation

```bash
php artisan about
php artisan test
```

---

## First Login

After seeding, log in with the default credentials from the seeder. Immediately change the password via the profile page.

---

## Creating a New Tenant Company

1. Super Admin logs in
2. Navigate to Company Management
3. Create a new company
4. Create users for that company and assign roles
