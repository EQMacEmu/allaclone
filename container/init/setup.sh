#!/bin/sh
# Explicit one-time setup, run inside the image before starting the stack.
set -eu
cd /app
umask 022

if [ -d data/mysql ] && [ -n "$(find data/mysql -mindepth 1 -print -quit)" ]; then
    echo 'Refusing to initialize a nonempty data/mysql directory.' >&2
    exit 1
fi
install -d -m 0755 data logs acme letsencrypt
install -d -m 0750 -o mysql -g mysql logs/mysql
install -d -m 0750 -o mysql -g mysql data/mysql
install -d -m 0755 -o mysql -g mysql /app/run/mysql
install -d -m 0700 tls
install -d -m 0755 www
if [ ! -e www/index.php ]; then
    install -m 0644 init/index.php www/index.php
fi

# Leave any existing certificates and QUIC key intact.
if [ ! -e tls/selfsigned.crt ] && [ ! -e tls/selfsigned.key ]; then
    openssl req -x509 -newkey rsa:2048 -sha256 -nodes -days 365 \
        -subj /CN=localhost \
        -addext 'subjectAltName=DNS:localhost,IP:127.0.0.1,IP:::1' \
        -keyout tls/selfsigned.key -out tls/selfsigned.crt
    chmod 0600 tls/selfsigned.key
fi
if [ ! -s tls/selfsigned.crt ] || [ ! -s tls/selfsigned.key ]; then
    echo 'Both tls/selfsigned.crt and tls/selfsigned.key must exist.' >&2
    exit 1
fi
if [ ! -e tls/quic-host.key ]; then
    (umask 077; openssl rand -out tls/quic-host.key 32)
fi

mariadb-install-db --defaults-file=/app/config/mariadb.cnf \
    --user=mysql --auth-root-authentication-method=socket --skip-test-db
echo 'Empty MariaDB instance and development TLS are ready. No application data imported.'
