<?php
/**
 * Lekhya Auto-Fixer v2
 * Upload to public_html/ → visit in browser → fixes everything it can.
 * DELETE after use.
 */
set_time_limit(300);
ini_set('display_errors', '1');
error_reporting(E_ALL);

$webRoot = __DIR__;
$steps   = [];

// ── Find lekhya-app ──────────────────────────────────────────────────────
$appDir = null;
foreach ([$webRoot.'/lekhya-app', $webRoot.'/lekhya', $webRoot.'/Lekhya-main/lekhya-app'] as $c) {
    if (file_exists($c.'/artisan')) { $appDir = realpath($c); break; }
}
if (!$appDir) {
    die('<div style="font-family:sans-serif;padding:40px;color:red">
    <h2>❌ Cannot find lekhya-app folder</h2>
    <p>Make sure you extracted the ZIP inside <code>public_html/</code>.</p></div>');
}

// ── Fix 1: Write correct index.php ──────────────────────────────────────
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
$ok = file_put_contents($webRoot.'/index.php', $indexContent) !== false;
$steps[] = ['ok'=>$ok, 'msg'=>$ok ? '✅ index.php fixed' : '❌ Could not write index.php'];

// ── Fix 2: Write .htaccess directly (embeds the content — no file needed) ─
$htaccess = <<<'HT'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HT;
$ok = file_put_contents($webRoot.'/.htaccess', $htaccess) !== false;
$steps[] = ['ok'=>$ok, 'msg'=>$ok ? '✅ .htaccess created' : '❌ Could not write .htaccess'];

// Also write it inside lekhya-app/public/ in case it's missing there too
@file_put_contents($appDir.'/public/.htaccess', $htaccess);

// ── Fix 3: Write install.php ─────────────────────────────────────────────
$installSrc = $appDir.'/public/install.php';
$installDst = $webRoot.'/install.php';
if (file_exists($installSrc) && copy($installSrc, $installDst)) {
    $steps[] = ['ok'=>true, 'msg'=>'✅ install.php copied'];
} else {
    $steps[] = ['ok'=>true, 'msg'=>'⚠️ install.php not copied (may already be in place)'];
}

// ── Fix 4: Storage permissions ───────────────────────────────────────────
foreach ([
    $appDir.'/storage', $appDir.'/storage/app', $appDir.'/storage/app/public',
    $appDir.'/storage/framework', $appDir.'/storage/framework/cache',
    $appDir.'/storage/framework/sessions', $appDir.'/storage/framework/views',
    $appDir.'/storage/logs', $appDir.'/bootstrap/cache',
] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    @chmod($dir, 0775);
}
$steps[] = ['ok'=>true, 'msg'=>'✅ Storage permissions set'];

// ── Fix 5: Try to run composer install ───────────────────────────────────
$vendorOk = file_exists($appDir.'/vendor/autoload.php');
$composerRan = false;
$composerOut = '';

if (!$vendorOk) {
    // Try various PHP execution methods Hostinger may allow
    $cmd = 'cd ' . escapeshellarg($appDir) . ' && composer install --no-dev --optimize-autoloader 2>&1';
    foreach (['exec', 'shell_exec', 'system', 'passthru'] as $fn) {
        if (function_exists($fn) && !in_array($fn, array_map('trim', explode(',', ini_get('disable_functions'))))) {
            if ($fn === 'exec') {
                exec($cmd, $out, $code);
                $composerOut = implode("\n", $out);
                $composerRan = ($code === 0);
            } elseif ($fn === 'shell_exec') {
                $composerOut = shell_exec($cmd) ?? '';
                $composerRan = str_contains($composerOut, 'Generating optimized autoload files');
            }
            if ($composerRan || !empty($composerOut)) break;
        }
    }
    // Re-check after attempt
    $vendorOk = file_exists($appDir.'/vendor/autoload.php');
}

// ── Fix 6: PHP checks ────────────────────────────────────────────────────
$phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
$steps[] = ['ok'=>$phpOk, 'msg'=>($phpOk?'✅':'❌').' PHP '.PHP_VERSION.($phpOk?' — good':' — need 8.1+')];

$missing = array_filter(['pdo','pdo_mysql','mbstring','openssl','tokenizer','xml','ctype','bcmath','fileinfo'], fn($e)=>!extension_loaded($e));
$steps[] = ['ok'=>empty($missing), 'msg'=>empty($missing) ? '✅ All PHP extensions OK' : '❌ Missing: '.implode(', ',$missing)];

// ── Summary ──────────────────────────────────────────────────────────────
$sshPath = $appDir;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lekhya Auto-Fixer</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f0f3f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);max-width:560px;width:100%}
.top{background:linear-gradient(135deg,#1B2A4A,#2e5a94);color:#fff;padding:22px 26px;border-radius:20px 20px 0 0}
.top h1{font-size:1.2rem;font-weight:800}
.top p{color:#b3c4df;font-size:.82rem;margin-top:2px}
.body{padding:22px 26px}
.step{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:.875rem;line-height:1.5}
.step:last-child{border-bottom:none}
.steps{margin-bottom:20px}
code{font-family:ui-monospace,monospace;font-size:.79em;background:#eef1f8;padding:2px 6px;border-radius:4px;color:#1B2A4A}
.ssh-box{background:#0f172a;border-radius:10px;padding:16px;margin:14px 0;overflow-x:auto}
.ssh-box pre{color:#7dd3fc;font-family:ui-monospace,monospace;font-size:.82rem;white-space:pre-wrap;word-break:break-all}
.ssh-label{font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
.banner{border-radius:10px;padding:14px 16px;margin-bottom:18px;font-size:.875rem;line-height:1.6;border:1px solid}
.banner.ok{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}
.banner.warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.banner.info{background:#eff6ff;border-color:#bfdbfe;color:#1e40af}
.banner strong{font-weight:700}
.btn{display:block;width:100%;margin-top:4px;padding:13px;background:#1B2A4A;color:#fff;border-radius:10px;font-weight:700;text-align:center;text-decoration:none;font-size:.95rem}
.btn:hover{background:#162240}
</style>
</head>
<body>
<div class="card">
  <div class="top">
    <h1>ल&nbsp; Lekhya Auto-Fixer</h1>
    <p>Checking and fixing your installation</p>
  </div>
  <div class="body">

    <div class="steps">
      <?php foreach ($steps as $s): ?>
      <div class="step"><?= htmlspecialchars($s['msg']) ?></div>
      <?php endforeach ?>
    </div>

    <?php if ($vendorOk): ?>

      <div class="banner ok">
        <strong>✅ All fixed!</strong> Click below to run the installer and enter your database password.
      </div>
      <a href="/install.php" class="btn">Go to Installer →</a>

    <?php else: ?>

      <div class="banner warn">
        <strong>⚠️ One manual step needed: run Composer</strong><br>
        The <code>vendor/</code> folder is missing. GitHub doesn't include it in downloads. You need to run one command via Hostinger's SSH terminal.
      </div>

      <div class="banner info">
        <strong>How to open the SSH terminal:</strong><br>
        Hostinger hPanel → <strong>Advanced</strong> → <strong>SSH Access</strong> → Enable it → click <strong>Open Terminal</strong> (or use any SSH app)
      </div>

      <p style="font-size:.82rem;font-weight:700;color:#374151;margin-bottom:6px">Paste this command in the terminal:</p>
      <div class="ssh-box">
        <pre>cd <?= htmlspecialchars($sshPath) ?> && composer install --no-dev --optimize-autoloader</pre>
      </div>

      <p style="font-size:.82rem;color:#6b7280;margin-bottom:14px">After it finishes (takes ~1 minute), <strong>refresh this page</strong> — the fixer will re-check and give you the green button.</p>

      <?php if ($composerOut): ?>
      <details style="margin-bottom:14px">
        <summary style="font-size:.8rem;cursor:pointer;color:#6b7280">Composer output (click to expand)</summary>
        <div class="ssh-box" style="margin-top:8px"><pre style="color:#94a3b8;font-size:.75rem"><?= htmlspecialchars($composerOut) ?></pre></div>
      </details>
      <?php endif ?>

    <?php endif ?>

    <p style="font-size:.75rem;color:#9ca3af;margin-top:16px">
      App folder: <code><?= htmlspecialchars($appDir) ?></code>
    </p>
  </div>
</div>
</body>
</html>
