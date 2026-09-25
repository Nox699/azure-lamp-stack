# Azure LAMP stack — Terraform + Ansible + Docker

This project deploys a small LAMP application to Azure using:

- **Terraform** for Azure infrastructure
- **cloud-init** for minimal first-boot bootstrap only
- **Ansible** for VM/software configuration
- **Docker Compose** for Apache/PHP + MariaDB
- **Playwright** for end-to-end tests

The solution was deployed and tested in Azure in Sweden Central on 22 September 2026. The final VM size used was `Standard_B2als_v2` because the originally planned `Standard_B1ms` SKU was unavailable for this subscription in Sweden Central at deployment time.

See `DESIGN-CHOICES.txt` for the reasoning behind the settings and `TEST-REPORT.md` for the executed test results.

## Architecture

See [`docs/architecture.md`](docs/architecture.md). The important security rule is that only Apache is public. MariaDB has no Docker host-port mapping, and the Azure NSG has no inbound TCP/3306 rule.

## Prerequisites

Install locally:

- Azure CLI and log in with `az login`
- Terraform >= 1.6
- Ansible (a Python venv is recommended)
- Node.js + npm
- Docker / Docker Compose for local testing
- an SSH key pair

For this project a dedicated key was used:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/azure-lamp
chmod 600 ~/.ssh/azure-lamp
chmod 644 ~/.ssh/azure-lamp.pub
```

## 1. Create the Azure infrastructure

Copy the Terraform variable example:

```bash
cp terraform/terraform.tfvars.example terraform/terraform.tfvars
```

Edit `terraform/terraform.tfvars`. Most importantly:

- set `admin_cidr` to your current public IPv4 address plus `/32`,
- make sure `ssh_public_key_path` points to your public key,
- keep `Standard_B2als_v2` unless another suitable SKU is required by current Azure capacity/quota.

Example:

```hcl
project_name        = "lamp-school"
location            = "Sweden Central"
vm_size             = "Standard_B2als_v2"
admin_username      = "azureadmin"
ssh_public_key_path = "~/.ssh/azure-lamp.pub"
admin_cidr          = "YOUR.PUBLIC.IP/32"
```

Then:

```bash
terraform -chdir=terraform init
terraform -chdir=terraform fmt -check
terraform -chdir=terraform validate
terraform -chdir=terraform plan
terraform -chdir=terraform apply
```

Show the resulting values:

```bash
terraform -chdir=terraform output
```

The output includes the public IP, web URL and an SSH command.

## 2. Configure the VM with Ansible

Create an inventory from the Terraform output:

```bash
./scripts/update-inventory.sh
```

Or copy and edit the example manually:

```bash
cp ansible/inventory.ini.example ansible/inventory.ini
```

Create the local secrets file:

```bash
cp ansible/secrets.yml.example ansible/secrets.yml
chmod 600 ansible/secrets.yml
```

Replace both example database passwords with different strong passwords. `ansible/secrets.yml` is ignored by Git.

Verify Ansible connectivity before changing the server:

```bash
ansible web -i ansible/inventory.ini -m ping
```

Run the playbook:

```bash
ansible-playbook -i ansible/inventory.ini ansible/playbook.yml
```

The playbook installs Docker and Docker Compose, deploys the files under `/opt/azure-lamp-stack`, writes a protected `.env`, starts Docker Compose, and waits for `/health.php` to return HTTP 200.

## 3. Test the deployment

Get the public IP:

```bash
PUBLIC_IP=$(terraform -chdir=terraform output -raw public_ip)
```

Manual smoke tests:

```bash
curl "http://$PUBLIC_IP/"
curl "http://$PUBLIC_IP/health.php"
timeout 5 nc -vz "$PUBLIC_IP" 80
timeout 5 nc -vz "$PUBLIC_IP" 3306
```

Expected result:

- TCP/80 succeeds.
- `/health.php` returns HTTP 200 with database status `ok`.
- TCP/3306 times out/fails because MariaDB is not public.

To inspect Docker over SSH:

```bash
ssh -i ~/.ssh/azure-lamp azureadmin@"$PUBLIC_IP"
docker ps
```

The deployment should show the `lamp_web` and `lamp_db` containers running.

### Playwright E2E tests

Install Node dependencies and Chromium:

```bash
npm install
npx playwright install chromium
```

On a new/minimal Ubuntu or WSL install, Chromium may also need system libraries:

```bash
sudo npx playwright install-deps chromium
```

Run all 3 tests against the Azure deployment:

```bash
BASE_URL="http://$PUBLIC_IP" npm run test:e2e
```

The tests verify:

1. the homepage loads,
2. a message can be written and remains after reload,
3. the health endpoint can query MariaDB.

The tested Azure deployment completed with **3 passed**.

## 4. Update the application

Edit the local application/configuration files, then run Ansible again:

```bash
ansible-playbook -i ansible/inventory.ini ansible/playbook.yml
```

Ansible copies the current project files and runs:

```bash
docker compose up -d --build --remove-orphans
```

This keeps server configuration repeatable instead of making manual changes over SSH.

## 5. Remove everything

Azure infrastructure is managed by Terraform, so it can be removed with:

```bash
terraform -chdir=terraform destroy
```

Review the destroy plan before confirming. This is the required cleanup path for the assignment.

## Security choices

- SSH uses a public key; password login is disabled.
- SSH/TCP 22 is restricted by `admin_cidr` in the Azure NSG.
- HTTP/TCP 80 is public so the assignment can be tested.
- MariaDB/TCP 3306 is not published by Docker and has no NSG allow rule.
- Database credentials are stored in a protected `.env` generated by Ansible, not committed to source control.
- `ansible/secrets.yml` and Terraform state/variable files are excluded from Git.
- Apache suppresses version/signature information and sends basic browser security headers.
- Docker logs are size-limited to reduce the chance of filling the VM disk.

## Cloud economics

### Final deployed size

The final VM is `Standard_B2als_v2` in Sweden Central (2 vCPU / 4 GiB RAM). `Standard_B1ms` was the original plan, but Azure returned `SkuNotAvailable` for this subscription/region, so an available small B-series v2 SKU was selected instead.

A current retail listing for Linux `Standard_B2als_v2` in Sweden Central is about **$0.0389/hour**, or about **$28.40/month** at 730 hours. Adding a Standard static public IPv4 and the small Standard SSD OS disk gives a practical estimate around **$35/month** before meaningful outbound traffic.

Budget estimate used for the assignment:

- approximately **$1.15/day**,
- approximately **$35/month**,
- approximately **$115 for 100 days**,
- approximately **170–175 days for $200**.

This still meets the challenge of running at least 100 days for $200 with a useful safety margin. Prices are estimates and can vary by subscription, date and Azure pricing changes; the Azure Pricing Calculator should be used for the final submitted screenshot/figure.

## On-premises vs Azure

For this small fixed workload, a rented VPS or owned server could be cheaper per unit of CPU/RAM. Azure was selected because the assignment focuses on cloud infrastructure and Infrastructure as Code. Azure makes the network, VM and security rules reproducible with Terraform and easy to remove again with `terraform destroy`.

The main benefits for this exercise are therefore automation, repeatability, controllable networking and easy provisioning—not that cloud is always the cheapest hosting option.

## Local Docker run

For local Docker testing:

```bash
cp .env.example .env
# Replace the example passwords.
docker compose up -d --build
curl http://localhost/
curl http://localhost/health.php
```

Stop containers without deleting DB data:

```bash
docker compose down
```

Delete containers **and** the database volume:

```bash
docker compose down -v
```

## Production note

This is production-style and deployment-ready for the school assignment, but it is not presented as a full enterprise production platform. A real public production service should additionally have HTTPS/TLS, backups, monitoring/alerts, centralized secrets such as Azure Key Vault, patch/vulnerability management, and availability/recovery planning.

### Note about changing MariaDB passwords

MariaDB's `MYSQL_*` initialization variables are applied when the database volume is first created. If passwords in `ansible/secrets.yml` are changed while keeping the existing `db_data` volume, the corresponding database user passwords must also be changed inside MariaDB. Recreating the volume is possible for a disposable environment, but it deletes stored messages.
