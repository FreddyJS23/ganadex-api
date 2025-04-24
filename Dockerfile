# Usa una imagen base específica para PHP
FROM php:8.2-fpm-alpine

# Instalar encabezados de Linux
RUN apk add --no-cache linux-headers

# Configura la zona horaria
ENV TZ=America/Caracas
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Instalar paquetes necesarios para PHP
RUN apk --no-cache upgrade && \
    apk --no-cache add bash git sudo openssh libxml2-dev oniguruma-dev autoconf gcc g++ make npm \
    freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev ssmtp icu-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install mbstring xml pcntl gd zip sockets pdo pdo_mysql bcmath soap intl && \
    docker-php-ext-enable mbstring xml gd zip pcntl sockets bcmath pdo pdo_mysql soap

# Instalar extensiones adicionales de PHP
RUN pecl channel-update pecl.php.net && \
    pecl install pcov swoole && \
    docker-php-ext-enable pcov swoole

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Seteo el directorio de trabajo
WORKDIR /var/www/app

# Copiar el código de la aplicación
COPY . .

# Copia composer.json y composer.lock, e instala dependencias
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos para el usuario www-data
RUN mkdir -p /var/www/app/storage/framework/{cache,sessions,views} /var/www/app/storage/logs /var/www/app/bootstrap/cache && \
    chown -R www-data:www-data /var/www/app/storage /var/www/app/bootstrap/cache && \
    chmod -R 775 /var/www/app/storage /var/www/app/bootstrap/cache

# Comandos Artisan para Laravel en producción
RUN php artisan view:clear && \
    php artisan event:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Comando por defecto
CMD ["php-fpm"]
