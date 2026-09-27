# Production Go-Live Checklist

This checklist must be executed on the production DigitalOcean Droplet before updating DNS records to point live traffic to the server.

---

## 1. Network & Port Exposure Verification (CRITICAL)

The only ports publicly reachable from the internet MUST be `22` (SSH), `80` (HTTP), and `443` (HTTPS). Internal application runtimes (Node.js SvelteKit and PHP-FPM) must never be accessible externally.

### Step 1.1: Verify Listening Sockets
Run on the droplet:
```bash
sudo ss -tlnp
```
**Expected Output Verification**:
- `nginx` is listening on `*:80` and `*:443` (or `0.0.0.0:80` and `0.0.0.0:443`).
- `node` (SvelteKit SSR) is bound **STRICTLY** to `127.0.0.1:3000` (or `localhost:3000`), **NEVER** `0.0.0.0:3000` or `:::3000`.
- `php-fpm` is listening on a UNIX socket (`/run/php/php8.3-fpm.sock`), **NOT** on any TCP port (e.g. `9000`).
- `mysql` is bound to `127.0.0.1:3306`.
- `redis-server` is bound to `127.0.0.1:6379`.

> **Warning**: If `node` appears listening on `0.0.0.0:3000`, check `/etc/supervisor/conf.d/cyf-frontend.conf` and ensure `HOST="127.0.0.1"` is present in the `environment` directive, then run `sudo supervisorctl update && sudo supervisorctl restart cyf-frontend`.

### Step 1.2: Verify Host Firewall (UFW)
Run on the droplet:
```bash
sudo ufw status verbose
```
**Expected Status**:
```text
Status: active
Logging: on (low)
Default: deny (incoming), allow (outgoing), disabled (routed)
New profiles: skip

To                         Action      From
--                         ------      ----
22/tcp                     ALLOW IN    Anywhere
80/tcp                     ALLOW IN    Anywhere
443/tcp                    ALLOW IN    Anywhere
22/tcp (v6)                ALLOW IN    Anywhere (v6)
80/tcp (v6)                ALLOW IN    Anywhere (v6)
443/tcp (v6)               ALLOW IN    Anywhere (v6)
```

### Step 1.3: Verify External Port Scan (from an external machine)
From your local terminal, run `nmap` against the droplet's public IP:
```bash
nmap -p 22,80,443,3000,9000,3306,6379 <DROPLET_IP>
```
**Expected Status**:
- `22/tcp`: open
- `80/tcp`: open
- `443/tcp`: open
- `3000/tcp`: filtered / closed
- `9000/tcp`: filtered / closed
- `3306/tcp`: filtered / closed
- `6379/tcp`: filtered / closed

---

## 2. Nginx & SvelteKit Security Header Verification

Verify through the public HTTPS URL (routed through Nginx) that security headers are applied correctly without duplicates.

### Step 2.1: Frontend HTML Response Check
Run:
```bash
curl -I https://courses.fcai-azhar.edu.eg/
```
**Required Verifications**:
- [ ] `Strict-Transport-Security: max-age=63072000; includeSubDomains; preload` is present.
- [ ] `X-Frame-Options: SAMEORIGIN` is present (exactly once).
- [ ] `X-Content-Type-Options: nosniff` is present (exactly once).
- [ ] `Referrer-Policy: strict-origin-when-cross-origin` is present.
- [ ] `Permissions-Policy: camera=(), microphone=(), geolocation=()` is present.
- [ ] `Content-Security-Policy` is present **EXACTLY ONCE**, includes per-request nonce (`script-src 'self' 'nonce-...'`), and contains **NO** `'unsafe-inline'`.
- [ ] `Access-Control-Allow-Origin` is **COMPLETELY ABSENT** from HTML pages.

### Step 2.2: Backend API Response Check
Run:
```bash
curl -I https://courses.fcai-azhar.edu.eg/api/v1/courses
```
**Required Verifications**:
- [ ] Security headers from Laravel middleware are present.
- [ ] `Content-Security-Policy` for API allows only trusted origins.
- [ ] `Access-Control-Allow-Origin` only appears on allowed cross-origin requests.

---

## 3. Storage & Upload Hardening Check

- [ ] Confirm storage directory permissions: `storage/app/private` is owned by `www-data:www-data` with mode `750`.
- [ ] Confirm direct web requests to private files are rejected:
  ```bash
  curl -I https://courses.fcai-azhar.edu.eg/storage/app/private/test.jpg
  ```
  Must return `404 Not Found` or `403 Forbidden`.
- [ ] Upload size limits: Test an upload exceeding the configured threshold (e.g. > 50MB) and verify Nginx returns `413 Request Entity Too Large` and Laravel returns `422 Unprocessable Entity`.

---

## 4. Application Runtime & Monitoring Check

- [ ] `sudo supervisorctl status` reports `cyf-frontend` and `cyf-worker:*` in `RUNNING` state.
- [ ] `curl -I https://courses.fcai-azhar.edu.eg/up` returns `HTTP 200 OK`.
- [ ] `tail -n 50 /var/log/supervisor/cyf-frontend.log` contains zero errors.
- [ ] `tail -n 50 /var/www/cyf/backend/storage/logs/laravel-*.log` contains zero errors.
