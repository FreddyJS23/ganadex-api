FROM php:8.2-fpm-alpine

#alpine usa apk para instalar paquetes
RUN  apk add --no-cache linux-headers
# Configura zona horaria (opcional)
ENV TZ=America/Caracas
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime

#paquetes para php
RUN apk --no-cache upgrade && \
    apk --no-cache add bash git sudo openssh  libxml2-dev oniguruma-dev autoconf gcc g++ make npm freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev ssmtp

# PHP: Install extensiones para php
RUN pecl channel-update pecl.php.net
RUN pecl install pcov swoole
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install mbstring xml  pcntl gd zip sockets pdo  pdo_mysql bcmath soap
RUN docker-php-ext-enable mbstring xml gd  zip pcov pcntl sockets bcmath pdo  pdo_mysql soap swoole
RUN docker-php-ext-install pdo pdo_mysql sockets
RUN apk add icu-dev
RUN docker-php-ext-configure intl && docker-php-ext-install mysqli pdo pdo_mysql intl
#descargo el instalador de composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Seteo el directorio de trabajo
WORKDIR /var/www/app
#copio el codigo de la aplicacion
COPY . .

# copia el archivo composer.json y instala las dependencias
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

#  permisos para el usuario www-data
RUN mkdir -p /var/www/app/storage/framework/{cache,sessions,views} /var/www/app/storage/logs /var/www/app/bootstrap/cache && \
    chown -R www-data:www-data /var/www/app/storage /var/www/app/bootstrap/cache && \
    chmod -R 775 /var/www/app/storage /var/www/app/bootstrap/cache

#comando artisan para laravel produccion
RUN php artisan view:clear
RUN php artisan event:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Set the default command to run php-fpm
CMD ["php-fpm"]