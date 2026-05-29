FROM php:8.2-apache

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    a2enmod rewrite

# PHP config
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/php.ini && \
    echo "post_max_size = 10M" >> /usr/local/etc/php/php.ini

# Copy app
COPY . /var/www/html/

# Permissions for uploads
RUN mkdir -p /var/www/html/assets/uploads/products && \
    chown -R www-data:www-data /var/www/html/assets/uploads && \
    chmod -R 775 /var/www/html/assets/uploads

# Apache config to allow .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/app.conf && \
    a2enconf app

EXPOSE 80
