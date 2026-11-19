FROM scratch
ADD rootfs.tar /

ARG SNAPSHOT
LABEL org.takproject.allaclone.debian-snapshot="${SNAPSHOT}"
ENV LANG=C.UTF-8 TZ=Etc/UTC

# Prevent Debian package installation from starting services during the build.
RUN printf '#!/bin/sh\nexit 101\n' > /usr/sbin/policy-rc.d && \
    chmod 0755 /usr/sbin/policy-rc.d

# APT sources and signature keys come from the snapshot bootstrap.
# Clear package-generated MariaDB data; setup will initialize persistent storage.
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        nginx \
        mariadb-server \
        mariadb-client \
        php8.4-cli \
        php8.4-fpm \
        php8.4-common \
        php8.4-opcache \
        php8.4-mysql \
        php8.4-gd \
        supervisor \
        cron \
        certbot \
        logrotate \
        openssl \
        ca-certificates \
        curl \
        unzip \
        util-linux && \
    apt-get clean && \
    find /var/lib/apt/lists -type f -delete && \
    find /var/lib/mysql -mindepth 1 -delete

# Native configuration is supplied by the /app bind mount.
RUN rm -f /etc/cron.d/certbot /etc/cron.daily/logrotate && \
    ln -s /app/config/cron /etc/cron.d/allaclone && \
    mkdir -p /etc/letsencrypt && \
    ln -sf /app/config/certbot.ini /etc/letsencrypt/cli.ini && \
    ln -s /app/config/php.ini /etc/php/8.4/cli/conf.d/99-allaclone.ini && \
    ln -s /app/config/php.ini /etc/php/8.4/fpm/conf.d/99-allaclone.ini

WORKDIR /app
STOPSIGNAL SIGTERM
CMD ["/bin/sh", "/app/ops/start.sh"]
