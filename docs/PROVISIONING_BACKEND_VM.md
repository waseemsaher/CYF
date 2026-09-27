# Provisioning the Backend VM (Provider-Agnostic Guide)

> **Purpose**: This guide provides exact, step-by-step instructions for provisioning the Codeera Laravel API backend on any plain Ubuntu VPS. It applies equally to **AWS EC2 (current temporary host)** and **Oracle Cloud Always Free (future permanent host)** without using provider-specific managed conveniences.

---

## 1. Instance Specification & Security Rules

### Sizing Guidance:
- **AWS EC2**: `t3.micro` (recommended: 2 vCPUs, 1 GiB RAM on Nitro) or `t2.micro` (1 vCPU, 1 GiB RAM). Add a 2GB swap file to prevent OOM errors during composer/migration runs.
- **Oracle Cloud**: Ampere A1 (ARM64, 2–4 OCPUs, 12–24 GiB RAM Always Free) or AMD E2.1.Micro (1 OCPU, 1 GiB RAM).

### Security Group / Firewall Rules (Public Inbound):
Only the following ports may be open to the public internet:
- `22/tcp` (SSH — recommended to restrict to owner's management IP)
- `80/tcp` (HTTP — redirects to HTTPS and Let's Encrypt validation)
- `443/tcp` (HTTPS — TLS encrypted web traffic)

All other services (MySQL, Redis, PHP-FPM) **MUST** be bound strictly to `127.0.0.1` or UNIX sockets and never exposed to the public internet.

---

## 2. Base System & Swap Setup

Connect via SSH and update packages:
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip zip software-properties-common ufw certbot python3-certbot-nginx supervisor
```

Configure 2GB swap space (essential for 1GB RAM micro instances):
```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Configure Host Firewall (UFW):
```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

## 3. Install Web Server, PHP 8.3, MySQL 8, Redis

### 3.1 Install Nginx & Redis
```bash
sudo apt install -y nginx redis-server
sudo systemctl enable nginx redis-server
sudo systemctl start nginx redis-server
```

Verify Redis is bound to localhost:
```bash
grep "^bind" /etc/redis/redis.conf
# Expected: bind 127.0.0.1 ::1
```

### 3.2 Install PHP 8.3 & Extensions
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
  php8.3-redis php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip \
  php8.3-gd php8.3-bcmath php8.3-intl
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
```

Verify PHP-FPM socket:
```bash
ls -la /run/php/php8.3-fpm.sock
```

### 3.3 Install Composer
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
```

### 3.4 Install & Configure MySQL 8 (Self-Managed)
```bash
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql
```

Verify MySQL binds to localhost only:
```bash
grep -E "bind-address" /etc/mysql/mysql.conf.d/mysqld.cnf
# Should be: bind-address = 127.0.0.1
```

Create application database and user:
```sql
sudo mysql
CREATE DATABASE cyf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cyf'@'localhost' IDENTIFIED BY 'STRONG_RANDOM_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON cyf.* TO 'cyf'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 4. Application Deployment Setup

### 4.1 Clone Repository & Set Permissions
```bash
sudo mkdir -p /var/www/cyf
sudo chown -R $USER:www-data /var/www/cyf
git clone https://github.com/waseemsaher/CYF.git /var/www/cyf
cd /var/www/cyf/backend
```

### 4.2 Configure Environment
```bash
cp .env.example .env
nano .env
```
Fill in the production values:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://api.codeera.tech`
- `FRONTEND_URL=https://codeera.tech`
- `CORS_ALLOWED_ORIGINS="https://codeera.tech,https://app.codeera.tech"`
- `SANCTUM_STATEFUL_DOMAINS="codeera.tech,app.codeera.tech"`
- `SESSION_DOMAIN=.codeera.tech`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_SAME_SITE=lax`
- `DB_CONNECTION=mysql`
- `DB_DATABASE=cyf`
- `DB_USERNAME=cyf`
- `DB_PASSWORD=YOUR_STRONG_PASSWORD`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`
- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp.sendgrid.net`
- `MAIL_PORT=587`
- `MAIL_USERNAME=apikey`
- `MAIL_PASSWORD=YOUR_SENDGRID_API_KEY`
- `FILESYSTEM_DISK=s3`
- `AWS_ACCESS_KEY_ID=YOUR_KEY`
- `AWS_SECRET_ACCESS_KEY=YOUR_SECRET`
- `AWS_DEFAULT_REGION=us-east-1`
- `AWS_BUCKET=codeera-media`
- `TELEGRAM_BOT_TOKEN=...`
- `TELEGRAM_WEBHOOK_SECRET=...`

### 4.3 Install Dependencies & Initialize Backend
```bash
cd /var/www/cyf/backend
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
php artisan production:verify
```

Fix storage permissions:
```bash
sudo chown -R www-data:www-data /var/www/cyf/backend/storage /var/www/cyf/backend/bootstrap/cache
sudo chmod -R 775 /var/www/cyf/backend/storage /var/www/cyf/backend/bootstrap/cache
```

---

## 5. Web Server (Nginx) & SSL

### 5.1 Link Nginx Configuration
```bash
sudo cp /var/www/cyf/deploy/nginx/cyf.conf /etc/nginx/sites-available/cyf
sudo ln -sf /etc/nginx/sites-available/cyf /etc/nginx/sites-enabled/cyf
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 5.2 Obtain Let's Encrypt Certificate
Before running certbot, ensure the DNS A record for `api.codeera.tech` points to this VM's public IP:
```bash
sudo certbot --nginx -d api.codeera.tech --non-interactive --agree-tos -m admin@codeera.tech
```

---

## 6. Supervisor (Queue Workers & Scheduler)

```bash
sudo cp /var/www/cyf/deploy/supervisor/cyf-worker.conf /etc/supervisor/conf.d/
sudo cp /var/www/cyf/deploy/supervisor/cyf-scheduler.conf /etc/supervisor/conf.d/

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
sudo supervisorctl status
```
Expected output:
```text
cyf-scheduler                    RUNNING   pid ...
cyf-worker:cyf-worker_00         RUNNING   pid ...
cyf-worker:cyf-worker_01         RUNNING   pid ...
```

---

## 7. Daily Database Backup to S3

Install crontab for automated daily backups at 03:00 AM:
```bash
sudo cp /var/www/cyf/deploy/cron/cyf-cron /etc/cron.d/cyf-cron
sudo chmod 644 /etc/cron.d/cyf-cron
```

Test backup manually:
```bash
sudo -u www-data bash /var/www/cyf/scripts/backup.sh
```

---

## 8. Swapping to Oracle Cloud (When Migrating)

Because everything above uses standard Ubuntu packages, migrating to Oracle Cloud later is simple:
1. Repeat sections 1 through 7 on the Oracle VM.
2. Transfer database: `php artisan db:restore --from-s3 --force`.
3. Repoint DNS `api.codeera.tech` A record to the Oracle VM IP.
4. Run Certbot to issue SSL on the new VM.
5. Done!
