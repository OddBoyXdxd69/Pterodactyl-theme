# Pterodactyl Dynamic Theme & Subdomains System

A professional, modern, and responsive left-sidebar theme layout for Pterodactyl v1.x with an integrated **Client Server Subdomain Management** system.

---

## Key Features

1. **Categorized Sidebar Layout**:
   - Organized left navigation sidebar dividing options into **Global** (Dashboard, Account), **Server Management** (Console, Files, etc.), and **Administration** (Admin Panel, Admin View) sections.
   - Sleek design with responsive visual dividers and custom FontAwesome indicators.

2. **Server Subdomain Manager**:
   - Integrated client tab letting users map subdomains to their servers.
   - Auto-fills target VPS IP and server allocation port details.
   - Support for multiple record types:
     - **SRV (Recommended for Minecraft)**: Creates an underlying target A record and maps a `_minecraft._tcp` SRV record pointing to that target.
     - **A & CNAME Records**: Maps standard connections (Web servers, Discord bots, Python hosts, Node scripts).
   - Syncs automatically with Cloudflare DNS services (with grace fallback to database if Cloudflare keys are inactive).

3. **Branding Configurations**:
   - Configure branding elements (Logo, Favicon, Accent Colors, Discord link, Support link) from the Admin panel settings.

---

## Installation

Run the following automated installation script as **root** on your panel VPS:

```bash
# 1. Download the installation script
curl -s -L https://raw.githubusercontent.com/OddBoyXdxd69/Pterodactyl-theme/main/install.sh -o install.sh

# 2. Make the script executable
chmod +x install.sh

# 3. Run the installer
sudo ./install.sh
```

---

## Admin Configuration

To configure the allowed domains and Cloudflare credentials for client subdomains:

1. Access your Pterodactyl **Admin Panel**.
2. Navigate to **Management** -> **Subdomains Config** (in the sidebar).
3. Update the following fields:
   * **Subdomains Status**: Toggle to Enable/Disable.
   * **Allowed Root Domains**: List the domains available for clients (comma or newline-separated, e.g. `mc-join.me, play-game.gg`).
   * **Cloudflare Email**: Your Cloudflare account email address.
   * **Cloudflare API Key**: Your Cloudflare Global API Key or Zone Edit Token.
   * **Cloudflare Zone ID**: The target Zone ID associated with your domains.
4. Click **Save Settings**.

---

## License & Credits

Developed and configured by **OddBoyXD**. All files inherit standard Pterodactyl licensing terms.
