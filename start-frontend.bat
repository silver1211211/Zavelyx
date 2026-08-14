@echo off
:: SilverBoost - start-frontend.bat
:: Starts ONLY the Vite frontend dev server.
:: Use this when Laravel is already running in another window.

SET NODE=C:\laragon\bin\nodejs\node-v22\node.exe
SET NPM_CLI=C:\laragon\bin\nodejs\node-v22\node_modules\npm\bin\npm-cli.js
SET PROJECT=C:\laragon\www\jeff

title SilverBoost ^| Vite
cd /d "%PROJECT%"

echo.
echo [SilverBoost] Starting Vite frontend dev server...
echo [SilverBoost] Keep this window open while developing.
echo.

"%NODE%" "%NPM_CLI%" run dev
