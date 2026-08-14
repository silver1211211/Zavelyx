# SilverBoost — Local Development Guide

Laravel 12 + Vue 3 + Inertia.js + Vite, running on Laragon (Windows).

---

## After Restarting Your PC — Do This Every Time

### Step 1 — Start Laragon

Open **Laragon** and click **Start All**.

This starts MySQL and makes `jeff.test` available.

### Step 2 — Start the project

Double-click **`start.bat`** in the project root.

The script checks and installs missing dependencies automatically, then opens two terminal windows.

### Step 3 — Open the browser

```
http://127.0.0.1:8000
```

Keep both terminal windows open while you work.

---

## Want Laragon to start automatically on boot?

In Laragon: **Menu → Preferences → General → Auto-start → On** 

With auto-start enabled, Laragon starts MySQL when you log in to Windows.  
Then you only need to double-click `start.bat` — no manual Laragon step.

---

## What each script does

| Script | What it does |
|---|---|
| `start.bat` | Full startup: checks deps, clears cache, starts Laravel + Vite |
| `start-project.bat` | Same as `start.bat` |
| `start-backend.bat` | Starts Laravel only (`php artisan serve`) |
| `start-frontend.bat` | Starts Vite only (`npm run dev`) |

---

## URLs

| URL | Requires |
|---|---|
| `http://127.0.0.1:8000` | `start.bat` running |
| `http://jeff.test` | Laragon running with Apache started |

---

## Manual commands (in a terminal at `C:\laragon\www\jeff`)

```bash
# Clear all Laravel caches
php artisan serve --host=127.0.0.1 --port=8000

# Start the Vite dev server (in a second terminal)
npm run dev

# Clear caches if something looks stale
php artisan optimize:clear
```

---

## Troubleshooting

### Blank page or site won't load after restart
1. Open Laragon → click **Start All** (starts MySQL)
2. Double-click `start.bat`
3. Open `http://127.0.0.1:8000`

### 500 Server Error
```bash
php artisan optimize:clear
```
If it mentions a database error, MySQL is not running — open Laragon.

### "Class not found" or vendor errors
```bash
composer install
```

### White/blank page, no CSS or JS
```bash
npm install
npm run dev
```
Then keep the Vite terminal open and refresh the browser.

### Port 8000 already in use
```bash
php artisan serve --port=8001
```
Then open `http://127.0.0.1:8001`.

### `jeff.test` not loading
Laragon's Apache is not running. Open Laragon → Start All.

---

## Tech Stack

| | |
|---|---|
| Backend | Laravel 12 (PHP 8.3) |
| Frontend | Vue 3 + Inertia.js |
| Build tool | Vite 7 |
| Styling | Tailwind CSS |
| Database | MySQL 8 (via Laragon) |
| Sessions | File-based (dev) |
| Auth | Laravel Sanctum + Spatie Permissions |
