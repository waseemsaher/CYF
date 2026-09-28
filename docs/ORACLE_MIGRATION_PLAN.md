# Oracle Migration Plan — moving the backend off AWS

> Purpose: this is the runbook for moving the backend from AWS EC2 to Oracle Cloud's Always Free
> tier before the AWS Free Plan credit expires (see `docs/AWS_MIGRATION_DEADLINE.md` for the
> exact date once it exists). Read this once now so the plan is understood; execute it close to
> the actual deadline, not months early (no need to pay for two servers longer than necessary).

---

## 1. Why this migration happens at all
The backend started on AWS EC2 using the AWS Free Plan (up to $200 in credit). That plan expires
6 months after the AWS account was created, or when the credit runs out — whichever comes first.
When it expires, AWS closes the account unless upgraded to a Paid Plan (which then bills standard
rates). Oracle Cloud's Always Free tier has no such expiry, so the plan from day one was: use AWS
temporarily, move to Oracle before the clock runs out.

Because the backend was deliberately built **without** AWS-managed services (no RDS, no
ElastiCache — MySQL and Redis run self-managed on the EC2 instance itself, per
`docs/REQUIREMENTS.md`), this migration is close to moving a normal Ubuntu server, not
re-architecting anything.

---

## 2. Timing — start with margin, not on the deadline day
- Start this process **at least 2–3 weeks before** the AWS Free Plan expiry date.
- Do NOT wait until the last few days — Oracle Cloud signup can be rejected by its anti-fraud
  system and sometimes needs a retry after a day or two (see the earlier signup guidance). If
  that happens close to the AWS deadline, there's no time left to recover.
- The two servers (AWS + Oracle) will run in parallel for a short overlap period (a few days) so
  the cutover can be verified calmly before AWS is shut down — budget for that overlap, it's
  normal and safe.

---

## 3. Pre-migration checklist (do this well before the cutover)
- [ ] Oracle Cloud Always Free account created and active (done ahead of time, not on the
      deadline day).
- [ ] Oracle Ampere A1 instance launched: Ubuntu, same OS version as the AWS instance if possible.
- [ ] Confirm the current AWS EC2 instance's specs (CPU/RAM/disk) so the Oracle instance is sized
      at least as large — check the Oracle account's current Always Free allowance first (it was
      last confirmed at 2 OCPU/12GB as of mid-2026, but always check the Oracle console directly
      since this can change).
- [ ] DNS TTL for `api.codeera.tech`'s A record lowered in advance (e.g. to 300 seconds/5 minutes)
      a day or two before the cutover, so the eventual DNS switch propagates fast instead of
      taking hours.

---

## 4. Migration steps

### 4.1 Provision the Oracle VM & Install Docker
Follow [`docs/PROVISIONING_BACKEND_VM.md`](file:///home/kaminari0x/cyf/docs/PROVISIONING_BACKEND_VM.md):
1. Launch Ubuntu 24.04 on Oracle Ampere A1 (or standard VM).
2. Configure Security List: open ports 22 (owner IP only), 80, 443. Never expose 3306 or 6379.
3. Attach an Oracle Reserved Public IP.
4. Configure 2GB swap file.
5. Install Docker Engine and the Docker Compose plugin.
6. Clone the repository into `/var/www/cyf`.

### 4.2 Copy Production Environment Configuration
- Securely copy the production `.env` file from the AWS instance to the Oracle instance at `/var/www/cyf/.env` (`scp` between instances out-of-band).
- All domain, Sanctum, CORS, SendGrid, and Telegram credentials carry over identically.
- If switching storage from S3 to Oracle Object Storage, update `AWS_ENDPOINT` and bucket credentials in `.env`.

### 4.3 Build Stack & Migrate Database
Because the entire backend is Dockerized, migration is straightforward:
1. On AWS EC2: Run `./scripts/backup.sh` to upload a final consistent database snapshot to S3 (`backups/cyf_db_<timestamp>.sql.gz`).
2. On Oracle VM: Build and start the stack:
   ```bash
   cd /var/www/cyf
   docker compose -f docker-compose.prod.yml build
   docker compose -f docker-compose.prod.yml up -d
   ```
3. Restore the database directly from S3 into the MySQL container:
   ```bash
   ./scripts/restore.sh --from-s3 --force
   ```
4. Verify data integrity by spot-checking table counts inside the container:
   ```bash
   docker compose -f docker-compose.prod.yml exec -T app php artisan tinker --execute="echo 'Users: ' . App\Models\User::count(); echo ' Courses: ' . App\Models\Course::count();"
   ```

### 4.4 Automated SSL via Caddy
- No manual Certbot or Nginx configuration is needed. Caddy in `docker-compose.prod.yml` manages TLS certificate issuance and renewal automatically via Let's Encrypt / ZeroSSL as soon as DNS points to the server.

### 4.5 Verify Before Cutover
Before changing public DNS:
- Temporarily test the Oracle server:
  ```bash
  docker compose -f docker-compose.prod.yml exec -T app php artisan test
  docker compose -f docker-compose.prod.yml exec -T web wget -qO- http://127.0.0.1:80/up
  ```
- Confirm the `queue` and `scheduler` containers are healthy and running:
  ```bash
  docker compose -f docker-compose.prod.yml ps
  ```

### 4.6 Cut Over DNS
1. Update the `api.codeera.tech` DNS A record to point to the Oracle Reserved Public IP.
2. Watch traffic shift over (the lowered TTL from the pre-migration checklist ensures propagation within minutes).
3. Keep the AWS instance running for 3–5 days as a fallback before terminating.

### 4.8 Decommission AWS
Once a few days have passed with no issues:
- Take one final backup from the AWS instance (in case anything was missed) and store it safely.
- Terminate the AWS EC2 instance (this is what actually stops it from ever billing you — an
  instance left running past the Free Plan's credit does incur real charges even before the
  6-month auto-close).
- Release/delete any other AWS resources created along the way (S3 bucket — only after confirming
  it's genuinely no longer used, Elastic IP if one was allocated — unattached Elastic IPs are a
  common source of surprise AWS charges even on otherwise-free accounts).

---

## 5. Rollback plan
If something goes seriously wrong after cutover and the AWS instance is still running (per the
overlap window in 4.7): revert the `api.codeera.tech` DNS record back to the AWS IP. Because
nothing was deleted on the AWS side yet, this is a fast, low-risk fallback. This is exactly why
step 4.7's "keep AWS running for a few days" matters — don't skip it to save a few dollars of
overlap time.

---

## 6. What does NOT change in this migration
- The domain (`codeera.tech`), the frontend (still Vercel, untouched by this entire migration),
  the app's code, and its behavior for students/teachers/admins — none of this changes. This is
  purely an infrastructure move for the backend server.
- No `REQUIREMENTS.md` business logic changes as a result of this migration — if anything here
  seems to require a business-logic change, stop and flag it rather than deciding unilaterally.