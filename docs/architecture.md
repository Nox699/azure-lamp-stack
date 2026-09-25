# Architecture diagram

```mermaid
flowchart TB
    PC[Admin PC / WSL\nTerraform + Ansible + Playwright]

    subgraph Azure[Azure - Sweden Central]
        RG[Resource Group\nlamp-school-rg]
        VNET[VNet\n10.20.0.0/16]
        SUBNET[Subnet\n10.20.1.0/24]
        NSG[NSG\nHTTP 80 from Internet\nSSH 22 from admin CIDR\nNo TCP 3306 allow rule]
        PIP[Standard Static Public IPv4]
        NIC[NIC]
        VM[Ubuntu 24.04 LTS VM\nStandard_B2als_v2\n2 vCPU / 4 GiB]

        subgraph Docker[Docker Compose on VM]
            WEB[Apache + PHP\nHost TCP/80 published]
            DB[MariaDB 11.4\nTCP/3306 internal only]
            VOL[(db_data volume)]
            WEB -->|private Docker network| DB
            DB --> VOL
        end

        RG --> VNET --> SUBNET --> NIC --> VM --> WEB
        NSG --> NIC
        PIP --> NIC
    end

    Internet((Internet)) -->|HTTP TCP/80| PIP
    PC -->|Terraform creates Azure resources| Azure
    PC -->|Ansible / SSH TCP/22\nrestricted by admin CIDR| VM
    PC -->|Playwright HTTP E2E tests| PIP
```

## Provisioning flow

```text
Terraform
   |
   +--> Resource Group
   +--> VNet + Subnet
   +--> NSG
   +--> Static Public IP
   +--> NIC
   +--> Ubuntu VM
             |
             v
          Ansible
             |
             +--> install/start Docker
             +--> copy application files
             +--> create protected .env
             +--> docker compose up -d --build
                         |
                         +--> Apache/PHP
                         +--> MariaDB + db_data
```

## Runtime/security flow

1. The internet reaches the VM through the Standard public IPv4 address.
2. The NSG allows HTTP on TCP/80 from the internet.
3. The NSG allows SSH on TCP/22 only from the configured administrator `/32` CIDR.
4. Apache/PHP is the only Docker service with a published host port.
5. MariaDB TCP/3306 has no Azure NSG allow rule and no Docker host-port mapping.
6. PHP reaches MariaDB using the private Docker bridge network and the Compose service name `db`.
7. MariaDB data is stored in the named `db_data` Docker volume.
8. Playwright tests the public-IP deployment from the administrator's machine.
