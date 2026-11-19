#!/bin/sh
set -eu
/usr/sbin/nginx -t -c /app/config/nginx.conf
/usr/sbin/nginx -s reload -c /app/config/nginx.conf
