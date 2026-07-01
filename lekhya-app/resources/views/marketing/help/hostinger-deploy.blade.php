@extends('layouts.marketing')
@section('title', 'Deploy Lekhya on Hostinger — Step by Step')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <nav class="text-sm text-gray-500 mb-8">
        <a href="{{ route('marketing.help') }}" class="hover:text-navy-600">Help</a> → <span class="text-gray-900">Deploy on Hostinger</span>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-4">Deploy Lekhya on Hostinger</h1>
    <p class="text-lg text-gray-600 mb-8">Complete step-by-step guide to host Lekhya on Hostinger VPS or Shared Hosting.</p>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-8">
        <h3 class="font-semibold text-blue-900 mb-2"><i class="fa fa-info-circle mr-2"></i>Recommended: Hostinger VPS</h3>
        <p class="text-sm text-blue-800">For Lekhya ERP, use <strong>Hostinger VPS KVM2 or higher</strong> (2 vCPU, 8GB RAM). This gives you PHP 8.4, MySQL 8.0, Redis, and root access needed for queue workers. Shared hosting works for basic use but can't run queue workers.</p>
    </div>

    <div class="space-y-10">

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Option A — Hostinger VPS (Recommended)</h2>

            @foreach([
                ['1', 'Get VPS + Set Up Server', 'bg-blue-600', [
                    '# Order Hostinger KVM VPS (Ubuntu 22.04)',
                    '# Connect via SSH',
                    'ssh root@your-vps-ip',
                    '',
                    '# Update system',
                    'apt update && apt upgrade -y',
                    '',
                    '# Install required packages',
                    'apt install -y nginx php8.4-fpm php8.4-mysql php8.4-redis',
                    'apt install -y php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip',
                    'apt install -y php8.4-gd php8.4-bcmath php8.4-intl',
                    'apt install -y mysql-server redis-server git unzip',
                    '',
                    '# Install Composer',
                    'curl -sS https://getcomposer.org/installer | php',
                    'mv composer.phar /usr/local/bin/composer',
                ]],
                ['2', 'Set Up MySQL Database', 'bg-green-600', [
                    'mysql -u root -p',
                    '',
                    '-- Inside MySQL:',
                    'CREATE DATABASE lekhya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
                    'CREATE USER "lekhya_user"@"localhost" IDENTIFIED BY "StrongPassword123!";',
                    'GRANT ALL PRIVILEGES ON lekhya.* TO "lekhya_user"@"localhost";',
                    'FLUSH PRIVILEGES;',
                    'EXIT;',
                ]],
                ['3', 'Clone & Configure Lekhya', 'bg-purple-600', [
                    'cd /var/www',
                    'git clone https://github.com/prabhassaas/lekhya.git',
                    'cd lekhya/lekhya-app',
                    '',
                    '# Install PHP dependencies',
                    'COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader',
                    '',
                    '# Set up environment',
                    'cp .env.example .env',
                    'php artisan key:generate',
                    '',
                    '# Edit .env with your settings:',
                    'nano .env',
                    '',
                    '# Key settings to change:',
                    'APP_ENV=production',
                    'APP_URL=https://yourdomain.com',
                    'DB_HOST=127.0.0.1',
                    'DB_DATABASE=lekhya',
                    'DB_USERNAME=lekhya_user',
                    'DB_PASSWORD=StrongPassword123!',
                    'CACHE_DRIVER=redis',
                    'SESSION_DRIVER=redis',
                    'QUEUE_CONNECTION=redis',
                ]],
                ['4', 'Run Migrations & Seed', 'bg-orange-600', [
                    'cd /var/www/lekhya/lekhya-app',
                    '',
                    '# Run database migrations',
                    'php artisan migrate --force',
                    '',
                    '# Seed GST HSN codes and plan data',
                    'php artisan db:seed --class=HsnSacSeeder --force',
                    'php artisan db:seed --class=PlanSeeder --force',
                    'php artisan db:seed --class=PermissionSeeder --force',
                    '',
                    '# Set up storage',
                    'php artisan storage:link',
                    'chown -R www-data:www-data storage bootstrap/cache',
                    'chmod -R 775 storage bootstrap/cache',
                    '',
                    '# Optimize for production',
                    'php artisan config:cache',
                    'php artisan route:cache',
                    'php artisan view:cache',
                ]],
                ['5', 'Configure Nginx', 'bg-teal-600', [
                    'nano /etc/nginx/sites-available/lekhya',
                    '',
                    '# Paste this config:',
                    'server {',
                    '    listen 80;',
                    '    server_name yourdomain.com www.yourdomain.com;',
                    '    root /var/www/lekhya/lekhya-app/public;',
                    '    index index.php;',
                    '',
                    '    location / {',
                    '        try_files $uri $uri/ /index.php?$query_string;',
                    '    }',
                    '',
                    '    location ~ \\.php$ {',
                    '        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;',
                    '        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;',
                    '        include fastcgi_params;',
                    '    }',
                    '}',
                    '',
                    '# Enable site',
                    'ln -s /etc/nginx/sites-available/lekhya /etc/nginx/sites-enabled/',
                    'nginx -t && systemctl reload nginx',
                ]],
                ['6', 'SSL (HTTPS) with Let\'s Encrypt', 'bg-yellow-600', [
                    'apt install -y certbot python3-certbot-nginx',
                    'certbot --nginx -d yourdomain.com -d www.yourdomain.com',
                    '',
                    '# Auto-renewal',
                    'crontab -e',
                    '# Add: 0 3 * * * certbot renew --quiet',
                ]],
                ['7', 'Set Up Queue Workers (for AI + Connector jobs)', 'bg-red-600', [
                    '# Create supervisor config for queue worker',
                    'apt install -y supervisor',
                    '',
                    'nano /etc/supervisor/conf.d/lekhya-queue.conf',
                    '',
                    '# Paste:',
                    '[program:lekhya-queue]',
                    'process_name=%(program_name)s_%(process_num)02d',
                    'command=php /var/www/lekhya/lekhya-app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600',
                    'directory=/var/www/lekhya/lekhya-app',
                    'user=www-data',
                    'numprocs=2',
                    'autostart=true',
                    'autorestart=true',
                    'stopwaitsecs=3600',
                    'stderr_logfile=/var/log/lekhya-queue.err.log',
                    'stdout_logfile=/var/log/lekhya-queue.out.log',
                    '',
                    '# Start',
                    'supervisorctl reread && supervisorctl update && supervisorctl start lekhya-queue:*',
                ]],
                ['8', 'Set Up Cron (for scheduled tasks)', 'bg-indigo-600', [
                    'crontab -e -u www-data',
                    '',
                    '# Add:',
                    '* * * * * cd /var/www/lekhya/lekhya-app && php artisan schedule:run >> /dev/null 2>&1',
                ]],
            ] as [$n, $title, $color, $commands])
            <div class="mb-8">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-8 h-8 rounded-full {{ $color }} text-white flex items-center justify-center font-bold">{{ $n }}</div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                </div>
                <div class="bg-gray-900 text-green-400 rounded-xl p-5 font-mono text-sm overflow-x-auto">
                    @foreach($commands as $cmd)
                        @if(str_starts_with($cmd, '#') || str_starts_with($cmd, '--'))
                        <p class="text-gray-500">{{ $cmd }}</p>
                        @elseif($cmd === '')
                        <p>&nbsp;</p>
                        @else
                        <p>{{ $cmd }}</p>
                        @endif
                    @endforeach
                </div>
            </div>
            @endforeach
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Option B — Hostinger Shared Hosting</h2>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 mb-4">
                <p class="text-sm text-yellow-900"><strong>Limitation:</strong> No queue workers. AI extraction and async jobs won't work. OK for basic accounting use.</p>
            </div>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                <li>Go to Hostinger → Hosting → hPanel → File Manager</li>
                <li>Upload lekhya-app/ contents to public_html/ (or a subdirectory)</li>
                <li>Point domain to public/ folder using .htaccess and custom document root</li>
                <li>Create MySQL database via hPanel → Databases</li>
                <li>Edit .env via File Manager</li>
                <li>Run migrations via hPanel → PHP → Run Script or SSH terminal (if available)</li>
                <li>Add cron job via hPanel → Advanced → Cron Jobs</li>
            </ol>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Post-Deployment Checklist</h2>
            <div class="space-y-2">
                @foreach([
                    'APP_ENV=production and APP_DEBUG=false in .env',
                    'HTTPS enabled with valid SSL certificate',
                    'Queue workers running (supervisor status)',
                    'Cron job added for scheduled tasks',
                    'Storage directory writable by www-data',
                    'Seed completed: HSN codes, permissions, plans',
                    'First admin user created via registration',
                    'Backup configured (daily MySQL dump + storage backup)',
                    'GST settings configured (GSTIN, GSP credentials or mock mode)',
                ] as $item)
                <div class="flex items-center space-x-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="w-5 h-5 border-2 border-gray-300 rounded"></div>
                    <span class="text-sm text-gray-700">{{ $item }}</span>
                </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
