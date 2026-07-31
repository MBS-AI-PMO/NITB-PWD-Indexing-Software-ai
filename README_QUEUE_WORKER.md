# Queue Worker Setup Guide

## 🏆 Best Approach: Continuous Worker

For production servers, use **Continuous Worker** (Systemd/Supervisor) - real-time processing with auto-restart.

---

## Quick Start

### Local Development:
```bash
# Windows
START_QUEUE_LOCAL.bat

# Or background (auto-restart)
START_QUEUE_WORKER_BACKGROUND.bat
```

### Live Server - Windows:
```bash
# Right-click → Run as Administrator
queue-worker-auto-start.bat
```

### Live Server - Linux:
```bash
chmod +x queue-worker-auto-start.sh
sudo ./queue-worker-auto-start.sh
```

---

## Commands

### Start Queue Worker:
```bash
php artisan queue:work --queue=document-extraction --tries=2 --timeout=600 --verbose
```

### Stop/Restart:
```bash
php artisan queue:restart
```

### Check Status:
```bash
# Pending jobs
php artisan tinker --execute="echo \DB::table('jobs')->count();"

# Failed jobs
php artisan queue:failed

# Queue status
php artisan queue:monitor document-extraction
```

Or use: `QUEUE_COMMANDS_QUICK.bat` (Windows menu)

---

## Files

### Setup Scripts:
- `queue-worker-auto-start.bat` - Windows live server setup
- `queue-worker-auto-start.sh` - Linux live server setup
- `queue-worker-supervisor.conf` - Supervisor config (Linux)
- `queue-worker-install.sh` - Auto-install (Linux)

### Local Development:
- `START_QUEUE_LOCAL.bat` - Start queue worker (local)
- `START_QUEUE_WORKER_BACKGROUND.bat` - Background with auto-restart
- `CHECK_QUEUE_STATUS.bat` - Check queue status

### Utilities:
- `QUEUE_COMMANDS_QUICK.bat` - Command menu
- `QUEUE_COMMANDS.txt` - All commands reference

### Documentation:
- `BEST_APPROACH.md` - Detailed comparison of approaches
- `RECOMMENDED_SETUP.txt` - Quick reference
- `QUEUE_WORKER_LIVE_SERVER.md` - Live server setup guide

---

## Documentation

- **Best Approach**: See `BEST_APPROACH.md`
- **Live Server Setup**: See `QUEUE_WORKER_LIVE_SERVER.md`
- **Commands Reference**: See `QUEUE_COMMANDS.txt`

---

## Important Notes

1. **Queue worker must be running** - Background processing ke liye queue worker chalta rehna chahiye
2. **Upload is instant** - Document upload immediately complete hota hai
3. **Extraction happens in background** - Text extraction background mein process hota hai
4. **Auto-restart** - Continuous worker automatically restarts on crash
