@echo off
:: SilverBoost - Complete Local Dev Startup
:: Double-click this file or run it from a terminal to start the full dev environment.

SET PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
SET COMPOSER=C:\laragon\bin\composer\composer.phar
SET NODE=C:\laragon\bin\nodejs\node-v22\node.exe
SET NPM_CLI=C:\laragon\bin\nodejs\node-v22\node_modules\npm\bin\npm-cli.js
SET MYSQLD=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe
SET MYINI=C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini
SET MYSQL=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe
SET PROJECT=C:\laragon\www\jeff

echo.
echo =============================================
echo   SilverBoost - Local Dev Startup
echo =============================================
echo.

:: ─── VERIFY BINARIES ───────────────────────────────────────────────────────
IF NOT EXIST "%PHP%" (
    echo [ERROR] PHP not found at:
    echo         %PHP%
    echo.
    echo         Make sure Laragon is installed, or update the PHP path at
    echo         the top of this script.
    pause & exit /b 1
)
IF NOT EXIST "%NODE%" (
    echo [ERROR] Node.js not found at:
    echo         %NODE%
    echo.
    echo         Make sure Laragon is installed with Node.js, or update
    echo         the NODE path at the top of this script.
    pause & exit /b 1
)
echo [OK] PHP and Node.js found.

:: ─── STEP 1: MySQL ─────────────────────────────────────────────────────────
echo.
echo [1/4] Checking MySQL...
"%MYSQL%" -u root -e "SELECT 1;" >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo       MySQL not running. Starting it now...
    start "" /B "%MYSQLD%" --defaults-file="%MYINI%" --standalone
    timeout /t 6 /nobreak >nul
    "%MYSQL%" -u root -e "SELECT 1;" >nul 2>&1
    IF %ERRORLEVEL% NEQ 0 (
        echo [WARN] MySQL still not responding.
        echo        If Laragon is already running, ignore this warning.
        echo        Otherwise open Laragon and click Start All.
    ) ELSE (
        echo [OK] MySQL started.
    )
) ELSE (
    echo [OK] MySQL is running.
)

:: ─── STEP 2: PHP dependencies ──────────────────────────────────────────────
echo.
echo [2/4] Checking PHP dependencies...
IF NOT EXIST "%PROJECT%\vendor\autoload.php" (
    echo       vendor/ not found. Running composer install...
    cd /d "%PROJECT%"
    "%PHP%" "%COMPOSER%" install
    IF %ERRORLEVEL% NEQ 0 (
        echo [ERROR] composer install failed. Fix the error above, then re-run this script.
        pause & exit /b 1
    )
    echo [OK] Composer dependencies installed.
) ELSE (
    echo [OK] vendor/ already present.
)

:: ─── STEP 3: Node dependencies ─────────────────────────────────────────────
echo.
echo [3/4] Checking Node dependencies...
IF NOT EXIST "%PROJECT%\node_modules\.bin\vite" (
    echo       node_modules missing or Vite not installed. Running npm install...
    cd /d "%PROJECT%"
    "%NODE%" "%NPM_CLI%" install
    IF %ERRORLEVEL% NEQ 0 (
        echo [ERROR] npm install failed. Fix the error above, then re-run this script.
        pause & exit /b 1
    )
    echo [OK] Node dependencies installed.
) ELSE (
    echo [OK] node_modules already present.
)

:: ─── STEP 4: Clear Laravel caches ─────────────────────────────────────────
echo.
echo [4/4] Clearing Laravel caches...
cd /d "%PROJECT%"
"%PHP%" artisan optimize:clear >nul 2>&1
echo [OK] Caches cleared.

:: ─── START SERVERS ─────────────────────────────────────────────────────────
echo.
echo =============================================
echo   Starting servers...
echo =============================================
echo.

echo [START] Vite dev server (npm run dev)...
start "SilverBoost - Vite" cmd /k "title SilverBoost ^| Vite && cd /d %PROJECT% && %NODE% %NPM_CLI% run dev"

timeout /t 2 /nobreak >nul

echo [START] Laravel server (php artisan serve)...
start "SilverBoost - Laravel" cmd /k "title SilverBoost ^| Laravel && cd /d %PROJECT% && %PHP% artisan serve --host=127.0.0.1 --port=8000"

echo.
echo =============================================
echo   SilverBoost is running!
echo.
echo   Open in your browser:
echo     http://127.0.0.1:8000
echo.
echo   Keep both terminal windows open:
echo     - SilverBoost ^| Vite
echo     - SilverBoost ^| Laravel
echo.
echo   To stop: close both terminal windows.
echo =============================================
echo.
pause
