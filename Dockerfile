FROM php:8.3-apache

RUN docker-php-ext-install mysqli  && a2enmod rewrite headers

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
HEALTHCHECK --interval=10s --timeout=3s --start-period=15s --retries=5 CMD curl -fsS http://localhost/health.php || exit 1
