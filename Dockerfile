FROM php:8.4-cli-alpine AS runtime

RUN apk add --no-cache \
        bash curl ffmpeg git icu-dev libzip-dev linux-headers postgresql-dev sqlite-dev unzip \
    && docker-php-ext-install bcmath intl pcntl pdo_pgsql pdo_sqlite zip \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts

COPY . .
RUN composer dump-autoload --optimize --no-interaction \
    && mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chmod -R ug+rwX storage bootstrap/cache \
    && chmod +x artisan

EXPOSE 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
