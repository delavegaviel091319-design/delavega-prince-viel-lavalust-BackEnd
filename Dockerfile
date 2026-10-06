ARG PHP_VERSION=8.5
 
FROM php:${PHP_VERSION}-apache
 
# Install PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql
 
# Enable Apache mod_rewrite
RUN a2enmod rewrite
 
# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
 
# Copy app files
COPY . /var/www/html/
 
# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
&& chmod -R 755 /var/www/html
 
# Point Apache document root to public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
 
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot ${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/sites-available/000-default.conf \
&& sed -i 's|<Directory /var/www/html>|<Directory ${APACHE_DOCUMENT_ROOT}>|g' /etc/apache2/apache2.conf
 
# Render tells the container which port to listen on through $PORT (default 10000).
# Apache is re-configured to that port when the container starts.
RUN printf '#!/bin/sh\nPORT="${PORT:-80}"\nsed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf\nsed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf\nexec apache2-foreground\n' > /usr/local/bin/start.sh \
&& chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]