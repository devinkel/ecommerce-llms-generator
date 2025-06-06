# 1. Use a imagem base do FrankenPHP com PHP 8.3 (Debian Bookworm)
FROM dunglas/frankenphp:latest-php8.3-bookworm AS base

# 2. Defina a variável de ambiente para escutar na porta 80 (HTTP)
ENV SERVER_NAME=:80

# 3. Substitua o php.ini pelo arquivo de produção para otimizar performance
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 4. Instale dependências do sistema e extensões PHP para PostgreSQL
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    ; \
    rm -rf /var/lib/apt/lists/*

# 5. Configure e instale as extensões PHP necessárias:
#    - pdo_pgsql e pgsql para PostgreSQL
#    - zip para descompactação/compactação
RUN set -eux; \
    docker-php-ext-configure zip; \
    docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip

# 6. Defina o diretório de trabalho como /app e copie todo o código-fonte da aplicação
WORKDIR /var/www
COPY . /var/www

# 7. Ajuste permissões de storage e cache do Lumen para evitar problemas de escrita
RUN chown -R www-data:www-data /var/www


# 8. Instale o Composer globalmente
RUN set -eux; \
    # Baixa o instalador do Composer
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php; \
    # Verifica criptograficamente (opcional, mas recomendado, pegue a última HASH em https://getcomposer.org/download/ )
    EXPECTED_SIG=$(curl -sS https://composer.github.io/installer.sig); \
    ACTUAL_SIG=$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');"); \
    if [ "$EXPECTED_SIG" != "$ACTUAL_SIG" ]; then \
    echo 'ERROR: Invalid installer signature'; exit 1; \
    fi; \
    # Instalação global no /usr/local/bin
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer; \
    rm /tmp/composer-setup.php

# 9. Instale dependências PHP via Composer (já disponível na imagem FrankenPHP)
RUN composer install --no-dev --optimize-autoloader --working-dir=/var/www

# 10. Exponha a porta 80 para o Nginx se comunicar por rede interna
EXPOSE 80

# 11. Comando padrão para iniciar o servidor FrankenPHP apontando para /app/public
CMD ["frankenphp", "php-server", "-r", "/var/www/public"]
