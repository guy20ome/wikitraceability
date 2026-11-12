#!/bin/bash

# Initialize the MediaWiki database used by the local Docker environment.

set -e

docker compose up -d --build

echo "Waiting for database to be ready..."
for i in {1..60}; do
  if docker compose exec -T database mariadb-admin ping -h localhost -u root -pwikiroot &>/dev/null; then
    echo "Database is ready."
    break
  fi

  if [ "$i" -eq 60 ]; then
    echo "ERROR: Database did not become ready within 60 seconds." >&2
    exit 1
  fi

  sleep 1
done

db_host=$(docker compose exec -T mediawiki printenv MEDIAWIKI_DB_HOST)
db_name=$(docker compose exec -T mediawiki printenv MEDIAWIKI_DB_NAME)
db_user=$(docker compose exec -T mediawiki printenv MEDIAWIKI_DB_USER)
db_password=$(docker compose exec -T mediawiki printenv MEDIAWIKI_DB_PASSWORD)
admin_user=$(docker compose exec -T mediawiki printenv MEDIAWIKI_ADMIN_USER)
admin_password=$(docker compose exec -T mediawiki printenv MEDIAWIKI_ADMIN_PASSWORD)
site_name=$(docker compose exec -T mediawiki printenv MEDIAWIKI_SITE_NAME)

table_count=$(docker compose exec -T mediawiki mysql -N -B -h "$db_host" -u "$db_user" -p"$db_password" "$db_name" \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();")

if [ "$table_count" -eq 0 ]; then
  mediawiki_container=$(docker compose ps -q mediawiki)
  mediawiki_image=$(docker inspect "$mediawiki_container" --format '{{.Image}}')
  mediawiki_network=$(docker inspect "$mediawiki_container" \
    --format '{{range $name, $network := .NetworkSettings.Networks}}{{println $name}}{{end}}' | head -n 1)

  echo "Installing MediaWiki into the empty database..."
  docker run --rm --network "$mediawiki_network" --entrypoint php "$mediawiki_image" \
    /var/www/html/maintenance/run.php install \
    --dbtype mysql \
    --dbserver "$db_host" \
    --dbname "$db_name" \
    --dbuser "$db_user" \
    --dbpass "$db_password" \
    --installdbuser "$db_user" \
    --installdbpass "$db_password" \
    --dbprefix trace_ \
    --scriptpath '' \
    --server http://localhost:8080 \
    --lang en \
    --confpath /tmp \
    --pass "$admin_password" \
    "$site_name" "$admin_user"
else
  echo "MediaWiki database already contains tables; skipping installation."
fi

echo "Applying MediaWiki and extension database updates..."
docker compose exec -T mediawiki php /var/www/html/maintenance/run.php update --quick

echo "MediaWiki initialization complete."
echo "Access at: http://localhost:8080"
echo "Admin user: $admin_user / $admin_password"
