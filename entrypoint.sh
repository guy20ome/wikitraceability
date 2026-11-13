#!/bin/bash

set -e

# Wait for database to be ready
settings_source=/var/www/html/LocalSettings2.php
if [ ! -f "$settings_source" ]; then
  echo "ERROR: Missing MediaWiki settings file: $settings_source" >&2
  exit 1
fi

if [ -n "$MEDIAWIKI_DB_HOST" ]; then
  echo "Waiting for database..."
  until mysql -h "$MEDIAWIKI_DB_HOST" -u "$MEDIAWIKI_DB_USER" -p"$MEDIAWIKI_DB_PASSWORD" "$MEDIAWIKI_DB_NAME" -e "SELECT 1;" 2>/dev/null; do
    sleep 2
  done
  echo "Database is ready!"
fi

# Run database updates if needed
if [ -n "$MEDIAWIKI_DB_HOST" ]; then
  table_count=$(mysql -N -B -h "$MEDIAWIKI_DB_HOST" -u "$MEDIAWIKI_DB_USER" -p"$MEDIAWIKI_DB_PASSWORD" "$MEDIAWIKI_DB_NAME" \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();")

  if [ "$table_count" -eq 0 ]; then
    echo "MediaWiki database is empty; installing MediaWiki..."
    php /var/www/html/maintenance/run.php install \
      --dbtype mysql \
      --dbserver "$MEDIAWIKI_DB_HOST" \
      --dbname "$MEDIAWIKI_DB_NAME" \
      --dbuser "$MEDIAWIKI_DB_USER" \
      --dbpass "$MEDIAWIKI_DB_PASSWORD" \
      --installdbuser "$MEDIAWIKI_DB_USER" \
      --installdbpass "$MEDIAWIKI_DB_PASSWORD" \
      --dbprefix trace_ \
      --scriptpath '' \
      --server http://localhost:8080 \
      --lang en \
      --confpath /tmp \
      --pass "$MEDIAWIKI_ADMIN_PASSWORD" \
      "$MEDIAWIKI_SITE_NAME" "$MEDIAWIKI_ADMIN_USER"
  fi
fi

# Make the configured settings available after installation, which must run
# before LocalSettings.php exists.
cp "$settings_source" /var/www/html/LocalSettings.php

# Ensure WikiTraceability is loaded before database updates.
if ! grep -q "WikiTraceability" /var/www/html/LocalSettings.php; then
  echo "Enabling WikiTraceability extension..."
  echo "wfLoadExtension( 'WikiTraceability' );" >> /var/www/html/LocalSettings.php
fi

if [ -n "$MEDIAWIKI_DB_HOST" ]; then
  echo "Running MediaWiki updates..."
  php /var/www/html/maintenance/run.php update --quick --skip-optimize
fi

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
