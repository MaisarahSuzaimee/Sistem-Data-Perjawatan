#!/usr/bin/env bash
# Run ONLY on mystaff.kdh.moh.gov.my (paste into the existing SSH session).
set -euo pipefail

SITE_ROOT="$HOME/web/mystaff.kdh.moh.gov.my"
APP_ROOT="$SITE_ROOT/public_html"
LOG_FILE="$APP_ROOT/storage/logs/laravel.log"
ENV_FILE="$APP_ROOT/.env"

echo "=== Target site ==="
pwd
echo "SITE_ROOT=$SITE_ROOT"

if [[ "$(pwd)" != "$SITE_ROOT" && "$(pwd)" != "$APP_ROOT" ]]; then
  cd "$SITE_ROOT"
fi

if [[ ! -d "$APP_ROOT" ]]; then
  echo "ERROR: $APP_ROOT not found. Aborting."
  exit 1
fi

echo
echo "=== Before ==="
ls -lh "$LOG_FILE" 2>/dev/null || echo "laravel.log missing"
du -sh "$SITE_ROOT" "$APP_ROOT/storage" 2>/dev/null || true

echo
echo "=== Truncate laravel.log ==="
if [[ -f "$LOG_FILE" ]]; then
  truncate -s 0 "$LOG_FILE"
else
  : > "$LOG_FILE"
fi
ls -lh "$LOG_FILE"

echo
echo "=== Update .env logging (mystaff only) ==="
cp -a "$ENV_FILE" "$ENV_FILE.bak.$(date +%Y%m%d%H%M%S)"

if grep -q '^LOG_LEVEL=' "$ENV_FILE"; then
  sed -i 's/^LOG_LEVEL=.*/LOG_LEVEL=error/' "$ENV_FILE"
else
  printf '\nLOG_LEVEL=error\n' >> "$ENV_FILE"
fi

if grep -q '^LOG_STACK=' "$ENV_FILE"; then
  sed -i 's/^LOG_STACK=.*/LOG_STACK=daily/' "$ENV_FILE"
else
  printf 'LOG_STACK=daily\n' >> "$ENV_FILE"
fi

# Keep channel on stack (uses LOG_STACK contents)
if grep -q '^LOG_CHANNEL=' "$ENV_FILE"; then
  sed -i 's/^LOG_CHANNEL=.*/LOG_CHANNEL=stack/' "$ENV_FILE"
else
  printf 'LOG_CHANNEL=stack\n' >> "$ENV_FILE"
fi

grep -E '^LOG_CHANNEL=|^LOG_STACK=|^LOG_LEVEL=|^APP_ENV=' "$ENV_FILE"

echo
echo "=== Refresh Laravel config cache ==="
cd "$APP_ROOT"
php artisan config:clear
php artisan config:cache

echo
echo "=== After ==="
ls -lh storage/logs/
du -sh "$SITE_ROOT" "$APP_ROOT/storage" "$APP_ROOT/vendor" 2>/dev/null || true

echo
echo "=== Spam check (pcntl / schedule) ==="
php -m 2>/dev/null | grep -i pcntl || echo "pcntl extension NOT loaded in this PHP CLI"
crontab -l 2>/dev/null | grep -n 'mystaff\|schedule:run' || echo "No mystaff/schedule lines in user crontab (or crontab empty)"

echo
echo "Done. Only mystaff.kdh.moh.gov.my was modified."
