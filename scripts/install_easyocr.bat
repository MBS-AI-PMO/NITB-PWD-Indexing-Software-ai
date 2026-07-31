@echo off
REM EasyOCR Installation Script for Windows (Laragon)
REM This script installs EasyOCR using the correct Python path

echo ========================================
echo EasyOCR Installation Script
echo ========================================
echo.

REM Try to find Python
where py >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [1/3] Found Python via 'py' launcher
    echo [2/3] Installing EasyOCR...
    py -m pip install easyocr
    if %ERRORLEVEL% EQU 0 (
        echo [3/3] EasyOCR installed successfully!
        echo.
        echo Verifying installation...
        py scripts/check_easyocr.py
    ) else (
        echo ERROR: Installation failed!
        echo Try manually: py -m pip install easyocr
    )
) else (
    echo ERROR: Python not found!
    echo Make sure Python is installed and in PATH.
    echo Try: python --version
)

echo.
pause
