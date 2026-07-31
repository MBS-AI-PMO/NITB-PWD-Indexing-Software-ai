# Queue Worker Auto-Start for Live Server

## 🏆 Recommended: Continuous Worker (Systemd/Supervisor)

This is the **best approach** for production servers - real-time processing with auto-restart.

---

## Windows Live Server

### Quick Setup:
```bash
# Right-click → Run as Administrator
queue-worker-auto-start.bat
```

### Manual Commands:
```bash
# Start now
schtasks /run /tn "Laravel Queue Worker - Document Extraction"

# Stop
schtasks /end /tn "Laravel Queue Worker - Document Extraction"

# Status
schtasks /query /tn "Laravel Queue Worker - Document Extraction"

# Remove
schtasks /delete /tn "Laravel Queue Worker - Document Extraction" /f
```

---

## Linux Live Server

### Option 1: Systemd Service (Recommended)

**Quick Setup:**
```bash
# Make script executable
chmod +x queue-worker-auto-start.sh

# Run as root
sudo ./queue-worker-auto-start.sh
```

**Manual Commands:**
```bash
# Status
sudo systemctl status laravel-queue-worker

# Start
sudo systemctl start laravel-queue-worker

# Stop
sudo systemctl stop laravel-queue-worker

# Restart
sudo systemctl restart laravel-queue-worker

# View logs
sudo journalctl -u laravel-queue-worker -f

# Disable auto-start
sudo systemctl disable laravel-queue-worker
```

### Option 2: Supervisor (Alternative)

**Setup:**
```bash
# Install supervisor
sudo apt-get install supervisor

# Copy config file
sudo cp queue-worker-supervisor.conf /etc/supervisor/conf.d/laravel-queue-worker.conf

# Edit config file - update paths
sudo nano /etc/supervisor/conf.d/laravel-queue-worker.conf

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start worker
sudo supervisorctl start laravel-queue-worker:*
```

**Commands:**
```bash
# Status
sudo supervisorctl status laravel-queue-worker:*

# Start
sudo supervisorctl start laravel-queue-worker:*

# Stop
sudo supervisorctl stop laravel-queue-worker:*

# Restart
sudo supervisorctl restart laravel-queue-worker:*

# View logs
tail -f /var/log/laravel-queue-worker.log
```

### Option 3: Auto-Install Script

**Quick Setup:**
```bash
# Make executable
chmod +x queue-worker-install.sh

# Run (auto-detects best method)
sudo ./queue-worker-install.sh
```

---

## Production Settings

### Recommended Queue Worker Settings:

```bash
php artisan queue:work \
  --queue=document-extraction \
  --tries=3 \
  --timeout=600 \
  --max-jobs=1000 \
  --max-time=3600 \
  --sleep=3 \
  --verbose
```

**Settings Explanation:**
- `--tries=3`: Retry failed jobs 3 times
- `--timeout=600`: 10 minutes max per job
- `--max-jobs=1000`: Restart after 1000 jobs (memory management)
- `--max-time=3600`: Restart after 1 hour (memory management)
- `--sleep=3`: Wait 3 seconds between jobs
- `--verbose`: Show detailed output

---

## Monitoring

### Check Queue Status:
```bash
# Pending jobs
php artisan tinker --execute="echo \DB::table('jobs')->count();"

# Failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Logs:
- **Windows**: Check Task Scheduler logs
- **Linux Systemd**: `sudo journalctl -u laravel-queue-worker -f`
- **Linux Supervisor**: `tail -f /var/log/laravel-queue-worker.log`
- **Application**: `storage/logs/laravel.log`

---

## Troubleshooting

### Queue Worker Not Starting:
1. Check PHP path: `which php` or `where php`
2. Check database connection
3. Check queue connection in `.env`: `QUEUE_CONNECTION=database`
4. Run migrations: `php artisan migrate`

### Jobs Not Processing:
1. Verify worker is running
2. Check pending jobs: `php artisan tinker --execute="echo \DB::table('jobs')->count();"`
3. Check failed jobs: `php artisan queue:failed`
4. Check logs for errors

### High Memory Usage:
- Reduce `--max-jobs` (e.g., 500 instead of 1000)
- Reduce `--max-time` (e.g., 1800 instead of 3600)
- Use multiple workers with lower limits

---

## Files

- `queue-worker-auto-start.bat` - Windows setup script
- `queue-worker-auto-start.sh` - Linux systemd setup script
- `queue-worker-supervisor.conf` - Supervisor config
- `queue-worker-install.sh` - Auto-install script
- `queue-worker-wrapper.bat` - Windows wrapper script (auto-created)

---

## Why Continuous Worker?

✅ **Real-time processing** - Jobs process immediately  
✅ **Auto-restart** - Automatically restarts if crashes  
✅ **Better for OCR** - Long-running OCR tasks handle better  
✅ **Production-ready** - Industry standard approach  
✅ **Memory management** - Built-in restart after X jobs/hours  

See `BEST_APPROACH.md` for detailed comparison.
