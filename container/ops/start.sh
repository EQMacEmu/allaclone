#!/bin/sh
set -eu
umask 022

if [ ! -d /app/data/mysql/mysql ]; then
    echo 'MariaDB is not initialized. Run the documented init/setup.sh step first.' >&2
    exit 1
fi
install -d -m 0755 /app/run /app/logs /app/logs/nginx /app/acme /app/letsencrypt
install -d -m 0755 -o mysql -g mysql /app/run/mysql
install -d -m 0750 -o mysql -g mysql /app/logs/mysql
install -d -m 0755 -o www-data -g www-data /app/run/php /app/run/nginx /app/logs/php
install -d -m 0700 -o www-data -g www-data /app/data/php/sessions

/usr/sbin/nginx -t -c /app/config/nginx.conf
/usr/sbin/php-fpm8.4 --test --fpm-config /app/config/php-fpm.conf
exec /usr/bin/supervisord -c /app/config/supervisord.conf
