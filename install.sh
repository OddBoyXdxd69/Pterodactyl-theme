#!/bin/bash

# Ensure the script is run as root
if [ "$EUID" -ne 0 ]; then
  echo "[-] Please run this script as root (sudo bash install.sh)"
  exit 1
fi

GITHUB_USERNAME="OddBoyXdxd69"
REPO_NAME="Pterodactyl-theme"
BRANCH_NAME="main"

BASE_URL="https://raw.githubusercontent.com/${GITHUB_USERNAME}/${REPO_NAME}/${BRANCH_NAME}"

# Detect Pterodactyl folder (Default: /var/www/pterodactyl)
PANEL_DIR="/var/www/pterodactyl"
if [ ! -d "$PANEL_DIR" ]; then
  echo "[?] Could not locate Pterodactyl at $PANEL_DIR."
  read -p "Enter your panel directory path: " PANEL_DIR
fi

if [ ! -f "$PANEL_DIR/artisan" ]; then
  echo "[-] Could not find a valid Pterodactyl installation in $PANEL_DIR."
  exit 1
fi

echo "[+] Starting Dynamic Theme Installation..."

# 1. Check for APP_ENVIRONMENT_ONLY in .env
if grep -q "APP_ENVIRONMENT_ONLY=true" "$PANEL_DIR/.env"; then
  echo "[*] Setting APP_ENVIRONMENT_ONLY=false in .env to enable database settings loading..."
  sed -i 's/APP_ENVIRONMENT_ONLY=true/APP_ENVIRONMENT_ONLY=false/g' "$PANEL_DIR/.env"
fi

# List of files to download from your repo
files=(
  "app/Http/Controllers/Admin/Settings/ThemeController.php"
  "app/Providers/SettingsServiceProvider.php"
  "app/Http/ViewComposers/AssetComposer.php"
  "resources/scripts/components/auth/LoginFormContainer.tsx"
  "resources/scripts/components/elements/PageContentBlock.tsx"
  "resources/scripts/state/settings.ts"
  "resources/views/admin/settings/theme.blade.php"
  "resources/views/layouts/admin.blade.php"
  "resources/views/partials/admin/settings/nav.blade.php"
  "resources/views/templates/wrapper.blade.php"
  "routes/admin.php"
  "tailwind.config.js"
  "resources/scripts/assets/css/GlobalStylesheet.ts"
  "resources/scripts/components/elements/SubNavigation.tsx"
  "resources/scripts/components/NavigationBar.tsx"
  "resources/scripts/components/App.tsx"
)

# 2. Download and replace files
echo "[*] Downloading and placing theme files..."
for file in "${files[@]}"; do
  echo "  -> Downloading: $file"
  mkdir -p "$(dirname "$PANEL_DIR/$file")"
  curl -s -L "$BASE_URL/$file" -o "$PANEL_DIR/$file"
done

# 3. Update permissions
echo "[*] Setting correct folder ownership..."
chown -R www-data:www-data "$PANEL_DIR"/*

# 4. Install dependencies and compile assets
echo "[*] Installing frontend dependencies & building theme assets..."
cd "$PANEL_DIR"
yarn install --ignore-engines

# Compile frontend with memory limit safeguard for low RAM VPS systems
NODE_OPTIONS="--max-old-space-size=1536" yarn run build:production --ignore-engines

# 5. Clear cache
echo "[*] Clearing panel caches..."
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan queue:restart

# 6. Detect and restart PHP-FPM
echo "[*] Restarting PHP-FPM..."
if systemctl is-active --quiet php8.3-fpm; then
  systemctl restart php8.3-fpm
elif systemctl is-active --quiet php8.2-fpm; then
  systemctl restart php8.2-fpm
elif systemctl is-active --quiet php8.1-fpm; then
  systemctl restart php8.1-fpm
fi

echo "[+] Dynamic Theme has been successfully installed and compiled!"
