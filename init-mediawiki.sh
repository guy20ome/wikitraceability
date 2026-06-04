#!/bin/bash

# MediaWiki Database and Configuration Initialization Script

set -e

echo "Waiting for database to be ready..."
for i in {1..60}; do
  if docker-compose exec -T database mariadb-admin ping -h localhost -u root -p"wikiroot" &>/dev/null; then
    echo "✓ Database is ready"
    break
  fi
  echo "Waiting... ($i/60)"
  sleep 1
done

echo "Running MediaWiki installation..."
docker-compose exec -T mediawiki php /var/www/html/maintenance/install.php \
  --dbtype mysql \
  --dbserver database \
  --dbname mediawiki \
  --dbuser mediawiki \
  --dbpass mediawikipass \
  --scriptpath /w \
  --lang en \
  --pass WikiTraceAdminPass2026 \
  "WikiTraceability" "Admin"

echo "Enabling WikiTraceability extension..."
docker-compose exec -T mediawiki bash -c \
  "if ! grep -q 'WikiTraceability' /var/www/html/LocalSettings.php; then
    echo \"wfLoadExtension( 'WikiTraceability' );\" >> /var/www/html/LocalSettings.php
  fi"

echo "✓ MediaWiki initialization complete!"
echo "✓ Access at: http://localhost:8080/w"
echo "✓ Admin user: Admin / WikiTraceAdminPass2026"
