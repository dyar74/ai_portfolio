#!/bin/sh
set -eu
cd /var/www/html

# Only prepare the app when starting php-fpm (not for messenger/console runs).
if [ "${1:-}" = "php-fpm" ]; then
  if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ missing -> composer install"
    composer install --no-interaction --prefer-dist --no-progress
  fi

  if [ -f bin/console ]; then
    if [ "${APP_ENV:-dev}" = "prod" ]; then
      echo "[entrypoint] warming prod cache"
      php bin/console cache:warmup --env=prod --no-debug
    fi
    if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
      echo "[entrypoint] running migrations"
      php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    fi
  fi
fi

exec "$@"
