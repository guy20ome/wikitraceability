#!/bin/bash

set -e

# Wait for database to be ready
if [ -n "$MEDIAWIKI_DB_HOST" ]; then
  echo "Waiting for database..."
  until mysql -h "$MEDIAWIKI_DB_HOST" -u "$MEDIAWIKI_DB_USER" -p"$MEDIAWIKI_DB_PASSWORD" "$MEDIAWIKI_DB_NAME" -e "SELECT 1;" 2>/dev/null; do
    sleep 2
  done
  echo "Database is ready!"
fi

# Run database updates if needed
if [ -f /var/www/html/LocalSettings.php ]; then
  table_count=$(mysql -N -B -h "$MEDIAWIKI_DB_HOST" -u "$MEDIAWIKI_DB_USER" -p"$MEDIAWIKI_DB_PASSWORD" "$MEDIAWIKI_DB_NAME" \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();")

  if [ "$table_count" -eq 0 ]; then
    echo "MediaWiki database is empty; run ./init-mediawiki.sh to initialize it."
  else
    echo "Running MediaWiki updates..."
    php /var/www/html/maintenance/run.php update --quick
  fi
fi

# Ensure WikiTraceability extension is loaded
if [ -f /var/www/html/LocalSettings.php ]; then
  if ! grep -q "WikiTraceability" /var/www/html/LocalSettings.php; then
    echo "Enabling WikiTraceability extension..."
    echo "wfLoadExtension( 'WikiTraceability' );" >> /var/www/html/LocalSettings.php
  fi
fi

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
