# Codeera Pilot Launch Runbook (docs/PILOT_RUNBOOK.md)

> [!IMPORTANT]
> **Notice to the Platform Owner**:
> This runbook is designed specifically for the term pilot launch under tight time pressure. Follow these steps in exact sequential order.
> **Infrastructure Reality Check**: No server has been pre-provisioned by the AI assistant, and no external DNS, cloud accounts (AWS/Vercel), or Telegram bot states have been altered. All steps below represent the concrete operational actions you must perform.

---

## 1. Owner-Only Pre-Launch Infrastructure Tasks

Complete these cloud and third-party dashboard steps first before deploying code.

### 1.1 AWS EC2 Virtual Machine Setup
1. **Log in to AWS Console**: Open [AWS Management Console](https://console.aws.amazon.com/) and navigate to **EC2** in your chosen region (e.g., `eu-central-1` Frankfurt or `us-east-1` N. Virginia).
2. **Launch Instance**:
   - **Name**: `codeera-pilot-backend`
   - **OS / AMI**: **Ubuntu 24.04 LTS (x86_64)**
   - **Instance Type**: `t3.micro` or `t2.micro` (1 vCPU, 1 GB RAM — fits within AWS Free Tier credits)
   - **Key Pair**: Select or create an SSH key pair (download the `.pem` file to your computer).
   - **Storage**: Set root volume to `25 GiB` gp3.
3. **Configure Security Group (Firewall)**:
   In **Network settings** -> **Create security group** named `codeera-backend-sg`:
   - **Rule 1 (SSH)**: Type `SSH`, Port `22`, Source `My IP` (`<YOUR_CURRENT_IP>/32`). *Never expose SSH to 0.0.0.0/0.*
   - **Rule 2 (HTTP)**: Type `HTTP`, Port `80`, Source `Anywhere-IPv4` (`0.0.0.0/0`) & `Anywhere-IPv6` (`::/0`) for ACME challenges and HTTPS redirects.
   - **Rule 3 (HTTPS)**: Type `HTTPS`, Port `443`, Source `Anywhere-IPv4` (`0.0.0.0/0`) & `Anywhere-IPv6` (`::/0`) for encrypted REST API traffic.
   - *Ensure ports 3306 (MySQL), 6379 (Redis), and 9000 (PHP-FPM) are NOT in the inbound rules.*
4. **Allocate & Attach Static Elastic IP (EIP)**:
   - Go to **EC2** -> **Network & Security** -> **Elastic IPs**.
   - Click **Allocate Elastic IP address** -> Click **Allocate**.
   - Select the newly allocated IP -> **Actions** -> **Associate Elastic IP address**.
   - Choose your instance `codeera-pilot-backend` and click **Associate**.
   - *Note down this IP address as `<STATIC_PUBLIC_IP>`.*

### 1.2 AWS S3 Buckets & Least-Privilege IAM User
1. **Create Object Storage Buckets**:
   Navigate to **Amazon S3** -> **Create bucket**:
   - **Bucket 1 (Media/Proofs)**: Name `codeera-media`.
     - Region: Same region as EC2.
     - **Block all public access**: Keep **ENABLED** (checked).
     - Object Ownership: ACLs disabled (recommended).
     - Click **Create bucket**.
   - **Bucket 2 (Database Backups)**: Name `codeera-backups`.
     - Region: Same region as EC2.
     - **Block all public access**: Keep **ENABLED** (checked).
     - Under **Management** -> **Lifecycle rules** -> Add rule to expire/delete backup objects after `30 days`.
2. **Create Least-Privilege IAM Credentials**:
   - Open **IAM** -> **Users** -> **Create user** named `codeera-app-agent`.
   - Select **Attach policies directly** -> Click **Create policy** -> Choose **JSON** editor:
   ```json
   {
       "Version": "2012-10-17",
       "Statement": [
           {
               "Sid": "AllowAppMediaAndBackups",
               "Effect": "Allow",
               "Action": [
                   "s3:PutObject",
                   "s3:GetObject",
                   "s3:DeleteObject",
                   "s3:ListBucket"
               ],
               "Resource": [
                   "arn:aws:s3:::codeera-media",
                   "arn:aws:s3:::codeera-media/*",
                   "arn:aws:s3:::codeera-backups",
                   "arn:aws:s3:::codeera-backups/*"
               ]
           }
       ]
   }
   ```
   - Name the policy `CodeeraAppS3Policy` and attach it to `codeera-app-agent`.
   - Go to the user's **Security credentials** tab -> **Create access key** (Application running outside AWS).
   - Save the **Access Key ID** and **Secret Access Key** safely.

### 1.3 DNS Records Configuration
In your domain registrar DNS management (e.g. Cloudflare / Namecheap / GoDaddy) for `codeera.tech`:

| Type | Host / Name | Value / Target | TTL | Note |
|---|---|---|---|---|
| **A** | `api` | `<STATIC_PUBLIC_IP>` | 300s (Auto) | Directs `api.codeera.tech` to backend EC2 |
| **A** or **CNAME** | `@` (apex) | As specified in Vercel Domains | Auto | Directs `codeera.tech` to Vercel |
| **CNAME** | `app` | `cname.vercel-dns.com` | Auto | Directs `app.codeera.tech` to Vercel |

### 1.4 Vercel Frontend Project Setup
1. Log in to [Vercel](https://vercel.com/) and click **Add New...** -> **Project**.
2. Import the GitHub repository `waseemsaher/CYF`.
3. In **Project Settings**:
   - **Framework Preset**: SvelteKit.
   - **Root Directory**: `frontend`.
4. In **Environment Variables**, configure:
   - `PUBLIC_API_URL` = `https://api.codeera.tech/api/v1`
   - `PUBLIC_ENABLE_LANGUAGE_TOGGLE` = `false`
5. In **Domains**, add `codeera.tech` and `app.codeera.tech`.
6. Click **Deploy**.

### 1.5 Telegram Bot Setup & Webhook Registration
1. In the Telegram app, search for `@BotFather` and send `/newbot`.
2. Name the bot (e.g. `Codeera Academy Bot`) and choose a username ending in `bot` (e.g. `CodeeraPilotBot`).
3. Copy the HTTP API token provided by BotFather (e.g. `123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ`).
4. **Add Bot to Course Group**:
   - Open your private Telegram group for the pilot course.
   - Go to group settings -> **Add Member** -> search for `@CodeeraPilotBot`.
   - Promote the bot to **Administrator** with permissions:
     - `Can invite users via link` (Checked)
     - `Can ban users` (Checked)
     - `Can manage video chats` (Optional)
5. **Register Webhook**:
   Pick a secure random string for `<TELEGRAM_WEBHOOK_SECRET>` (e.g., generated with `openssl rand -hex 16`), then register the webhook from your local terminal:
   ```bash
   curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/setWebhook" \
     -H "Content-Type: application/json" \
     -d '{
       "url": "https://api.codeera.tech/api/v1/telegram/webhook",
       "secret_token": "<TELEGRAM_WEBHOOK_SECRET>",
       "allowed_updates": ["chat_join_request", "message"]
     }'
   ```
   Verify response is `{"ok":true,"result":true,"description":"Webhook was set"}`.

---

## 2. First Deploy & Initial Data Initialization

Connect to your EC2 instance via SSH:
```bash
ssh -i /path/to/your-key.pem ubuntu@<STATIC_PUBLIC_IP>
```

### 2.1 Server Hardening & 2GB Swap Setup
```bash
sudo apt-get update && sudo apt-get upgrade -y

# Allocate and activate 2GB swap file to prevent memory exhaustion
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Install Docker Engine & Docker Compose Plugin
sudo apt-get install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker "$USER"
newgrp docker
```

### 2.2 Clone Repository & Configure Environment
```bash
sudo mkdir -p /var/www/cyf
sudo chown -R "$USER":"$USER" /var/www/cyf
git clone https://github.com/waseemsaher/CYF.git /var/www/cyf
cd /var/www/cyf

# Copy production environment template
cp backend/.env.production.example .env
nano .env
```

Set your production values in `/var/www/cyf/.env`:
```dotenv
APP_NAME=Codeera
APP_ENV=production
APP_KEY=                      # Leave blank initially; generated below
APP_DEBUG=false
APP_URL=https://api.codeera.tech

FRONTEND_URL=https://codeera.tech
CORS_ALLOWED_ORIGINS="https://codeera.tech,https://app.codeera.tech"
SANCTUM_STATEFUL_DOMAINS="codeera.tech,app.codeera.tech"

SERVER_NAME=api.codeera.tech
ACME_EMAIL=admin@codeera.tech

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cyf
DB_USERNAME=cyf_user
DB_PASSWORD=<GENERATE_SECURE_PASSWORD>
DB_ROOT_PASSWORD=<GENERATE_SECURE_ROOT_PASSWORD>

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=<GENERATE_SECURE_REDIS_PASSWORD>

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@codeera.tech"
MAIL_FROM_NAME="Codeera"

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<YOUR_IAM_ACCESS_KEY>
AWS_SECRET_ACCESS_KEY=<YOUR_IAM_SECRET_KEY>
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=codeera-media
AWS_BACKUP_BUCKET=codeera-backups

TELEGRAM_BOT_TOKEN=<YOUR_TELEGRAM_BOT_TOKEN>
TELEGRAM_BOT_USERNAME="CodeeraPilotBot"
TELEGRAM_WEBHOOK_SECRET=<YOUR_TELEGRAM_WEBHOOK_SECRET>

APP_IMAGE=ghcr.io/waseemsaher/cyf-backend:latest
```

Generate application key:
```bash
APP_KEY=$(docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show)
sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
```

### 2.3 Execute Deployment
```bash
./scripts/deploy.sh
```
Verify all containers are healthy:
```bash
docker compose -f docker-compose.prod.yml ps
```

### 2.4 Initialize Superadmin Account
```bash
docker compose -f docker-compose.prod.yml exec -T app php artisan admin:create-superadmin admin@codeera.tech --name="Platform Owner"
```
*Save the printed superadmin password immediately.*

### 2.5 Configure Payment Method Settings
Set your real Egyptian wallet numbers and InstaPay handle:
```bash
# Vodafone Cash / Mobile Wallet number
docker compose -f docker-compose.prod.yml exec -T app php artisan settings:set payment_methods.vodafone_cash "010XXXXXXXX"

# InstaPay handle
docker compose -f docker-compose.prod.yml exec -T app php artisan settings:set payment_methods.instapay_handle "owner@instapay"

# Payment Instructions displayed on student checkout
docker compose -f docker-compose.prod.yml exec -T app php artisan settings:set payment_methods.instructions "يرجى تحويل الرسوم المقررة عبر فودافون كاش أو إنستاباي، والاحتفاظ بلقطة شاشة واضحة لعملية التحويل تتضمن رقم العملية وتاريخها."

# Upload size limits
docker compose -f docker-compose.prod.yml exec -T app php artisan settings:set uploads.proof_max_size_kb 5120
```

### 2.6 Import Course Content
Create your private content file `/var/www/cyf/pilot-content.json` using the template format documented in `docs/examples/pilot-content.example.json` (with real telegram chat ID and invite link).

1. Run strict dry-run validation:
   ```bash
   docker compose -f docker-compose.prod.yml exec -T app php artisan content:import pilot-content.json --dry-run
   ```
2. Execute real import:
   ```bash
   docker compose -f docker-compose.prod.yml exec -T app php artisan content:import pilot-content.json
   ```

### 2.7 Teacher Account Setup
```bash
# Create teacher account (temporary password printed once)
docker compose -f docker-compose.prod.yml exec -T app php artisan teacher:create teacher@codeera.tech "Dr. Teacher Name"

# Assign teacher to pilot course with 70% revenue share
docker compose -f docker-compose.prod.yml exec -T app php artisan teacher:assign teacher@codeera.tech fcai-discrete-math --share=70
```

---

## 3. Full Dress Rehearsal (Before Taking Real Student Money)

Perform this end-to-end rehearsal on the live production stack:

| Step | Action | Expected Result |
|---|---|---|
| **1. Student Registration** | Go to `https://codeera.tech/register`, create account with test student credentials. | Account created, logged in, redirected to student dashboard. |
| **2. Course Catalog** | Navigate to `/courses`, select the pilot course. | Course details display correctly in Arabic, with price formatted in Western digits (e.g. `150 ج.م`). |
| **3. Checkout** | Click **اشتراك / Enroll**, select Vodafone Cash / InstaPay, enter phone number, upload test image file. | Success message displays: "تم إرسال إيصال الدفع بنجاح وفي انتظار المراجعة". Payment status is `pending`. |
| **4. Admin Review** | Log in to `/admin/payments` as Superadmin, inspect proof image via signed S3 URL, click **قبول / Approve**. | Payment changes to `approved`, active `Enrollment` created, queued Telegram notification prepared. |
| **5. Telegram Linking** | In student dashboard, click **ربط تليجرام**, opens bot deep link `https://t.me/CodeeraPilotBot?start=<token>`, click Start. | Bot replies: "تم ربط حسابك بنجاح". Dashboard reflects Telegram linked status. |
| **6. Group Access** | Student clicks the course Telegram invite link, sends **Request to Join**. | Bot approves join request automatically within seconds without admin intervention. |
| **7. Content Access** | Student visits `/my-courses/fcai-discrete-math`. | Course shows `is_unlocked: true`, lecture links are accessible, course materials download without 403. |
| **8. Revocation Drill** | In EC2 terminal, run:<br>`docker compose -f docker-compose.prod.yml exec -T app php artisan enrollment:revoke <student-email> fcai-discrete-math` | Enrollment status changes to `revoked`. Refreshing course page locks content (download returns 403). |
| **9. Removal Sweep** | Run in terminal:<br>`docker compose -f docker-compose.prod.yml exec -T app php artisan telegram:remove-expired` | Bot removes test student from Telegram channel and group. |

---

## 4. Launch-Day Monitoring & Runbook Commands

Execute these diagnostic commands during launch day:

### 4.1 Check Stack Container Health
```bash
docker compose -f docker-compose.prod.yml ps
```
*Expected*: All 6 containers (`web`, `app`, `queue`, `scheduler`, `mysql`, `redis`) display status `Up (healthy)`.

### 4.2 Monitor Memory & CPU Usage
```bash
docker stats --no-stream
```
*Budget Check*: Total memory usage should remain around 300–450 MB on the 1GB VM.

### 4.3 Inspect Application & Queue Logs
```bash
# View last 100 lines of Laravel API logs
docker compose -f docker-compose.prod.yml exec -T app tail -n 100 storage/logs/laravel.log

# Stream Caddy reverse proxy logs
docker compose -f docker-compose.prod.yml logs --tail=100 -f web

# Check queue worker execution
docker compose -f docker-compose.prod.yml logs --tail=50 queue
```

### 4.4 Inspect Failed Jobs
```bash
docker compose -f docker-compose.prod.yml exec -T app php artisan queue:failed
```
If any jobs failed, view exception details and retry:
```bash
docker compose -f docker-compose.prod.yml exec -T app php artisan queue:retry all
```

### 4.5 Disk Space Verification
```bash
df -h /
```
Ensure `/var/lib/docker` has at least 5–10 GB free.

### 4.6 On-Demand Backup & Verification
```bash
# Manually trigger backup drill
./scripts/backup.sh
```
Verify the compressed archive was uploaded to S3:
```bash
docker compose -f docker-compose.prod.yml exec -T app php artisan tinker --execute="echo json_encode(Storage::disk('s3')->files('backups'));"
```

### 4.7 Restore from Backup (Disaster Recovery)
If a database corruption occurs:
```bash
./scripts/restore.sh --from-s3 --force
```

### 4.8 Instant Rollback Procedure
If a bad update was deployed, revert immediately to the previous commit:
```bash
git checkout <PREVIOUS_COMMIT_HASH>
./scripts/deploy.sh
```

---

## 5. Manual Fallback Plan (If Anything Breaks During Launch)

If any automated flow experiences unexpected issues on launch day:

### Fallback 1: Manual Payment Processing & Direct Access Grant
If the online checkout or proof upload has an issue:
1. Instruct students via Telegram/WhatsApp to send payment to the official wallet number.
2. Verify payment received in your Vodafone Cash / InstaPay app.
3. Grant course access directly via the Artisan CLI:
   ```bash
   docker compose -f docker-compose.prod.yml exec -T app php artisan enrollment:grant <student-email> fcai-discrete-math
   ```
4. Access is activated immediately; the student can refresh their dashboard and view content.

### Fallback 2: Manual Student Password Reset
If a student is locked out of their account:
```bash
docker compose -f docker-compose.prod.yml exec -T app php artisan user:reset-password <student-email>
```
Send the printed temporary password to the student privately. They will be forced to choose a new password upon login.

### Fallback 3: Telegram Bot API Outage
If Telegram's API is unresponsive:
1. In the Telegram group settings, generate a temporary direct invite link.
2. Share the link manually only with approved students via private chat.
