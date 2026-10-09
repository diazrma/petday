# Laravel: assets (Vite) + dependências (Composer) + PHP/Apache. Banco SQLite em volume próprio.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

FROM php:8.3-apache
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl zip bcmath gd exif opcache pdo_sqlite \
 && a2enmod rewrite \
 && sed -ri 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf
WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database/docker \
 && chown -R www-data:www-data storage bootstrap/cache database \
 && chmod +x docker/start.sh
EXPOSE 80
CMD ["docker/start.sh"]
