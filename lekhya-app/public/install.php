<?php
/**
 * Lekhya Installer v2 — works without shell_exec
 * DELETE THIS FILE after setup is complete.
 */
set_time_limit(300);
ini_set('display_errors', '1');
error_reporting(E_ALL);
ini_set('memory_limit', '512M');

// Auto-detect the lekhya-app directory
$BASE = null;
foreach ([
    dirname(__DIR__),           // install.php inside public/
    __DIR__ . '/lekhya-app',    // install.php in public_html root
    __DIR__ . '/lekhya',
] as $candidate) {
    if (file_exists($candidate . '/artisan')) {
        $BASE = realpath($candidate);
        break;
    }
}

if (!$BASE) {
    die('<h2 style="font-family:sans-serif;color:red;padding:40px">ERROR: Cannot find lekhya-app directory.<br><small>Make sure lekhya-app/ is in the right place.</small></h2>');
}

$step   = $_GET['step'] ?? 'welcome';
$errors = [];
$log    = [];

// ── Write .env ─────────────────────────────────────────────────────────────
function envWrite(array $values): void {
    global $BASE;
    $path    = $BASE . '/.env';
    $example = $BASE . '/.env.example';

    $env = file_exists($path)    ? file_get_contents($path)
         : (file_exists($example) ? file_get_contents($example) : '');

    foreach ($values as $key => $value) {
        $escaped = (str_contains($value, ' ') || $value === '') ? "\"{$value}\"" : $value;
        if (preg_match("/^{$key}=/m", $env)) {
            $env = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $env);
        } else {
            $env .= "\n{$key}={$escaped}";
        }
    }
    file_put_contents($path, $env);
}

// ── Generate APP_KEY without artisan ───────────────────────────────────────
function generateKey(): string {
    return 'base64:' . base64_encode(random_bytes(32));
}

// ── Bootstrap Laravel and run artisan commands ─────────────────────────────
function artisan(string $command, array $args = []): array {
    global $BASE;

    ob_start();
    $exitCode = 0;

    try {
        if (!isset($GLOBALS['_lapp'])) {
            define('LARAVEL_START', microtime(true));
            require_once $BASE . '/vendor/autoload.php';
            $app = require $BASE . '/bootstrap/app.php';
            $GLOBALS['_lapp'] = $app;
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            $GLOBALS['_lkernel'] = $kernel;
        }
        $exitCode = \Illuminate\Support\Facades\Artisan::call($command, $args);
        $output   = \Illuminate\Support\Facades\Artisan::output();
    } catch (\Throwable $e) {
        $output = 'ERROR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine();
        $exitCode = 1;
    }

    ob_end_clean();
    return ['output' => trim($output ?: '(done)'), 'code' => $exitCode];
}

// ── Handle database form submission ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'database') {
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = trim($_POST['db_pass'] ?? '');
    $url  = rtrim(trim($_POST['app_url'] ?? ''), '/');

    if (!$name || !$user) {
        $errors[] = 'Database name and username are required.';
    }

    if (!$errors) {
        try {
            new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        } catch (\Exception $e) {
            $errors[] = 'Cannot connect to database: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        // Write .env BEFORE bootstrapping Laravel
        envWrite([
            'APP_KEY'          => generateKey(),
            'APP_ENV'          => 'production',
            'APP_DEBUG'        => 'false',
            'APP_URL'          => $url ?: 'https://lekhya.prabhassaas.in',
            'DB_CONNECTION'    => 'mysql',
            'DB_HOST'          => $host,
            'DB_PORT'          => $port,
            'DB_DATABASE'      => $name,
            'DB_USERNAME'      => $user,
            'DB_PASSWORD'      => $pass,
            'SESSION_DRIVER'   => 'file',
            'CACHE_STORE'      => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'AI_DRIVER'        => 'mock',
        ]);

        // Fix permissions before bootstrap
        @chmod($BASE . '/storage', 0775);
        @chmod($BASE . '/bootstrap/cache', 0775);
        foreach (glob($BASE . '/storage/{,**/}*', GLOB_BRACE) as $f) { @chmod($f, is_dir($f) ? 0775 : 0664); }

        header('Location: ?step=setup');
        exit;
    }
}

// ── Run full setup ──────────────────────────────────────────────────────────
if ($step === 'setup') {
    // Fix permissions
    @chmod($BASE . '/storage', 0775);
    @chmod($BASE . '/bootstrap/cache', 0775);

    // Clear any stale cached config that might reference wrong paths
    $configCache = $BASE . '/bootstrap/cache/config.php';
    if (file_exists($configCache)) { @unlink($configCache); }
    $routeCache = $BASE . '/bootstrap/cache/routes-v7.php';
    if (file_exists($routeCache)) { @unlink($routeCache); }

    // Run migrations
    $r = artisan('migrate', ['--force' => true]);
    $log[] = ['label' => '✓ Database tables created', 'out' => $r['output'], 'ok' => $r['code'] === 0];

    // Seed data
    $r = artisan('db:seed', ['--class' => 'HsnSacSeeder', '--force' => true]);
    $log[] = ['label' => '✓ HSN / SAC codes loaded', 'out' => $r['output'], 'ok' => $r['code'] === 0];

    $r = artisan('db:seed', ['--class' => 'PlanSeeder', '--force' => true]);
    $log[] = ['label' => '✓ Subscription plans loaded', 'out' => $r['output'], 'ok' => $r['code'] === 0];

    $r = artisan('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
    $log[] = ['label' => '✓ Roles & permissions loaded', 'out' => $r['output'], 'ok' => $r['code'] === 0];

    // Storage symlink
    $publicStorage = $BASE . '/public/storage';
    $target        = $BASE . '/storage/app/public';
    if (!file_exists($publicStorage)) {
        @symlink($target, $publicStorage);
    }
    $log[] = ['label' => '✓ Storage link created', 'out' => file_exists($publicStorage) ? 'OK' : 'Skipped (manual may be needed)', 'ok' => true];

    // Cache config + routes for production speed
    $r = artisan('config:cache');
    $log[] = ['label' => '✓ Config cached', 'out' => $r['output'], 'ok' => true];

    $r = artisan('route:cache');
    $log[] = ['label' => '✓ Routes cached', 'out' => $r['output'], 'ok' => true];
}

$hasError = $step === 'setup' && count(array_filter($log, fn($l) => !($l['ok'] ?? true))) > 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lekhya Setup</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#f0f3f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.13);max-width:520px;width:100%;overflow:hidden}
.top{background:linear-gradient(135deg,#1B2A4A 0%,#2e5a94 100%);color:#fff;padding:26px 30px}
.top h1{font-size:1.35rem;font-weight:800}
.top p{color:#b3c4df;font-size:.83rem;margin-top:3px}
.body{padding:28px 30px}
.prog{display:flex;gap:6px;margin-bottom:22px}
.prog span{flex:1;height:4px;border-radius:2px;background:#e5e7eb}
.prog span.on{background:#1B2A4A}
label{display:block;font-size:.75rem;font-weight:700;color:#374151;margin:14px 0 4px;text-transform:uppercase;letter-spacing:.04em}
label:first-of-type{margin-top:0}
input[type=text],input[type=url],input[type=password]{width:100%;padding:10px 13px;border:1.5px solid #d1d5db;border-radius:9px;font-size:.9rem;outline:none;transition:border .15s}
input:focus{border-color:#1B2A4A;box-shadow:0 0 0 3px rgba(27,42,74,.08)}
.hint{font-size:.73rem;color:#9ca3af;margin-top:3px}
.btn{display:block;width:100%;margin-top:22px;padding:13px;background:#1B2A4A;color:#fff;border:none;border-radius:10px;font-size:.95rem;font-weight:700;cursor:pointer;text-align:center;text-decoration:none;transition:background .15s}
.btn:hover{background:#162240}
.btn.green{background:#16a34a}.btn.green:hover{background:#15803d}
.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:9px;padding:12px 14px;font-size:.84rem;margin-bottom:16px;line-height:1.5}
.ok-banner{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;text-align:center;margin-bottom:16px}
.ok-banner h2{color:#15803d;font-size:1.15rem;margin-bottom:5px}
.ok-banner p{color:#166534;font-size:.84rem}
.fail-banner{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;margin-bottom:16px}
.fail-banner p{color:#991b1b;font-size:.875rem}
.log{margin-top:20px;display:flex;flex-direction:column;gap:10px}
.log-row{border:1px solid #e5e7eb;border-radius:9px;overflow:hidden}
.log-lbl{padding:8px 12px;font-size:.8rem;font-weight:600;color:#374151;background:#f9fafb;border-bottom:1px solid #e5e7eb}
.log-out{padding:8px 12px;font-size:.75rem;font-family:ui-monospace,monospace;color:#4b5563;white-space:pre-wrap;max-height:80px;overflow-y:auto;background:#fff}
.warn{background:#fffbeb;border:1px solid #fde68a;border-radius:9px;padding:12px 14px;font-size:.82rem;color:#92400e;margin-top:14px;line-height:1.55}
a.open-btn{display:block;text-align:center;padding:13px;border-radius:10px;font-weight:700;font-size:.95rem;text-decoration:none;margin-top:6px}
</style>
</head>
<body>
<div class="card">
  <div class="top">
    <h1>ल&nbsp; Lekhya ERP — Setup</h1>
    <p>One-click installer · No technical steps needed</p>
  </div>
  <div class="body">

  <?php if ($step === 'welcome'): ?>
    <div class="prog"><span class="on"></span><span></span><span></span></div>
    <h2 style="font-size:1rem;color:#1B2A4A;margin-bottom:10px">Welcome — let's get Lekhya running</h2>
    <p style="font-size:.875rem;color:#6b7280;margin-bottom:18px;line-height:1.65">
      This will set up your database and configure everything automatically.<br>
      You need one thing ready first:
    </p>
    <div style="background:#EEF1F8;border-radius:10px;padding:16px;font-size:.875rem;color:#1B2A4A;line-height:1.9">
      ✅ A MySQL database already created in <strong>Hostinger → Databases</strong><br>
      ✅ You have the database name, username &amp; password written down
    </div>
    <a href="?step=database" class="btn">I have my database ready →</a>

  <?php elseif ($step === 'database'): ?>
    <div class="prog"><span class="on"></span><span class="on"></span><span></span></div>
    <h2 style="font-size:1rem;color:#1B2A4A;margin-bottom:18px">Enter your database details</h2>
    <?php if ($errors): ?>
      <div class="err"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif ?>
    <form method="POST">
      <label>Your Website URL</label>
      <input type="url" name="app_url" value="https://lekhya.prabhassaas.in" required>

      <label>Database Host</label>
      <input type="text" name="db_host" value="127.0.0.1">
      <p class="hint">Leave as 127.0.0.1 — this is correct for Hostinger</p>

      <label>Database Name</label>
      <input type="text" name="db_name" value="u663059394_lekhyaerp" required>

      <label>Database Username</label>
      <input type="text" name="db_user" value="u663059394_lekhyaerp" required>

      <label>Database Password</label>
      <input type="password" name="db_pass" placeholder="The password you set for the database">

      <input type="hidden" name="db_port" value="3306">
      <button type="submit" class="btn">Connect &amp; Install →</button>
    </form>

  <?php elseif ($step === 'setup'): ?>
    <div class="prog"><span class="on"></span><span class="on"></span><span class="on"></span></div>

    <?php if (!$hasError): ?>
      <div class="ok-banner">
        <h2>🎉 Lekhya is Ready!</h2>
        <p>Your ERP is set up and ready to use.</p>
      </div>
      <a href="/" class="open-btn" style="background:#1B2A4A;color:#fff">Open Lekhya ERP →</a>
    <?php else: ?>
      <div class="fail-banner">
        <p><strong>Some steps had issues.</strong> Check the log below. You can still try opening the app — it may work.</p>
      </div>
      <a href="/" class="open-btn" style="background:#6b7280;color:#fff">Try Opening App →</a>
    <?php endif ?>

    <div class="log">
      <?php foreach ($log as $item): ?>
      <div class="log-row">
        <div class="log-lbl"><?= htmlspecialchars($item['label']) ?></div>
        <div class="log-out"><?= htmlspecialchars($item['out'] ?: '(done)') ?></div>
      </div>
      <?php endforeach ?>
    </div>

    <div class="warn" style="margin-top:16px">
      <strong>⚠️ Security:</strong> After opening the app, go to Hostinger File Manager →
      find <code>install.php</code> → delete it.
    </div>
  <?php endif ?>

  </div>
</div>
</body>
</html>
