<!DOCTYPE html>
<html>
<head>
    <title>Bulk Upload Configuration Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .ok { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Bulk Upload Configuration Check</h1>
    
    <div class="section">
        <h2>PHP Configuration</h2>
        <?php
        $configs = [
            'upload_max_filesize' => ['required' => '50M', 'critical' => true],
            'post_max_size' => ['required' => '50M', 'critical' => true],
            'max_execution_time' => ['required' => '300', 'critical' => true],
            'max_input_time' => ['required' => '300', 'critical' => false],
            'memory_limit' => ['required' => '512M', 'critical' => true],
            'max_file_uploads' => ['required' => '100', 'critical' => false],
        ];
        
        foreach ($configs as $setting => $info) {
            $current = ini_get($setting);
            $required = $info['required'];
            $critical = $info['critical'];
            
            // Convert to bytes for comparison
            $currentBytes = convertToBytes($current);
            $requiredBytes = convertToBytes($required);
            
            $status = $currentBytes >= $requiredBytes ? 'ok' : ($critical ? 'error' : 'warning');
            $statusText = $currentBytes >= $requiredBytes ? 'OK' : 'NEEDS UPDATE';
            
            echo "<div class='$status'>$setting: $current (Required: $required) - $statusText</div>";
        }
        
        function convertToBytes($val) {
            $val = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $val = intval($val);
            switch($last) {
                case 'g': $val *= 1024;
                case 'm': $val *= 1024;
                case 'k': $val *= 1024;
            }
            return $val;
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Required Extensions</h2>
        <?php
        $extensions = ['zip', 'gd', 'fileinfo'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $status = $loaded ? 'ok' : 'error';
            $statusText = $loaded ? 'LOADED' : 'MISSING';
            echo "<div class='$status'>$ext: $statusText</div>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Directory Permissions</h2>
        <?php
        $directories = [
            'storage/app' => storage_path('app'),
            'storage/app/public' => storage_path('app/public'),
            'storage/logs' => storage_path('logs'),
        ];
        
        foreach ($directories as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists ? is_writable($path) : false;
            
            if (!$exists) {
                echo "<div class='error'>$name: Directory does not exist ($path)</div>";
            } elseif (!$writable) {
                echo "<div class='error'>$name: Not writable ($path)</div>";
            } else {
                echo "<div class='ok'>$name: OK ($path)</div>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Server Information</h2>
        <div>PHP Version: <?php echo phpversion(); ?></div>
        <div>Server Software: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
        <div>Operating System: <?php echo PHP_OS; ?></div>
        <div>Max Upload Size (calculated): <?php echo min(convertToBytes(ini_get('upload_max_filesize')), convertToBytes(ini_get('post_max_size'))) / 1024 / 1024; ?>MB</div>
    </div>
    
    <div class="section">
        <h2>Recommendations</h2>
        <ul>
            <li>If you see any RED errors above, those must be fixed for bulk upload to work</li>
            <li>Contact your hosting provider to increase PHP limits if needed</li>
            <li>For shared hosting, you may need to upgrade to a higher plan</li>
            <li>Consider processing images in smaller batches (20-30 images per ZIP)</li>
            <li>Optimize images before uploading (compress, resize to reasonable dimensions)</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Common 502 Error Causes</h2>
        <ul>
            <li><strong>Timeout:</strong> Processing takes too long (increase max_execution_time)</li>
            <li><strong>Memory:</strong> Not enough memory (increase memory_limit)</li>
            <li><strong>File Size:</strong> ZIP file too large (split into smaller files)</li>
            <li><strong>Server Overload:</strong> Too many concurrent requests</li>
            <li><strong>Web Server Limits:</strong> Nginx/Apache timeout settings</li>
        </ul>
    </div>
</body>
</html>