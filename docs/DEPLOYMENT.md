# Privasee Deployment Guide

This guide covers the deployment of the Privasee Laravel application to production environments.

## Prerequisites

- Ubuntu 20.04+ or CentOS 8+ server
- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- Nginx or Apache
- Node.js 18+
- Composer 2.0+
- SSL certificate
- Domain name configured

## Server Requirements

### Minimum System Requirements

- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 50GB SSD
- **Bandwidth**: 100Mbps

### Recommended System Requirements

- **CPU**: 4+ cores
- **RAM**: 8GB+
- **Storage**: 100GB+ SSD
- **Bandwidth**: 1Gbps

## Installation Steps

### 1. Server Setup

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y curl wget git unzip software-properties-common

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd \
    php8.2-imagick php8.2-intl php8.2-bcmath php8.2-soap
```

### 2. Database Setup

```bash
# Install MySQL
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE privasee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'privasee_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON privasee.* TO 'privasee_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Redis Setup

```bash
# Install Redis
sudo apt install -y redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf

# Set the following configurations:
# maxmemory 256mb
# maxmemory-policy allkeys-lru
# save 900 1
# save 300 10
# save 60 10000

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### 4. Web Server Setup (Nginx)

```bash
# Install Nginx
sudo apt install -y nginx

# Create site configuration
sudo nano /etc/nginx/sites-available/privasee
```

```nginx
server {
    listen 80;
    server_name privasee.ae www.privasee.ae;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name privasee.ae www.privasee.ae;
    root /var/www/privasee/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/privasee.ae.crt;
    ssl_certificate_key /etc/ssl/private/privasee.ae.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/api/v1/auth/(login|register) {
        limit_req zone=login burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\. {
        deny all;
    }

    error_log /var/log/nginx/privasee_error.log;
    access_log /var/log/nginx/privasee_access.log;
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/privasee /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 5. Application Deployment

```bash
# Create application directory
sudo mkdir -p /var/www/privasee
cd /var/www

# Clone repository
sudo git clone https://github.com/your-repo/privasee.git
cd privasee

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js and NPM
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install NPM dependencies and build assets
npm ci --production
npm run build

# Set up environment file
cp .env.example .env
nano .env
```

### 6. Environment Configuration

```bash
# Generate application key
php artisan key:generate

# Configure .env file
```

```env
APP_NAME="Privasee"
APP_ENV=production
APP_KEY=base64:generated_key_here
APP_DEBUG=false
APP_URL=https://privasee.ae

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=privasee
DB_USERNAME=privasee_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Add your production credentials for:
# - Stripe
# - Twilio
# - AWS S3
# - Google Maps
# - Email service
```

### 7. Database Migration and Seeding

```bash
# Run migrations
php artisan migrate --force

# Seed database (optional for production)
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=AdminUserSeeder

# Create storage link
php artisan storage:link
```

### 8. File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/privasee

# Set proper permissions
sudo chmod -R 755 /var/www/privasee
sudo chmod -R 775 /var/www/privasee/storage
sudo chmod -R 775 /var/www/privasee/bootstrap/cache
```

### 9. Queue Workers Setup

```bash
# Create supervisor configuration
sudo nano /etc/supervisor/conf.d/privasee-worker.conf
```

```ini
[program:privasee-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/privasee/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/privasee/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Install and start supervisor
sudo apt install -y supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start privasee-worker:*
```

### 10. Cron Jobs Setup

```bash
# Add Laravel scheduler to crontab
sudo crontab -e -u www-data

# Add this line:
* * * * * cd /var/www/privasee && php artisan schedule:run >> /dev/null 2>&1
```

### 11. SSL Certificate Setup

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d privasee.ae -d www.privasee.ae

# Test auto-renewal
sudo certbot renew --dry-run
```

### 12. Optimization

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize autoloader
composer dump-autoload --optimize

# Clear application cache
php artisan cache:clear
```

## Monitoring and Logging

### 1. Log Rotation

```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/privasee
```

```
/var/www/privasee/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 www-data www-data
    postrotate
        /usr/bin/supervisorctl restart privasee-worker:*
    endscript
}
```

### 2. Monitoring Setup

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Set up log monitoring
tail -f /var/www/privasee/storage/logs/laravel.log
tail -f /var/log/nginx/privasee_error.log
```

## Backup Strategy

### 1. Database Backup

```bash
# Create backup script
sudo nano /usr/local/bin/backup-privasee.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/privasee"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u privasee_user -p'secure_password' privasee > $BACKUP_DIR/database_$DATE.sql

# Application backup
tar -czf $BACKUP_DIR/application_$DATE.tar.gz -C /var/www privasee

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

```bash
# Make executable and add to cron
sudo chmod +x /usr/local/bin/backup-privasee.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/backup-privasee.sh
```

### 2. File Backup to S3

```bash
# Install AWS CLI
sudo apt install -y awscli

# Configure AWS credentials
aws configure

# Create S3 sync script
sudo nano /usr/local/bin/sync-to-s3.sh
```

```bash
#!/bin/bash
aws s3 sync /var/backups/privasee s3://privasee-backups/$(date +%Y/%m/%d)/ --delete
```

## Security Hardening

### 1. Firewall Configuration

```bash
# Install and configure UFW
sudo ufw enable
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw status
```

### 2. Fail2Ban Setup

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Create custom configuration
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
action = iptables-multiport[name=ReqLimit, port="http,https", protocol=tcp]
logpath = /var/log/nginx/privasee_error.log
findtime = 600
bantime = 7200
maxretry = 10
```

### 3. Additional Security

```bash
# Disable unused services
sudo systemctl disable apache2 2>/dev/null || true

# Update system regularly
sudo apt update && sudo apt upgrade -y

# Set up automatic security updates
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

## Performance Optimization

### 1. PHP-FPM Tuning

```bash
# Edit PHP-FPM configuration
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### 2. MySQL Optimization

```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 1
query_cache_size = 256M
```

### 3. Redis Optimization

```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf
```

```ini
maxmemory 512mb
maxmemory-policy allkeys-lru
tcp-keepalive 60
```

## Deployment Automation

### 1. Deployment Script

Use the provided `deploy.sh` script for automated deployments:

```bash
# Make deployment script executable
chmod +x deploy.sh

# Run deployment
./deploy.sh
```

### 2. CI/CD Pipeline (GitHub Actions)

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.KEY }}
        script: |
          cd /var/www/privasee
          ./deploy.sh
```

## Health Checks

### 1. Application Health Check

```bash
# Create health check endpoint test
curl -f https://privasee.ae/health || echo "Health check failed"
```

### 2. Service Monitoring

```bash
# Check all services
sudo systemctl status nginx php8.2-fpm mysql redis-server supervisor
```

## Troubleshooting

### Common Issues

1. **Permission Issues**
   ```bash
   sudo chown -R www-data:www-data /var/www/privasee
   sudo chmod -R 775 storage bootstrap/cache
   ```

2. **Queue Workers Not Running**
   ```bash
   sudo supervisorctl restart privasee-worker:*
   sudo supervisorctl status
   ```

3. **Database Connection Issues**
   ```bash
   php artisan tinker
   DB::connection()->getPdo();
   ```

4. **Cache Issues**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

### Log Locations

- Application logs: `/var/www/privasee/storage/logs/laravel.log`
- Nginx logs: `/var/log/nginx/privasee_*.log`
- PHP-FPM logs: `/var/log/php8.2-fpm.log`
- MySQL logs: `/var/log/mysql/error.log`
- Redis logs: `/var/log/redis/redis-server.log`

## Maintenance

### Regular Tasks

1. **Daily**
   - Monitor application logs
   - Check system resources
   - Verify backups

2. **Weekly**
   - Update system packages
   - Review security logs
   - Performance monitoring

3. **Monthly**
   - Security audit
   - Database optimization
   - Backup testing

### Update Process

1. Test updates in staging environment
2. Create full backup
3. Put application in maintenance mode
4. Deploy updates
5. Run migrations
6. Clear caches
7. Test functionality
8. Remove maintenance mode

```bash
# Maintenance mode
php artisan down --message="Updating application" --retry=60

# After deployment
php artisan up
```

This deployment guide ensures a secure, optimized, and maintainable production environment for the Privasee platform.