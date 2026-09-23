# Al-Azhar FCAI Course Platform
### منصة دورات كلية الحاسبات والذكاء الاصطناعي — جامعة الأزهر

An e-learning course management and enrollment platform specifically designed for students of the Faculty of Computers & Artificial Intelligence (FCAI) at Al-Azhar University, Egypt.

---

## 🏗️ Architecture & Stack

The repository is structured as a high-performance monorepo:

```
├── .github/workflows/  # Continuous Integration (PHP 8.3, Pest, Larastan, SvelteKit)
├── backend/            # Laravel 12 REST API (/api/v1)
├── frontend/           # SvelteKit (Svelte 5 runes, SSR, Tailwind CSS, RTL)
├── deploy/             # Production Nginx, PHP-FPM, Supervisor, PM2, and Docker configurations
├── scripts/            # Zero-downtime deployment, backup, and restore automation
└── docs/               # Architecture requirements, decisions log, API specs, and runbooks
```

### Core Technologies
- **Backend**: PHP 8.3+, Laravel 12, MySQL 8 (InnoDB utf8mb4), Redis (Queues, Cache, Sessions), Laravel Sanctum (SPA Cookie Auth).
- **Frontend**: SvelteKit with Svelte 5 (Runes), TypeScript (Strict), Tailwind CSS with CSS logical properties (`dir="rtl"` primary, LTR supported), IBM Plex Sans Arabic typography.
- **Telegram Bot**: Automated join-request management, member verification, expiry handling, and private notifications.
- **Media & Storage**: DigitalOcean Spaces (S3-compatible) private bucket with pre-signed URLs; server-side EXIF stripping and MIME verification.
- **DevOps**: Systemd/Supervisor worker daemonization, PM2 cluster, automated S3 database backups, and zero-downtime Bash deploy scripts.

---

## 🚀 Quickstart & Local Setup

### 1. Prerequisites
- PHP 8.3+ with extensions: `pdo_mysql`, `pdo_sqlite`, `redis`, `gd`, `intl`, `mbstring`, `xml`
- Composer 2.x
- Node.js 20+ & npm
- MySQL 8.x and Redis

### 2. Backend Setup
```bash
cd backend

# Install dependencies
composer install

# Environment configuration
cp .env.example .env
php artisan key:generate

# Run database migrations and seed reference data
php artisan migrate:fresh --seed

# Start the development server
php artisan serve --port=8000
```

### 3. Frontend Setup
```bash
cd frontend

# Install dependencies
npm install

# Configure environment variables
# (VITE_API_BASE_URL=http://localhost:8000/api/v1)
cp .env.example .env

# Run the SvelteKit development server
npm run dev
```
Open `http://localhost:5173` in your browser.

---

## 🧪 Verification & Quality Checks

Run the full verification suite before committing any code:

### Backend Checks
```bash
cd backend

# Code formatting (Laravel Pint)
./vendor/bin/pint --test

# Static analysis (Larastan Level 6)
./vendor/bin/phpstan analyse

# Test suite (Pest)
./vendor/bin/pest

# Fresh database migrations & seeders check
php artisan migrate:fresh --seed
```

### Frontend Checks
```bash
cd frontend

# Type-check and Svelte diagnostics
npm run check

# Production SSR bundle build
npm run build
```

### Ops & Deployment Script Validation
```bash
bash -n scripts/*.sh
```

---

## 📚 Documentation Reference

- **[AGENT.md](AGENT.md)**: Strict instructions and architectural guidelines for AI developers and contributors.
- **[REQUIREMENTS.md](docs/REQUIREMENTS.md)**: Detailed business logic, domain models, flows, roles, and milestones.
- **[DECISIONS.md](docs/DECISIONS.md)**: Architectural Decision Records (ADRs) across all milestones (M0–M8).
- **[API.md](docs/API.md)**: Complete REST API documentation for all `/api/v1` endpoints.
- **[DEPLOYMENT.md](docs/DEPLOYMENT.md)**: Production deployment runbook, DigitalOcean server setup, Nginx, SSL, Supervisor, and Telegram webhooks.

---

## 🛡️ License

Proprietary — Al-Azhar FCAI Course Platform. All rights reserved.
