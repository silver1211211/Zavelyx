param(
    [int] $LaravelPort = 8000,
    [int] $MysqlPort = 3306,
    [int] $RedisPort = 6379
)

$ErrorActionPreference = 'Stop'

$project = Split-Path -Parent $PSScriptRoot
$php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
$composer = 'C:\laragon\bin\composer\composer.phar'
$mysql = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe'
$mysqld = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe'
$mysqlIni = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini'
$redis = 'C:\laragon\bin\redis\redis-x64-5.0.14.1\redis-server.exe'

function Update-EnvValue {
    param([string] $Key, [string] $Value)

    $envPath = Join-Path $project '.env'
    $content = Get-Content $envPath
    if ($content -match "^$Key=") {
        $content = $content -replace "^$Key=.*", "$Key=$Value"
    } else {
        $content += "$Key=$Value"
    }
    Set-Content -Path $envPath -Value $content
}

if (-not (Test-Path $php) -or -not (Test-Path $mysql) -or -not (Test-Path $mysqld)) {
    throw 'Laragon PHP/MySQL binaries were not found. Install Laragon or update the paths in scripts/start-local.ps1.'
}

$mysqlListener = Get-NetTCPConnection -LocalPort $MysqlPort -ErrorAction SilentlyContinue | Where-Object State -eq 'Listen' | Select-Object -First 1
if (-not $mysqlListener) {
    Start-Process -FilePath $mysqld -ArgumentList @("--defaults-file=$mysqlIni", '--console') -WindowStyle Hidden
    Start-Sleep -Seconds 5
}

if (-not (Get-NetTCPConnection -LocalPort $MysqlPort -ErrorAction SilentlyContinue | Where-Object State -eq 'Listen')) {
    throw "MySQL did not start on port $MysqlPort. Check Laragon's MySQL logs under C:\laragon\data\mysql-8.4."
}

if ((Test-Path $redis) -and -not (Get-NetTCPConnection -LocalPort $RedisPort -ErrorAction SilentlyContinue | Where-Object State -eq 'Listen')) {
    Start-Process -FilePath $redis -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

Update-EnvValue DB_CONNECTION mysql
Update-EnvValue DB_HOST 127.0.0.1
Update-EnvValue DB_PORT $MysqlPort
Update-EnvValue DB_DATABASE jeffpay
Update-EnvValue DB_USERNAME root
Update-EnvValue DB_PASSWORD ''
Update-EnvValue SESSION_DRIVER file
Update-EnvValue REDIS_CLIENT predis

& $mysql --host=127.0.0.1 --port=$MysqlPort --user=root -e "CREATE DATABASE IF NOT EXISTS jeffpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

Push-Location $project
try {
    & $php artisan config:clear
    & $php artisan cache:clear
    & $php artisan optimize:clear
    & $php artisan migrate --force
    & $php artisan db:seed --force

    if (-not (Test-Path (Join-Path $project 'public\build\manifest.json'))) {
        cmd /c npm run build
    }

    Get-CimInstance Win32_Process |
        Where-Object { $_.CommandLine -like '*artisan serve*' -and $_.CommandLine -like "*$project*" } |
        ForEach-Object { Stop-Process -Id $_.ProcessId -Force }

    Start-Process -FilePath $php -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', "--port=$LaravelPort") -WorkingDirectory $project -WindowStyle Hidden
    Start-Sleep -Seconds 3

    Write-Host "Laravel is running at http://127.0.0.1:$LaravelPort"
} finally {
    Pop-Location
}
