FROM php:8.2-fpm-alpine

# Install nginx + ekstensi PHP
RUN apk add --no-cache nginx && \
    docker-php-ext-install mysqli pdo pdo_mysql

# PHP config
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /usr/local/etc/php/php.ini && \
    sed -i 's/post_max_size = 8M/post_max_size = 10M/' /usr/local/etc/php/php.ini

# Nginx config
RUN mkdir -p /run/nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy app
COPY . /var/www/html/

# Permissions untuk folder upload
RUN mkdir -p /var/www/html/assets/uploads/products && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/assets/uploads

# Entrypoint
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
