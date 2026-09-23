# Production Deployment & Operations Guide

This guide describes how to deploy, operate, and maintain the **Al-Azhar FCAI Course Platform** on a DigitalOcean Droplet with production reliability, automated backups, and process supervision.

---

## 1. Production Architecture Overview

```
                      Internet
                         │
                         ▼
             [ Nginx Reverse Proxy (SSL/HTTP2) ]
                         │
         ┌───────────────┴───────────────┐
         ▼                               ▼
  /api/*, /up, /sanctum/*                /*
[ PHP-FPM 8.3 (Laravel API) ]   [ Node.js (SvelteKit SSR) ]
         │ (FastCGI)                     │ (Port 3000)
         ▼                               ▼
  [ MySQL 8.4 Database ]          [ Browser Client ]
  [ Redis 7 (Cache/Queues) ]
  [ Supervisor (Queue Workers) ]
  [ DigitalOcean Spaces (S3) ]
```

---

## 2. Server Sizing & Provisioning

- **Recommended Droplet**: DigitalOcean Basic Droplet with Premium Intel/AMD CPUs.
  - **Memory**: 2 GB RAM minimum (4 GB recommended for comfortable build & concurrency).
  - **CPUs**: 2 vCPUs.
  - **Disk**: 50 GB NVMe SSD.
  - **OS**: Ubuntu 24.04 LTS (Noble Numbat) or 22.04 LTS.
  - **Backups**: Enable DigitalOcean Droplet Automated Weekly Backups in the DO control panel.

### Initial Server Hardening:
```bash
# 1. Update packages
sudo apt update && sudo apt upgrade -y

# 2. Create deployer user
sudo adduser deployer
sudo usermod -aG sudo deployer

# 3. Setup UFW Firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

## 3. Required Software Installation

```bash
# 1. Install Ondřej Surý PHP 8.3 repository
sudo apt install -y software-properties-common ca-certificates lsb-release apt-transport-https
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# 2. Install PHP 8.3, FPM, and required extensions
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-zip php8.3-curl php8.3-gd php8.3-intl php8.3-redis \
    php8.3-bcmath php8.3-opcache

# 3. Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 4. Install Node.js 20 LTS (NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# 5. Install Nginx, MySQL 8.4, Redis, Supervisor, and Certbot
sudo apt install -y nginx mysql-server redis-server supervisor certbot python3-certbot-nginx git unzip
```

---

## 4. DigitalOcean Spaces (Private Storage) Setup

1. In the DigitalOcean Console, go to **Spaces** → **Create Spaces Bucket**.
2. **Region**: Frankfurt (`fra1`) or Amsterdam (`ams3`).
3. **Bucket Name**: e.g., `cyf-storage`.
4. **File Listing**: Restrict file listing (Private).
5. In **API** → **Spaces Keys**, generate a new Access Key and Secret Key.
6. Configure bucket CORS to allow `GET` from your domain (`https://courses.fcai-azhar.edu.eg`).

---

## 5. Application Installation

```bash
# 1. Create web directory and clone repository
sudo mkdir -p /var/www/cyf
sudo chown -R deployer:www-data /var/www/cyf
git clone https://github.com/waseemsaher/CYF.git /var/www/cyf
cd /var/www/cyf

# 2. Configure Backend
cd /var/www/cyf/backend
cp .env.production.example .env
# Edit .env with production database credentials, APP_KEY, and Spaces keys:
nano .env

# Generate Application Key
php artisan key:generate

# Set permissions for storage and bootstrap cache
sudo chown -R www-data:www-data /var/www/cyf/backend/storage /var/www/cyf/backend/bootstrap/cache
sudo chmod -R 775 /var/www/cyf/backend/storage /var/www/cyf/backend/bootstrap/cache

# Install dependencies and run migrations
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force

# Optimize Laravel caching
php artisan optimize

# 3. Configure Frontend
cd /var/www/cyf/frontend
cp .env.production.example .env
npm ci
npm run build
sudo chown -R www-data:www-data /var/www/cyf/frontend
```

---

## 6. Web Server & Process Supervision

### Step 1: Link Nginx Configuration
```bash
sudo cp /var/www/cyf/deploy/nginx/cyf.conf /etc/nginx/sites-available/cyf
sudo ln -s /etc/nginx/sites-available/cyf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### Step 2: Issue SSL Certificate via Let's Encrypt
```bash
sudo certbot --nginx -d courses.fcai-azhar.edu.eg
```

### Step 3: Configure PHP OPcache and FPM
```bash
sudo cp /var/www/cyf/deploy/php/opcache.ini /etc/php/8.3/mods-available/opcache.ini
sudo cp /var/www/cyf/deploy/php/www.conf /etc/php/8.3/fpm/pool.d/www.conf
sudo systemctl restart php8.3-fpm
```

### Step 4: Configure Supervisor
```bash
sudo cp /var/www/cyf/deploy/supervisor/cyf-worker.conf /etc/supervisor/conf.d/
sudo cp /var/www/cyf/deploy/supervisor/cyf-frontend.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

### Step 5: Configure Crontab (Scheduler & Backups)
```bash
sudo cp /var/www/cyf/deploy/cron/cyf-cron /etc/cron.d/cyf-cron
sudo chmod 0644 /etc/cron.d/cyf-cron
```

---

## 7. Telegram Bot Webhook Registration

To receive account linking and group join requests in production:
```bash
curl -X POST "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook" \
     -H "Content-Type: application/json" \
     -d '{
       "url": "https://courses.fcai-azhar.edu.eg/api/v1/telegram/webhook",
       "secret_token": "<TELEGRAM_WEBHOOK_SECRET>",
       "allowed_updates": ["message", "chat_join_request"]
     }'
```

Verify the webhook is active:
```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getWebhookInfo"
```

---

## 8. Database Backups & Restore Runbook

### Running a Manual Backup
```bash
sudo /var/www/cyf/scripts/backup.sh
```
Backups are archived in `/var/backups/cyf/` and automatically pruned after 7 days. If `DO_SPACES_BUCKET` is configured, archives are automatically copied to cloud storage.

### Restoring from a Backup
```bash
sudo /var/www/cyf/scripts/restore.sh /var/backups/cyf/cyf_db_YYYYMMDD_HHMMSS.sql.gz
```

---

## 9. Routine Deployments (`deploy.sh`)

To deploy updates to the production server with zero downtime:
```bash
cd /var/www/cyf
./scripts/deploy.sh main
```

The script automatically:
1. Pulls the latest commits.
2. Puts the application into maintenance mode with a secret bypass token.
3. Installs optimized Composer and npm dependencies.
4. Runs database migrations (`migrate --force`).
5. Re-caches configurations and routes (`php artisan optimize`).
6. Restarts queue workers and the SvelteKit Node SSR process.
7. Reloads PHP-FPM and Nginx.
8. Disables maintenance mode and verifies health check (`GET /up`).

---

## 10. Health Check, Monitoring & Rollbacks

- **Health Check**: `GET /up` returns HTTP 200 OK when the application and database are healthy.
- **Queue Workers**: Monitor worker status with `sudo supervisorctl status`. View logs with `tail -f /var/log/supervisor/cyf-worker.log`.
- **Failed Jobs**:
  ```bash
  php artisan queue:failed
  php artisan queue:retry all
  ```
- **Application Logs**:
  ```bash
  tail -f /var/www/cyf/backend/storage/logs/laravel-$(date +%Y-%m-%d).log
  ```
- **Emergency Rollback**:
  ```bash
  cd /var/www/cyf
  git checkout <previous-commit-hash>
  ./scripts/deploy.sh <previous-commit-hash>
  ```
