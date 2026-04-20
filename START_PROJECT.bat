@echo off
REM **================================================**
REM ** File: START_PROJECT.bat                         **
REM ** Responsibility: Start XAMPP and open project    **
REM ** Usage: Double-click this file to start          **
REM **================================================**

echo.
echo ╔════════════════════════════════════════════════╗
echo ║   Jobbly Project - Quick Start                 ║
echo ╚════════════════════════════════════════════════╝
echo.

REM Check if XAMPP exists
if not exist "C:\xampp\apache\bin\httpd.exe" (
    echo ✗ Error: XAMPP not found at C:\xampp
    echo   Please install XAMPP first from: https://www.apachefriends.org
    pause
    exit /b 1
)

echo Starting XAMPP services...
echo.

REM Start Apache
echo [1/2] Starting Apache...
"C:\xampp\apache\bin\httpd.exe" -k start >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Apache started successfully
) else (
    echo ✗ Failed to start Apache
    echo   Make sure port 80 is not in use
    pause
    exit /b 1
)

REM Start MySQL
echo [2/2] Starting MySQL...
cd /d "C:\xampp\mysql\bin"
start mysqld.exe --console >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ MySQL started successfully
) else (
    echo ⚠ MySQL may already be running
)

REM Wait a bit for services to start
timeout /t 2 /nobreak >nul

echo.
echo ╔════════════════════════════════════════════════╗
echo ║   Services Started Successfully!               ║
echo ╚════════════════════════════════════════════════╝
echo.
echo You can now access:
echo   Web:  http://localhost/jobbly
echo   API:  http://localhost/jobbly/fetch_sources.php
echo.
echo Or run from terminal:
echo   cd C:\xampp\htdocs\jobbly
echo   php fetch_sources_cli.php
echo.
echo To stop services later, use:
echo   httpd -k stop  (Apache)
echo   mysqladmin shutdown  (MySQL)
echo.
pause
