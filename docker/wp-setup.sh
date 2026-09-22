#!/usr/bin/env sh
set -e

# Verzeichnisse für Plugin-Installation und Uploads sicherstellen
mkdir -p /var/www/html/wp-content/upgrade /var/www/html/wp-content/uploads
chown -R www-data:www-data /var/www/html/wp-content/upgrade /var/www/html/wp-content/uploads 2>/dev/null || true

if wp core is-installed 2>/dev/null; then
  echo "WordPress already installed, skipping setup."
else
  wp core install \
    --url="$WP_HOME" \
    --title="$WP_TITLE" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email
fi

wp plugin activate easy-sams

if [ "$PLUGIN_CHECK_SKIP_INSTALL" != "1" ]; then
  wp plugin install plugin-check --activate
fi

if wp post list --post_type=page --post_status=any --name=easy-sams-demo --format=ids 2>/dev/null | grep -q .; then
  echo "Demo page already exists, skipping."
else
  DEMO_CONTENT="<!-- wp:heading --><h2 class=\"wp-block-heading\">Tabelle</h2><!-- /wp:heading --><!-- wp:esams/standings {\"sourceId\":\"$ESAMS_SOURCE_ID\",\"seasonUuid\":\"$ESAMS_SEASON_UUID\",\"leagueUuid\":\"$ESAMS_LEAGUE_UUID\",\"teamUuid\":\"$ESAMS_TEAM_UUID\"} /--><!-- wp:heading --><h2 class=\"wp-block-heading\">Ergebnisse / Spielplan</h2><!-- /wp:heading --><!-- wp:esams/results {\"sourceId\":\"$ESAMS_SOURCE_ID\",\"seasonUuid\":\"$ESAMS_SEASON_UUID\",\"leagueUuid\":\"$ESAMS_LEAGUE_UUID\",\"teamUuid\":\"$ESAMS_TEAM_UUID\",\"includeFuture\":true} /--></p>"
  wp post create \
    --post_type=page \
    --post_title="Easy SAMS Demo" \
    --post_name=easy-sams-demo \
    --post_status=publish \
    --post_content="$DEMO_CONTENT"
  echo "Demo-Seite erstellt: $WP_HOME/easy-sams-demo/"
fi

tail -f /dev/null
