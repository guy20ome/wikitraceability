# Docker MediaWiki + Semantic MediaWiki + WikiTraceability Setup

This provides a complete local development environment with **MediaWiki**, **Semantic MediaWiki**, and the **WikiTraceability** extension pre-configured using Docker.

## Prerequisites

- Docker (v20.10+) and Docker Compose (v2.0+)
- At least 2GB of free disk space
- Port 8080 available on your machine

## Quick Start (First Time)

```bash
# 1. Clone the repository (if not already done)
git clone https://github.com/guy20ome/wikitraceability.git
cd wikitraceability

# 2. Build and start all services; the container initializes MediaWiki and
#    Semantic MediaWiki automatically on first startup
docker compose up -d --build

# 3. Wait for the site to be ready
docker compose logs -f mediawiki

# 4. Access MediaWiki
# Open: http://localhost:8080
# Admin: Admin / WikiTraceAdminPass2026
```

## Services

| Service | URL | Credentials |
|---------|-----|-------------|
| MediaWiki | `http://localhost:8080` | Admin / WikiTraceAdminPass2026 |
| MariaDB | `database:3306` (internal) | mediawiki / mediawikipass |

## Database Credentials

- **Host**: `database` (Docker internal) or `localhost` (external)
- **Database**: `mediawiki`
- **User**: `mediawiki`
- **Password**: `mediawikipass`
- **Root Password**: `wikiroot`

## What's Pre-Installed

✅ **MediaWiki 1.41** - Latest LTS version
✅ **Semantic MediaWiki 5.1.0** - Compatible with MediaWiki 1.41
✅ **WikiTraceability Extension** - Automatically loaded and mounted

## Common Commands

| Command | Description |
|---------|-------------|
| `docker compose up -d` | Start services (detached) |
| `docker compose up -d --build` | Rebuild and start |
| `docker compose down` | Stop services |
| `docker compose down -v` | Stop and remove data |
| `docker compose logs -f mediawiki` | View MediaWiki logs |
| `docker compose exec mediawiki bash` | Shell access |
| `docker compose restart mediawiki` | Restart MediaWiki |

## Using the Setup Script

A helper script is provided for common operations:

```bash
# Show help
./docker-setup.sh help

# Start services; the database is initialized automatically on first run
./docker-setup.sh up

# Stop services
./docker-setup.sh down

# View logs
./docker-setup.sh logs

# Enter container shell
./docker-setup.sh shell

# Full cleanup (removes everything)
./docker-setup.sh clean

# Rebuild from scratch
./docker-setup.sh rebuild
```

## Development Workflow

### Making Changes
1. Edit extension files in your local `./` directory
2. Changes are **automatically synced** to the container (read-only mount)
3. Refresh your browser to see changes
4. For PHP changes, restart MediaWiki: `docker compose restart mediawiki`

### Running Database Updates
```bash
docker compose exec mediawiki php /var/www/html/maintenance/run.php update
```

### Running Tests (if PHPUnit installed)
```bash
docker compose exec mediawiki bash
cd /var/www/html/extensions/WikiTraceability
composer install --dev
./vendor/bin/phpunit
```

## Extension Features

### WikiTraceability Special Pages
- `Special:TraceabilityProduct` - Create/edit products
- `Special:TraceabilityTransformation` - Create/edit transformations; add or remove inputs and outputs (at least one of each) and choose products from suggestions after typing at least three characters
- `Special:TraceabilityTree?product=ProductName` - View traceability tree
- Logged-in users can edit or delete products and transformations from their wiki pages. Deleting a product is blocked while any transformation uses it as an input or output. The extension's `wikitraceability-delete` right is granted to registered users for these special-page operations only.

### Semantic MediaWiki Features
- All WikiTraceability data stored as SMW properties
- Query products and transformations using SMW syntax
- Full semantic querying capabilities

## Troubleshooting

### Container won't start
```bash
docker compose down
docker compose up --build --force-recreate
```

### Database connection issues
```bash
# Reset database completely (removes all wiki data)
docker compose down -v
docker compose up -d
./init-mediawiki.sh
```

### Extension not loading
```bash
# Check LocalSettings.php
docker compose exec mediawiki cat /var/www/html/LocalSettings.php | grep WikiTraceability

# Check extension directory
docker compose exec mediawiki ls -la /var/www/html/extensions/WikiTraceability/
```

### Permission issues
```bash
docker compose exec mediawiki chown -R www-data:www-data /var/www/html/extensions/WikiTraceability
```

### View MediaWiki logs
```bash
docker compose exec mediawiki tail -f /var/www/html/debug.log
```

### Check SMW installation
```bash
docker compose exec mediawiki php /var/www/html/extensions/SemanticMediaWiki/maintenance/rebuildData.php
```

## Configuration

### Custom Admin Credentials
Edit `docker-compose.yml` and change:
```yaml
environment:
  MEDIAWIKI_ADMIN_USER: Admin
  MEDIAWIKI_ADMIN_PASSWORD: WikiTraceAdminPass2026
```

### Change Port
Edit `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # Change first number to your preferred port
```

### Enable Additional Extensions
Edit `LocalSettings2.php` and add:
```php
wfLoadExtension( 'ExtensionName' );
```

## Useful Links

- [MediaWiki Documentation](https://www.mediawiki.org/wiki/Manual:Contents)
- [Semantic MediaWiki](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki)
- [Extension Development](https://www.mediawiki.org/wiki/Manual:Extension_registration)
- [Docker MediaWiki](https://hub.docker.com/_/mediawiki)

## Cleanup

To completely remove all Docker resources:
```bash
./docker-setup.sh clean
# or manually:
docker compose down -v
```
