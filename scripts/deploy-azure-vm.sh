#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOCATION="${AZURE_LOCATION:-centralus}"
RESOURCE_GROUP="${AZURE_RESOURCE_GROUP:-rg-hostalconnect-unab-20261111-cus}"
SUBSCRIPTION_ID="${AZURE_SUBSCRIPTION_ID:-$(az account show --query id -o tsv)}"
ADMIN_USERNAME="${AZURE_VM_ADMIN:-hcadmin}"
SSH_PRIVATE_KEY_FILE="${SSH_PRIVATE_KEY_FILE:-$HOME/.ssh/rua-azure-ed25519}"
SSH_PUBLIC_KEY_FILE="${SSH_PUBLIC_KEY_FILE:-${SSH_PRIVATE_KEY_FILE}.pub}"
VM_TEMPLATE="$ROOT_DIR/infra/azure-vm/main.bicep"
COMPOSE_FILE="$ROOT_DIR/infra/azure-vm/compose.yaml"
CADDY_FILE="$ROOT_DIR/infra/azure-vm/Caddyfile"
TEMP_DIR="$(mktemp -d)"

cleanup() {
    rm -rf -- "$TEMP_DIR"
}
trap cleanup EXIT

fail() {
    printf 'ERROR: %s\n' "$1" >&2
    exit 1
}

for executable in az docker openssl python3 ssh scp curl; do
    command -v "$executable" >/dev/null 2>&1 || fail "Missing required command: $executable"
done

[[ -f "$SSH_PRIVATE_KEY_FILE" ]] || fail "SSH private key not found: $SSH_PRIVATE_KEY_FILE"
[[ -f "$SSH_PUBLIC_KEY_FILE" ]] || fail "SSH public key not found: $SSH_PUBLIC_KEY_FILE"
[[ -f "$VM_TEMPLATE" ]] || fail "Bicep template not found: $VM_TEMPLATE"

if [[ -z "${DEMO_ACCOUNT_PASSWORD:-}" ]]; then
    if [[ ! -r /dev/tty ]]; then
        fail 'Set DEMO_ACCOUNT_PASSWORD or run this script from a terminal to enter the demo password.'
    fi
    read -r -s -p 'Shared password for the three demo accounts: ' DEMO_ACCOUNT_PASSWORD </dev/tty
    printf '\n' >/dev/tty
fi
[[ -n "$DEMO_ACCOUNT_PASSWORD" ]] || fail 'The demo password cannot be empty.'

az account set --subscription "$SUBSCRIPTION_ID"
ACCOUNT_ID="$(az account show --query id -o tsv)"
[[ "$ACCOUNT_ID" == "$SUBSCRIPTION_ID" ]] || fail 'Azure CLI did not select the expected subscription.'

if [[ "$(az group exists --name "$RESOURCE_GROUP" -o tsv)" == 'true' ]]; then
    fail "Resource group $RESOURCE_GROUP already exists; refusing to overwrite an existing deployment."
fi

SSH_SOURCE_ADDRESS="$(curl --ipv4 --fail --silent --show-error https://api.ipify.org)"
[[ "$SSH_SOURCE_ADDRESS" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail 'Could not determine the current IPv4 address for SSH restriction.'
SSH_PUBLIC_KEY="$(<"$SSH_PUBLIC_KEY_FILE")"

python3 - "$TEMP_DIR/deployment.parameters.json" "$SSH_PUBLIC_KEY" "$SSH_SOURCE_ADDRESS/32" "$RESOURCE_GROUP" "$LOCATION" "$ADMIN_USERNAME" <<'PY'
import json
import sys

path, public_key, source_ip, resource_group, location, admin_username = sys.argv[1:]
parameters = {
    "resourceGroupName": {"value": resource_group},
    "environmentName": {"value": "hostalconnect-demo"},
    "location": {"value": location},
    "adminUsername": {"value": admin_username},
    "sshPublicKey": {"value": public_key},
    "sshSourceAddressPrefix": {"value": source_ip},
}
with open(path, "w", encoding="utf-8") as file:
    json.dump(
        {
            "$schema": "https://schema.management.azure.com/schemas/2019-04-01/deploymentParameters.json#",
            "contentVersion": "1.0.0.0",
            "parameters": parameters,
        },
        file,
    )
PY
chmod 600 "$TEMP_DIR/deployment.parameters.json"

printf 'DEMO_ACCOUNT_PASSWORD=%s\n' "$DEMO_ACCOUNT_PASSWORD" >"$TEMP_DIR/demo.env"
chmod 600 "$TEMP_DIR/demo.env"

printf 'Building the Laravel production image locally...\n'
docker build --file "$ROOT_DIR/Dockerfile.render" --tag hostalconnect-app:production "$ROOT_DIR"
docker save hostalconnect-app:production --output "$TEMP_DIR/hostalconnect-app.tar"

printf 'Deploying the VM infrastructure to %s...\n' "$LOCATION"
DEPLOYMENT_NAME="hostalconnect-$(date -u +%Y%m%d%H%M%S)"
DEPLOYMENT_OUTPUT="$(
    az deployment sub create \
        --name "$DEPLOYMENT_NAME" \
        --location "$LOCATION" \
        --template-file "$VM_TEMPLATE" \
        --parameters "@$TEMP_DIR/deployment.parameters.json" \
        --subscription "$SUBSCRIPTION_ID" \
        --output json
)"
read -r PUBLIC_IP FQDN DEPLOYED_RESOURCE_GROUP < <(
    python3 -c '
import json, sys
deployment = json.load(sys.stdin)
outputs = deployment["properties"]["outputs"]
print(
    outputs["publicIpAddress"]["value"],
    outputs["fqdn"]["value"],
    outputs["resourceGroupName"]["value"],
)
' <<<"$DEPLOYMENT_OUTPUT"
)

SSH_TARGET="$ADMIN_USERNAME@$PUBLIC_IP"
SSH_OPTIONS=(-i "$SSH_PRIVATE_KEY_FILE" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10)
printf 'Waiting for SSH and Docker initialization on %s...\n' "$FQDN"
ready=0
for attempt in $(seq 1 90); do
    if ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" 'cloud-init status --wait >/dev/null 2>&1 && docker compose version >/dev/null 2>&1'; then
        ready=1
        break
    fi
    sleep 10
done
[[ "$ready" == 1 ]] || fail 'The VM did not finish its Docker bootstrap within 15 minutes.'

APP_KEY="base64:$(openssl rand -base64 32)"
MYSQL_PASSWORD="$(openssl rand -hex 32)"
MYSQL_ROOT_PASSWORD="$(openssl rand -hex 32)"
cat >"$TEMP_DIR/server.env" <<EOF
APP_NAME="HostalConnect UNAB"
APP_ENV=production
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_URL=https://$FQDN
APP_DOMAIN=$FQDN
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
BCRYPT_ROUNDS=12
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=hostalconnect
DB_USERNAME=hostalconnect
DB_PASSWORD=$MYSQL_PASSWORD
MYSQL_DATABASE=hostalconnect
MYSQL_USER=hostalconnect
MYSQL_PASSWORD=$MYSQL_PASSWORD
MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD
SESSION_DRIVER=cookie
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
MAIL_MAILER=log
EOF
chmod 600 "$TEMP_DIR/server.env"

ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" 'sudo install -d -o root -g root -m 0750 /opt/hostalconnect'
scp "${SSH_OPTIONS[@]}" "$COMPOSE_FILE" "$CADDY_FILE" "$TEMP_DIR/server.env" "$TEMP_DIR/demo.env" "$SSH_TARGET:/tmp/"
ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" \
    'sudo mv /tmp/compose.yaml /opt/hostalconnect/compose.yaml &&
     sudo mv /tmp/Caddyfile /opt/hostalconnect/Caddyfile &&
     sudo mv /tmp/server.env /opt/hostalconnect/.env &&
     sudo mv /tmp/demo.env /tmp/hostalconnect-demo.env &&
     sudo chown root:root /opt/hostalconnect/.env /tmp/hostalconnect-demo.env &&
     sudo chmod 600 /opt/hostalconnect/.env /tmp/hostalconnect-demo.env'

printf 'Transferring the application image to the VM...\n'
docker save hostalconnect-app:production | ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" 'sudo docker load'

REMOTE_COMPOSE='sudo docker compose --project-directory /opt/hostalconnect --env-file /opt/hostalconnect/.env -f /opt/hostalconnect/compose.yaml'
ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" "$REMOTE_COMPOSE up -d db app"
ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" "$REMOTE_COMPOSE run --rm app php artisan migrate --force"
ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" \
    "set -eu
     trap 'sudo rm -f -- /tmp/hostalconnect-demo.env' EXIT
     $REMOTE_COMPOSE run --rm app php artisan config:clear
     sudo docker compose --project-directory /opt/hostalconnect --env-file /opt/hostalconnect/.env --env-file /tmp/hostalconnect-demo.env -f /opt/hostalconnect/compose.yaml run --rm app php artisan db:seed --class=DemoAccountsSeeder --force
     $REMOTE_COMPOSE run --rm app php artisan config:cache"
ssh "${SSH_OPTIONS[@]}" "$SSH_TARGET" "$REMOTE_COMPOSE up -d caddy"

printf 'Checking HTTPS and Laravel health at https://%s/up...\n' "$FQDN"
healthy=0
for attempt in $(seq 1 60); do
    if curl --fail --silent --show-error "https://$FQDN/up" >/dev/null 2>&1; then
        healthy=1
        break
    fi
    sleep 10
done
[[ "$healthy" == 1 ]] || fail "HTTPS health check did not pass at https://$FQDN/up."

printf 'Deployment ready.\nResource group: %s\nVM: %s\nPublic IP: %s\nURL: https://%s\n' \
    "$DEPLOYED_RESOURCE_GROUP" \
    "$(python3 -c 'import json,sys; print(json.load(sys.stdin)[\"properties\"][\"outputs\"][\"vmName\"][\"value\"])' <<<"$DEPLOYMENT_OUTPUT")" \
    "$PUBLIC_IP" \
    "$FQDN"
