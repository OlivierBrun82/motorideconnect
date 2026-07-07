#!/bin/sh
set -e

composer install --no-interaction --no-progress

until php bin/console doctrine:database:create --if-not-exists --no-interaction; do
    >&2 echo "En attente de MySQL..."
    sleep 2
done

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec "$@"
