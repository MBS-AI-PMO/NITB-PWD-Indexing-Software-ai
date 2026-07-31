# Queue Worker Status - How It's Running

## 🔍 Current Status Check

### Local Development (Current Setup):

**Status:** Manual/Command-based
- Queue worker manually start karna padta hai
- Auto-start setup nahi hai abhi

**How to Check:**
```bash
# Check if running
tasklist | findstr php.exe

# Check pending jobs
php artisan tinker --execute="echo \DB::table('jobs')->count();"
```

**How to Start:**
```bash
# Option 1: Simple start
START_QUEUE_LOCAL.bat

# Option 2: Background with auto-restart
START_QUEUE_WORKER_BACKGROUND.bat
```

---

## 📋 Setup Options

### Option 1: Manual Start (Current)
**Status:** ✅ Working
- Har baar manually start karna padta hai
- Window open rakhni padti hai

**Start Command:**
```bash
START_QUEUE_LOCAL.bat
```

---

### Option 2: Auto-Start (Recommended)

#### A. Laragon Startup (Local Development)
**Setup:**
```bash
# Run once
AUTO_START_NOW.bat
```

**How it works:**
- Laragon start hote hi queue worker automatically start hoga
- Background mein chalega

**Status Check:**
- File: `C:\laragon\etc\startup\queue-worker.bat` (should exist)

---

#### B. Windows Task Scheduler (Local/Live)
**Setup:**
```bash
# Run as Administrator
queue-worker-auto-start.bat
```

**How it works:**
- Windows boot hote hi queue worker automatically start hoga
- System service ki tarah chalega

**Status Check:**
```bash
schtasks /query /tn "Laravel Queue Worker - Document Extraction"
```

---

## 🌐 Live Server Setup

### Windows Live Server:
**Setup:**
```bash
# Right-click → Run as Administrator
queue-worker-auto-start.bat
```

**Status:**
- Auto-start on boot ✅
- Runs as Windows service ✅
- Auto-restart on crash ✅

**Check Status:**
```bash
schtasks /query /tn "Laravel Queue Worker - Document Extraction"
```

---

### Linux Live Server:
**Setup:**
```bash
chmod +x queue-worker-auto-start.sh
sudo ./queue-worker-auto-start.sh
```

**Status:**
- Auto-start on boot ✅
- Runs as systemd service ✅
- Auto-restart on crash ✅

**Check Status:**
```bash
sudo systemctl status laravel-queue-worker
```

---

## 🔧 Quick Status Check Script

**Run:**
```bash
CHECK_QUEUE_STATUS_NOW.bat
```

**Ye dikhayega:**
- PHP processes running hain ya nahi
- Pending jobs count
- Failed jobs
- Recent activity logs

---

## 📊 Current Setup Summary

### Local (Your Machine):
- **Current:** Manual start required
- **Auto-start available:** Yes (AUTO_START_NOW.bat)
- **Status:** Working (manual)

### Live Server:
- **Setup needed:** Yes
- **Method:** queue-worker-auto-start.bat (Windows) or .sh (Linux)
- **Status:** Not set up yet (if not deployed)

---

## 🚀 Recommended Action

### For Local Development:
```bash
# One-time setup for auto-start
AUTO_START_NOW.bat
```

### For Live Server:
```bash
# Windows
queue-worker-auto-start.bat (Run as Admin)

# Linux
sudo ./queue-worker-auto-start.sh
```

---

## ✅ Verification

**Check if auto-start is configured:**

**Windows:**
```bash
# Check Task Scheduler
schtasks /query /tn "Laravel Queue Worker - Document Extraction"

# Check Laragon startup
dir C:\laragon\etc\startup\queue-worker.bat
```

**Linux:**
```bash
# Check systemd service
sudo systemctl status laravel-queue-worker

# Check if enabled
sudo systemctl is-enabled laravel-queue-worker
```
