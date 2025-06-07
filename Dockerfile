# Dockerfile

# 1) Use a imagem oficial FrankenPHP (Caddy + Swoole + PHP 8.3)
FROM dunglas/frankenphp:latest-php8.3-bookworm

WORKDIR /app

# 2) Instala libcap2-bin (para setcap), zip, git e unzip
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcap2-bin zip git unzip \
    && rm -rf /var/lib/apt/lists/* \
    # PHP zip extension via pecl helper embutido
    && install-php-extensions zip

# 3) Instala o Composer
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# 4) Copia só composer.json/lock e instala dependências
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# 5) Remove todas as capabilities do binário frankenphp
#    (elimina CAP_NET_BIND_SERVICE que quebra no Render)
RUN setcap -r "$(which frankenphp)"

# 6) Copia todo o código da aplicação
COPY . .

# 7) Exponha uma porta não-privilegiada (>=1024) para evitar precisar de capabilities
EXPOSE 8080

# 8) Inicia FrankenPHP — ele lerá SERVER_NAME/PORT para saber onde escutar
ENTRYPOINT ["frankenphp", "run"]
CMD []
