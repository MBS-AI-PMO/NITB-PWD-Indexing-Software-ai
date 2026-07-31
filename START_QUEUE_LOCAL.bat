@echo off
title Queue Worker - Local Development
echo ========================================
echo Queue Worker - Local Development
echo ========================================
echo.
echo Starting queue worker...
echo This window must stay open.
echo Press Ctrl+C to stop.
echo.
echo ========================================
echo.

cd /d C:\laragon\www\solo_dms

php artisan queue:work --queue=document-extraction --tries=2 --timeout=600 --verbose

pause
