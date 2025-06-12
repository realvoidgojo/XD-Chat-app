# XD Chat App - Deployment Guide

## Issues Fixed

### 1. X-Frame-Options Meta Tag Error
**Problem**: X-Frame-Options was being set via meta tag in `header.php`, which is invalid.
**Solution**: Removed X-Frame-Options from meta tags and ensured it's only set via HTTP headers in `.htaccess`.

### 2. Image URL Issues
**Problem**: ErrorDocument 404 was pointing to non-existent `/app/errors/404.php` path.
**Solution**: 
- Removed incorrect ErrorDocument paths
- Created `error.php` for proper error handling
- Fixed uploads directory permissions

### 3. Performance Issues
**Problem**: Duplicate security headers and inefficient configuration.
**Solution**:
- Moved security headers to `.htaccess` for better performance
- Optimized PHP configuration in Dockerfile
- Added OPcache settings for better performance
- Reduced redundant header setting in `init.php`

## Deployment Steps

### 1. Environment Setup
Ensure your Render environment has:
- PHP 8.2 or higher
- Apache with mod_rewrite, mod_headers, mod_deflate, mod_expires
- PostgreSQL database (configured in `config/database.php`)

### 2. File Permissions
The Dockerfile sets proper permissions:
- Application files: 755
- Uploads directory: 775
- .htaccess: 644

### 3. Configuration Files
- **Dockerfile**: Optimized for production with OPcache
- **.htaccess**: Security headers and performance optimizations
- **error.php**: Custom error handling
- **init.php**: Streamlined initialization

## Performance Optimizations

### 1. Security Headers
All security headers are now handled by `.htaccess`:
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Content-Security-Policy: Comprehensive policy
- Referrer-Policy: strict-origin-when-cross-origin

### 2. Caching
- Browser caching for static assets (CSS, JS, images)
- Compression enabled for text-based files
- OPcache enabled for PHP performance

### 3. Database Optimization
- Connection pooling (if supported by your database)
- Query optimization in User model
- Proper error handling

## Troubleshooting

### 1. Console Errors
If you see X-Frame-Options errors:
- Check that `.htaccess` is being processed
- Verify Apache mod_headers is enabled
- Clear browser cache

### 2. Image Loading Issues
If images don't load:
- Check uploads directory permissions (775)
- Verify image paths are correct
- Check browser console for 404 errors

### 3. Performance Issues
Monitor performance with:
```
https://your-app.onrender.com/performance.php?debug=1
```

### 4. Common Issues

#### Apache Configuration
Ensure these modules are enabled:
```bash
a2enmod rewrite headers deflate expires
```

#### File Permissions
```bash
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/uploads
chmod 644 /var/www/html/.htaccess
```

#### Database Connection
Check `config/database.php` for correct connection parameters.

## Monitoring

### 1. Performance Metrics
Use the performance monitoring script:
- Access: `/performance.php?debug=1`
- Monitors: Load time, memory usage, database queries

### 2. Error Logs
Check Apache error logs for issues:
- 404 errors for missing images
- PHP errors for application issues
- Performance warnings

### 3. Browser Console
Monitor for:
- X-Frame-Options warnings
- Network errors
- JavaScript errors

## Security Considerations

### 1. File Access
- Sensitive directories are protected
- PHP execution disabled in uploads
- Hidden files are blocked

### 2. Input Validation
- SQL injection protection
- XSS protection
- File upload restrictions

### 3. Session Security
- Secure session configuration
- CSRF protection
- Session regeneration

## Maintenance

### 1. Regular Updates
- Keep PHP and Apache updated
- Monitor security advisories
- Update dependencies

### 2. Backup Strategy
- Regular database backups
- File system backups
- Configuration backups

### 3. Monitoring
- Set up performance alerts
- Monitor error rates
- Track user experience metrics

## Support

For additional support:
1. Check the error logs
2. Use the performance monitoring script
3. Review browser console for client-side issues
4. Verify all configuration files are properly deployed 