<?php
/**
 * Lekhya Auto-Fixer
 * Upload to public_html/ → visit in browser → it fixes everything automatically.
 * Delete this file after it runs.
 */
set_time_limit(300);
ini_set('display_errors', '1');
error_reporting(E_ALL);

$webRoot = __DIR__;
$steps   = [];

// ── 1. Find lekhya-app directory ─────────────────────────────────────────
$appDir = null;
foreach ([$webRoot . '/lekhya-app', $webRoot . '/lekhya', $webRoot . '/Lekhya-main/lekhya-app'] as $c) {
    if (file_exists($c . '/artisan')) { $appDir = $c; break; }
}
if (!$appDir) {
    die('<div style="font-family:sans-serif;padding:40px;color:red">
    <h2>❌ Cannot find lekhya-app folder</h2>
    <p>Make sure you uploaded and extracted the ZIP in <code>public_html/</code> and the folder is called <code>lekhya-app</code>.</p>
    <p>Current location of fix.php: <code>' . $webRoot . '</code></p>
    </div>');
}

// ── 2. Write correct index.php ────────────────────────────────────────────
$indexContent = <<<'PHP'
<?php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
if (file_exists($maintenance = __DIR__.'/lekhya-app/storage/framework/maintenance.php')) {
    require $maintenance;
}
require __DIR__.'/lekhya-app/vendor/autoload.php';
$app = require_once __DIR__.'/lekhya-app/bootstrap/app.php';
$app->handleRequest(Request::capture());
PHP;

$indexPath = $webRoot . '/index.php';
if (file_put_contents($indexPath, $indexContent) !== false) {
    $steps[] = ['ok' => true,  'msg' => '✅ index.php fixed — now points correctly to lekhya-app/'];
} else {
    $steps[] = ['ok' => false, 'msg' => '❌ Could not write index.php — check folder permissions'];
}

// ── 3. Copy .htaccess from lekhya-app/public/ ─────────────────────────────
$htSrc  = $appDir . '/public/.htaccess';
$htDest = $webRoot . '/.htaccess';
if (file_exists($htSrc)) {
    if (copy($htSrc, $htDest)) {
        $steps[] = ['ok' => true,  'msg' => '✅ .htaccess copied — URL routing is now set up'];
    } else {
        $steps[] = ['ok' => false, 'msg' => '❌ Could not copy .htaccess — check permissions'];
    }
} else {
    $steps[] = ['ok' => false, 'msg' => '❌ .htaccess not found in lekhya-app/public/'];
}

// ── 4. Copy latest install.php from lekhya-app/public/ ────────────────────
$instSrc  = $appDir . '/public/install.php';
$instDest = $webRoot . '/install.php';
if (file_exists($instSrc)) {
    if (copy($instSrc, $instDest)) {
        $steps[] = ['ok' => true,  'msg' => '✅ install.php updated to latest version'];
    } else {
        $steps[] = ['ok' => false, 'msg' => '❌ Could not copy install.php'];
    }
} else {
    $steps[] = ['ok' => false, 'msg' => '⚠️ install.php not found in lekhya-app/public/ — skipped'];
}

// ── 5. Fix storage permissions ─────────────────────────────────────────────
$storageDirs = [$appDir.'/storage', $appDir.'/storage/app', $appDir.'/storage/app/public',
                $appDir.'/storage/framework', $appDir.'/storage/framework/cache',
                $appDir.'/storage/framework/sessions', $appDir.'/storage/framework/views',
                $appDir.'/storage/logs', $appDir.'/bootstrap/cache'];
$permOk = true;
foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    if (!@chmod($dir, 0775)) { $permOk = false; }
}
$steps[] = ['ok' => $permOk, 'msg' => $permOk ? '✅ Storage folder permissions set' : '⚠️ Could not set all permissions (may still work)'];

// ── 6. Check PHP version ───────────────────────────────────────────────────
$phpOk  = version_compare(PHP_VERSION, '8.1.0', '>=');
$steps[] = ['ok' => $phpOk, 'msg' => ($phpOk ? '✅' : '❌') . ' PHP version: ' . PHP_VERSION . ($phpOk ? ' (good)' : ' — need PHP 8.1 or higher')];

// ── 7. Check required extensions ──────────────────────────────────────────
$required = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'bcmath', 'fileinfo'];
$missing  = array_filter($required, fn($e) => !extension_loaded($e));
if (empty($missing)) {
    $steps[] = ['ok' => true,  'msg' => '✅ All required PHP extensions are available'];
} else {
    $steps[] = ['ok' => false, 'msg' => '❌ Missing PHP extensions: ' . implode(', ', $missing) . ' — enable in hPanel → PHP Configuration'];
}

// ── 8. Check vendor directory ──────────────────────────────────────────────
$vendorOk = file_exists($appDir . '/vendor/autoload.php');
$steps[]  = ['ok' => $vendorOk, 'msg' => $vendorOk ? '✅ Vendor (Composer) dependencies found' : '❌ vendor/ folder missing — did the full zip extract correctly?'];

$allOk    = !in_array(false, array_column($steps, 'ok'));
$criticals = array_filter($steps, fn($s) => !$s['ok']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lekhya Auto-Fixer</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f0f3f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);max-width:540px;width:100%;overflow:hidden}
.top{background:linear-gradient(135deg,#1B2A4A,#2e5a94);color:#fff;padding:24px 28px}
.top h1{font-size:1.25rem;font-weight:800}
.top p{color:#b3c4df;font-size:.82rem;margin-top:3px}
.body{padding:24px 28px}
.step{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:.875rem;line-height:1.5}
.step:last-child{border-bottom:none}
.ok{color:#15803d}.fail{color:#dc2626}
code{font-family:ui-monospace,monospace;font-size:.8em;background:#f0f3f8;padding:2px 6px;border-radius:4px;color:#1B2A4A}
.banner{border-radius:12px;padding:18px 20px;margin-bottom:20px;text-align:center}
.banner.green{background:#f0fdf4;border:1px solid #bbf7d0}
.banner.green h2{color:#15803d;font-size:1.1rem;margin-bottom:4px}
.banner.green p{color:#166534;font-size:.84rem}
.banner.amber{background:#fffbeb;border:1px solid #fde68a}
.banner.amber h2{color:#b45309;font-size:1.1rem;margin-bottom:4px}
.banner.amber p{color:#92400e;font-size:.84rem;line-height:1.55}
.btn{display:block;width:100%;margin-top:18px;padding:13px;background:#1B2A4A;color:#fff;border-radius:10px;font-weight:700;text-align:center;text-decoration:none;font-size:.95rem}
.btn:hover{background:#162240}
.info{background:#EEF1F8;border-radius:9px;padding:12px 14px;font-size:.82rem;color:#1B2A4A;margin-top:16px;line-height:1.6}
</style>
</head>
<body>
<div class="card">
  <div class="top">
    <h1>ल&nbsp; Lekhya Auto-Fixer</h1>
    <p>Checking and fixing your installation</p>
  </div>
  <div class="body">

    <?php if ($allOk): ?>
    <div class="banner green">
      <h2>✅ All checks passed!</h2>
      <p>Your files are configured correctly. Click below to run the installer.</p>
    </div>
    <?php else: ?>
    <div class="banner amber">
      <h2>⚠️ Some issues found</h2>
      <p>Fixed what I could automatically. Check the red items below — you may need to act on them before continuing.</p>
    </div>
    <?php endif ?>

    <div>
      <?php foreach ($steps as $s): ?>
      <div class="step <?= $s['ok'] ? 'ok' : 'fail' ?>">
        <?= htmlspecialchars($s['msg']) ?>
      </div>
      <?php endforeach ?>
    </div>

    <?php if ($vendorOk && $phpOk && empty(array_filter($missing ?? []))): ?>
    <a href="/install.php" class="btn">Continue to Installer →</a>
    <?php else: ?>
    <div class="info">
      <strong>Fix the red items above first, then refresh this page.</strong><br>
      If PHP version is wrong: hPanel → Websites → lekhya.prabhassaas.in → PHP Configuration → change to PHP 8.2<br>
      If vendor/ is missing: re-upload the ZIP and extract again — make sure the full zip was uploaded.
    </div>
    <?php endif ?>

    <div class="info" style="margin-top:12px">
      <strong>lekhya-app found at:</strong> <code><?= htmlspecialchars($appDir) ?></code><br>
      <strong>Web root:</strong> <code><?= htmlspecialchars($webRoot) ?></code>
    </div>

  </div>
</div>
</body>
</html>
