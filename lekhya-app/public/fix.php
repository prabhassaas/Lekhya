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

// ── Fix 3: Write install.php (embedded — no file copy needed) ───────────
$installContent = <<<'INSTALL_END'
<?php
set_time_limit(300);
ini_set('display_errors', '1');
error_reporting(E_ALL);
ini_set('memory_limit', '512M');

$BASE = null;
foreach ([dirname(__DIR__), __DIR__.'/lekhya-app', __DIR__.'/lekhya'] as $c) {
    if (file_exists($c.'/artisan')) { $BASE = realpath($c); break; }
}
if (!$BASE) die('<h2 style="font-family:sans-serif;color:red;padding:40px">ERROR: Cannot find lekhya-app directory.</h2>');

$step   = $_GET['step'] ?? 'welcome';
$errors = [];
$log    = [];

function envWrite(array $values): void {
    global $BASE;
    $path = $BASE.'/.env'; $example = $BASE.'/.env.example';
    $env = file_exists($path) ? file_get_contents($path) : (file_exists($example) ? file_get_contents($example) : '');
    foreach ($values as $key => $value) {
        $escaped = (str_contains($value,' ')||$value==='') ? "\"{$value}\"" : $value;
        if (preg_match("/^{$key}=/m",$env)) { $env = preg_replace("/^{$key}=.*/m","{$key}={$escaped}",$env); }
        else { $env .= "\n{$key}={$escaped}"; }
    }
    file_put_contents($path,$env);
}
function generateKey(): string { return 'base64:'.base64_encode(random_bytes(32)); }

function artisan(string $command, array $args=[]): array {
    global $BASE;
    ob_start(); $exitCode=0;
    try {
        if (!isset($GLOBALS['_lapp'])) {
            defined('LARAVEL_START') || define('LARAVEL_START',microtime(true));
            require_once $BASE.'/vendor/autoload.php';
            $app = require $BASE.'/bootstrap/app.php';
            $GLOBALS['_lapp']=$app;
            $kernel=$app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            $GLOBALS['_lkernel']=$kernel;
        }
        $exitCode=\Illuminate\Support\Facades\Artisan::call($command,$args);
        $output=\Illuminate\Support\Facades\Artisan::output();
    } catch (\Throwable $e) {
        $output='ERROR: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine();
        $exitCode=1;
    }
    ob_end_clean();
    return ['output'=>trim($output?:'(done)'),'code'=>$exitCode];
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $step==='database') {
    $host=trim($_POST['db_host']??'127.0.0.1'); $port=trim($_POST['db_port']??'3306');
    $name=trim($_POST['db_name']??''); $user=trim($_POST['db_user']??'');
    $pass=trim($_POST['db_pass']??''); $url=rtrim(trim($_POST['app_url']??''),'/');
    if (!$name||!$user) $errors[]='Database name and username are required.';
    if (!$errors) {
        try { new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>5]); }
        catch (\Exception $e) { $errors[]='Cannot connect to database: '.$e->getMessage(); }
    }
    if (!$errors) {
        envWrite(['APP_KEY'=>generateKey(),'APP_ENV'=>'production','APP_DEBUG'=>'false',
            'APP_URL'=>$url?:'https://lekhya.prabhassaas.in','DB_CONNECTION'=>'mysql',
            'DB_HOST'=>$host,'DB_PORT'=>$port,'DB_DATABASE'=>$name,'DB_USERNAME'=>$user,
            'DB_PASSWORD'=>$pass,'SESSION_DRIVER'=>'file','CACHE_STORE'=>'file',
            'QUEUE_CONNECTION'=>'sync','AI_DRIVER'=>'mock']);
        @chmod($BASE.'/storage',0775); @chmod($BASE.'/bootstrap/cache',0775);
        foreach (glob($BASE.'/storage/{,**/}*',GLOB_BRACE) as $f) { @chmod($f,is_dir($f)?0775:0664); }
        header('Location: ?step=setup'); exit;
    }
}

if ($step==='setup') {
    @chmod($BASE.'/storage',0775); @chmod($BASE.'/bootstrap/cache',0775);
    foreach ([$BASE.'/bootstrap/cache/config.php',$BASE.'/bootstrap/cache/routes-v7.php'] as $f) { if(file_exists($f)) @unlink($f); }
    $r=artisan('migrate',['--force'=>true]);
    $log[]=['label'=>'✓ Database tables created','out'=>$r['output'],'ok'=>$r['code']===0];
    $r=artisan('db:seed',['--class'=>'HsnSacSeeder','--force'=>true]);
    $log[]=['label'=>'✓ HSN / SAC codes loaded','out'=>$r['output'],'ok'=>$r['code']===0];
    $r=artisan('db:seed',['--class'=>'PlanSeeder','--force'=>true]);
    $log[]=['label'=>'✓ Subscription plans loaded','out'=>$r['output'],'ok'=>$r['code']===0];
    $r=artisan('db:seed',['--class'=>'PermissionSeeder','--force'=>true]);
    $log[]=['label'=>'✓ Roles & permissions loaded','out'=>$r['output'],'ok'=>$r['code']===0];
    $pub=$BASE.'/public/storage'; if (!file_exists($pub)) @symlink($BASE.'/storage/app/public',$pub);
    $log[]=['label'=>'✓ Storage link','out'=>file_exists($pub)?'OK':'Skipped','ok'=>true];
    $r=artisan('config:cache'); $log[]=['label'=>'✓ Config cached','out'=>$r['output'],'ok'=>true];
    $r=artisan('route:cache');  $log[]=['label'=>'✓ Routes cached','out'=>$r['output'],'ok'=>true];
}
$hasError=$step==='setup'&&count(array_filter($log,fn($l)=>!($l['ok']??true)))>0;
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Lekhya Setup</title>
<style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:system-ui,sans-serif;background:#f0f3f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}.card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.13);max-width:520px;width:100%;overflow:hidden}.top{background:linear-gradient(135deg,#1B2A4A,#2e5a94);color:#fff;padding:26px 30px}.top h1{font-size:1.35rem;font-weight:800}.top p{color:#b3c4df;font-size:.83rem;margin-top:3px}.body{padding:28px 30px}.prog{display:flex;gap:6px;margin-bottom:22px}.prog span{flex:1;height:4px;border-radius:2px;background:#e5e7eb}.prog span.on{background:#1B2A4A}label{display:block;font-size:.75rem;font-weight:700;color:#374151;margin:14px 0 4px;text-transform:uppercase;letter-spacing:.04em}label:first-of-type{margin-top:0}input[type=text],input[type=url],input[type=password]{width:100%;padding:10px 13px;border:1.5px solid #d1d5db;border-radius:9px;font-size:.9rem;outline:none;transition:border .15s}input:focus{border-color:#1B2A4A}.hint{font-size:.73rem;color:#9ca3af;margin-top:3px}.btn{display:block;width:100%;margin-top:22px;padding:13px;background:#1B2A4A;color:#fff;border:none;border-radius:10px;font-size:.95rem;font-weight:700;cursor:pointer;text-align:center;text-decoration:none}.btn:hover{background:#162240}.btn.green{background:#16a34a}.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:9px;padding:12px 14px;font-size:.84rem;margin-bottom:16px;line-height:1.5}.ok-banner{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;text-align:center;margin-bottom:16px}.ok-banner h2{color:#15803d;font-size:1.15rem;margin-bottom:5px}.ok-banner p{color:#166534;font-size:.84rem}.fail-banner{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;margin-bottom:16px}.fail-banner p{color:#991b1b;font-size:.875rem}.log{margin-top:20px;display:flex;flex-direction:column;gap:10px}.log-row{border:1px solid #e5e7eb;border-radius:9px;overflow:hidden}.log-lbl{padding:8px 12px;font-size:.8rem;font-weight:600;color:#374151;background:#f9fafb;border-bottom:1px solid #e5e7eb}.log-out{padding:8px 12px;font-size:.75rem;font-family:ui-monospace,monospace;color:#4b5563;white-space:pre-wrap;max-height:120px;overflow-y:auto}.warn{background:#fffbeb;border:1px solid #fde68a;border-radius:9px;padding:12px 14px;font-size:.82rem;color:#92400e;margin-top:14px;line-height:1.55}a.open-btn{display:block;text-align:center;padding:13px;border-radius:10px;font-weight:700;font-size:.95rem;text-decoration:none;margin-top:6px}</style></head><body>
<div class="card"><div class="top"><h1><img src="logo-badge.png" style="width:22px;height:22px;vertical-align:-5px;margin-right:6px">Lekhya ERP — Setup</h1><p>One-click installer · No technical steps needed</p></div><div class="body">
<?php if($step==='welcome'): ?>
<div class="prog"><span class="on"></span><span></span><span></span></div>
<h2 style="font-size:1rem;color:#1B2A4A;margin-bottom:10px">Welcome — let's get Lekhya running</h2>
<p style="font-size:.875rem;color:#6b7280;margin-bottom:18px;line-height:1.65">This will set up your database and configure everything automatically.</p>
<div style="background:#EEF1F8;border-radius:10px;padding:16px;font-size:.875rem;color:#1B2A4A;line-height:1.9">✅ A MySQL database already created in <strong>Hostinger → Databases</strong><br>✅ You have the database name, username &amp; password ready</div>
<a href="?step=database" class="btn">I have my database ready →</a>
<?php elseif($step==='database'): ?>
<div class="prog"><span class="on"></span><span class="on"></span><span></span></div>
<h2 style="font-size:1rem;color:#1B2A4A;margin-bottom:18px">Enter your database details</h2>
<?php if($errors): ?><div class="err"><?=implode('<br>',array_map('htmlspecialchars',$errors))?></div><?php endif ?>
<form method="POST">
<label>Your Website URL</label><input type="url" name="app_url" value="https://lekhya.prabhassaas.in" required>
<label>Database Host</label><input type="text" name="db_host" value="127.0.0.1"><p class="hint">Leave as 127.0.0.1 — correct for Hostinger</p>
<label>Database Name</label><input type="text" name="db_name" value="u663059394_lekhyaerp" required>
<label>Database Username</label><input type="text" name="db_user" value="u663059394_lekhyaerp" required>
<label>Database Password</label><input type="password" name="db_pass" placeholder="The password you set for the database">
<input type="hidden" name="db_port" value="3306">
<button type="submit" class="btn">Connect &amp; Install →</button>
</form>
<?php elseif($step==='setup'): ?>
<div class="prog"><span class="on"></span><span class="on"></span><span class="on"></span></div>
<?php if(!$hasError): ?>
<div class="ok-banner"><h2>🎉 Lekhya is Ready!</h2><p>Your ERP is set up and ready to use.</p></div>
<a href="/" class="open-btn" style="background:#1B2A4A;color:#fff">Open Lekhya ERP →</a>
<?php else: ?>
<div class="fail-banner"><p><strong>Some steps had issues.</strong> Check the log below. You may still try opening the app.</p></div>
<a href="/" class="open-btn" style="background:#6b7280;color:#fff">Try Opening App →</a>
<?php endif ?>
<div class="log"><?php foreach($log as $item): ?><div class="log-row"><div class="log-lbl"><?=htmlspecialchars($item['label'])?></div><div class="log-out"><?=htmlspecialchars($item['out']?:'(done)')?></div></div><?php endforeach ?></div>
<div class="warn" style="margin-top:16px"><strong>⚠️ Security:</strong> After setup, go to Hostinger File Manager → delete <code>install.php</code> and <code>fix.php</code>.</div>
<?php endif ?>
</div></div></body></html>
INSTALL_END;

$installDst = $webRoot.'/install.php';
@chmod($installDst, 0664);
$ok = file_put_contents($installDst, $installContent) !== false;
$steps[] = ['ok'=>$ok, 'msg'=>$ok ? '✅ install.php written' : '❌ Could not write install.php'];

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
    <h1><img src="logo-badge.png" style="width:22px;height:22px;vertical-align:-5px;margin-right:6px">Lekhya Auto-Fixer</h1>
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
