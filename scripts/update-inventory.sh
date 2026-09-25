#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
IP="$(terraform -chdir="$ROOT_DIR/terraform" output -raw public_ip)"
USER_NAME="${ANSIBLE_USER:-azureadmin}"
KEY_PATH="${ANSIBLE_KEY_PATH:-~/.ssh/id_ed25519}"

cat > "$ROOT_DIR/ansible/inventory.ini" <<INVENTORY
[web]
${IP} ansible_user=${USER_NAME} ansible_ssh_private_key_file=${KEY_PATH}
INVENTORY

printf 'Wrote ansible/inventory.ini for %s\n' "$IP"
