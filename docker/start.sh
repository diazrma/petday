#!/bin/sh
# Sobe o Laravel no container: banco SQLite no volume (copia o da pasta na 1ª vez), migrations e Apache.
set -e
cd /var/www/html
DB=database/docker/database.sqlite
if [ ! -f "$DB" ]; then
  if [ -f /seed/database.sqlite ]; then cp /seed/database.sqlite "$DB"; else touch "$DB"; fi
  chown www-data:www-data "$DB"
fi
if [ -z "$APP_KEY" ]; then export APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"; echo "APP_KEY ausente: usando uma chave temporária (defina APP_KEY no .env)"; fi
# tudo como www-data (o dono dos arquivos de storage), nunca como root
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
su -s /bin/sh www-data -c "php artisan package:discover --ansi" || true
# banco remoto (ex.: Neon) pode estar acordando: tenta algumas vezes
i=0
until su -s /bin/sh www-data -c "php artisan migrate --force"; do
  i=$((i+1)); [ $i -ge 10 ] && { echo "migrations falharam (veja acima)"; break; }
  echo "banco indisponível, nova tentativa em 3s ($i/10)"; sleep 3
done
php artisan storage:link 2>/dev/null || true
# Render (e afins) informam a porta em $PORT; local continua na 80
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
  sed -ri "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
  sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf
fi
exec apache2-foreground
