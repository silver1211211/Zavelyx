<?php
/**
 * Zavelyx Installation Wizard
 * Standalone installer — works before .env is configured.
 * Delete this file after installation is complete.
 */

define('INSTALL_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('LOCK_FILE', BASE_PATH . '/storage/installed');

// ── Security ──────────────────────────────────────────────────────────────────

if (file_exists(LOCK_FILE)) {
    http_response_code(404);
    die('<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:4rem;background:#060d1a;color:#94a3b8"><h2 style="color:#fff">Already installed</h2><p>This installer has been locked. Delete <code>storage/installed</code> to re-run.</p></body></html>');
}

if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
    die('<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:4rem;background:#060d1a;color:#94a3b8"><h2 style="color:#f87171">Dependencies missing</h2><p>Run <code>composer install</code> before using the installer.</p></body></html>');
}

// ── Session ───────────────────────────────────────────────────────────────────

session_start();
if (!isset($_SESSION['install'])) {
    $_SESSION['install'] = ['step' => 1, 'data' => []];
}

$step   = $_SESSION['install']['step'] ?? 1;
$stored = $_SESSION['install']['data']  ?? [];
$errors = [];
$step   = max(1, min(6, (int)$step));

// ── Helpers ───────────────────────────────────────────────────────────────────

function req(string $ext): bool   { return extension_loaded($ext); }
function writable(string $p): bool { return is_writable(BASE_PATH . '/' . $p); }
function exists(string $p): bool   { return file_exists(BASE_PATH . '/' . $p); }

function check_php(): array {
    $ok  = version_compare(PHP_VERSION, '8.2.0', '>=');
    return ['ok' => $ok, 'value' => PHP_VERSION, 'required' => '>= 8.2'];
}

function all_requirements(): array {
    $php = check_php();
    $exts = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'curl', 'zip'];
    $dirs = ['storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'];
    $checks = [];

    $checks['php'] = ['label' => 'PHP >= 8.2', 'ok' => $php['ok'], 'detail' => 'Current: ' . PHP_VERSION, 'required' => true];
    foreach ($exts as $ext) {
        $checks["ext_$ext"] = ['label' => "ext-$ext", 'ok' => req($ext), 'detail' => req($ext) ? 'Loaded' : 'Missing', 'required' => true];
    }
    foreach ($dirs as $dir) {
        $checks["dir_$dir"] = ['label' => $dir . '/', 'ok' => writable($dir), 'detail' => writable($dir) ? 'Writable' : 'Not writable', 'required' => true];
    }
    $checks['vendor'] = ['label' => 'vendor/ directory', 'ok' => exists('vendor/autoload.php'), 'detail' => exists('vendor/autoload.php') ? 'Found' : 'Missing — run composer install', 'required' => true];
    $checks['env']    = ['label' => '.env file', 'ok' => !exists('.env'), 'detail' => exists('.env') ? 'Exists (will be overwritten)' : 'Will be created', 'required' => false];

    return $checks;
}

function all_pass(array $checks): bool {
    foreach ($checks as $c) {
        if ($c['required'] && !$c['ok']) return false;
    }
    return true;
}

function test_db(string $host, string $port, string $db, string $user, string $pass): array {
    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        // Try to create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db`");
        return ['ok' => true, 'message' => "Connected to MySQL {$pdo->getAttribute(PDO::ATTR_SERVER_VERSION)} — database \"$db\" ready."];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function write_env(array $d): bool {
    $queueConn  = ($d['queue_driver'] === 'redis') ? 'redis'    : 'database';
    $cacheStore = ($d['queue_driver'] === 'redis') ? 'redis'    : 'file';
    $redisBlock = ($d['queue_driver'] === 'redis') ? "\nREDIS_CLIENT=predis\nREDIS_HOST={$d['redis_host']}\nREDIS_PASSWORD=null\nREDIS_PORT={$d['redis_port']}" : '';

    $env = <<<ENV
APP_NAME="{$d['app_name']}"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL={$d['app_url']}
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$d['db_host']}
DB_PORT={$d['db_port']}
DB_DATABASE={$d['db_name']}
DB_USERNAME={$d['db_user']}
DB_PASSWORD={$d['db_pass']}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_COOKIE=nexahub_session

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION={$queueConn}
CACHE_STORE={$cacheStore}
{$redisBlock}

MAIL_MAILER={$d['mail_mailer']}
MAIL_HOST={$d['mail_host']}
MAIL_PORT={$d['mail_port']}
MAIL_USERNAME={$d['mail_user']}
MAIL_PASSWORD={$d['mail_pass']}
MAIL_ENCRYPTION={$d['mail_enc']}
MAIL_FROM_ADDRESS={$d['mail_from']}
MAIL_FROM_NAME="\${APP_NAME}"

VITE_APP_NAME="\${APP_NAME}"
ENV;

    return (bool) file_put_contents(BASE_PATH . '/.env', trim($env) . "\n");
}

function run_artisan(string $command): array {
    require_once BASE_PATH . '/vendor/autoload.php';
    $app = require BASE_PATH . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    ob_start();
    $exitCode = Illuminate\Support\Facades\Artisan::call($command);
    $output   = ob_get_clean();
    $output  .= Illuminate\Support\Facades\Artisan::output();

    return ['code' => $exitCode, 'output' => trim($output)];
}

function create_storage_link(): string {
    $target = BASE_PATH . '/storage/app/public';
    $link   = BASE_PATH . '/public/storage';
    if (is_link($link) || (is_dir($link) && !is_link($link))) return 'Already exists.';
    if (!file_exists($target)) mkdir($target, 0755, true);
    // Suppress the warning so Laravel's error handler can't convert it to an exception
    if (@symlink($target, $link)) return 'Created.';
    // Windows fallback: directory junction (no admin rights needed)
    if (PHP_OS_FAMILY === 'Windows') {
        $t = str_replace('/', '\\', $target);
        $l = str_replace('/', '\\', $link);
        exec("mklink /J \"$l\" \"$t\" 2>&1", $out, $code);
        if ($code === 0) return 'Created (Windows junction).';
    }
    return 'Skipped — run manually: php artisan storage:link';
}

function set_admin_credentials(string $username, string $password): void {
    require_once BASE_PATH . '/vendor/autoload.php';
    $app = require BASE_PATH . '/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    App\Models\Setting::set('admin.username', $username);
    App\Models\Setting::set('admin.password', password_hash($password, PASSWORD_BCRYPT));
}

function create_lock(): void {
    file_put_contents(LOCK_FILE, date('Y-m-d H:i:s') . ' — Zavelyx installed');
}

// ── POST handlers ─────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'next_1') {
        $checks = all_requirements();
        if (all_pass($checks)) {
            $_SESSION['install']['step'] = 2;
            header('Location: install.php'); exit;
        }
        $errors[] = 'Fix the failed requirements before continuing.';
    }

    if ($action === 'next_2') {
        $host = trim($_POST['db_host'] ?? '127.0.0.1');
        $port = trim($_POST['db_port'] ?? '3306');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = trim($_POST['db_pass'] ?? '');

        if (!$name || !$user) { $errors[] = 'Database name and username are required.'; }
        else {
            $test = test_db($host, $port, $name, $user, $pass);
            if (!$test['ok']) { $errors[] = 'Database connection failed: ' . $test['message']; }
            else {
                $_SESSION['install']['data'] = array_merge($stored, compact('host', 'port', 'name', 'user', 'pass') + ['db_host'=>$host,'db_port'=>$port,'db_name'=>$name,'db_user'=>$user,'db_pass'=>$pass]);
                $_SESSION['install']['step'] = 3;
                header('Location: install.php'); exit;
            }
        }
    }

    if ($action === 'next_3') {
        $app_name    = trim($_POST['app_name']    ?? 'Zavelyx');
        $app_url     = rtrim(trim($_POST['app_url'] ?? 'https://yourdomain.com'), '/');
        $queue_driver = $_POST['queue_driver'] ?? 'database';
        $redis_host  = trim($_POST['redis_host'] ?? '127.0.0.1');
        $redis_port  = trim($_POST['redis_port'] ?? '6379');
        $mail_mailer = $_POST['mail_mailer'] ?? 'log';
        $mail_host   = trim($_POST['mail_host']   ?? 'smtp.mailtrap.io');
        $mail_port   = trim($_POST['mail_port']   ?? '587');
        $mail_user   = trim($_POST['mail_user']   ?? '');
        $mail_pass   = trim($_POST['mail_pass']   ?? '');
        $mail_enc    = $_POST['mail_enc']          ?? 'tls';
        $mail_from   = trim($_POST['mail_from']   ?? 'hello@' . parse_url($app_url, PHP_URL_HOST));

        if (!$app_name || !$app_url) { $errors[] = 'App name and URL are required.'; }
        else {
            $_SESSION['install']['data'] = array_merge($stored, compact(
                'app_name','app_url','queue_driver','redis_host','redis_port',
                'mail_mailer','mail_host','mail_port','mail_user','mail_pass','mail_enc','mail_from'
            ));
            $_SESSION['install']['step'] = 4;
            header('Location: install.php'); exit;
        }
    }

    if ($action === 'next_4') {
        $admin_user = trim($_POST['admin_user'] ?? 'admin');
        $admin_pass = trim($_POST['admin_pass'] ?? '');
        $admin_pass2 = trim($_POST['admin_pass2'] ?? '');

        if (strlen($admin_user) < 3)   { $errors[] = 'Username must be at least 3 characters.'; }
        elseif (strlen($admin_pass) < 6) { $errors[] = 'Password must be at least 6 characters.'; }
        elseif ($admin_pass !== $admin_pass2) { $errors[] = 'Passwords do not match.'; }
        else {
            $_SESSION['install']['data'] = array_merge($stored, compact('admin_user', 'admin_pass'));
            $_SESSION['install']['step'] = 5;
            header('Location: install.php'); exit;
        }
    }

    if ($action === 'run_install') {
        $d = $_SESSION['install']['data'];
        $log = [];

        // 1. Write .env
        if (!write_env($d)) { $errors[] = '.env write failed — check storage permissions.'; }
        else { $log[] = ['ok' => true, 'msg' => '.env written']; }

        if (empty($errors)) {
            try {
                // 2. Generate APP_KEY
                $keyResult = run_artisan('key:generate --force');
                $log[] = ['ok' => $keyResult['code'] === 0, 'msg' => 'APP_KEY generated'];

                // 3. Run migrations
                $migResult = run_artisan('migrate --force');
                $migOk = $migResult['code'] === 0;
                $log[] = ['ok' => $migOk, 'msg' => 'Database migrated — ' . ($migOk ? 'success' : 'error: ' . $migResult['output'])];
                if (!$migOk) throw new RuntimeException('Migration failed: ' . $migResult['output']);

                // 4. Storage link (never fatal — Windows may need manual step)
                try {
                    $linkMsg = create_storage_link();
                } catch (Throwable) {
                    $linkMsg = 'Skipped — run manually: php artisan storage:link';
                }
                $log[] = ['ok' => true, 'msg' => 'Storage link: ' . $linkMsg];

                // 5. Config & route cache
                try { run_artisan('config:cache'); run_artisan('route:cache'); } catch (Throwable) {}
                $log[] = ['ok' => true, 'msg' => 'Config & route cache built'];

                // 6. Set admin credentials
                if (!empty($d['admin_user']) && !empty($d['admin_pass'])) {
                    try {
                        set_admin_credentials($d['admin_user'], $d['admin_pass']);
                        $log[] = ['ok' => true, 'msg' => 'Admin credentials set'];
                    } catch (Throwable $e) {
                        $log[] = ['ok' => false, 'msg' => 'Admin credentials failed: ' . $e->getMessage()];
                    }
                }

                // 7. Create lock
                create_lock();
                $log[] = ['ok' => true, 'msg' => 'Installer locked'];

            } catch (Throwable $e) {
                $log[] = ['ok' => false, 'msg' => 'Fatal: ' . $e->getMessage()];
                $errors[] = $e->getMessage();
            }

            $_SESSION['install']['log']  = $log;
            if (empty($errors)) {
                $_SESSION['install']['step'] = 6;
                header('Location: install.php'); exit;
            }
        }
    }

    // Re-render same step with errors
    $step = $_SESSION['install']['step'];
}

$checks = ($step === 1) ? all_requirements() : [];
$passed = ($step === 1) ? all_pass($checks) : true;

// ── HTML ──────────────────────────────────────────────────────────────────────

$stepLabels = ['Requirements','Database','Application','Admin Account','Installing','Complete'];
$stored = $_SESSION['install']['data'] ?? [];
$log    = $_SESSION['install']['log']  ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Zavelyx Installer v<?= INSTALL_VERSION ?></title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#060d1a;--card:#0a1628;--border:rgba(14,165,233,.12);--sky:#0ea5e9;--cyan:#22d3ee;--text:#f8fafc;--muted:#94a3b8;--dim:#475569;--red:#f87171;--green:#34d399;--amber:#fbbf24}
html,body{min-height:100vh;background:var(--bg);font-family:'Inter',system-ui,sans-serif;color:var(--text);-webkit-font-smoothing:antialiased}
body::before{content:'';position:fixed;inset:0;background-image:radial-gradient(circle at 20% 20%,rgba(14,165,233,.06) 0,transparent 50%),radial-gradient(circle at 80% 80%,rgba(99,102,241,.05) 0,transparent 50%),radial-gradient(rgba(148,163,184,.04) 1px,transparent 1px);background-size:100% 100%,100% 100%,28px 28px;pointer-events:none;z-index:0}
.wrap{position:relative;z-index:1;max-width:680px;margin:0 auto;padding:2.5rem 1.25rem 4rem}
/* Brand */
.brand{display:flex;align-items:center;gap:.6rem;margin-bottom:2.5rem}
.brand-dot{width:9px;height:9px;border-radius:50%;background:linear-gradient(135deg,var(--sky),var(--cyan));box-shadow:0 0 10px rgba(14,165,233,.6)}
.brand-name{font-size:1rem;font-weight:900;letter-spacing:-.02em}
.brand-name span{background:linear-gradient(90deg,var(--sky),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.brand-ver{margin-left:auto;font-size:.7rem;color:var(--dim);font-weight:600}
/* Progress */
.progress{display:flex;align-items:center;gap:.25rem;margin-bottom:2rem;overflow-x:auto;padding-bottom:.5rem}
.prog-step{display:flex;align-items:center;gap:.25rem;flex-shrink:0}
.prog-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;transition:all .2s}
.prog-dot.done{background:var(--green);color:#fff}
.prog-dot.active{background:var(--sky);color:#fff;box-shadow:0 0 0 3px rgba(14,165,233,.25)}
.prog-dot.future{background:rgba(255,255,255,.05);color:var(--dim);border:1px solid var(--border)}
.prog-label{font-size:.7rem;font-weight:600;color:var(--dim)}
.prog-label.active{color:var(--sky)}
.prog-label.done{color:var(--green)}
.prog-divider{flex:1;min-width:12px;height:1px;background:var(--border)}
/* Card */
.card{background:var(--card);border:1px solid var(--border);border-radius:1.25rem;overflow:hidden}
.card-header{padding:1.5rem 1.75rem 1.25rem;border-bottom:1px solid var(--border)}
.card-header h1{font-size:1.15rem;font-weight:800;color:var(--text);margin-bottom:.25rem}
.card-header p{font-size:.85rem;color:var(--muted);line-height:1.5}
.card-body{padding:1.75rem}
/* Form */
.field{margin-bottom:1.1rem}
.field label{display:block;font-size:.78rem;font-weight:700;color:var(--muted);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.04em}
.field input,.field select{width:100%;height:2.5rem;padding:0 .875rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:.65rem;color:var(--text);font-size:.875rem;font-family:inherit;outline:none;transition:all .15s}
.field input:focus,.field select:focus{border-color:rgba(14,165,233,.45);box-shadow:0 0 0 3px rgba(14,165,233,.12)}
.field input::placeholder{color:var(--dim)}
.field select option{background:#0a1628;color:var(--text)}
.field-hint{font-size:.75rem;color:var(--dim);margin-top:.3rem}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:.875rem}
@media(max-width:480px){.field-row{grid-template-columns:1fr}}
/* Errors */
.errors{padding:.875rem 1rem;background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2);border-radius:.75rem;margin-bottom:1.25rem}
.errors p{font-size:.82rem;color:var(--red);display:flex;align-items:flex-start;gap:.4rem}
.errors p+p{margin-top:.25rem}
/* Requirement rows */
.req-grid{display:grid;gap:.45rem}
.req-row{display:flex;align-items:center;gap:.6rem;padding:.55rem .75rem;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:.6rem}
.req-icon{width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:900;flex-shrink:0}
.req-icon.ok{background:rgba(52,211,153,.15);color:var(--green)}
.req-icon.fail{background:rgba(248,113,113,.15);color:var(--red)}
.req-icon.warn{background:rgba(251,191,36,.12);color:var(--amber)}
.req-label{flex:1;font-size:.82rem;color:var(--muted);font-weight:500}
.req-detail{font-size:.75rem;color:var(--dim)}
/* Buttons */
.btn-row{display:flex;align-items:center;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.35rem;border-radius:.75rem;font-size:.85rem;font-weight:700;cursor:pointer;border:none;transition:all .15s;font-family:inherit}
.btn-primary{background:linear-gradient(135deg,var(--sky),#2563eb);color:#fff;box-shadow:0 4px 14px rgba(14,165,233,.25)}
.btn-primary:hover{box-shadow:0 6px 18px rgba(14,165,233,.35);transform:translateY(-1px)}
.btn-primary:disabled{opacity:.5;cursor:not-allowed;transform:none}
.btn-ghost{background:rgba(255,255,255,.05);color:var(--muted);border:1px solid rgba(255,255,255,.08)}
.btn-ghost:hover{background:rgba(255,255,255,.09);color:var(--text)}
/* Log rows */
.log-row{display:flex;align-items:flex-start;gap:.6rem;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.04)}
.log-row:last-child{border:none}
.log-icon{width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:900;flex-shrink:0;margin-top:.1rem}
.log-icon.ok{background:rgba(52,211,153,.15);color:var(--green)}
.log-icon.fail{background:rgba(248,113,113,.15);color:var(--red)}
.log-msg{font-size:.82rem;color:var(--muted)}
/* Section title */
.section-title{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--dim);margin:1.25rem 0 .65rem;display:flex;align-items:center;gap:.5rem}
.section-title::after{content:'';flex:1;height:1px;background:var(--border)}
/* Success */
.success-icon{width:64px;height:64px;border-radius:50%;background:rgba(52,211,153,.12);border:2px solid rgba(52,211,153,.25);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:1.75rem}
.cred-box{background:rgba(14,165,233,.06);border:1px solid rgba(14,165,233,.15);border-radius:.75rem;padding:.875rem 1rem;margin:.75rem 0;font-size:.82rem;color:var(--muted)}
.cred-box strong{color:var(--sky)}
.warn-box{background:rgba(251,191,36,.06);border:1px solid rgba(251,191,36,.15);border-radius:.75rem;padding:.875rem 1rem;margin:.75rem 0;font-size:.82rem;color:var(--amber);display:flex;gap:.5rem}
.cron-box{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:.75rem;padding:.875rem 1rem;margin:.75rem 0}
.cron-box code{font-size:.78rem;color:var(--cyan);font-family:monospace;display:block;line-height:1.7}
/* Toggle group */
.radio-group{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.5rem}
.radio-card{position:relative}
.radio-card input{position:absolute;opacity:0;width:0;height:0}
.radio-card label{display:block;padding:.65rem .875rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:.65rem;cursor:pointer;font-size:.82rem;color:var(--muted);font-weight:600;transition:all .15s;text-align:center}
.radio-card input:checked+label{background:rgba(14,165,233,.1);border-color:rgba(14,165,233,.3);color:var(--sky)}
</style>
</head>
<body>
<div class="wrap">

    <!-- Brand -->
    <div class="brand">
        <span class="brand-dot"></span>
        <span class="brand-name">Nexa<span>Hub</span> <span style="-webkit-text-fill-color:var(--dim);background:none;font-weight:600;font-size:.8rem">Installer</span></span>
        <span class="brand-ver">v<?= INSTALL_VERSION ?></span>
    </div>

    <!-- Progress -->
    <div class="progress">
        <?php foreach ($stepLabels as $i => $label):
            $n = $i + 1;
            $cls = $n < $step ? 'done' : ($n === $step ? 'active' : 'future');
            $lbl = $n < $step ? 'done' : ($n === $step ? 'active' : '');
            $icon = $n < $step ? '✓' : $n;
        ?>
        <?php if ($i > 0): ?><div class="prog-divider"></div><?php endif; ?>
        <div class="prog-step">
            <div class="prog-dot <?= $cls ?>"><?= $icon ?></div>
            <span class="prog-label <?= $lbl ?>"><?= $label ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $e): ?>
        <p><span>⚠</span><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── STEP 1: Requirements ─────────────────────────────────────────── -->
    <?php if ($step === 1): ?>
    <div class="card">
        <div class="card-header">
            <h1>System Requirements</h1>
            <p>Checking your server environment before installation begins.</p>
        </div>
        <div class="card-body">
            <div class="req-grid">
                <?php foreach ($checks as $key => $c):
                    $icon = $c['ok'] ? 'ok' : ($c['required'] ? 'fail' : 'warn');
                    $sym  = $c['ok'] ? '✓' : ($c['required'] ? '✗' : '!');
                ?>
                <div class="req-row">
                    <span class="req-icon <?= $icon ?>"><?= $sym ?></span>
                    <span class="req-label"><?= htmlspecialchars($c['label']) ?></span>
                    <span class="req-detail"><?= htmlspecialchars($c['detail']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="btn-row">
                <input type="hidden" name="action" value="next_1">
                <button class="btn btn-primary" type="submit" <?= $passed ? '' : 'disabled' ?>>
                    <?= $passed ? 'Continue →' : 'Fix issues to continue' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ── STEP 2: Database ─────────────────────────────────────────────── -->
    <?php elseif ($step === 2): ?>
    <div class="card">
        <div class="card-header">
            <h1>Database Configuration</h1>
            <p>Enter your MySQL / MariaDB connection details. The database will be created automatically if it doesn't exist.</p>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="next_2">
                <div class="field-row">
                    <div class="field">
                        <label>DB Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($stored['db_host'] ?? '127.0.0.1') ?>" placeholder="127.0.0.1">
                    </div>
                    <div class="field">
                        <label>DB Port</label>
                        <input type="text" name="db_port" value="<?= htmlspecialchars($stored['db_port'] ?? '3306') ?>" placeholder="3306">
                    </div>
                </div>
                <div class="field">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($stored['db_name'] ?? '') ?>" placeholder="nexahub" required>
                    <p class="field-hint">Will be created if it doesn't exist (requires CREATE privilege).</p>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>Username</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($stored['db_user'] ?? 'root') ?>" placeholder="root" required>
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="db_pass" value="" placeholder="(empty for no password)">
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn btn-primary" type="submit">Test &amp; Continue →</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── STEP 3: Application ──────────────────────────────────────────── -->
    <?php elseif ($step === 3): ?>
    <div class="card">
        <div class="card-header">
            <h1>Application Settings</h1>
            <p>Configure your site name, URL, queue driver and email settings.</p>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="next_3">

                <p class="section-title">Site</p>
                <div class="field-row">
                    <div class="field">
                        <label>Site Name</label>
                        <input type="text" name="app_name" value="<?= htmlspecialchars($stored['app_name'] ?? 'Zavelyx') ?>" required>
                    </div>
                    <div class="field">
                        <label>Site URL</label>
                        <input type="url" name="app_url" value="<?= htmlspecialchars($stored['app_url'] ?? 'https://yourdomain.com') ?>" placeholder="https://yourdomain.com" required>
                    </div>
                </div>

                <p class="section-title">Queue &amp; Cache Driver</p>
                <div class="radio-group">
                    <div class="radio-card">
                        <input type="radio" name="queue_driver" id="q_db" value="database" <?= ($stored['queue_driver'] ?? 'database') === 'database' ? 'checked' : '' ?>>
                        <label for="q_db">🗄️ Database (CPanel friendly)</label>
                    </div>
                    <div class="radio-card">
                        <input type="radio" name="queue_driver" id="q_redis" value="redis" <?= ($stored['queue_driver'] ?? '') === 'redis' ? 'checked' : '' ?>>
                        <label for="q_redis">⚡ Redis (recommended)</label>
                    </div>
                </div>
                <div class="field-row" id="redis_fields" style="<?= ($stored['queue_driver'] ?? 'database') === 'redis' ? '' : 'opacity:.35;pointer-events:none' ?>">
                    <div class="field">
                        <label>Redis Host</label>
                        <input type="text" name="redis_host" value="<?= htmlspecialchars($stored['redis_host'] ?? '127.0.0.1') ?>">
                    </div>
                    <div class="field">
                        <label>Redis Port</label>
                        <input type="text" name="redis_port" value="<?= htmlspecialchars($stored['redis_port'] ?? '6379') ?>">
                    </div>
                </div>

                <p class="section-title">Mail</p>
                <div class="radio-group">
                    <div class="radio-card">
                        <input type="radio" name="mail_mailer" id="m_log" value="log" <?= ($stored['mail_mailer'] ?? 'log') === 'log' ? 'checked' : '' ?>>
                        <label for="m_log">📋 Log only (dev)</label>
                    </div>
                    <div class="radio-card">
                        <input type="radio" name="mail_mailer" id="m_smtp" value="smtp" <?= ($stored['mail_mailer'] ?? '') === 'smtp' ? 'checked' : '' ?>>
                        <label for="m_smtp">📧 SMTP</label>
                    </div>
                </div>
                <div id="smtp_fields" style="<?= ($stored['mail_mailer'] ?? 'log') === 'smtp' ? '' : 'opacity:.35;pointer-events:none' ?>">
                    <div class="field-row">
                        <div class="field">
                            <label>SMTP Host</label>
                            <input type="text" name="mail_host" value="<?= htmlspecialchars($stored['mail_host'] ?? 'smtp.mailtrap.io') ?>">
                        </div>
                        <div class="field">
                            <label>SMTP Port</label>
                            <input type="text" name="mail_port" value="<?= htmlspecialchars($stored['mail_port'] ?? '587') ?>">
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Username</label>
                            <input type="text" name="mail_user" value="<?= htmlspecialchars($stored['mail_user'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label>Password</label>
                            <input type="password" name="mail_pass" value="">
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label>Encryption</label>
                            <select name="mail_enc">
                                <option value="tls" <?= ($stored['mail_enc'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($stored['mail_enc'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= ($stored['mail_enc'] ?? '') === '' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>From Address</label>
                            <input type="email" name="mail_from" value="<?= htmlspecialchars($stored['mail_from'] ?? '') ?>" placeholder="hello@yourdomain.com">
                        </div>
                    </div>
                </div>

                <div class="btn-row">
                    <button class="btn btn-primary" type="submit">Continue →</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.querySelectorAll('[name=queue_driver]').forEach(r => r.addEventListener('change', function() {
        document.getElementById('redis_fields').style.cssText = this.value === 'redis' ? '' : 'opacity:.35;pointer-events:none';
    }));
    document.querySelectorAll('[name=mail_mailer]').forEach(r => r.addEventListener('change', function() {
        document.getElementById('smtp_fields').style.cssText = this.value === 'smtp' ? '' : 'opacity:.35;pointer-events:none';
    }));
    </script>

    <!-- ── STEP 4: Admin Account ────────────────────────────────────────── -->
    <?php elseif ($step === 4): ?>
    <div class="card">
        <div class="card-header">
            <h1>Admin Account</h1>
            <p>Set the credentials you'll use to log in to the admin panel at <code style="color:var(--sky)">/admin</code>.</p>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="next_4">
                <div class="field">
                    <label>Admin Username</label>
                    <input type="text" name="admin_user" value="<?= htmlspecialchars($stored['admin_user'] ?? 'admin') ?>" minlength="3" required autocomplete="username">
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="admin_pass" minlength="6" required autocomplete="new-password">
                        <p class="field-hint">Min 6 characters.</p>
                    </div>
                    <div class="field">
                        <label>Confirm Password</label>
                        <input type="password" name="admin_pass2" minlength="6" required autocomplete="new-password">
                    </div>
                </div>
                <div class="btn-row">
                    <button class="btn btn-primary" type="submit">Continue →</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── STEP 5: Run Installation ─────────────────────────────────────── -->
    <?php elseif ($step === 5): ?>
    <div class="card">
        <div class="card-header">
            <h1>Ready to Install</h1>
            <p>Review your configuration and click <strong>Install Zavelyx</strong> to begin. This will write your <code>.env</code>, run database migrations, and configure the application.</p>
        </div>
        <div class="card-body">
            <p class="section-title">Summary</p>
            <div class="req-grid" style="margin-bottom:.75rem">
                <div class="req-row"><span class="req-icon ok">✓</span><span class="req-label">Site Name</span><span class="req-detail"><?= htmlspecialchars($stored['app_name'] ?? '') ?></span></div>
                <div class="req-row"><span class="req-icon ok">✓</span><span class="req-label">Site URL</span><span class="req-detail"><?= htmlspecialchars($stored['app_url'] ?? '') ?></span></div>
                <div class="req-row"><span class="req-icon ok">✓</span><span class="req-label">Database</span><span class="req-detail"><?= htmlspecialchars(($stored['db_user'] ?? '') . '@' . ($stored['db_host'] ?? '') . '/' . ($stored['db_name'] ?? '')) ?></span></div>
                <div class="req-row"><span class="req-icon ok">✓</span><span class="req-label">Queue Driver</span><span class="req-detail"><?= htmlspecialchars($stored['queue_driver'] ?? 'database') ?></span></div>
                <div class="req-row"><span class="req-icon ok">✓</span><span class="req-label">Mail</span><span class="req-detail"><?= htmlspecialchars($stored['mail_mailer'] ?? 'log') ?></span></div>
                <div class="req-row"><span class="req-icon ok">✓</span><span class="req-label">Admin Username</span><span class="req-detail"><?= htmlspecialchars($stored['admin_user'] ?? '') ?></span></div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="run_install">
                <div class="btn-row">
                    <button class="btn btn-primary" type="submit" id="install-btn" onclick="this.disabled=true;this.textContent='Installing…';this.closest('form').submit()">
                        🚀 Install Zavelyx
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── STEP 6: Complete ─────────────────────────────────────────────── -->
    <?php elseif ($step === 6): ?>
    <div class="card">
        <div class="card-header">
            <h1>Installation Complete</h1>
            <p>Zavelyx has been successfully installed on your server.</p>
        </div>
        <div class="card-body">
            <div style="text-align:center;margin-bottom:1.5rem">
                <div class="success-icon">🎉</div>
                <p style="color:var(--green);font-weight:800;font-size:1.05rem">Successfully installed!</p>
            </div>

            <?php if (!empty($log)): ?>
            <p class="section-title">Installation Log</p>
            <?php foreach ($log as $entry): ?>
            <div class="log-row">
                <span class="log-icon <?= $entry['ok'] ? 'ok' : 'fail' ?>"><?= $entry['ok'] ? '✓' : '✗' ?></span>
                <span class="log-msg"><?= htmlspecialchars($entry['msg']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <div class="cred-box" style="margin-top:1.25rem">
                <strong>Admin Panel:</strong> <a href="<?= htmlspecialchars($stored['app_url'] ?? '') ?>/admin" style="color:var(--sky)"><?= htmlspecialchars($stored['app_url'] ?? '') ?>/admin</a><br>
                <strong>Username:</strong> <?= htmlspecialchars($stored['admin_user'] ?? 'admin') ?><br>
                <strong>Password:</strong> <em style="color:var(--dim)">(the password you set)</em>
            </div>

            <div class="warn-box">
                <span>⚠</span>
                <div><strong>Security:</strong> This installer is now locked. For extra safety, delete <code>public/install.php</code> from your server.</div>
            </div>

            <p class="section-title">Cron Job (required)</p>
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:.5rem">Add this cron to your cPanel Cron Jobs (every minute):</p>
            <div class="cron-box">
                <code>* * * * * cd <?= BASE_PATH ?> && php artisan schedule:run >> /dev/null 2>&amp;1</code>
            </div>

            <div class="btn-row">
                <a href="<?= htmlspecialchars($stored['app_url'] ?? '/') ?>" class="btn btn-ghost">Visit Site</a>
                <a href="<?= htmlspecialchars($stored['app_url'] ?? '/') ?>/admin" class="btn btn-primary">Open Admin Panel →</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
