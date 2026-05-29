FROM php:8.2-apache

# Disable semua MPM dulu, lalu aktifkan hanya prefork
RUN a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true && \
    a2enmod mpm_prefork && \
    docker-php-ext-install mysqli pdo pdo_mysql && \
    a2enmod rewrite

# PHP config
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /usr/local/etc/php/php.ini && \
    sed -i 's/post_max_size = 8M/post_max_size = 10M/' /usr/local/etc/php/php.ini

# Apache: izinkan .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy app
COPY . /var/www/html/

# Permissions untuk folder upload
RUN mkdir -p /var/www/html/assets/uploads/products && \
    chown -R www-data:www-data /var/www/html/assets/uploads && \
    chmod -R 775 /var/www/html/assets/uploads

EXPOSE 80
