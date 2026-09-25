FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends curl \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install mysqli mbstring \
 && a2enmod rewrite headers
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/skymart
COPY . /var/www/skymart/
RUN chown -R www-data:www-data /var/www/skymart
EXPOSE 80
HEALTHCHECK --interval=10s --timeout=3s --start-period=15s --retries=5 CMD curl -fsS http://localhost/health.php || exit 1
