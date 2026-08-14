# SilverBoost - Project Startup Script (PowerShell)
# Run with: .\start.ps1

$PHP     = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$NODE    = "C:\laragon\bin\nodejs\node-v22\node.exe"
$NPMCLI  = "C:\laragon\bin\nodejs\node-v22\node_modules\npm\bin\npm-cli.js"
$MYSQLD  = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
$MYINI   = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini"
$MYSQL   = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$PROJECT = "C:\laragon\www\jeff"

Write-Host "=== SilverBoost Dev Server Startup ===" -ForegroundColor Cyan
Write-Host ""

# Check/start MySQL
Write-Host "[1/3] Checking MySQL..." -ForegroundColor Yellow
$mysqlTest = & $MYSQL -u root -e "SELECT 1;" 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Starting MySQL server..." -ForegroundColor Yellow
    Start-Process -FilePath $MYSQLD -ArgumentList "--defaults-file=`"$MYINI`"", "--standalone" -WindowStyle Hidden
    Start-Sleep -Seconds 5
    Write-Host "MySQL started." -ForegroundColor Green
} else {
    Write-Host "MySQL already running." -ForegroundColor Green
}

Write-Host ""
Write-Host "[2/3] Starting Vite (npm run dev)..." -ForegroundColor Yellow
Start-Process "cmd" -ArgumentList "/k", "cd /d `"$PROJECT`" && `"$NODE`" `"$NPMCLI`" run dev"

Write-Host ""
Write-Host "[3/3] Starting Laravel (php artisan serve)..." -ForegroundColor Yellow
Start-Process "cmd" -ArgumentList "/k", "cd /d `"$PROJECT`" && `"$PHP`" artisan serve --host=127.0.0.1 --port=8000"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SilverBoost is starting up!" -ForegroundColor Green
Write-Host ""
Write-Host "  Open in browser:" -ForegroundColor White
Write-Host "    http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "    http://jeff.test  (requires Laragon running)" -ForegroundColor Green
Write-Host ""
Write-Host "  Keep the Vite and Laravel windows open." -ForegroundColor White
Write-Host "========================================" -ForegroundColor Cyan
