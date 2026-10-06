FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libonig-dev unzip git \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install intl mbstring mysqli gd \
    && (a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true) \
    && a2enmod mpm_prefork rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && cp docker/apache.conf /etc/apache2/sites-available/000-default.conf \
    && chmod +x docker/start.sh
ENV CI_ENVIRONMENT=production
EXPOSE 80
CMD ["sh", "docker/start.sh"]
