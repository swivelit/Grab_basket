<?php
// PHP configuration check for bulk upload issues
echo "<h2>PHP Configuration for Bulk Upload</h2>";

echo "<h3>Upload Settings:</h3>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_input_time: " . ini_get('max_input_time') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

echo "<h3>Extensions:</h3>";
echo "ZipArchive: " . (class_exists('ZipArchive') ? 'Available' : 'NOT AVAILABLE') . "<br>";
echo "GD: " . (extension_loaded('gd') ? 'Available' : 'NOT AVAILABLE') . "<br>";

echo "<h3>Server Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "<br>";

echo "<h3>Temporary Directory:</h3>";
echo "sys_get_temp_dir(): " . sys_get_temp_dir() . "<br>";
echo "Writable: " . (is_writable(sys_get_temp_dir()) ? 'Yes' : 'No') . "<br>";

echo "<h3>Storage Directory:</h3>";
$storagePath = dirname(__DIR__) . '/storage/app';
echo "Storage path: " . $storagePath . "<br>";
echo "Exists: " . (is_dir($storagePath) ? 'Yes' : 'No') . "<br>";
echo "Writable: " . (is_writable($storagePath) ? 'Yes' : 'No') . "<br>";

echo "<h3>Recommendations:</h3>";
if (ini_get('upload_max_filesize') < '50M') {
    echo "⚠️ Increase upload_max_filesize to at least 50M<br>";
}
if (ini_get('post_max_size') < '50M') {
    echo "⚠️ Increase post_max_size to at least 50M<br>";
}
if (ini_get('max_execution_time') < 300) {
    echo "⚠️ Increase max_execution_time to at least 300 seconds<br>";
}
if (ini_get('memory_limit') < '256M') {
    echo "⚠️ Increase memory_limit to at least 256M<br>";
}
?>