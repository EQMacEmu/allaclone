#!/bin/sh
set -eu
/usr/bin/supervisorctl -c /app/config/supervisord.conf status >/dev/null
/usr/bin/mariadb --defaults-file=/app/config/mariadb.cnf \
    --user=root --batch --skip-column-names --execute='SELECT 1' >/dev/null
# Require a successful PHP page; a public HTTPS redirect is not a health result.
status=$(/usr/bin/curl --fail --silent --max-time 3 --user-agent allaclone-health \
    --output /dev/null --write-out '%{http_code}' http://127.0.0.1:8081/)
[ "$status" = 200 ]
