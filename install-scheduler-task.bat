@echo off
:: Registers the Laravel scheduler as a Windows Task.
:: Runs automatically every minute — no manual intervention needed after this.
:: Double-click to run. No administrator rights required.

cd /d %~dp0
set PROJECT_DIR=%~dp0
set PHP_PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set LOG_PATH=%PROJECT_DIR%storage\logs\scheduler.log
set ARTISAN=%PROJECT_DIR%artisan

echo Installing SilverBoost Scheduler as Windows Task...

:: Remove existing task if present
schtasks /delete /tn "SilverBoostScheduler" /f 2>nul

:: Create task — runs as current user, every 1 minute, starts on login
schtasks /create ^
  /tn "SilverBoostScheduler" ^
  /tr "\"%PHP_PATH%\" \"%ARTISAN%\" schedule:run" ^
  /sc MINUTE ^
  /mo 1 ^
  /f

if %ERRORLEVEL% neq 0 (
    echo.
    echo FAILED. If you see an access error, try running as Administrator.
    pause
    exit /b 1
)

echo.
echo Done! Task "SilverBoostScheduler" created successfully.
echo Orders will sync automatically every minute after every reboot.
echo.
echo To verify:  schtasks /query /tn "SilverBoostScheduler"
echo To remove:  schtasks /delete /tn "SilverBoostScheduler" /f
echo.
pause
