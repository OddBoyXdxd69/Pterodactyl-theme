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
4. Click **Save Configurations**.
5. Set up the Node Backup Agent on your Node VPS(s) (see below).

---

## Node Backup Agent Setup

On each of your Node (Wings) VPS servers, configure the backup agent to run on a regular cron job to process backup and restore tasks:

1. Download and install the agent script on the Node VPS:
   ```bash
   curl -sSL -o /usr/local/bin/node_backup_agent.sh https://<your-panel-domain>/node_backup_agent.sh
   chmod +x /usr/local/bin/node_backup_agent.sh
   ```
2. Create a cron job to run the agent every minute by running `crontab -e` and adding:
   ```cron
   * * * * * /usr/local/bin/node_backup_agent.sh >/dev/null 2>&1
   ```

---

## License & Credits

Developed and configured by **OddBoyXD**. All files inherit standard Pterodactyl licensing terms.
