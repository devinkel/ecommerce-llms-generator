FROM dunglas/frankenphp:latest-php8.3-bookworm
WORKDIR /app

# Composer + zip/git/unzip (como antes)
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer \
    && install-php-extensions zip \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

COPY . .

# EXPOSE a porta não-privilegiada 8080
EXPOSE 8080

# Inicia o FrankenPHP (Caddy+Swoole) —
# ele vai ler SERVER_NAME e abrir na porta certa
ENTRYPOINT ["frankenphp", "run"]
CMD []
