FROM php:8.3-cli-alpine

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html
COPY . /var/www/html/

ENV PORT=80
EXPOSE 80

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-80} -t /var/www/html /var/www/html/router.php"]
