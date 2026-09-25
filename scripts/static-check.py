#!/usr/bin/env python3
from pathlib import Path
import re
import sys
import yaml

root = Path(__file__).resolve().parents[1]
errors = []

compose = yaml.safe_load((root / "docker-compose.yml").read_text())
services = compose.get("services", {})

if set(services) != {"web", "db"}:
    errors.append(f"compose services should be web+db, got {sorted(services)}")
if services.get("web", {}).get("ports") != ["80:80"]:
    errors.append("web must publish exactly 80:80")
if "ports" in services.get("db", {}):
    errors.append("db must not publish any host port")
if "db_data" not in compose.get("volumes", {}):
    errors.append("db_data volume missing")
if "healthcheck" not in services.get("db", {}):
    errors.append("database healthcheck missing")

main_tf = (root / "terraform/main.tf").read_text()
required_resources = [
    "azurerm_resource_group",
    "azurerm_virtual_network",
    "azurerm_subnet",
    "azurerm_network_security_group",
    "azurerm_public_ip",
    "azurerm_network_interface",
    "azurerm_linux_virtual_machine",
]
for resource in required_resources:
    if f'resource "{resource}"' not in main_tf:
        errors.append(f"Terraform resource missing: {resource}")

if re.search(r'destination_port_range\s*=\s*"3306"', main_tf):
    errors.append("Terraform unexpectedly contains an explicit 3306 NSG rule")
if 'destination_port_range     = "80"' not in main_tf:
    errors.append("Terraform HTTP/80 rule missing")
if 'destination_port_range     = "22"' not in main_tf:
    errors.append("Terraform SSH/22 rule missing")
if 'disable_password_authentication = true' not in main_tf:
    errors.append("Terraform must disable SSH password authentication")

playbook = yaml.safe_load((root / "ansible/playbook.yml").read_text())
if not isinstance(playbook, list) or not playbook:
    errors.append("Ansible playbook did not parse as a play list")

for php_file in (root / "src").glob("*.php"):
    if php_file.stat().st_size == 0:
        errors.append(f"empty PHP file: {php_file.name}")

if errors:
    for error in errors:
        print(f"FAIL: {error}")
    sys.exit(1)

print("PASS: project static checks")
