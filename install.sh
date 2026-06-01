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
  "app/Http/Controllers/Admin/Settings/SubdomainController.php"
  "app/Providers/SettingsServiceProvider.php"
  "app/Http/ViewComposers/AssetComposer.php"
  "resources/scripts/components/auth/LoginFormContainer.tsx"
  "resources/scripts/components/elements/PageContentBlock.tsx"
  "resources/scripts/state/settings.ts"
  "resources/views/admin/settings/theme.blade.php"
  "resources/views/admin/settings/subdomains.blade.php"
  "resources/views/layouts/admin.blade.php"
  "resources/views/partials/admin/settings/nav.blade.php"
  "resources/views/templates/wrapper.blade.php"
  "routes/admin.php"
  "tailwind.config.js"
  "resources/scripts/assets/css/GlobalStylesheet.ts"
  "resources/scripts/components/elements/SubNavigation.tsx"
  "resources/scripts/components/NavigationBar.tsx"
  "resources/scripts/components/App.tsx"
  "database/migrations/2026_05_31_100000_create_subdomains_table.php"
  "app/Models/Subdomain.php"
  "app/Http/Controllers/Api/Client/Servers/SubdomainController.php"
  "routes/api-client.php"
  "resources/scripts/routers/routes.ts"
  "resources/scripts/routers/ServerRouter.tsx"
  "resources/scripts/components/server/subdomains/SubdomainsContainer.tsx"
  "resources/scripts/components/server/files/FileManagerContainer.tsx"
  "resources/scripts/components/server/files/PullFileButton.tsx"
  "resources/scripts/components/server/files/NewDirectoryButton.tsx"
  "resources/scripts/components/server/files/style.module.css"
  "database/migrations/2026_05_31_110000_add_subdomain_limit_to_servers_table.php"
  "app/Services/Servers/BuildModificationService.php"
  "app/Http/Controllers/Admin/ServersController.php"
  "resources/views/admin/servers/view/build.blade.php"
  "app/Transformers/Api/Client/ServerTransformer.php"
  "resources/scripts/api/server/getServer.ts"
  "resources/scripts/components/server/plugins/PluginsContainer.tsx"
  "app/Http/Controllers/Admin/Settings/PluginsController.php"
  "resources/views/admin/settings/plugins.blade.php"
  "app/Http/Controllers/Admin/Settings/VersionsController.php"
  "resources/views/admin/settings/versions.blade.php"
  "resources/scripts/components/server/versions/VersionsContainer.tsx"
  "database/migrations/2026_05_31_120000_create_tickets_table.php"
  "app/Models/Ticket.php"
  "app/Models/TicketMessage.php"
  "app/Http/Controllers/Api/Client/SupportController.php"
  "app/Http/Controllers/Admin/Settings/TicketsController.php"
  "app/Http/Controllers/Admin/Settings/TicketsConfigController.php"
  "resources/views/admin/settings/tickets/index.blade.php"
  "resources/views/admin/settings/tickets/view.blade.php"
  "resources/views/admin/settings/tickets/config.blade.php"
  "resources/scripts/routers/DashboardRouter.tsx"
  "resources/scripts/components/dashboard/SupportContainer.tsx"
)

# 2. Download and replace files
echo "[*] Downloading and placing theme files..."
for file in "${files[@]}"; do
  echo "  -> Downloading: $file"
  mkdir -p "$(dirname "$PANEL_DIR/$file")"
  curl -s -L "$BASE_URL/$file" -o "$PANEL_DIR/$file"
done

# 3. Update permissions
echo "[*] Creating branding directory & setting folder ownership..."
mkdir -p "$PANEL_DIR/public/assets/branding"
chown -R www-data:www-data "$PANEL_DIR"/*

# 4. Install dependencies and compile assets
echo "[*] Installing frontend dependencies & building theme assets..."
cd "$PANEL_DIR"
yarn install --ignore-engines

# Compile frontend with memory limit safeguard for low RAM VPS systems
echo "[*] Running clean and webpack build..."
cd "$PANEL_DIR/public/assets" && find . \( -name "*.js" -o -name "*.map" \) -type f -delete
cd "$PANEL_DIR"
NODE_OPTIONS="--max-old-space-size=1536" NODE_ENV=production ./node_modules/.bin/webpack --mode production

# 5. Clear cache & run migrations
echo "[*] Running database migrations & clearing caches..."
php artisan migrate --force
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
