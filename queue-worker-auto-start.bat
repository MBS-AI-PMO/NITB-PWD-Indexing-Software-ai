@echo off
REM ========================================
REM Queue Worker Auto-Start Script
REM For Windows Live Server
REM ========================================

REM Get script directory
set SCRIPT_DIR=%~dp0
set SCRIPT_DIR=%SCRIPT_DIR:~0,-1%

REM Change to project directory
cd /d "%SCRIPT_DIR%"

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Administrator privileges required!
    echo Please run as Administrator.
    pause
    exit /b 1
)

REM Get PHP path
set PHP_PATH=
where php >nul 2>&1
if %errorlevel% equ 0 (
    for /f "delims=" %%i in ('where php') do set PHP_PATH=%%i
) else (
    echo ERROR: PHP not found in PATH!
    pause
    exit /b 1
)

echo ========================================
echo Queue Worker Auto-Start Setup
echo ========================================
echo.
echo Project: %SCRIPT_DIR%
echo PHP: %PHP_PATH%
echo.

REM Create wrapper batch file
set WRAPPER_BAT=%SCRIPT_DIR%\queue-worker-wrapper.bat
(
echo @echo off
echo cd /d "%SCRIPT_DIR%"
echo php artisan queue:work --queue=document-extraction --tries=3 --timeout=600 --max-jobs=1000 --max-time=3600 --sleep=3 --verbose
) > "%WRAPPER_BAT%"

echo [1/3] Created wrapper script: %WRAPPER_BAT%
echo.

REM Delete existing task if exists
schtasks /query /tn "Laravel Queue Worker - Document Extraction" >nul 2>&1
if %errorlevel% equ 0 (
    echo [2/3] Removing existing task...
    schtasks /delete /tn "Laravel Queue Worker - Document Extraction" /f >nul 2>&1
)

REM Create Windows Task Scheduler task
echo [3/3] Creating Windows Task Scheduler task...
schtasks /create /tn "Laravel Queue Worker - Document Extraction" /tr "\"%WRAPPER_BAT%\"" /sc onstart /ru SYSTEM /f /rl highest /delay 0001:00

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SUCCESS: Auto-Start Setup Complete!
    echo ========================================
    echo.
    echo Queue worker will automatically start on system boot.
    echo.
    echo Commands:
    echo   Start now:  schtasks /run /tn "Laravel Queue Worker - Document Extraction"
    echo   Stop:       schtasks /end /tn "Laravel Queue Worker - Document Extraction"
    echo   Status:     schtasks /query /tn "Laravel Queue Worker - Document Extraction"
    echo   Remove:     schtasks /delete /tn "Laravel Queue Worker - Document Extraction" /f
    echo.
    
    REM Start the worker now
    echo Starting queue worker now...
    schtasks /run /tn "Laravel Queue Worker - Document Extraction"
    
    if %errorlevel% equ 0 (
        echo [OK] Queue worker started!
    ) else (
        echo [WARNING] Could not start immediately. Will start on next boot.
    )
) else (
    echo.
    echo ERROR: Failed to create task!
    pause
    exit /b 1
)

echo.
pause
