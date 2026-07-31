#!/bin/bash
# ========================================
# Queue Worker Auto-Start Script
# For Linux Live Server (Systemd Service)
# ========================================

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_NAME="solo_dms"
SERVICE_NAME="laravel-queue-worker"

echo "========================================"
echo "Queue Worker Auto-Start Setup (Linux)"
echo "========================================"
echo ""
echo "Project: $SCRIPT_DIR"
echo "Service: $SERVICE_NAME"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "ERROR: Please run as root (use sudo)"
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found!"
    exit 1
fi

PHP_PATH=$(which php)
echo "PHP: $PHP_PATH"
echo ""

# Get current user (for running the service)
if [ -z "$SUDO_USER" ]; then
    RUN_USER="www-data"
else
    RUN_USER="$SUDO_USER"
fi

echo "Service will run as: $RUN_USER"
echo ""

# Create systemd service file
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"

cat > "$SERVICE_FILE" << EOF
[Unit]
Description=Laravel Queue Worker - Document Extraction
After=network.target mysql.service

[Service]
Type=simple
User=$RUN_USER
WorkingDirectory=$SCRIPT_DIR
ExecStart=$PHP_PATH artisan queue:work --queue=document-extraction --tries=3 --timeout=600 --max-jobs=1000 --max-time=3600 --sleep=3 --verbose
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

echo "[1/4] Created systemd service file: $SERVICE_FILE"
echo ""

# Reload systemd
systemctl daemon-reload
echo "[2/4] Reloaded systemd daemon"
echo ""

# Enable service (start on boot)
systemctl enable "$SERVICE_NAME"
echo "[3/4] Enabled service (will start on boot)"
echo ""

# Start service now
systemctl start "$SERVICE_NAME"
echo "[4/4] Started service"
echo ""

# Check status
sleep 2
if systemctl is-active --quiet "$SERVICE_NAME"; then
    echo "========================================"
    echo "SUCCESS: Auto-Start Setup Complete!"
    echo "========================================"
    echo ""
    echo "Queue worker is running and will auto-start on boot."
    echo ""
    echo "Commands:"
    echo "  Status:  sudo systemctl status $SERVICE_NAME"
    echo "  Start:   sudo systemctl start $SERVICE_NAME"
    echo "  Stop:    sudo systemctl stop $SERVICE_NAME"
    echo "  Restart: sudo systemctl restart $SERVICE_NAME"
    echo "  Logs:    sudo journalctl -u $SERVICE_NAME -f"
    echo "  Disable: sudo systemctl disable $SERVICE_NAME"
    echo ""
else
    echo "ERROR: Service failed to start!"
    echo "Check logs: sudo journalctl -u $SERVICE_NAME -n 50"
    exit 1
fi
