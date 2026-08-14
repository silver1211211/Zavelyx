@echo off
title SilverBoost Scheduler
cd /d %~dp0

echo ================================================
echo  SilverBoost Laravel Scheduler
echo  Running: php artisan schedule:work
echo  Orders sync every 1 minute automatically.
echo  Press Ctrl+C to stop.
echo ================================================
echo.

php artisan schedule:work

pause
