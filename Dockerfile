# Build stage: Composer com dependências otimizadas
FROM composer:2 AS builder

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative


FROM php:8.3-fpm-alpine

# Instalar nginx e dependências
RUN apk add --no-cache nginx supervisor bash \
    libpng-dev libxml2-dev postgresql-dev oniguruma-dev \
    && docker-php-ext-install pdo_pgsql mbstring bcmath opcache

# Diretório de trabalho
WORKDIR /var/www
# Copia dependências
COPY --from=builder /app/vendor ./vendor

# Copia arquivos da aplicação
COPY . .

# Copia configs do nginx e script de inicialização
COPY nginx.conf /etc/nginx/nginx.conf
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Criar diretório de logs para o nginx
RUN mkdir -p /var/log/nginx /run/nginx

# Corrige permissões de escrita do Lumen
RUN chmod -R 777 storage

# Exportar porta (Render usa variável $PORT)
ENV PORT=8080
EXPOSE $PORT

CMD ["/start.sh"]
