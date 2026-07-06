<?php
/**
 * Lekhya One-Click Installer
 * Run this ONCE after uploading. Delete it after setup is complete.
 */

define('BASE', dirname(__DIR__));
define('INSTALL_SECRET', 'lekhya-install-2025'); // change this before uploading if you want extra security

$step   = $_GET['step']  ?? 'welcome';
$secret = $_GET['secret'] ?? '';
$errors = [];
$log    = [];

// ── helpers ──────────────────────────────────────────────────────────────────
function run(string $cmd): string {
    return trim(shell_exec("cd " . escapeshellarg(BASE) . " && {$cmd} 2>&1") ?? '');
}
function envSet(string $key, string $value): void {
    $path = BASE . '/.env';
    $env  = file_get_contents($path);
    if (preg_match("/^{$key}=/m", $env)) {
        $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
    } else {
        $env .= "\n{$key}={$value}";
    }
    file_put_contents($path, $env);
}
function envGet(string $key): string {
    $env = file_get_contents(BASE . '/.env');
    preg_match("/^{$key}=(.*)$/m", $env, $m);
    return trim($m[1] ?? '');
}

// ── actions ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'database') {
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = trim($_POST['db_pass'] ?? '');
    $url  = trim($_POST['app_url'] ?? '');

    if (!$name || !$user) { $errors[] = 'Database name and username are required.'; }

    if (!$errors) {
        // Test connection first
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name}", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (Exception $e) {
            $errors[] = 'DB connection failed: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        // Write .env
        if (!file_exists(BASE . '/.env')) {
            copy(BASE . '/.env.example', BASE . '/.env');
        }
        envSet('DB_CONNECTION', 'mysql');
        envSet('DB_HOST',       $host);
        envSet('DB_PORT',       $port);
        envSet('DB_DATABASE',   $name);
        envSet('DB_USERNAME',   $user);
        envSet('DB_PASSWORD',   $pass);
        envSet('APP_URL',       $url ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        envSet('APP_ENV',       'production');
        envSet('APP_DEBUG',     'false');
        envSet('SESSION_DRIVER','file');
        envSet('CACHE_STORE',   'file');
        envSet('QUEUE_CONNECTION','sync');
        envSet('AI_DRIVER',     'mock');

        header('Location: ?step=setup&secret=' . INSTALL_SECRET);
        exit;
    }
}

if ($step === 'setup' && $secret === INSTALL_SECRET) {
    // Generate key
    $log[] = ['cmd' => 'php artisan key:generate --force', 'out' => run('php artisan key:generate --force')];
    // Clear caches
    run('php artisan config:clear');
    run('php artisan cache:clear');
    // Migrate
    $log[] = ['cmd' => 'php artisan migrate --force', 'out' => run('php artisan migrate --force')];
    // Seed
    $log[] = ['cmd' => 'Seed HsnSac', 'out' => run('php artisan db:seed --class=HsnSacSeeder --force')];
    $log[] = ['cmd' => 'Seed Plans',  'out' => run('php artisan db:seed --class=PlanSeeder --force')];
    $log[] = ['cmd' => 'Seed Permissions', 'out' => run('php artisan db:seed --class=PermissionSeeder --force')];
    // Storage link
    $log[] = ['cmd' => 'storage:link', 'out' => run('php artisan storage:link 2>/dev/null || true')];
    // Permissions
    run('chmod -R 775 storage bootstrap/cache');
    // Cache for production
    $log[] = ['cmd' => 'config:cache', 'out' => run('php artisan config:cache')];
    $log[] = ['cmd' => 'route:cache',  'out' => run('php artisan route:cache')];
    $log[] = ['cmd' => 'view:cache',   'out' => run('php artisan view:cache')];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lekhya Installer</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f0f3f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.1);max-width:560px;width:100%;overflow:hidden}
.header{background:#1B2A4A;color:#fff;padding:28px 32px}
.header h1{font-size:1.5rem;font-weight:700}
.header p{color:#b3c4df;font-size:.875rem;margin-top:4px}
.body{padding:32px}
.step-bar{display:flex;gap:8px;margin-bottom:28px}
.step-bar span{flex:1;height:4px;border-radius:2px;background:#e5e7eb}
.step-bar span.done{background:#1B2A4A}
label{display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px;margin-top:16px}
label:first-of-type{margin-top:0}
input{width:100%;padding:10px 14px;border:1.5px solid #d1d5db;border-radius:8px;font-size:.9rem;outline:none;transition:border-color .2s}
input:focus{border-color:#1B2A4A}
.row{display:grid;grid-template-columns:1fr auto;gap:12px}
.row input:last-child{width:80px}
button{width:100%;margin-top:24px;padding:12px;background:#1B2A4A;color:#fff;border:none;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;transition:background .2s}
button:hover{background:#162240}
.error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:12px 16px;font-size:.85rem;margin-bottom:20px}
.log-item{margin-bottom:12px}
.log-item .cmd{font-size:.75rem;font-weight:600;color:#6b7280;font-family:monospace;margin-bottom:4px}
.log-item .out{background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:10px 14px;font-size:.78rem;font-family:monospace;white-space:pre-wrap;color:#374151;max-height:120px;overflow-y:auto}
.success{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px;text-align:center;margin-bottom:20px}
.success h2{color:#16a34a;font-size:1.25rem;margin-bottom:6px}
.success p{color:#15803d;font-size:.875rem}
.warn{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;font-size:.85rem;color:#92400e;margin-top:16px}
a.btn{display:block;text-align:center;margin-top:12px;padding:12px;background:#16a34a;color:#fff;border-radius:8px;font-weight:600;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <h1>ल Lekhya — Setup</h1>
    <p>One-click installer · Takes about 30 seconds</p>
  </div>
  <div class="body">

    <?php if ($step === 'welcome'): ?>
    <div class="step-bar"><span class="done"></span><span></span><span></span></div>
    <h2 style="font-size:1.1rem;color:#1B2A4A;margin-bottom:8px">Welcome to Lekhya ERP</h2>
    <p style="color:#6b7280;font-size:.875rem;margin-bottom:24px">This installer will set up your database, run migrations, and configure the app. You'll need your Hostinger MySQL credentials.</p>
    <p style="font-size:.8rem;color:#6b7280"><strong>Before you continue, make sure you have:</strong></p>
    <ul style="margin:10px 0 20px 20px;font-size:.85rem;color:#374151;line-height:1.8">
      <li>Created a MySQL database in Hostinger hPanel</li>
      <li>Noted the DB name, username, and password</li>
    </ul>
    <a href="?step=database" style="display:block;text-align:center;padding:12px;background:#1B2A4A;color:#fff;border-radius:8px;font-weight:600;text-decoration:none">Start Setup →</a>

    <?php elseif ($step === 'database'): ?>
    <div class="step-bar"><span class="done"></span><span class="done"></span><span></span></div>
    <h2 style="font-size:1.1rem;color:#1B2A4A;margin-bottom:20px">Database Configuration</h2>

    <?php if ($errors): ?>
      <div class="error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif ?>

    <form method="POST" action="?step=database">
      <label>Your Website URL</label>
      <input type="url" name="app_url" placeholder="https://yourdomain.com" value="<?= htmlspecialchars('https://' . ($_SERVER['HTTP_HOST'] ?? '')) ?>">

      <label>Database Host</label>
      <input type="text" name="db_host" value="127.0.0.1" required>

      <label>Database Port</label>
      <input type="text" name="db_port" value="3306">

      <label>Database Name</label>
      <input type="text" name="db_name" placeholder="e.g. u123456789_lekhya" required>

      <label>Database Username</label>
      <input type="text" name="db_user" placeholder="e.g. u123456789_lekhya" required>

      <label>Database Password</label>
      <input type="password" name="db_pass" placeholder="Your database password">

      <button type="submit">Connect & Continue →</button>
    </form>

    <?php elseif ($step === 'setup'): ?>
    <div class="step-bar"><span class="done"></span><span class="done"></span><span class="done"></span></div>

    <?php
    $hasError = false;
    foreach ($log as $item) {
        if (str_contains(strtolower($item['out']), 'error') || str_contains(strtolower($item['out']), 'exception')) {
            $hasError = true;
        }
    }
    ?>

    <?php if (!$hasError): ?>
    <div class="success">
      <h2>✓ Lekhya is Ready!</h2>
      <p>Database migrated, seeded, and app configured.</p>
    </div>
    <a class="btn" href="/">Open Lekhya ERP →</a>
    <div class="warn" style="margin-top:16px">
      <strong>Security:</strong> Delete <code>public/install.php</code> from your File Manager now. This file should not stay on the server.
    </div>
    <?php else: ?>
    <div class="error"><strong>Some steps had errors.</strong> Check the log below.</div>
    <a class="btn" style="background:#dc2626" href="/">Try Opening App →</a>
    <?php endif ?>

    <div style="margin-top:24px">
      <p style="font-size:.8rem;font-weight:600;color:#6b7280;margin-bottom:12px">Setup Log:</p>
      <?php foreach ($log as $item): ?>
      <div class="log-item">
        <div class="cmd">$ <?= htmlspecialchars($item['cmd']) ?></div>
        <div class="out"><?= htmlspecialchars($item['out'] ?: '(no output)') ?></div>
      </div>
      <?php endforeach ?>
    </div>

    <?php else: ?>
    <div class="error">Invalid access. <a href="?step=welcome">Start over</a></div>
    <?php endif ?>

  </div>
</div>
</body>
</html>
