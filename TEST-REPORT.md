# Test report

## Final Azure deployment test status

Date: 22 September 2026  
Region: Sweden Central  
VM size: `Standard_B2als_v2`  
Public test endpoint used: `http://<PUBLIC_IP>`

The solution has now been deployed and tested against a real Azure VM.

## Terraform / Azure

Executed successfully:

- `terraform init`
- `terraform validate` -> configuration valid
- `terraform plan`
- `terraform apply`

The first VM creation attempt with `Standard_B1ms` failed because Azure reported `SkuNotAvailable` / capacity restrictions for this subscription in Sweden Central. Existing Terraform-managed network resources remained in state. The VM size was changed to the available `Standard_B2als_v2`, after which Terraform created the VM successfully.

Verified Azure resources:

- Resource Group
- VNet
- Subnet
- NSG
- Standard static Public IPv4
- NIC
- NSG/NIC association
- Ubuntu Linux VM

## SSH

SSH key authentication to the VM succeeded using the dedicated local private key and the Terraform-installed public key.

Password authentication is disabled in Terraform.

## Ansible

Connectivity test:

```text
SUCCESS
ping: pong
```

Deployment playbook completed successfully with:

```text
ok=13
changed=7
unreachable=0
failed=0
skipped=0
rescued=0
ignored=0
```

The playbook successfully installed/started Docker, copied the application, wrote the protected environment file, started the LAMP containers and passed the local `/health.php` check.

## Network/security checks

Public HTTP TCP/80: **reachable / PASS**

Public MariaDB TCP/3306: **not reachable / PASS**

The TCP/3306 test timed out rather than connecting, which is expected because the Azure NSG has no 3306 allow rule and Docker does not publish the MariaDB port.

## Playwright E2E tests

Command executed:

```bash
BASE_URL=http://<PUBLIC_IP> npm run test:e2e
```

Final result:

```text
Running 3 tests using 1 worker
✓ homepage loads
✓ message can be saved and is still visible after reload
✓ health endpoint confirms database connectivity

3 passed (2.2s)
```

Therefore all three required E2E scenarios passed against the Azure public-IP deployment.

An initial local Playwright run failed because the WSL Chromium runtime was missing `libnspr4.so`. After installing Playwright's required Linux/Chromium dependencies, the same tests passed. This was a local test-runner dependency issue, not an Azure/application failure.

## What the tests demonstrate

- The web application is publicly reachable through Azure TCP/80.
- Apache serves the PHP application.
- PHP can connect to MariaDB over the internal Docker network.
- MariaDB can write/read application data.
- Data remains visible after a browser reload.
- The application health endpoint can query the database.
- MariaDB is not directly reachable from the public internet.
- Ansible can configure the VM over restricted SSH.
- Terraform can provision the required Azure infrastructure.

## Remaining final lifecycle test

Before the assignment is considered completely finished, demonstrate/perform the required cleanup when the environment is no longer needed:

```bash
terraform -chdir=terraform destroy
```

This should be done after screenshots/demonstration/testing are complete, because it removes the Azure resources.
