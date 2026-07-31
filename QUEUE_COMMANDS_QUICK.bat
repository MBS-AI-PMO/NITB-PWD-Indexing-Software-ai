@echo off
title Queue Commands Menu
echo ========================================
echo PHP ARTISAN QUEUE COMMANDS
echo ========================================
echo.
echo Choose an option:
echo.
echo 1. Start Queue Worker
echo 2. Stop Queue Worker (Restart)
echo 3. Check Pending Jobs
echo 4. Check Failed Jobs
echo 5. Retry Failed Jobs
echo 6. View Queue Status
echo 7. Exit
echo.
set /p CHOICE="Enter choice (1-7): "

cd /d C:\laragon\www\solo_dms

if "%CHOICE%"=="1" (
    echo.
    echo Starting queue worker...
    echo Press Ctrl+C to stop.
    echo.
    php artisan queue:work --queue=document-extraction --tries=2 --timeout=600 --verbose
) else if "%CHOICE%"=="2" (
    echo.
    echo Restarting queue worker...
    php artisan queue:restart
    echo Queue worker will restart on next job.
) else if "%CHOICE%"=="3" (
    echo.
    echo Checking pending jobs...
    php artisan tinker --execute="echo 'Pending jobs: ' . \DB::table('jobs')->count();"
) else if "%CHOICE%"=="4" (
    echo.
    echo Checking failed jobs...
    php artisan queue:failed
) else if "%CHOICE%"=="5" (
    echo.
    echo Retrying failed jobs...
    php artisan queue:retry all
) else if "%CHOICE%"=="6" (
    echo.
    echo Queue status...
    php artisan queue:monitor document-extraction
) else if "%CHOICE%"=="7" (
    exit /b 0
) else (
    echo Invalid choice!
)

echo.
pause
