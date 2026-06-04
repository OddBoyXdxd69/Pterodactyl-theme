#!/usr/bin/env bash
# Universal Backup Node Agent Script
# Automatically polls the panel for backup/restore tasks and processes them using rclone.

set -e

# Configuration
CONFIG_PATH="/etc/pterodactyl/config.yml"
if [ ! -f "$CONFIG_PATH" ]; then
    echo "Error: Wings configuration not found at $CONFIG_PATH" >&2
    exit 1
fi

# Ensure rclone is installed
if ! command -v rclone &> /dev/null; then
    echo "rclone is not installed. Installing now..."
    curl -s https://rclone.org/install.sh | bash || true
    if ! command -v rclone &> /dev/null; then
        echo "Error: Failed to install rclone. Please install it manually." >&2
        exit 1
    fi
fi

# Parse config
PANEL_URL=$(grep "panel:" "$CONFIG_PATH" | sed -E "s/panel:[[:space:]]*['\"]?([^'\"]+)['\"]?/\1/")
TOKEN=$(grep "token:" "$CONFIG_PATH" | sed -E "s/token:[[:space:]]*['\"]?([^'\"]+)['\"]?/\1/")

# Trim trailing slash from PANEL_URL
PANEL_URL="${PANEL_URL%/}"

echo "Connecting to panel: $PANEL_URL"

# Fetch queue
QUEUE_URL="$PANEL_URL/api/remote/backups/queue"
RESPONSE=$(curl -s -L -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" "$QUEUE_URL")

# Check if response is empty or invalid
if [ -z "$RESPONSE" ] || [ "$RESPONSE" = "[]" ] || [ "$(echo "$RESPONSE" | grep -o 'backup_id')" = "" ]; then
    echo "No pending backup or restore tasks in queue."
    exit 0
fi

# Loop through tasks in JSON array
read_tasks() {
    python3 -c "
import json, sys
data = json.loads(sys.argv[1])
for t in data:
    print(f\"{t.get('backup_id')} {t.get('action')} {t.get('server_uuid')} {t.get('gdrive_folder_id')} {t.get('file_id','')} {t.get('filename','')} {t.get('access_token','')}\")
" "$RESPONSE"
}

tasks=$(read_tasks || true)

if [ -z "$tasks" ]; then
    echo "No valid tasks found or Python parsing failed."
    exit 0
fi

echo "$tasks" | while read -r backup_id action server_uuid gdrive_folder_id file_id filename access_token; do
    if [ -z "$backup_id" ]; then continue; fi
    
    echo "Processing task ID: $backup_id, Action: $action, Server UUID: $server_uuid"
    
    VOLUME_DIR="/var/lib/pterodactyl/volumes/$server_uuid"
    
    # Verify server directory exists
    if [ ! -d "$VOLUME_DIR" ]; then
        echo "Error: Server directory $VOLUME_DIR not found."
        # Callback to panel with failure
        curl -s -X POST -H "Authorization: Bearer $TOKEN" \
             -H "Content-Type: application/json" \
             -d "{\"backup_id\":$backup_id,\"status\":\"failed\"}" \
             "$PANEL_URL/api/remote/backups/callback"
        continue
    fi
    
    STATUS="success"
    SIZE_BYTES=0
    FINAL_FILE_ID="$file_id"
    
    if [ "$action" = "backup" ]; then
        echo "Starting backup of server files..."
        DRIVE_TOKEN="{\"access_token\":\"$access_token\",\"token_type\":\"Bearer\"}"
        
        set +e
        tar -czf - -C "$VOLUME_DIR" . | rclone rcat --drive-token="$DRIVE_TOKEN" :drive,root_folder_id="$gdrive_folder_id":"$filename"
        RC_STATUS=$?
        set -e
        
        if [ $RC_STATUS -eq 0 ]; then
            echo "Backup upload completed successfully."
            set +e
            SIZE_STR=$(rclone size --drive-token="$DRIVE_TOKEN" :drive,root_folder_id="$gdrive_folder_id":"$filename" 2>/dev/null)
            SIZE_BYTES=$(echo "$SIZE_STR" | grep -o '([0-9]\+ Bytes)' | tr -d '() Bytes' || echo "0")
            FINAL_FILE_ID=$(rclone lsf --drive-token="$DRIVE_TOKEN" --format "i" :drive,root_folder_id="$gdrive_folder_id":"$filename" 2>/dev/null | head -n 1)
            set -e
        else
            echo "Error: rclone upload failed with status $RC_STATUS."
            STATUS="failed"
        fi
        
    elif [ "$action" = "restore" ]; then
        echo "Starting restore of server files..."
        DRIVE_TOKEN="{\"access_token\":\"$access_token\",\"token_type\":\"Bearer\"}"
        
        # Wipe current files safely before restore
        rm -rf "${VOLUME_DIR:?}"/*
        
        set +e
        rclone cat --drive-token="$DRIVE_TOKEN" :drive,root_folder_id="$gdrive_folder_id":"$filename" | tar -xzf - -C "$VOLUME_DIR"
        RC_STATUS=$?
        set -e
        
        if [ $RC_STATUS -eq 0 ]; then
            echo "Restore files extracted successfully. Resetting permissions..."
            chown -R pterodactyl:pterodactyl "$VOLUME_DIR" || true
        else
            echo "Error: Restore extraction failed with status $RC_STATUS."
            STATUS="failed"
        fi
    else
        echo "Unknown action: $action"
        STATUS="failed"
    fi
    
    # Callback to panel
    echo "Sending callback to panel with status: $STATUS"
    set +e
    curl -s -X POST -H "Authorization: Bearer $TOKEN" \
         -H "Content-Type: application/json" \
         -d "{\"backup_id\":$backup_id,\"status\":\"$STATUS\",\"file_id\":\"$FINAL_FILE_ID\",\"filename\":\"$filename\",\"file_size\":$SIZE_BYTES}" \
         "$PANEL_URL/api/remote/backups/callback"
    set -e
    
    echo "Finished processing task ID: $backup_id"
done

echo "Universal Backup Node Agent completed run."
