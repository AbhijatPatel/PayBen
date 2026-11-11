FROM php:8.3-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite & headers modules
RUN a2enmod rewrite headers

# Copy backend files to web root
COPY . /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]

