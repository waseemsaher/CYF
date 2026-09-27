# AWS Free Plan Expiry & Oracle Cloud Migration Deadline

> **Critical Notice**: The AWS EC2 backend deployment is strictly temporary, utilizing the AWS Free Plan / student credits. It must be migrated to Oracle Cloud's Always Free tier before the credit or 6-month period expires to avoid sudden service disruption or unexpected charges.

---

## 1. Expiration & Cutoff Timeline

| Milestone | Target Date | Notes |
|---|---|---|
| **AWS Account Activation** | **September 22, 2026** | Initial AWS account & repository creation date |
| **AWS Free Plan Expiry (6-Month Cap)** | **March 22, 2027** | AWS Free Plan automatically closes or converts to standard billing |
| **Recommended Migration Start Date** | **March 1, 2027** | Start Oracle account signup and VM provisioning (3 weeks prior) |
| **Owner Calendar Reminder Date** | **March 8, 2027** | Hard reminder (2 weeks prior) to begin active cutover |

### How to Check the Exact Date in AWS Console:
1. Log in to the [AWS Management Console](https://console.aws.amazon.com/).
2. Navigate to **Billing and Cost Management** → **Free Tier** (or search "Free Tier" in the search bar).
3. Review:
   - **Remaining credits / credit balance**.
   - **Month-to-date usage** and **expiration date**.
4. Confirm whether your plan expires by date or by credit exhaustion ($200 credit limit).

---

## 2. Oracle Migration Checklist

When the reminder fires, follow this step-by-step checklist to execute the migration calmly and safely:

- [ ] **Step 1: Create Oracle Cloud Always Free Account** (Do this 2-3 weeks in advance).
  - Select an Ampere A1 (ARM64, up to 4 OCPUs / 24GB RAM free) or AMD E2 instance.
  - Choose Ubuntu 24.04 LTS or 22.04 LTS.
- [ ] **Step 2: Provision Oracle VM**
  - Follow the provider-agnostic steps in [`docs/PROVISIONING_BACKEND_VM.md`](file:///home/kaminari0x/cyf/docs/PROVISIONING_BACKEND_VM.md).
  - Open only ports 22, 80, and 443 in Oracle's VCN Security Lists / Network Security Groups.
- [ ] **Step 3: Lower DNS TTL in Advance**
  - At your domain registrar/DNS provider, lower the TTL on `api.codeera.tech`'s A record to **300 seconds (5 minutes)** 24 hours before cutover.
- [ ] **Step 4: Transfer Database**
  - On AWS EC2: Run `php /var/www/cyf/backend/artisan db:backup` or `bash /var/www/cyf/scripts/backup.sh`.
  - On Oracle VM: Run `php /var/www/cyf/backend/artisan db:restore --from-s3 --force` (or `scp` the dump file from AWS and run `bash /var/www/cyf/scripts/restore.sh`).
  - Verify record counts match across critical tables (`users`, `courses`, `payments`, `enrollments`).
- [ ] **Step 5: Configure Storage & Secrets**
  - Copy `.env` to the Oracle VM securely out-of-band (`scp`).
  - If migrating files from AWS S3 to Oracle Object Storage: synchronize bucket contents via `rclone` or S3 API, and update `.env` storage credentials. (Or retain S3 disk if leaving only the EC2 compute layer).
- [ ] **Step 6: Setup Nginx & Certbot on Oracle**
  - Copy `deploy/nginx/cyf.conf` to `/etc/nginx/sites-available/cyf`.
  - Issue Let's Encrypt certificate via Certbot for `api.codeera.tech`.
- [ ] **Step 7: Pre-Cutover Verification**
  - Verify all supervisor processes (`cyf-worker`, `cyf-scheduler`) are active on Oracle.
  - Run critical path test requests directly against Oracle VM.
- [ ] **Step 8: DNS Cutover**
  - Update the A record for `api.codeera.tech` at your registrar to point to the new Oracle Cloud public IP.
  - Monitor traffic propagation and error logs.
- [ ] **Step 9: Overlap Window & Decommissioning**
  - Keep the AWS EC2 instance running in parallel for 3-5 days as a fallback.
  - Once verified stable, take one final safety snapshot/dump.
  - Terminate the EC2 instance and release any Elastic IPs in the AWS EC2 console to prevent lingering charges.

---

For the full detailed runbook, refer to [`docs/ORACLE_MIGRATION_PLAN.md`](file:///home/kaminari0x/cyf/docs/ORACLE_MIGRATION_PLAN.md).
