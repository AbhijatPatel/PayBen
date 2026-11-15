FROM php:8.3-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite & headers modules
RUN a2enmod rewrite headers

# Enable AllowOverride All so .htaccess works
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copy backend files to web root
COPY . /var/www/html/

# Make entrypoint script executable
RUN chmod +x /var/www/html/docker-entrypoint.sh

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
