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

### 4.1 Provision the Oracle VM (same stack, different provider)
Follow the same provider-agnostic provisioning steps used for the AWS EC2 setup (Nginx, PHP-FPM,
MySQL 8, Redis, Composer, Supervisor — self-managed, nothing Oracle-managed). If
`docs/` has a "Provisioning the backend VM" doc from the AWS setup, reuse it almost unchanged;
update only provider-specific bits (security group → Oracle's equivalent network security rules:
only 22/80/443 open, everything else bound to localhost).

### 4.2 Move the database
1. On AWS: `mysqldump` a fresh full backup of the production database.
2. Transfer that dump file securely to the Oracle VM (`scp`, or via the same S3 bucket already
   used for backups — download from S3 on the Oracle side).
3. On Oracle: create the database and import the dump.
4. Spot-check row counts on a few key tables (users, courses, payments, enrollments) against the
   AWS source to confirm the import is complete and matches.

### 4.3 Move file storage
Depends on what was decided in `docs/DECISIONS.md` for the AWS phase (either AWS S3 stayed as the
storage driver, or it was already Oracle-ready):
- If still using AWS S3: either keep using S3 as the storage backend even after the app server
  moves to Oracle (S3 access isn't tied to running on AWS — this is the simplest option and
  avoids a file migration entirely), OR migrate files to Oracle Object Storage if the goal is to
  leave AWS completely. Decide this explicitly, don't leave it ambiguous.
- If migrating storage too: copy every file from the S3 bucket to Oracle Object Storage (or local
  disk on the Oracle VM, matching whatever the smaller/simpler option was for file storage),
  update `backend/config/filesystems.php`'s active disk, and re-verify signed URLs still work
  correctly.

### 4.4 Environment and secrets
- Recreate `.env` on the Oracle VM with the same values as AWS (database credentials will differ
  since it's a new local MySQL instance; storage credentials depend on 4.3's decision; app key,
  Sanctum/session/CORS domain settings, SendGrid, Telegram bot token/webhook secret all carry
  over unchanged since the domain itself isn't changing).
- Do NOT commit the `.env` file anywhere — copy it directly, out of band (e.g. `scp` between the
  two servers directly, not through git or chat).

### 4.5 Point Nginx and SSL at the new server
- Set up Nginx on Oracle the same way as on AWS (API-only reverse proxy to PHP-FPM).
- Get a fresh SSL certificate for `api.codeera.tech` on the Oracle VM via Certbot — don't try to
  copy the AWS certificate over; issuing a new one is simpler and more reliable.

### 4.6 Verify before cutting over — this is the most important step
Before touching DNS, verify the Oracle server fully works while AWS is still live and still the
one serving real traffic:
- Temporarily hit the Oracle server directly by IP (or a temporary test subdomain) and run through
  the full critical path: register/login, browse courses, submit a payment, admin approval flow,
  Telegram linking, quiz attempt.
- Confirm the queue workers and scheduled jobs (enrollment expiry, Telegram membership removal)
  are actually running under Supervisor on the Oracle VM.
- Confirm backups are configured and working on the new server too, from day one — don't let a
  gap in backup coverage happen during the transition.

### 4.7 Cut over
1. Update the `api.codeera.tech` DNS A record to point to the Oracle VM's IP.
2. Watch traffic shift over (the lowered TTL from the pre-migration checklist should make this
   fast — usually minutes, not hours).
3. Keep the AWS instance running, untouched, for a few more days as a fallback in case something
   unexpected shows up under real traffic that testing didn't catch.
4. Monitor error logs and the Telegram webhook specifically in the hours right after cutover —
   Telegram will keep trying the old server's IP briefly if there's any DNS caching on their end,
   so confirm webhook deliveries are landing on Oracle successfully.

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