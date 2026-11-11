FROM php:8.3-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite & headers modules
RUN a2enmod rewrite headers

# Enable AllowOverride All so .htaccess works
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copy backend files to web root
COPY . /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Configure Apache port dynamically for Railway ($PORT) and run foreground
CMD sh -c 'sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf && sed -i "s/:80/:${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf && apache2-foreground'
