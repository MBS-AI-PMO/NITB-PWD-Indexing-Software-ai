#!/bin/bash
# ========================================
# Queue Worker Install Script
# Auto-detects and installs best method
# ========================================

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "========================================"
echo "Queue Worker Auto-Start Installer"
echo "========================================"
echo ""
echo "Detecting system..."

# Check if systemd is available
if systemctl --version &> /dev/null; then
    echo "[*] Systemd detected - Using systemd service"
    echo ""
    sudo bash "$SCRIPT_DIR/queue-worker-auto-start.sh"
    
# Check if supervisor is available
elif command -v supervisorctl &> /dev/null; then
    echo "[*] Supervisor detected - Using supervisor"
    echo ""
    echo "Please copy queue-worker-supervisor.conf to /etc/supervisor/conf.d/"
    echo "Then run:"
    echo "  sudo supervisorctl reread"
    echo "  sudo supervisorctl update"
    echo "  sudo supervisorctl start laravel-queue-worker:*"
    
# Fallback: Create simple init script
else
    echo "[*] Creating simple init script"
    echo ""
    
    INIT_SCRIPT="/etc/init.d/laravel-queue-worker"
    
    cat > "$INIT_SCRIPT" << 'EOF'
#!/bin/bash
# Laravel Queue Worker Init Script

SCRIPT_DIR="/path/to/solo_dms"
PHP_PATH=$(which php)
PIDFILE="/var/run/laravel-queue-worker.pid"

case "$1" in
    start)
        echo "Starting Laravel Queue Worker..."
        cd "$SCRIPT_DIR"
        nohup $PHP_PATH artisan queue:work --queue=document-extraction --tries=3 --timeout=600 --max-jobs=1000 --max-time=3600 --sleep=3 --verbose > /var/log/laravel-queue-worker.log 2>&1 &
        echo $! > $PIDFILE
        ;;
    stop)
        echo "Stopping Laravel Queue Worker..."
        if [ -f $PIDFILE ]; then
            kill $(cat $PIDFILE)
            rm $PIDFILE
        fi
        ;;
    restart)
        $0 stop
        sleep 2
        $0 start
        ;;
    status)
        if [ -f $PIDFILE ]; then
            PID=$(cat $PIDFILE)
            if ps -p $PID > /dev/null; then
                echo "Queue worker is running (PID: $PID)"
            else
                echo "Queue worker is not running"
            fi
        else
            echo "Queue worker is not running"
        fi
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac
EOF
    
    chmod +x "$INIT_SCRIPT"
    echo "Created: $INIT_SCRIPT"
    echo ""
    echo "Please update SCRIPT_DIR in the init script, then:"
    echo "  sudo update-rc.d laravel-queue-worker defaults"
    echo "  sudo service laravel-queue-worker start"
fi
