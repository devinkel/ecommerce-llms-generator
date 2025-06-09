#!/bin/sh

# Inicia o PHP-FPM
php-fpm -D

# Inicia o Nginx em foreground (Render exige isso)
nginx -g "daemon off;"
