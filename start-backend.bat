@echo off
:: SilverBoost - start-backend.bat
:: Starts ONLY the Laravel backend server on http://127.0.0.1:8000
:: Use this when Vite is already running in another window.

SET PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
SET PROJECT=C:\laragon\www\jeff

title SilverBoost ^| Laravel
cd /d "%PROJECT%"

echo.
echo [SilverBoost] Starting Laravel backend...
echo [SilverBoost] URL: http://127.0.0.1:8000
echo [SilverBoost] Keep this window open while developing.
echo.

"%PHP%" artisan serve --host=127.0.0.1 --port=8000
