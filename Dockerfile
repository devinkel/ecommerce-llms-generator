# Dockerfile

FROM dunglas/frankenphp:latest-php8.3-bookworm

WORKDIR /app

# 1) Instala o Composer globalmente
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# 2) Instala dependências de SO necessárias antes de compilar extensões
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    git unzip pkg-config libbrotli-dev \
    && rm -rf /var/lib/apt/lists/*

# 3) Usa install-php-extensions para zip e swoole
RUN install-php-extensions zip swoole

# 4) Copia arquivos de composer para cache de dependências
COPY composer.json composer.lock ./

# 5) Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# 6) Copia o restante da aplicação
COPY . .

EXPOSE 80

CMD ["frankenphp", "run"]
