# Allaclone container

A Debian 13 image running nginx, PHP 8.4, MariaDB and cron under Supervisor.
The default configuration serves a local development site over HTTPS.

## Local setup

Use a Debian 13 amd64 host. Run these commands as root:

```sh
apt-get update
apt-get install -y docker.io docker-cli docker-compose docker-buildx mmdebstrap \
    debian-archive-keyring ca-certificates git curl rsync
systemctl enable --now docker

git clone https://github.com/EQMacEmu/allaclone.git
cd allaclone/container
```

Use your fork's URL when working from a fork. Keep this directory inside the
application checkout. Run the remaining commands from `container/`.

Build the image, initialize its empty storage and start the services:

```sh
./build.sh
docker compose run --rm --no-deps allaclone /bin/sh /app/init/setup.sh
docker compose up -d --wait
```

Initialization creates MariaDB's system tables, a self-signed certificate and a
temporary setup page. Run it only for a fresh installation.

Install the application and data:

```sh
./maintenance setup
./maintenance update-database
./maintenance update-allaclone
./maintenance update-quests
```

`setup` creates `config/application.php` from the example, generates a random
database password and creates the application database/user. Rerunning it preserves
existing settings and credentials. The other commands import the TAKP database,
deploy this checkout's PHP files/maps, and download/import the Lua quests.

After the quest import succeeds, enable these settings in
`config/application.php`:

```php
$AllowQuestsNPC = true;
$ItemQuestSource = true;
$AllowEpics = true;
```

Keep `$AlwaysBuildQuest = false;` and apply the settings:

```sh
docker compose exec allaclone supervisorctl -c /app/config/supervisord.conf restart php-fpm
```

Open <https://localhost:8443/> and accept the self-signed certificate warning.
<http://localhost:8080/> redirects there. Ports are bound to IPv4 localhost;
HTTP/3 uses UDP 8443. If Docker runs on another machine, forward TCP ports 8080
and 8443 through SSH to browse it locally.

Check service status with:

```sh
docker compose ps
docker compose exec allaclone supervisorctl -c /app/config/supervisord.conf status
```

## Files and directories

Compose mounts `container/` at `/app` and the surrounding application checkout
read-only at `/source`. Settings are ordinary editable files.

| Path | Purpose |
| --- | --- |
| `build.sh`, `snapshot`, `Dockerfile` | Build one image from signed Debian snapshot packages |
| `compose.yaml` | Container, mounts, ports and health check |
| `maintenance` | Application setup and update commands |
| `config/application.example.php` | Default application settings |
| `config/application.php` | Private application settings and generated credentials |
| `config/nginx*.conf` | HTTP redirect, HTTPS/HTTP/3, PHP/static routing and health listener |
| `config/php.ini`, `config/php-fpm.conf` | PHP settings and worker pool |
| `config/mariadb.cnf` | Database settings and local socket |
| `config/supervisord.conf` | Service startup, shutdown and restart settings |
| `config/cron`, `config/logrotate`, `config/certbot.ini` | Log rotation and certificate renewal |
| `init/` | Initial database/TLS setup and the application's config loader |
| `ops/` | Startup, health, reload and maintenance helpers |
| `source/quests/` | Lua quest checkout |
| `www/` | Generated web files; edit the application checkout instead |
| `data/`, `logs/` | Persistent databases, update backups/reports and service logs |
| `tls/`, `letsencrypt/`, `acme/` | Development keys, managed certificates and ACME challenges |

Runtime files and `config/application.php` are ignored by Git. Replacing or removing
the container preserves them. nginx and PHP workers cannot write deployed PHP files.
Keep `config/cron` owned by root with mode `0644`.

## Editing and updating

Edit application files in the surrounding checkout, then deploy them:

```sh
./maintenance update-allaclone
```

This exports tracked working files, including your edits. Use `git add` for new files
before deploying. Dotfiles, `container/` and CLI tools are excluded from the web
export. To get upstream changes first, run `git -C .. pull --ff-only`.

| Command | Effect |
| --- | --- |
| `./maintenance update-database [ZIP_URL]` | Replace reference tables with a downloaded TAKP snapshot |
| `./maintenance update-allaclone` | Replace web files and bundled maps from this checkout |
| `./maintenance update-quests` | Fetch quest sources and rebuild generated quest tables |
| `./maintenance update-quests --local` | Import the existing Lua checkout without fetching |

The default database URL is shown by `./maintenance --help`. Reference imports
replace local changes in reference tables, preserving other tables. Refresh quests
after a database update. Quest imports replace generated quest data.

Updates briefly pause PHP and retain a previous web export or database dump under
`data/`. A failure during replacement can leave PHP stopped; inspect the reported
logs, fix the problem and rerun the same command.

To update packages, change `snapshot` and the matching image tag in `compose.yaml`.
For package or Dockerfile changes, rebuild and replace the container:

```sh
./build.sh
docker compose up -d --force-recreate --wait
```

Use `docker compose restart` after service configuration changes.
`docker compose down` stops/removes the container without deleting its data.

## Production notes

Point your hostname at the server and allow TCP 80/443 and UDP 443. Ensure Docker
bridge forwarding and outbound DNS/HTTPS work through the host firewall.

Change the Compose ports to `"80:80/tcp"`, `"443:443/tcp"` and `"443:443/udp"`.
Set `server_name` in both nginx server configs to your hostname, change the HTTP
redirect to `return 308 https://$host$request_uri;`, and change `Alt-Svc` to
`'h3=":443"; ma=86400'`. Keep the internal health listener on port 8081 and the
ACME challenge location. Set `$site_url` in `config/application.php` to your HTTPS
origin, adjust the titles, and keep `$root_url = '/';`.

Apply the ports with `docker compose up -d --force-recreate --wait`. Keep the
self-signed certificate paths until issuance succeeds. Substitute your hostname
and email in this command:

```sh
docker compose exec allaclone certbot certonly --webroot -w /app/acme \
    --cert-name allaclone.example.org -d allaclone.example.org \
    --email you@example.org --agree-tos
```

Then set these paths in `config/nginx-tls.conf`:

```nginx
ssl_certificate /app/letsencrypt/live/allaclone.example.org/fullchain.pem;
ssl_certificate_key /app/letsencrypt/live/allaclone.example.org/privkey.pem;
```

Reload nginx and test renewal:

```sh
docker compose exec allaclone /bin/sh /app/ops/nginx-reload.sh
docker compose exec allaclone certbot renew --dry-run --run-deploy-hooks
```

Cron checks renewal twice daily and reloads nginx after successful renewal.
Include private configuration, certificates and any data you need to retain in
your backups.
