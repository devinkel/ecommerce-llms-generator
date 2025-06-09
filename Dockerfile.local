# Build stage: Composer com dependências otimizadas
FROM composer:2 AS builder

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative

# Runtime: PHP-FPM com PHP 8.3
FROM php:8.3-fpm-alpine

# Instalar dependências e extensões
RUN apk add --no-cache \
    libpng libpng-dev \
    libxml2-dev \
    postgresql-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath opcache \
    && rm -rf /var/cache/apk/*

# Define diretório da aplicação
WORKDIR /var/www

# Copia dependências
COPY --from=builder /app/vendor ./vendor

# Copia o restante do código
COPY . .

# Configurações seguras para o ambiente
RUN chown -R www-data:www-data /var/www

# ⚠️ Removido o USER www-data para evitar erro com FPM rodando sem root
# ⚠️ Trocar para 'dynamic' por padrão, mais seguro em ambiente incerto
RUN { \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 10'; \
    echo 'pm.start_servers = 2'; \
    echo 'pm.min_spare_servers = 1'; \
    echo 'pm.max_spare_servers = 3'; \
    echo 'pm.max_requests = 500'; \
    } > /usr/local/etc/php-fpm.d/zz-docker.conf

# OPcache tuning para produção
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Corrige escuta do FPM para aceitar conexões externas (necessário no Docker)
RUN sed -i 's|^listen = 127.0.0.1:9000|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000
CMD ["php-fpm", "-F"]
