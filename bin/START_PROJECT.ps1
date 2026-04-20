#!/usr/bin/env pwsh
# **================================================**
# ** File: START_PROJECT.ps1                         **
# ** Responsibility: Start XAMPP and open project    **
# ** Usage: Right-click -> Run with PowerShell       **
# **         OR: powershell -ExecutionPolicy Bypass -File START_PROJECT.ps1
# **================================================**

Write-Host ""
Write-Host "╔════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   Jobbly Project - Quick Start                 ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Check if XAMPP exists
if (-not (Test-Path "C:\xampp\apache\bin\httpd.exe")) {
    Write-Host "✗ Error: XAMPP not found at C:\xampp" -ForegroundColor Red
    Write-Host "  Please install XAMPP first from: https://www.apachefriends.org" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "Starting XAMPP services..." -ForegroundColor Yellow
Write-Host ""

# Start Apache
Write-Host "[1/2] Starting Apache..." -ForegroundColor Cyan
try {
    & "C:\xampp\apache\bin\httpd.exe" -k start | Out-Null
    Write-Host "✓ Apache started successfully" -ForegroundColor Green
}
catch {
    Write-Host "✗ Failed to start Apache" -ForegroundColor Red
    Write-Host "  Make sure port 80 is not in use" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# Start MySQL
Write-Host "[2/2] Starting MySQL..." -ForegroundColor Cyan
try {
    $mysql_proc = Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--console" -WindowStyle Minimized -PassThru
    Write-Host "✓ MySQL started successfully" -ForegroundColor Green
}
catch {
    Write-Host "⚠ MySQL may already be running" -ForegroundColor Yellow
}

# Wait for services to start
Start-Sleep -Seconds 2

Write-Host ""
Write-Host "╔════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║   Services Started Successfully!               ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "You can now access:" -ForegroundColor Cyan
Write-Host "  Web:  http://localhost/jobbly/app" -ForegroundColor White
Write-Host "  API:  http://localhost/jobbly/src/fetch_sources.php" -ForegroundColor White
Write-Host ""
Write-Host "Or run from terminal:" -ForegroundColor Cyan
Write-Host "  cd C:\xampp\htdocs\jobbly" -ForegroundColor White
Write-Host "  php src/fetch_sources_cli.php" -ForegroundColor White
Write-Host ""
Write-Host "To stop services later:" -ForegroundColor Cyan
Write-Host "  httpd -k stop  (Apache)" -ForegroundColor White
Write-Host "  mysqladmin shutdown  (MySQL)" -ForegroundColor White
Write-Host ""

# Ask if user wants to open browser
$response = Read-Host "Would you like to open http://localhost/jobbly/app in your browser? (y/n)"
if ($response -eq 'y' -or $response -eq 'Y') {
    Start-Process "http://localhost/jobbly/app"
}

Read-Host "Press Enter to exit"
