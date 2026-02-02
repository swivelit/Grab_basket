<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Server Configuration Required</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#f8f9fa;}</style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-danger">Server configuration required</h3>
                        <p class="lead">The application cannot format numbers because the PHP <strong>intl</strong> extension is not available on this server.</p>

                        <p>This commonly causes pages (for example the admin dashboard) to return a 500 error. To fix this, enable or install the <code>intl</code> extension for PHP and restart the web server.</p>

                        <h5>Quick instructions</h5>
                        <ul>
                            <li>On Debian/Ubuntu (example for PHP 8.2): <code>sudo apt update && sudo apt install -y php8.2-intl && sudo systemctl restart php8.2-fpm nginx</code></li>
                            <li>On RHEL/Amazon Linux: install the matching <code>php-intl</code> package and restart php-fpm/httpd.</li>
                            <li>On Windows: enable <code>php_intl.dll</code> in your <code>php.ini</code> and restart your web server.</li>
                        </ul>

                        <p class="text-muted small">If you don't have server access, contact your hosting provider or DevOps and share this page.</p>

                        <a href="/" class="btn btn-secondary">Back to site</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>