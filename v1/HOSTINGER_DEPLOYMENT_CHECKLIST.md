# HOSTINGER DEPLOYMENT CHECKLIST

## Pre-Deployment Preparation

### 1. Environment Setup
- [ ] Set `APP_ENV=production` in .env
- [ ] Set `APP_DEBUG=false` in .env  
- [ ] Update `APP_URL` to your Hostinger domain
- [ ] Generate new `APP_KEY` if needed: `php artisan key:generate`

### 2. Database Configuration
- [ ] Create MySQL database in Hostinger cPanel
- [ ] Update .env with Hostinger database credentials
- [ ] Export local database and import to Hostinger
- [ ] Run migrations: `php artisan migrate --force`

### 3. File Optimization
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `npm run production` to compile assets
- [ ] Clear all caches: `php artisan cache:clear`
- [ ] Optimize configuration: `php artisan config:cache`
- [ ] Optimize routes: `php artisan route:cache`

## Deployment Steps

### 1. File Upload to Hostinger
```bash
# Upload Laravel files to public_html/
# Structure should be:
# public_html/
#   ├── index.php (Laravel's public/index.php)
#   ├── .htaccess (optimized version)
#   ├── css/ (compiled assets)
#   ├── js/ (compiled assets)
#   ├── storage/ -> ../storage/app/public
#   └── ...other public files
#
# Laravel core files go one level up from public_html/
# /domains/yourdomain.com/
#   ├── public_html/ (Laravel public directory contents)
#   ├── app/
#   ├── config/
#   ├── storage/
#   ├── vendor/
#   ├── .env
#   └── ...other Laravel files
```

### 2. File Permissions (Critical)
- [ ] Set directories to 755: `find . -type d -exec chmod 755 {} \;`
- [ ] Set files to 644: `find . -type f -exec chmod 644 {} \;`
- [ ] Set storage writable: `chmod -R 775 storage/`
- [ ] Set bootstrap/cache writable: `chmod -R 775 bootstrap/cache/`

### 3. Hostinger-Specific Configuration
- [ ] Verify PHP version is 8.0+ in Hostinger cPanel
- [ ] Enable required PHP extensions (JSON, cURL, OpenSSL, PDO, Mbstring, Tokenizer, XML)
- [ ] Create storage symlink: `php artisan storage:link`

### 4. .htaccess Verification
- [ ] Confirm .htaccess is in public_html root
- [ ] Test HTTPS redirect works
- [ ] Verify Laravel routing works (no 404s)
- [ ] Check asset loading (CSS, JS, images)

## Post-Deployment Testing

### 1. Basic Functionality
- [ ] Homepage loads correctly
- [ ] User registration works
- [ ] User login works
- [ ] Password reset emails send
- [ ] File uploads work (within 32MB limit)

### 2. E-commerce Features
- [ ] Product categories display
- [ ] Product search works
- [ ] Shopping cart functionality
- [ ] Checkout process
- [ ] Order management
- [ ] Payment gateway integration

### 3. Admin Features
- [ ] Admin panel accessible
- [ ] Product management
- [ ] Order management
- [ ] User management
- [ ] Analytics/reports

### 4. Performance & Security
- [ ] Page load times < 3 seconds
- [ ] Images compress/load correctly
- [ ] HTTPS enforced
- [ ] Security headers present
- [ ] No exposed sensitive files

## Troubleshooting Common Issues

### 500 Internal Server Error
1. Check .env file exists and is properly configured
2. Verify file permissions (755/644)
3. Check storage directory permissions
4. Review error logs in storage/logs/laravel.log

### Assets Not Loading
1. Run `npm run production` to recompile assets
2. Clear Laravel caches: `php artisan cache:clear`
3. Check .htaccess file is correct
4. Verify asset paths in views

### Database Connection Error
1. Verify database credentials in .env
2. Check database exists in Hostinger cPanel
3. Test connection with basic PHP script
4. Ensure database user has proper permissions

### File Upload Issues
1. Check upload limits in .htaccess (32MB)
2. Verify storage symlink: `php artisan storage:link`
3. Check storage directory permissions (775)
4. Test with small files first

### Email Not Sending
1. Configure SMTP settings in .env
2. Use Hostinger's SMTP server or external service
3. Test with simple Mail::raw() command
4. Check mail logs for errors

## Optimization Tips

### Performance
- Enable OPcache in Hostinger cPanel
- Use Redis/Memcached if available
- Optimize images before upload
- Enable GZIP compression (already in .htaccess)

### Security
- Regular backups via Hostinger cPanel
- Keep Laravel framework updated
- Monitor security logs
- Use strong database passwords

### Maintenance
- Schedule regular cache clearing
- Monitor disk space usage
- Check error logs weekly
- Update dependencies monthly

## Support Resources

- **Hostinger Support**: Available 24/7 via chat
- **Laravel Documentation**: laravel.com/docs
- **Error Logs**: Check storage/logs/laravel.log
- **Server Logs**: Available in Hostinger cPanel

This checklist ensures a smooth deployment to Hostinger with optimized performance and security.