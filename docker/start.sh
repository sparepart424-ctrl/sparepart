#!/bin/sh
# Jalankan PHP-FPM di background, lalu Nginx di foreground
php-fpm -D
nginx -g "daemon off;"
