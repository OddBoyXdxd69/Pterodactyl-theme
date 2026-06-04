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

4. **Universal Cloud Backups & Disaster Recovery**:
   - Back up the entire panel MySQL database structure and user credentials directly to Google Drive.
   - Back up entire Nodes (all servers hosted on a node) or individual servers to Google Drive with automated queueing.
   - High performance zero-disk-waste streaming: Server folders are zipped and streamed directly to Google Drive via Rclone (`rclone rcat`) on-the-fly, preventing local storage exhaustion.
   - Dual authentication options: Supports Google Drive OAuth2 credentials and Google Cloud Service Account JSON keys.
   - Automated disaster recovery: Restores database/users structure first, then downloads and extracts server folders back to the target node VPS.

---

## Installation

Run the following one-line automated installation command as **root** on your panel VPS:

```bash
curl -s -L https://raw.githubusercontent.com/OddBoyXdxd69/Pterodactyl-theme/main/install.sh | sudo bash
```

Alternatively, you can download and run the script manually:

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

### Subdomains System
To configure the allowed domains and Cloudflare credentials for client subdomains:

1. Access your Pterodactyl **Admin Panel**.
2. Navigate to **Settings** -> **Subdomains Config** or **General** -> **Subdomains**.
3. Update the fields and click **Save Settings**.

### Universal Cloud Backups Setup
1. Access your Pterodactyl **Admin Panel**.
2. Navigate to **Settings** -> **Universal Backups**.
3. Configure your Google Drive Credentials:
   * Select your **Authentication Method** (OAuth2 Client or Service Account JSON).
   * Enter your Google Drive **Target Folder ID**.
   * Fill in the corresponding OAuth2 client fields or paste the JSON key content.
4. Set **Ignore Server Backup Limits** to **Yes** if you wish to bypass standard server quotas.
5. Click **Save Configurations**.
6. Backups will run instantly on the nodes via the existing connected **Wings API**. No cron jobs or script installations are required on the node VPS!

---

## Suggested Future Features

Here are 10 suggested features to further expand the panel's capabilities:

1. **AES-256 Backup Encryption**: Add an option to encrypt tarballs on the node with AES-256 before uploading to Google Drive, ensuring complete data security and privacy.
2. **Automated Discord Bot Monitoring**: A built-in Discord bot that admins/users can trigger manually using `/monitor` in their server to receive real-time resource tracking (CPU, RAM, Status, Player Count) updated every 60 seconds.
3. **Advanced Backup Retention Rules**: Implement a retention policy scheduler (e.g., grandfather-father-son scheme: keep 7 daily, 4 weekly, and 12 monthly backups) instead of only holding the most recent backup.
4. **Client-Owned Cloud Credentials**: Allow individual clients to connect their own Google Drive or Dropbox accounts in their dashboard settings to save their own server backups.
5. **Interactive Glassmorphic Customizer**: A visual theme editor inside the admin panel to customize gradients, colors, glass opacity levels, and custom backgrounds on-the-fly.
6. **Node-to-Node Live Transfers**: One-click server migration that zips, transfers, and restores a server between different Node VPS hosts directly from the admin panel using Wings APIs.
7. **Cloudflare SSL Proxy for Custom Client Domains**: Allow clients to point their own custom domains (e.g. `play.myname.com`) to allocations and automatically issue SSL certificates via Cloudflare proxy API.
8. **Live Container Resource Dashboard**: Integration of visual canvas charts showing historical memory, CPU, and IO usage graphs directly inside the server admin view.
9. **In-game Chat to Discord sync (RCON/Webhooks)**: Bi-directional chat sync linking server RCON console and player activities to selected Discord channels.
10. **Support Billing & Invoicing Engine**: Complete ticket integration with Stripe/PayPal payment gateways to manage subscription-based container creations and suspensions automatically.

---

## License & Credits

Developed and configured by **OddBoyXD**. All files inherit standard Pterodactyl licensing terms.
