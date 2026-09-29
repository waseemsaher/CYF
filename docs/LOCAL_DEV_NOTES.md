# Local Development Notes

Machine-specific fixes that apply to the **developer's host** only.
CI (GitHub Actions) and the production server do not need any of these.

---

## Docker Build Fails with HTTPS Timeouts (Alpine apk)

### Symptom

`docker build` hangs or fails during `apk add` with TLS handshake timeouts
when pulling packages from `dl-cdn.alpinelinux.org`. The build process shows
0% CPU and stalls indefinitely.

### Root Cause

The host's Docker bridge network uses the kernel default MTU (1500).
If the physical link has a lower effective MTU (common with PPPoE, VPNs, or
certain ISP configurations), large TLS packets are silently dropped.

### Workaround — Use `--network=host` for Builds

This bypasses the Docker bridge network entirely by using the host's
network stack, which has the correct MTU negotiated by the OS.

```bash
docker build --network=host --no-cache -t cyf-backend:latest ./backend
```

**This flag is needed only on this development machine.** GitHub Actions
and the production server build and pull images over networks without this
MTU issue and should use the standard build command without `--network=host`.

### Alternative — Fix the Docker Daemon MTU (requires sudo)

If you prefer a permanent fix for all Docker builds on this machine:

```bash
sudo tee /etc/docker/daemon.json <<'EOF'
{
  "mtu": 1400
}
EOF
sudo systemctl restart docker
```

Then build normally without `--network=host`:

```bash
docker build --no-cache -t cyf-backend:latest ./backend
```

### Why Not Fix It in the Dockerfile?

Downgrading Alpine repos from HTTPS to HTTP (`sed` on `/etc/apk/repositories`)
removes transport encryption for every build environment. The MTU issue is
specific to this machine's network path and must be fixed outside the
Dockerfile.
