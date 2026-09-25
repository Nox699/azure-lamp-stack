#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

python3 "$ROOT_DIR/scripts/static-check.py"

for file in "$ROOT_DIR"/src/*.php; do
  php -l "$file" >/dev/null
  echo "PASS: php -l ${file#$ROOT_DIR/}"
done

node --check "$ROOT_DIR/playwright.config.js"
echo "PASS: node syntax playwright.config.js"
node --check "$ROOT_DIR/tests/lamp.spec.js"
echo "PASS: node syntax tests/lamp.spec.js"

bash -n "$ROOT_DIR/scripts/update-inventory.sh"
echo "PASS: bash syntax scripts/update-inventory.sh"
bash -n "$ROOT_DIR/scripts/smoke-test.sh"
echo "PASS: bash syntax scripts/smoke-test.sh"

python3 - "$ROOT_DIR" <<'PY'
from pathlib import Path
from jinja2 import Template
import sys
root = Path(sys.argv[1])
template = Template((root / 'ansible/env.j2').read_text())
rendered = template.render(
    mysql_database='lampdb',
    mysql_user='lampuser',
    mysql_password='test-app-password',
    mysql_root_password='test-root-password',
)
required = [
    'MYSQL_DATABASE=lampdb',
    'MYSQL_USER=lampuser',
    'MYSQL_PASSWORD=test-app-password',
    'MYSQL_ROOT_PASSWORD=test-root-password',
]
assert all(x in rendered for x in required)
print('PASS: Ansible env template renders')
PY
