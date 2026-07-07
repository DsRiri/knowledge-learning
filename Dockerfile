FROM php:8.4-cli

WORKDIR /app

COPY . .

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install zip intl pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

# Supprimer .env pour forcer l'utilisation des variables Railway
RUN rm -f .env

EXPOSE $PORT

# Version corrigée - exec form avec shell pour les commandes multiples
ENTRYPOINT ["/bin/sh", "-c"]
CMD ["php bin/console doctrine:migrations:migrate --no-interaction && php -S 0.0.0.0:$PORT -t public"]