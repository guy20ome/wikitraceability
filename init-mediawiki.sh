#!/bin/bash

# Start the local Docker environment. MediaWiki and Semantic MediaWiki are
# initialized automatically by the container on first startup.

set -e

docker compose up -d --build

echo "MediaWiki initialization runs automatically during container startup."
echo "Follow progress with: docker compose logs -f mediawiki"
echo "Access at: http://localhost:8080"
echo "Admin user: Admin / WikiTraceAdminPass2026"
