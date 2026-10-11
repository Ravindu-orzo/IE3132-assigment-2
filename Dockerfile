FROM php:8.3-apache

# PDO MySQL driver (the app uses PDO with the mysql: DSN)
RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers

# Hardened php.ini (display_errors off, expose_php off)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Application code -> web root.
# attachments/ is NOT baked in: it is bind-mounted by docker-compose so
# you can add/replace challenge files without rebuilding the image.
COPY app/ /var/www/html/

# Apache's .htaccess in attachments/ needs AllowOverride (already "All"
# in the official image's docker-php.conf); keep downloads going
# through download.php only.
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
