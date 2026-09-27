# Production Go-Live Checklist (Vercel Frontend + AWS EC2 Backend)

> **Stack Architecture**:
> - **Frontend**: Vercel Serverless Edge CDN (`codeera.tech`, `app.codeera.tech`)
> - **Backend API**: AWS EC2 Ubuntu VPS (`api.codeera.tech`) — temporary host pending Oracle migration
> - **Email**: SendGrid SMTP (`codeera.tech` domain authentication)
> - **File Storage & Backups**: AWS S3 Private Bucket with signed URLs

---

## 1. Domain & DNS Configuration (At Registrar)

Before routing traffic, configure the following DNS records at the domain registrar for `codeera.tech`:

### 1.1 Frontend (Vercel)
| Type | Name / Host | Value / Target | Notes |
|---|---|---|---|
| **A** | `@` (apex `codeera.tech`) | `76.76.21.21` | Vercel Anycast edge IP |
| **CNAME** | `app` (`app.codeera.tech`) | `cname.vercel-dns.com.` | Primary web app |
| **CNAME** | `www` (`www.codeera.tech`) | `cname.vercel-dns.com.` | Redirects to apex root |

### 1.2 Backend API (AWS EC2)
| Type | Name / Host | Value / Target | Notes |
|---|---|---|---|
| **A** | `api` (`api.codeera.tech`) | `<EC2_PUBLIC_IPV4_ADDRESS>` | Points directly to the AWS EC2 VM |

### 1.3 Email & Domain Authentication (SendGrid)
| Type | Name / Host | Value / Target | Purpose |
|---|---|---|---|
| **CNAME** | `em` (or assigned prefix) | `sendgrid.net` | SendGrid Mail CNAME (handles SPF alignment) |
| **CNAME** | `s1._domainkey` | `s1.domainkey.uXXXX.sendgrid.net` | SendGrid DKIM Key 1 |
| **CNAME** | `s2._domainkey` | `s2.domainkey.uXXXX.sendgrid.net` | SendGrid DKIM Key 2 |
| **TXT** | `@` (`codeera.tech`) | `v=spf1 include:sendgrid.net ~all` | Sender Policy Framework (SPF) |
| **TXT** | `_dmarc` | `v=DMARC1; p=none; rua=mailto:dmarc@codeera.tech` | DMARC policy |

---

## 2. Network & Port Exposure Verification on EC2 (CRITICAL)

The only ports publicly reachable from the internet MUST be `22` (SSH), `80` (HTTP), and `443` (HTTPS). Internal application runtimes (PHP-FPM, MySQL, Redis) must NEVER be exposed.

### 2.1 Verify Listening Sockets
Run on the EC2 VM:
```bash
sudo ss -tlnp
```
**Expected Output**:
- `nginx` is listening on `*:80` and `*:443` (or `0.0.0.0:80` and `0.0.0.0:443`).
- `php-fpm` is listening strictly on UNIX socket `/run/php/php8.3-fpm.sock` (NOT on TCP `9000`).
- `mysql` is bound to `127.0.0.1:3306`.
- `redis-server` is bound to `127.0.0.1:6379`.
- **NO Node.js process** is running or listening on port `3000` (frontend is 100% on Vercel).

### 2.2 Verify AWS Security Group
In the AWS EC2 Console → Network & Security → Security Groups:
- Inbound:
  - `SSH` (22/tcp) restricted to your IP address.
  - `HTTP` (80/tcp) allowed from `0.0.0.0/0`.
  - `HTTPS` (443/tcp) allowed from `0.0.0.0/0`.
- Outbound: `All traffic` allowed.

### 2.3 Verify Host Firewall (UFW)
Run on the VM:
```bash
sudo ufw status verbose
```
Expected: `22`, `80`, `443` allowed; default incoming `deny`.

### 2.4 External Port Scan Verification
From an external computer:
```bash
nmap -p 22,80,443,3000,3306,6379,9000 <EC2_PUBLIC_IP>
```
- `22/tcp`: open
- `80/tcp`: open
- `443/tcp`: open
- `3000/tcp`: filtered / closed
- `3306/tcp`: filtered / closed
- `6379/tcp`: filtered / closed
- `9000/tcp`: filtered / closed

---

## 3. Frontend Deployment (Vercel)

- [ ] Repository connected to Vercel.
- [ ] Root directory set to `frontend`.
- [ ] Framework preset detected as **SvelteKit**.
- [ ] Build command: `npm run build` (outputs to `.vercel/output`).
- [ ] Environment variable in Vercel project settings:
  - `PUBLIC_API_BASE_URL` = `https://api.codeera.tech/api/v1`
- [ ] Domains assigned: `codeera.tech` (apex) and `app.codeera.tech`.

---

## 4. Cross-Origin Sanctum Authentication Check

- [ ] Send request to `https://api.codeera.tech/sanctum/csrf-cookie` from `https://app.codeera.tech`.
- [ ] Verify `Set-Cookie` headers have `Domain=.codeera.tech; Secure; SameSite=Lax`.
- [ ] Login test: Submit credentials from `https://app.codeera.tech/login`. Confirm login returns `200 OK` and session cookie is stored in browser.
- [ ] Subsequent request to `https://api.codeera.tech/api/v1/me` succeeds with cookie credentials (no Authorization Bearer header needed).

---

## 5. Storage & S3 Hardening Check

- [ ] S3 Bucket Permissions: Bucket is **Private** (Block all public access enabled).
- [ ] IAM credentials in `.env` are scoped with least privilege to only the `codeera-media` bucket.
- [ ] Signed URLs: Admin proof image URLs and course item file downloads generate expiring signed URLs (`temporaryUrl`).
- [ ] Upload limits: Nginx enforces `client_max_body_size 55M`.

---

## 6. Daily Automated Backups Check

- [ ] Test backup creation: `sudo -u www-data php /var/www/cyf/backend/artisan db:backup`.
- [ ] Confirm compressed file is saved locally to `/var/backups/cyf/`.
- [ ] Confirm file is uploaded to `s3://codeera-media/backups/`.
- [ ] Test restore procedure: `sudo -u www-data php /var/www/cyf/backend/artisan db:restore --from-s3 --force`.
- [ ] Confirm daily cron job is installed at `/etc/cron.d/cyf-cron`.

---

## 7. Supervisor & Health Check

- [ ] `sudo supervisorctl status` reports:
  - `cyf-worker:*` in `RUNNING` state.
  - `cyf-scheduler` in `RUNNING` state.
- [ ] `curl -I https://api.codeera.tech/up` returns `HTTP 200 OK`.
- [ ] `tail -n 50 /var/www/cyf/backend/storage/logs/laravel.log` contains zero fatal errors.
