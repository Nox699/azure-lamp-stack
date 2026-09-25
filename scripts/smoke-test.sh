#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 <public-ip>" >&2
  exit 2
fi

IP="$1"

echo "[1/3] HTTP homepage"
curl --fail --silent --show-error "http://${IP}/" | grep -q "LAMP-stack virker!"
echo "PASS"

echo "[2/3] Health endpoint"
curl --fail --silent --show-error "http://${IP}/health.php" | grep -q '"status":"ok"'
echo "PASS"

echo "[3/3] MariaDB must not be reachable publicly"
if timeout 3 bash -c "</dev/tcp/${IP}/3306" 2>/dev/null; then
  echo "FAIL: TCP/3306 is publicly reachable" >&2
  exit 1
fi
echo "PASS"
