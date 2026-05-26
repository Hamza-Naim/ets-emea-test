#!/bin/sh
set -e
mkdir -p /var/www/api/var /var/www/api/vendor /var/www/api/config/jwt
chown -R www-data:www-data /var/www/api/var /var/www/api/vendor /var/www/api/config/jwt
chmod -R 775 /var/www/api/var /var/www/api/vendor
[ -f /var/www/api/config/jwt/private.pem ] && chmod 640 /var/www/api/config/jwt/private.pem
[ -f /var/www/api/config/jwt/public.pem ] && chmod 644 /var/www/api/config/jwt/public.pem
exec "$@"