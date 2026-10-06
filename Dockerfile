FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends curl libonig-dev libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install mysqli mbstring gd \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod rewrite headers
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/skymart
COPY . /var/www/skymart/
RUN mkdir -p /var/lib/skymart/uploads && chown -R www-data:www-data /var/www/skymart /var/lib/skymart
EXPOSE 80
HEALTHCHECK --interval=10s --timeout=3s --start-period=15s --retries=5 CMD curl -fsS http://localhost/health.php || exit 1
