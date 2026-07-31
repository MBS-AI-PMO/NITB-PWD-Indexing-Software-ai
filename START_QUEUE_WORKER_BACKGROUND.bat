@echo off
title Queue Worker - Background (Auto-Restart)
echo ========================================
echo Queue Worker - Background Mode
echo ========================================
echo.
echo Starting queue worker in background...
echo Window will minimize in 3 seconds...
echo.

cd /d C:\laragon\www\solo_dms

REM Minimize window after 3 seconds
timeout /t 3 /nobreak >nul
if not "%1"=="minimized" (
    start /min cmd /c "%~f0" minimized
    exit
)

REM Auto-restart loop
:loop
echo.
echo ========================================
echo [%date% %time%] Starting queue worker...
echo ========================================
echo.

REM Check pending jobs first
php artisan tinker --execute="echo 'Pending jobs: ' . \DB::table('jobs')->count() . PHP_EOL;" 2>nul

echo.
echo Processing jobs from 'document-extraction' queue...
echo Press Ctrl+C to stop (or close this window)
echo.

REM Start queue worker with verbose output
php artisan queue:work --queue=document-extraction --tries=2 --timeout=600 --verbose --sleep=3 --max-time=3600

echo.
echo ========================================
echo [%date% %time%] Queue worker stopped.
echo ========================================
echo.
echo Restarting in 5 seconds...
echo (Press Ctrl+C to stop completely)
echo.

timeout /t 5 /nobreak >nul
goto loop
