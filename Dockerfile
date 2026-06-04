FROM mediawiki:1.41

# Install additional PHP extensions if needed
RUN apt-get update && apt-get install -y \
    git \
    && rm -rf /var/lib/apt/lists/*

# Copy the extension into the extensions directory
COPY extension.json /var/www/html/w/extensions/WikiTraceability/
COPY includes /var/www/html/w/extensions/WikiTraceability/includes
COPY resources /var/www/html/w/extensions/WikiTraceability/resources
COPY i18n /var/www/html/w/extensions/WikiTraceability/i18n

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/w/extensions/WikiTraceability

FROM mediawiki:1.41

# Install additional PHP extensions if needed
RUN apt-get update && apt-get install -y \
    git \
    && rm -rf /var/lib/apt/lists/*

# Copy the extension into the extensions directory
COPY extension.json /var/www/html/extensions/WikiTraceability/
COPY includes /var/www/html/extensions/WikiTraceability/includes
COPY resources /var/www/html/extensions/WikiTraceability/resources
COPY i18n /var/www/html/extensions/WikiTraceability/i18n

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/extensions/WikiTraceability

# Create entrypoint script to enable extension after setup
RUN echo '#!/bin/bash\n\
set -e\n\
# Enable the extension if LocalSettings.php exists and is properly configured\n\
if [ -f /var/www/html/LocalSettings.php ] && grep -q "wgDBuser" /var/www/html/LocalSettings.php; then\n\
  if ! grep -q "WikiTraceability" /var/www/html/LocalSettings.php; then\n\
    echo "Enabling WikiTraceability extension..."\n\
    echo '\''wfLoadExtension( '\''WikiTraceability'\'');'\'' >> /var/www/html/LocalSettings.php\n\
  fi\n\
fi\n\
# Start Apache\n\
exec apache2-foreground\n\
' > /entrypoint.sh && chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
EXPOSE 80
