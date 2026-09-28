# Provisioning the Backend VM (Docker Compose Deployment Guide)

> [!IMPORTANT]
> **Infrastructure Status Notice**: This pull request prepares and verifies all **application code, Docker configuration, and deployment automation**. No AWS EC2 or Oracle Cloud virtual machine has been provisioned yet. The repository owner must provision the server manually using the exact provider-agnostic steps detailed below before pointing DNS.

---

## 1. Instance Specification & Security Group

### Instance Sizing
* **Operating System**: Ubuntu 24.04 LTS (x86_64 or ARM64).
* **AWS EC2 (Phase 1 — Temporary)**: `t2.micro` or `t3.micro` (1 vCPU, 1 GB RAM) or `t4g.micro` (ARM64).
* **Oracle Cloud Always Free (Phase 2 — Permanent)**: `VM.Standard.A1.Flex` (1–4 OCPUs, 6–24 GB RAM, ARM64) or `VM.Standard.E2.1.Micro`.
* **Storage**: 20–30 GB gp3 / Standard SSD volume.

### Security Group / Ingress Firewall Rules
Configure the cloud firewall (AWS Security Group / Oracle Security List) strictly as follows:

| Protocol | Port | Source | Purpose |
| :--- | :--- | :--- | :--- |
| **TCP** | `22` | `<OWNER_MANAGEMENT_IP>/32` | Administrative SSH access (Owner IP only) |
| **TCP** | `80` | `0.0.0.0/0`, `::/0` | Let's Encrypt HTTP-01 ACME challenges & HTTPS redirect |
| **TCP** | `443` | `0.0.0.0/0`, `::/0` | Encrypted REST API traffic (Caddy Reverse Proxy) |

> [!CAUTION]
> **Strict Internal Port Isolation**: Never expose port `3306` (MySQL), port `6379` (Redis), or port `9000` (FastCGI) to the public internet. The `docker-compose.prod.yml` configuration deliberately publishes **only ports 80 and 443** on the `web` reverse proxy. All database, cache, and application communication occurs exclusively over the isolated internal Docker bridge network (`cyf-network`).

---

## 2. Allocate and Attach a Static Public IP

Before configuring DNS, allocate a static public IP:
* **AWS EC2**: Allocate an **Elastic IP (EIP)** and associate it with the EC2 instance.
* **Oracle Cloud**: Allocate a **Reserved Public IP** and associate it with the instance VNIC.

> [!WARNING]
> Default cloud public IPs are ephemeral. If an instance is stopped and started without an attached Elastic IP / Reserved IP, its public IP address changes, which immediately severs public DNS resolution for `api.codeera.tech`.

---

## 3. Server Hardening & 2GB Swap Configuration

Connect to the instance via SSH:
```bash
ssh -i /path/to/private-key.pem ubuntu@<STATIC_PUBLIC_IP>
```

### Update Packages & Configure 2GB Swap File
On 1–2 GB RAM instances, a swap file provides essential memory headroom during composer builds and database maintenance routines:

```bash
sudo apt-get update && sudo apt-get upgrade -y

# Allocate 2GB Swap File
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Make swap persistent across reboots
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Verify swap activation
free -h
```

---

## 4. Install Docker Engine & Compose Plugin

Install the official Docker Engine and Docker Compose v2:

```bash
# Install prerequisites
sudo apt-get install -y ca-certificates curl gnupg

# Add Docker official GPG key
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

# Add Docker APT repository
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine and Compose Plugin
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Allow current user to run docker without sudo
sudo usermod -aG docker "$USER"
newgrp docker

# Verify installation
docker --version
docker compose version
```

---

## 5. Clone Repository and Configure Production Secrets

```bash
# Create application directory
sudo mkdir -p /var/www/cyf
sudo chown -R "$USER":"$USER" /var/www/cyf

# Clone repository
git clone https://github.com/waseemsaher/CYF.git /var/www/cyf
cd /var/www/cyf

# Create production environment file from template
cp backend/.env.production.example .env
```

Edit `/var/www/cyf/.env` with your secure credentials:
```bash
nano .env
```

Ensure the following variables are set:
```dotenv
APP_NAME=Codeera
APP_ENV=production
APP_KEY=                          # Generate with: docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://api.codeera.tech

FRONTEND_URL=https://codeera.tech
CORS_ALLOWED_ORIGINS="https://codeera.tech,https://app.codeera.tech"
SANCTUM_STATEFUL_DOMAINS="codeera.tech,app.codeera.tech"

SESSION_DRIVER=redis
SESSION_DOMAIN=.codeera.tech
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

SERVER_NAME=api.codeera.tech
ACME_EMAIL=admin@codeera.tech

# Database (Internal Docker network hostname)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cyf
DB_USERNAME=cyf_user
DB_PASSWORD=<STRONG_GENERATED_APP_PASSWORD>
DB_ROOT_PASSWORD=<STRONG_GENERATED_ROOT_PASSWORD>

# Redis (Internal Docker network hostname)
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=<STRONG_GENERATED_REDIS_PASSWORD>

# Object Storage (AWS S3)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<AWS_S3_ACCESS_KEY>
AWS_SECRET_ACCESS_KEY=<AWS_S3_SECRET_KEY>
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=codeera-media
AWS_BACKUP_BUCKET=codeera-backups

# Transactional Email (Default to 'log' for pilot launch; no mail sent until provider configured)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@codeera.tech"
MAIL_FROM_NAME="Codeera"

# Container Image (Fast Path: pull from GHCR; Fallback: build locally)
APP_IMAGE=ghcr.io/waseemsaher/cyf-backend:latest
```

---

## 6. Deploy the Docker Stack

> [!TIP]
> **Fast Path vs. Host Build**:
> - **Fast Path (Recommended, ~5 minutes)**: Pull the pre-built, tested `linux/amd64` Docker image from GitHub Container Registry (`GHCR`). This prevents out-of-memory errors and completes in minutes on 1–2 GB RAM instances.
> - **Fallback Path (Local Host Build, ~15–20 minutes)**: Build directly on the VM. The 2GB swap file created in Section 3 ensures compilation completes safely without OOM.
> - **Multi-Architecture Note**: Current CI builds `linux/amd64` for AWS EC2 `t2`/`t3` instances. For future Oracle Cloud Ampere A1 (ARM64) instances, multi-arch builds can be enabled in CI or natively built on the VM.

### Option A: Fast Path (Pull pre-built image from GHCR)

```bash
cd /var/www/cyf

# Pull pre-built images from GitHub Container Registry
docker compose -f docker-compose.prod.yml pull

# Run deployment automation
./scripts/deploy.sh
```

### Option B: Fallback Path (Build locally on host with 2GB swap)

```bash
cd /var/www/cyf

# Builds image on VM using local Dockerfile and 2GB swap
APP_IMAGE=cyf-backend:prod ./scripts/deploy.sh
```

The deployment script executes the following automated pipeline:
1. Pulls or builds the production PHP 8.4 FPM container.
2. Runs database migrations (`php artisan migrate --force`).
3. Launches `web` (Caddy), `app` (PHP-FPM), `queue` (Worker), `scheduler` (Artisan Scheduler), `mysql` (MySQL 8), and `redis` (Redis 7).
4. Optimizes Laravel cache (`php artisan optimize`).
5. Performs automated `/up` health checks.

---

## 7. Operational Verification & Monitoring

### Check Container Status
```bash
docker compose -f docker-compose.prod.yml ps
```
All containers should display status `Up (healthy)`.

### Check Resource Usage (Memory Budget)
```bash
docker stats --no-stream
```
*Expected baseline memory usage*:
* Caddy (`cyf-prod-web`): ~17 MiB
* PHP-FPM (`cyf-prod-app`): ~48 MiB
* Queue Worker (`cyf-prod-queue`): ~33 MiB
* Scheduler (`cyf-prod-scheduler`): ~34 MiB
* MySQL 8 (`cyf-prod-mysql`): ~167 MiB
* Redis 7 (`cyf-prod-redis`): ~6 MiB
* **Total Stack Baseline**: **~305 MiB** (easily fits within 1 GB RAM).

### Verify Public Health Check Endpoint
```bash
curl -I https://api.codeera.tech/up
```
Expected response:
```http
HTTP/2 200
content-type: text/html; charset=utf-8
strict-transport-security: max-age=63072000; includeSubDomains; preload
x-content-type-options: nosniff
x-frame-options: DENY
```

### Create Initial Superadmin User
```bash
docker compose -f docker-compose.prod.yml exec -T app php artisan cyf:create-superadmin admin@codeera.tech "Platform Owner"
```

### Trigger On-Demand Backup Drill
```bash
./scripts/backup.sh
```
This dumps MySQL, gzips the archive, and uploads to `s3://${AWS_BUCKET}/backups/cyf_db_<timestamp>.sql.gz`.
